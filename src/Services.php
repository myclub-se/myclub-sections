<?php

namespace MyClub\MyClubSections;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Services\Admin;
use MyClub\MyClubSections\Services\Api;
use MyClub\MyClubSections\Services\Blocks;
use MyClub\MyClubSections\Services\i18n;
use MyClub\MyClubSections\Services\MyClubCron;
use MyClub\MyClubSections\Services\ShortCodes;
use MyClub\MyClubSections\Services\Taxonomy;

/**
 * Class Services
 *
 * This class is responsible for registering and instantiating services.
 *
 */
class Services
{
    const SERVICES = [
        Admin::class,
        Api::class,
        Blocks::class,
        i18n::class,
        MyClubCron::class,
        ShortCodes::class,
        Taxonomy::class,
    ];

    /**
     * Registers all services.
     *
     * This method iterates through the services obtained from the `SERVICES` constant and instantiates each service.
     * If the instantiated service has a `register` method, it is called to register the service.
     *
     * @return void
     * @since 1.0.0
     */
    public static function registerServices()
    {
        foreach ( self::SERVICES as $class ) {
            $service = new $class();
            if ( method_exists( $service, 'register' ) ) {
                $service->register();
            }
        }
    }
}