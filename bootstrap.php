<?php

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Dotenv\Dotenv;

require_once "vendor/autoload.php";

$dotenv = Dotenv::createImmutable(__DIR__ . '/docker/');
$dotenv->load();

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;
$config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/src"], $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);

$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

$connection = [
    'pdo' => new \PDO("mysql:host=mysql;dbname={$dbname}", $username, $password),
];

$container = App::getContainerInstence();
$container->set(EntityManagerInterface::class, static function() use ($connection, $config) {
    return EntityManager::create($connection, $config);
});
