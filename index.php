<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/vendor/autoload.php';
//echo "__DIR__:" . __DIR__;

  /** ----------------------------------------------------------------------- */
 /** ---------------------------- settings.php ---------------------------- **/
/** ----------------------------------------------------------------------- */

/* database */
$config = [
    'displayErrorDetails' => true,
    'database' => [
        'host' => "sql209.epizy.com",
        'user' => "epiz_23064535",
        'pass' => "2qXECXBKV",
        'dbname' => "epiz_23064535_wintercleanup"
    ],
//    'logger' => [
//        'name' => 'wintercleanup-app',
//        'path' => (
//        (PHP_SAPI == 'cli-server') ? __DIR__ . '/../logs/app.log' : '/wintercleanup_php.log') // wherever you want on your local machine
//    ]
];

$app = new \Slim\App(["settings" => $config]);


  /** -------------------------------------------------------------------------- **/
 /** ---------------------------- dependencies.php ---------------------------- **/
/** -------------------------------------------------------------------------- **/

$container = $app->getContainer();

$container['view'] = new \Slim\Views\PhpRenderer("./dist/wintercleanup/");

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler("./logs/app.log");
    $formatter = new Monolog\Formatter\LineFormatter(
        null, // Format of message in log, default [%datetime%] %channel%.%level_name%: %message% %context% %extra%\n
        null, // Datetime format
        true, // allowInlineLineBreaks option, default false
        true  // ignoreEmptyContextAndExtra option, default false
    );
    $file_handler->setFormatter($formatter);
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['database'] = function ($c) {
    $db = $c['settings']['database'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],$db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};


  /** ------------------------------------------------------------------------ **/
 /** --------------------------- index.php ---------------------------------- **/
/** ------------------------------------------------------------------------ **/

$app->group('/', function () {
    $this->get('', function (Request $request, Response $response) {
        $response = $this->view->render($response, "index.html", ["router" => $this->router]);
        return $response;
//        return 'Hell=';
    });

    $this->group('entities', function () {
        $this->group('/task', function () {
            $this->get('[/[{id:[0-9]{1,}}[/]]]', 'Rocksfort\WinterCleanup\entities\task\TaskResource:get');
            $this->post('[/]', 'Rocksfort\WinterCleanup\entities\task\TaskResource:createOrUpdateOne');
            $this->delete('[/]', 'Rocksfort\WinterCleanup\entities\task\TaskResource:deleteOne');
        });
    });
});

$app->run();
