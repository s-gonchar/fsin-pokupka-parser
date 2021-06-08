<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Dotenv\Dotenv;

require_once "vendor/autoload.php";

//$dotenv = Dotenv::createImmutable(__DIR__ . '/docker/');
//$dotenv->load();

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;
$config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/src"], $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);

//$dbname = getenv('DB_NAME');
//$username = getenv('DB_USER');
//$password = getenv('DB_PASSWORD');
$dbname = 'fsin_pokupka';
$username = 'postgres';
$password = 'secret';

$platform = new PostgreSQL100Platform();
$options = array(
    'dbname' => $dbname,
    'user' => $username,
    'password' => $password,
    'host' => 'postgres',
    'port' => 5432,
    'driver' => 'pdo_pgsql',
    'platform' => $platform,
);
$connection = DriverManager::getConnection($options);

$container = App::getContainerInstence();
$container->set(EntityManagerInterface::class, static function() use ($connection, $config) {
    return EntityManager::create($connection, $config);
});
