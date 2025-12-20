<?php

namespace MyClub\MyClubSections\Tasks;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\Common\BackgroundProcessing\Background_Process;
use MyClub\MyClubSections\Services\SectionService;
use MyClub\MyClubSections\Utils;

class RefreshSectionsTask extends Background_Process
{
    protected $prefix = 'myclub_sections';
    protected $action = 'refresh_sections_task';

    private static ?RefreshSectionsTask $instance = null;

    public static function init(): RefreshSectionsTask
    {
        if ( self::$instance === null ) {
            self::$instance = new RefreshSectionsTask();
        }
        return self::$instance;
    }

    protected function task( $item ): bool
    {
        $service = new SectionService();
        $service->updateSectionPage( $item );
        return false;
    }

    protected function complete()
    {
        parent::complete();

        $service = new SectionService();
        $service->removeUnusedSectionPages();
        Utils::updateOrCreateOption( 'myclub_sections_last_sections_sync', gmdate( "c" ), 'no' );
    }
}