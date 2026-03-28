<?php

namespace MyClub\MyClubSections\Tasks;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\Common\BackgroundProcessing\Background_Process;
use MyClub\MyClubSections\Services\NewsService;
use MyClub\MyClubSections\Utils;

class RefreshNewsTask extends Background_Process
{
    protected $prefix = 'myclub_sections';
    protected $action = 'refresh_news_task';

    private static ?RefreshNewsTask $instance = null;

    /**
     * Initializes the class if it hasn't been initialized already.
     *
     * @return RefreshNewsTask Returns an instance of the class. If the class has already been initialized, it returns the existing instance.
     */
    public static function init(): RefreshNewsTask
    {
        if ( self::$instance === null ) {
            self::$instance = new RefreshNewsTask();
        }
        return self::$instance;
    }

    /**
     * Refreshes news for the section id sent to the method or for the club if item is null
     *
     * @param mixed $item The sectionId of the news to get or null
     * @return mixed returns false to indicate that no further processing is required.
     * @since 1.0.0
     */
    protected function task( $item )
    {
        $service = new NewsService();
        $service->loadNews( $item );
        return false;
    }

    /**
     * Completes the processing of all retrieved news items.
     *
     * @return void
     * @since 1.0.0
     */
    protected function complete()
    {
        parent::complete();

        $service = new NewsService();
        $service->removeUnusedNewsItems();
        $service->updateClubNewsCategory();

        Utils::updateOrCreateOption( 'myclub_sections_last_news_sync', gmdate( "c" ), 'no' );
    }
}
