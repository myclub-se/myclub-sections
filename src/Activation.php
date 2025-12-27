<?php

namespace MyClub\MyClubSections;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Services\ActivityService;
use MyClub\MyClubSections\Services\NewsService;
use MyClub\MyClubSections\Services\SectionService;

class Activation
{
    private array $options;

    public function __construct()
    {
        $this->options = array (
            [
                'name'     => 'myclub_sections_version',
                'value'    => null,
                'autoload' => 'no'
            ],
            [
                'name'     => 'myclub_sections_api_key',
                'value'    => null,
                'autoload' => 'no'
            ],
            [
                'name'     => 'myclub_sections_section_slug',
                'value'    => 'sections',
                'autoload' => 'no'
            ],
            [
                'name'     => 'myclub_sections_section_news_slug',
                'value'    => 'section-news',
                'autoload' => 'no'
            ],
            [
                'name'     => 'myclub_sections_add_news_categories',
                'value'    => '0',
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_delete_unused_news',
                'value'    => '0',
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_last_sections_sync',
                'value'    => null,
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_last_club_calendar_sync',
                'value'    => null,
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_last_news_sync',
                'value'    => null,
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_calendar_title',
                'value'    => __( 'Calendar', 'myclub-sections' ),
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_club_calendar_title',
                'value'    => __( 'Calendar', 'myclub-sections' ),
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_club_news_title',
                'value'    => __( 'News', 'myclub-sections' ),
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_coming_games_title',
                'value'    => __( 'Upcoming games', 'myclub-sections' ),
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_description_title',
                'value'    => __( 'Description', 'myclub-sections' ),
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_news_title',
                'value'    => __( 'News', 'myclub-sections' ),
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_page_calendar',
                'value'    => '1',
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_page_coming_games',
                'value'    => '1',
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_page_description',
                'value'    => '1',
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_page_news',
                'value'    => '1',
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_page_template',
                'value'    => '',
                'autoload' => 'no',
            ],
            [
                'name'     => 'myclub_sections_show_items_order',
                'value'    => array ( 'default' ),
                'autoload' => 'no',
            ]
        );
    }

    public function activate()
    {
        foreach ( $this->options as $option ) {
            $this->addOption( $option[ 'name' ], $option[ 'value' ], $option[ 'autoload' ] );
        }

        ActivityService::createActivityTables();
    }

    public function deactivate()
    {
        delete_option( 'myclub_sections_api_key' );
    }

    public function uninstall()
    {
        foreach ( $this->options as $option ) {
            delete_option( $option[ 'name' ] );
        }

        $newsService = new NewsService();
        $newsService->deleteAllNews();

        $sectionsService = new SectionService();
        $sectionsService->deleteAllSections();

        ActivityService::init();
        ActivityService::deleteActivityTables();
    }

    /**
     * Adds an option to the WordPress database if it doesn't already exist.
     *
     * @param string $optionName The name of the option.
     * @param mixed $default The default value for the option.
     * @param string|null $autoload Sets if the option should be loaded.
     *
     * @return void
     * @since 1.0.0
     */
    private function addOption( string $optionName, $default, string $autoload )
    {
        if ( get_option( $optionName ) === false ) {
            add_option( $optionName, $default, '', $autoload );
        }
    }
}