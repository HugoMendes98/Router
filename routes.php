<?php

return function (Router $router) {
    $router->on('/', function(Response $res) {
    	$res->send("routes in another file");
	});
};