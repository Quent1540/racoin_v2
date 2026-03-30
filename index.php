<?php

require __DIR__ . '/vendor/autoload.php';

use App\Controller\CategoryController;
use App\Controller\DepartmentController;
use App\Controller\HomeController;
use App\Controller\ItemController;
use App\Controller\AddController;
use App\Controller\SearchController;
use App\Controller\AnnonceurController;
use App\Controller\ApiKeyController;
use App\Db\Connection;
use App\Model\Annonce;
use App\Model\Categorie;
use App\Model\Annonceur;
use App\Model\Departement;

use Slim\Factory\AppFactory;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Level;

if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0777, true);
}

Connection::createConn();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$logger = new Logger('http_logger');
$logger->pushProcessor(new UidProcessor());
$logger->pushProcessor(new WebProcessor());
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/app.log', Logger::INFO));

$app->add(function (Request $request, RequestHandler $handler) use ($logger): Response {
    $start = microtime(true);

    $method = $request->getMethod();
    $uri = $request->getUri();
    $path = $uri->getPath();
    $query = $uri->getQuery();
    $serverParams = $request->getServerParams();
    $ip = $serverParams['REMOTE_ADDR'] ?? $request->getHeaderLine('X-Forwarded-For') ?: 'unknown';
    $ua = $request->getHeaderLine('User-Agent');

    $bodySnippet = '';
    try {
        $parsed = $request->getParsedBody();
        if (is_array($parsed) || is_object($parsed)) {
            $bodySnippet = substr(json_encode($parsed), 0, 512);
        } elseif (is_string($parsed)) {
            $bodySnippet = substr($parsed, 0, 512);
        }
    } catch (Throwable $e) {
        $bodySnippet = '';
    }

    $logger->info('incoming_request', [
        'method' => $method,
        'path' => $path,
        'query' => $query,
        'client_ip' => $ip,
        'user_agent' => $ua,
        'body' => $bodySnippet
    ]);

    $response = $handler->handle($request);

    $duration = (microtime(true) - $start) * 1000; // ms
    $status = $response->getStatusCode();
    $size = $response->getBody()->getSize();

    $logger->info('request_completed', [
        'method' => $method,
        'path' => $path,
        'status' => $status,
        'duration_ms' => (int)$duration,
        'response_bytes' => $size,
    ]);

    return $response;
});

$app->add(function ($request, $handler) {
    ob_start();
    $response = $handler->handle($request);
    $content = ob_get_clean();
    if ($content !== '') {
        $response->getBody()->write($content);
    }
    return $response;
});

$loader = new FilesystemLoader(__DIR__ . '/template');
$twig   = new Environment($loader);

if (!isset($_SESSION)) {
    session_start();
    $_SESSION['formStarted'] = true;
}
if (!isset($_SESSION['token'])) {
    $token = md5(uniqid(random_int(0, mt_getrandmax()), TRUE));
    $_SESSION['token'] = $token;
    $_SESSION['token_time'] = time();
} else {
    $token = $_SESSION['token'];
}

$menu = [
    [ 'href' => './index.php', 'text' => 'Accueil' ]
];

$chemin = dirname((string) $_SERVER['SCRIPT_NAME']);

$cat = new CategoryController();
$dpt = new DepartmentController();

$callAndCapture = function (callable $fn, Response $response) {
    ob_start();
    $fn();
    $content = ob_get_clean();
    $response->getBody()->write($content);
    return $response;
};

$app->get('/', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $index = new HomeController();
    return $callAndCapture(fn() => $index->displayAllAnnonce($twig, $menu, $chemin, $cat->getCategories()), $response);
});

$app->get('/item/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $n = $args['n'];
    $item = new ItemController();
    return $callAndCapture(fn() => $item->afficherItem($twig, $menu, $chemin, $n, $cat->getCategories()), $response);
});

$app->get('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $dpt, $callAndCapture) {
    $ajout = new AddController();
    return $callAndCapture(fn() => $ajout->addItemView($twig, $menu, $chemin, $cat->getCategories(), $dpt->getAllDepartments()), $response);
});

$app->post('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin, $callAndCapture) {
    $allPostVars = $request->getParsedBody();
    $ajout = new AddController();
    return $callAndCapture(fn() => $ajout->addNewItem($twig, $menu, $chemin, $allPostVars), $response);
});

$app->get('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $callAndCapture) {
    $id = $args['id'];
    $item = new ItemController();
    return $callAndCapture(fn() => $item->modifyGet($twig, $menu, $chemin, $id), $response);
});

$app->post('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $dpt, $callAndCapture) {
    $id = $args['id'];
    $allPostVars = $request->getParsedBody();
    $item = new ItemController();
    return $callAndCapture(fn() => $item->modifyPost($twig, $menu, $chemin, $id, $allPostVars, $cat->getCategories(), $dpt->getAllDepartments()), $response);
});

$app->map(['GET','POST'], '/item/{id}/confirm', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $callAndCapture) {
    $id = $args['id'];
    $allPostVars = $request->getParsedBody();
    $item = new ItemController();
    return $callAndCapture(fn() => $item->edit($twig, $menu, $allPostVars, $id), $response);
});

$app->get('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $s = new SearchController();
    return $callAndCapture(fn() => $s->show($twig, $menu, $chemin, $cat->getCategories()), $response);
});

$app->post('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $array = $request->getParsedBody();
    $s = new SearchController();
    return $callAndCapture(fn() => $s->research($array, $twig, $menu, $chemin, $cat->getCategories()), $response);
});

$app->get('/annonceur/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $n = $args['n'];
    $annonceur = new AnnonceurController();
    return $callAndCapture(fn() => $annonceur->afficherAnnonceur($twig, $menu, $chemin, $n, $cat->getCategories()), $response);
});

$app->get('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $callAndCapture) {
    $n = $args['n'];
    $item = new ItemController();
    return $callAndCapture(fn() => $item->supprimerItemGet($twig, $menu, $chemin, $n), $response);
});

$app->post('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $n = $args['n'];
    $item = new ItemController();
    return $callAndCapture(fn() => $item->supprimerItemPost($twig, $menu, $chemin, $n, $cat->getCategories()), $response);
});

$app->get('/cat/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $n = $args['n'];
    $categorie = new CategoryController();
    return $callAndCapture(fn() => $categorie->displayCategorie($twig, $menu, $chemin, $cat->getCategories(), $n), $response);
});

// API key routes
$app->get('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $kg = new ApiKeyController();
    return $callAndCapture(fn() => $kg->show($twig, $menu, $chemin, $cat->getCategories()), $response);
});
$app->post('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $callAndCapture) {
    $nom = $request->getParsedBody()['nom'] ?? '';
    $kg = new ApiKeyController();
    return $callAndCapture(fn() => $kg->generateKey($twig, $menu, $chemin, $cat->getCategories(), $nom), $response);
});

$app->group('/api', function ($group) use ($twig, $menu, $chemin, $cat) {
    $group->group('/annonce', function ($g) {
        $g->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = $args['id'];
            $annonceList = ['id_annonce', 'id_categorie as categorie', 'id_annonceur as annonceur', 'id_departement as departement', 'prix', 'date', 'titre', 'description', 'ville'];
            $return = Annonce::select($annonceList)->find($id);
            if (isset($return)) {
                $response = $response->withHeader('Content-Type', 'application/json');
                $return->categorie = Categorie::find($return->categorie);
                $return->annonceur = Annonceur::select('email', 'nom_annonceur', 'telephone')->find($return->annonceur);
                $return->departement = Departement::select('id_departement', 'nom_departement')->find($return->departement);
                $links = ['self' => ['href' => '/api/annonce/' . $return->id_annonce]];
                $return->links = $links;
                $response->getBody()->write($return->toJson());
                return $response;
            }
            return $response->withStatus(404);
        });
    });

    $group->group('/annonces', function ($g) {
        $g->get('/', function (Request $request, Response $response) {
            $annonceList = ['id_annonce', 'prix', 'titre', 'ville'];
            $response = $response->withHeader('Content-Type', 'application/json');
            $a = Annonce::all($annonceList);
            $links = [];
            foreach ($a as $ann) {
                $links['self']['href'] = '/api/annonce/' . $ann->id_annonce;
                $ann->links = $links;
            }
            $links['self']['href'] = '/api/annonces/';
            $a->links = $links;
            $response->getBody()->write($a->toJson());
            return $response;
        });
    });

    $group->group('/categorie', function ($g) {
        $g->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = $args['id'];
            $response = $response->withHeader('Content-Type', 'application/json');
            $a = Annonce::select('id_annonce', 'prix', 'titre', 'ville')->where('id_categorie', '=', $id)->get();
            $links = [];
            foreach ($a as $ann) {
                $links['self']['href'] = '/api/annonce/' . $ann->id_annonce;
                $ann->links = $links;
            }
            $c = Categorie::find($id);
            $links['self']['href'] = '/api/categorie/' . $id;
            $c->links = $links;
            $c->annonces = $a;
            $response->getBody()->write($c->toJson());
            return $response;
        });
    });

    $group->group('/categories', function ($g) {
        $g->get('/', function (Request $request, Response $response) {
            $response = $response->withHeader('Content-Type', 'application/json');
            $c = Categorie::get();
            $links = [];
            foreach ($c as $cat) {
                $links['self']['href'] = '/api/categorie/' . $cat->id_categorie;
                $cat->links = $links;
            }
            $links['self']['href'] = '/api/categories/';
            $c->links = $links;
            $response->getBody()->write($c->toJson());
            return $response;
        });
    });
});


$app->run();
