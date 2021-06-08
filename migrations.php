<?php

return [
    'table_storage' => [
        'table_name' => 'migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 255,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],

    'migrations_paths' => [
        'Migrations' =>  './Migrations',
    ],

    'all_or_nothing' => false,
    'check_database_platform' => true,
    'organize_migrations' => 'none',
//    'connection' => null,
//    'em' => null,
];