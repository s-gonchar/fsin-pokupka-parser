<?php

use Services\ParserService;

require_once __DIR__ . '/../bootstrap.php';

$container = App::getContainerInstence();
/** @var ParserService $service */
$service = $container->get(ParserService::class);

$service->parse();
