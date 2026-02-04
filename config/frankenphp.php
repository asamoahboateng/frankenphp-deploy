<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Project Identification
    |--------------------------------------------------------------------------
    |
    | These values identify your project in Docker containers and networks.
    |
    */

    'project_name' => env('COMPOSE_PROJECT_NAME', 'myapp'),
    'app_domain' => env('APP_DOMAIN', 'myapp.test'),
    'app_env' => env('APP_ENV', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Service Names
    |--------------------------------------------------------------------------
    |
    | Container and service names used in docker-compose files.
    |
    */

    'service_name' => 'frankenphp',

    /*
    |--------------------------------------------------------------------------
    | Traefik Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the Traefik reverse proxy.
    |
    */

    'traefik_network' => env('TRAEFIK_NETWORK', 'km_traefik-public'),
    'traefik_container' => env('TRAEFIK_CONTAINER', 'km_traefik'),

    /*
    |--------------------------------------------------------------------------
    | Docker Directory
    |--------------------------------------------------------------------------
    |
    | The directory name where Docker files are published (relative to project root).
    |
    */

    'docker_directory' => 'zd_server_franken',
];
