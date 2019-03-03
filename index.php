<?php
require_once __DIR__ . '/Router.php';
define('PUBLIC_FOLDER', __DIR__ . '/public');

$router = new Router();

$router
	->byExt('css', PUBLIC_FOLDER . '/css')
	->byExt('js', PUBLIC_FOLDER . '/js')
	->byExt(['png', 'jpg', 'jpeg', 'gif', 'tiff', 'bmp', 'ico'], PUBLIC_FOLDER . '/images')
;


$router
	->setViewsPath(PUBLIC_FOLDER . '/views')
	->get('/', function (Response $res) {
		$res->render('/path/to/file', ["var" => "value"]);
	})
	->post('/whereismysite', function(Response $res) {
		$res->send([
			"here" => $res->getBaseUrl()
		]);
	})
	->use('/media', [
		"*" => [ // something, "" to say nothing (/media)
			"method" => Router::GET,
			"callback" => function(Response $res, $args) {
				// test before all (not using $res
				echo $args[0];
			}
		],
		":file" => [
			"method" => Router::GET,
			"callback" => function(Response $res, $args) {
				$res->sendFile('/path/to/' . $args["file"] );
			}
		]
	])
	->use('/file', require __DIR__ . '/routes.php')
	->on('*', function (Response $res) {
		$res->send(404);
	})
;