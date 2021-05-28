<?php


use DI\Container;
use DI\ContainerBuilder;

class App
{
    /** @var Container */
    private static $container;

    public static function getContainerInstence(): Container
    {
        return self::$container ?: self::buildContainer();
    }

    private static function buildContainer(): Container
    {
        $builder = new ContainerBuilder();
        return self::$container = $builder->build();
    }
}