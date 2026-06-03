<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Api\RestApi;
use MyClub\MyClubSections\Utils;
use WP_Query;


class Admin extends Base
{
    public function register()
    {
        add_action( 'admin_menu', [
                $this,
                'addAdminMenu'
        ] );
        add_action( 'admin_init', [
                $this,
                'onAdminInit'
        ] );
        add_action( 'update_option_myclub_sections_api_key', [
                $this,
                'updateApiKey'
        ], 10, 0 );
        add_action( 'update_option_myclub_sections_show_items_order', [
                $this,
                'updateShowOrder'
        ], 10, 2 );
        add_action( 'update_option_myclub_sections_page_template', [
                $this,
                'updatePageTemplate'
        ], 10, 2 );
        add_action( 'wp_ajax_myclub_reload_sections', [
                $this,
                'ajaxReloadSections'
        ] );
        add_action( 'wp_ajax_myclub_reload_section_news', [
                $this,
                'ajaxReloadNews'
        ] );
        add_action( 'wp_ajax_myclub_sync_section_club_calendar', [
                $this,
                'ajaxSyncClubCalendar'
        ] );
        add_action( 'admin_enqueue_scripts', [
                $this,
                'enqueueAdminJS'
        ] );
        add_action( 'admin_notices', [
                $this,
                'wpCronAdminNotice'
        ] );
        add_action( 'manage_post_posts_columns', [
                $this,
                'addSectionNewsColumn'
        ] );
        add_action( 'after_switch_theme', [
                $this,
                'updateThemePageTemplate'
        ] );
        add_action( 'wp_dashboard_setup', [
                $this,
                'setupDashboardWidget'
        ] );

        add_filter( 'manage_post_posts_custom_column', [
                $this,
                'addSectionNewsColumnContent'
        ], 10, 2 );
        add_filter( "plugin_action_links_" . plugin_basename( $this->plugin_path . '/myclub-sections.php' ), [
                $this,
                'addPluginSettingsLink'
        ] );
        add_filter( 'myclub_common_cron_interval_label', [
                $this,
                'applyCronIntervalLabel'
        ], 10, 2 );
    }

    public function addAdminMenu()
    {
        add_options_page(
                __( 'MyClub Sections plugin settings', 'myclub-sections' ),
                __( 'MyClub Sections', 'myclub-sections' ),
                'manage_options',
                'myclub-sections-settings',
                [
                        $this,
                        'adminSettings'
                ]
        );
    }

    /**
     * Adds the plugin settings link to the list of plugin action links.
     *
     * This method accepts an array of links and adds a link to the plugin settings page.
     *
     * @param array $links An array of existing plugin action links.
     * @return array An array of modified plugin action links with the added settings link.
     * @since 1.0.0
     */
    public function addPluginSettingsLink( array $links ): array
    {
        $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=myclub-sections-settings' ) ) . '">' . __( 'Settings', 'myclub-sections' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Adds the "Section news" taxonomy column to the post listing.
     *
     * @param array $columns Array that contains the existing columns for the post listings.
     * @return array Updated array with the "Section news" column added.
     * @since 1.0.0
     */
    public function addSectionNewsColumn( array $columns ): array
    {
        $index = array_search( 'author', array_keys( $columns ) );

        if ( $index && count( $columns ) > $index ) {
            return array_merge(
                    array_slice( $columns, 0, $index + 1 ),
                    [ 'section_news' => __( 'Section news', 'myclub-sections' ) ],
                    array_slice( $columns, $index + 1, count( $columns ) )
            );
        } else {
            return array_merge( $columns, [ 'section_news' => __( 'Section news', 'myclub-sections' ) ] );
        }
    }

    /**
     * Adds the content for the 'section_news' column for post listing page.
     *
     * @param string $column_key The key of the column.
     * @param int $post_id The ID of the post.
     * @return void
     * @since 1.0.0
     */
    public function addSectionNewsColumnContent( string $column_key, int $post_id )
    {
        if ( $column_key === 'section_news' ) {
            $names = [];
            $terms = wp_get_post_terms( $post_id, NewsService::MYCLUB_SECTIONS_NEWS );
            foreach ( $terms as $term ) {
                $names[] = $term->name;
            }
            echo esc_attr( join( ', ', $names ) );
        }
    }


    /**
     * Loads the admin settings page template.
     *
     * This method includes the admin settings template file located within the plugin's directory.
     *
     * @return mixed Returns the result of the included file.
     * @since 1.0.0
     */
    public function adminSettings()
    {
        return require_once $this->plugin_path . '/templates/admin/admin-settings.php';
    }

    /**
     * Registers settings for MyClub Sections across multiple settings tabs.
     *
     * This method defines and registers various settings associated with the MyClub Sections plugin.
     * These settings are categorized into different tabs:
     * - Tab 1: General settings such as API keys and section slugs.
     * - Tab 2: Title settings, defining titles for various sections like Calendar and News.
     * - Tab 3: Display settings, including visibility and ordering of sections.
     * - Tab 4: Calendar settings, including views and layout configurations.
     *
     * Each setting is registered with a sanitize callback and a defined default value.
     *
     * @return void
     * @since 1.0.0
     */
    public function addMyclubSectionsSettings()
    {
        # region tab1 settings

        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_api_key', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeApiKey'
                ],
                'default'           => NULL
        ] );
        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_section_slug', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeSectionSlug'
                ],
                'default'           => 'sections'
        ] );
        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_section_news_slug', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeSectionNewsSlug'
                ],
                'default'           => 'section-news'
        ] );
        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_add_news_categories', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '0'
        ] );
        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_delete_unused_news', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '0'
        ] );

        # endregion

        # region tab2 settings (title settings)

        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_calendar_title', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarTitle'
                ],
                'default'           => __( 'Calendar', 'myclub-sections' ),
                'show_in_rest'      => true
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_club_calendar_title', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeClubCalendarTitle'
                ],
                'default'           => __( 'Calendar', 'myclub-sections' ),
                'show_in_rest'      => true
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_club_news_title', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeClubNewsTitle'
                ],
                'default'           => __( 'News', 'myclub-sections' )
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_coming_games_title', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeComingGamesTitle'
                ],
                'default'           => __( 'Upcoming games', 'myclub-sections' )
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_description_title', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeDescriptionTitle'
                ],
                'default'           => __( 'Description', 'myclub-sections' ),
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_news_title', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeNewsTitle'
                ],
                'default'           => __( 'News', 'myclub-sections' )
        ] );

        # endregion

        # region tab3 settings (display settings)

        register_setting( 'myclub_sections_settings_tab3', 'myclub_sections_page_description', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab3', 'myclub_sections_page_calendar', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab3', 'myclub_sections_page_news', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab3', 'myclub_sections_page_coming_games', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab3', 'myclub_sections_page_template', [
                'sanitize_callback' => [
                        $this,
                        'sanitizePageTemplate'
                ],
                'default'           => ''
        ] );
        register_setting( 'myclub_sections_settings_tab3', 'myclub_sections_show_items_order', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeShowItemsOrder'
                ],
                'default'           => array (
                        'default',
                )
        ] );
        register_setting( 'myclub_sections_settings_tab3', 'myclub_sections_news_ingress_word_length', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeNewsIngressWordLength'
                ],
                'default'           => '0'
        ] );
        # endregion

        # region tab4 settings (calendar settings)

        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_no_activities_message', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeNoActivitiesMessage'
                ],
                'default'           => __( "No activities to display", "myclub-sections" )
        ] );

        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_desktop_views', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarViews'
                ],
                'default'           => Utils::getCalendarDesktopViews()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_desktop_views_default', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarDesktopViewDefault'
                ],
                'default'           => Utils::getCalendarDesktopViewsDefault()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_mobile_views', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarViews'
                ],
                'default'           => Utils::getCalendarMobileViews()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_mobile_views_default', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarMobileViewDefault'
                ],
                'default'           => Utils::getCalendarMobileViewsDefault()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_show_week_numbers', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_show_subscribe_button', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_height', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeSectionCalendarHeight'
                ],
                'default'           => ''
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_desktop_views', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarViews'
                ],
                'default'           => Utils::getCalendarDesktopViews()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_desktop_views_default', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarDesktopViewDefault'
                ],
                'default'           => Utils::getCalendarDesktopViewsDefault()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_mobile_views', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarViews'
                ],
                'default'           => Utils::getCalendarMobileViews()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_mobile_views_default', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCalendarMobileViewDefault'
                ],
                'default'           => Utils::getCalendarMobileViewsDefault()
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_show_week_numbers', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_height', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeClubCalendarHeight'
                ],
                'default'           => ''
        ] );

        # endregion

        # region tab1 sections

        add_settings_section( 'myclub_sections_main', __( 'MyClub Sections Main Settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here are the general settings available from the MyClub Sections plugin. The available Gutenberg blocks and their usage is described under the "Gutenberg blocks" tab. The available shortcodes and their usage are described under the "Shortcodes" tab. Please check the documentation there.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab1' );

        # endregion

        # region tab2 sections (title settings)

        add_settings_section( 'myclub_sections_title_settings', __( 'Title settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here you can set the titles for the fields that are displayed on the section pages. The titles are used in the Gutenberg blocks and shortcodes. You cannot leave the title field empty.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab2' );

        # endregion

        # region tab3 sections (display settings)

        add_settings_section( 'myclub_sections_display_settings', __( 'Display settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here you can set the display options for the section pages. You select which fields should be visible and then in which order. On a Gutenberg theme you can also choose which template should be used for the section pages.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab3' );

        # endregion

        # region tab4 sections (calendar settings)

        add_settings_section( 'myclub_sections_calendar_settings', __( 'General calendar settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here you can set the general calendar settings. These settings are used for the section calendar and the club calendar.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab4' );

        add_settings_section( 'myclub_sections_section_calendar_settings', __( 'Section calendar settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here you can set the calendar views for the section calendar. You can choose which views should be available for desktop and mobile devices.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab4' );
        add_settings_section( 'myclub_sections_club_calendar_settings', __( 'Club calendar settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here you can set the calendar views for the club calendar. You can choose which views should be available for desktop and mobile devices.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab4' );

        # endregion

        # region tab1 fields

        add_settings_field( 'myclub_sections_api_key', __( 'MyClub API Key', 'myclub-sections' ), [
                $this,
                'renderApiKey'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_main', [ 'label_for' => 'myclub_sections_api_key' ] );
        add_settings_field( 'myclub_sections_section_slug', __( 'Slug for section pages', 'myclub-sections' ), [
                $this,
                'renderSectionsSlug'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_main', [ 'label_for' => 'myclub_sections_section_slug' ] );
        add_settings_field( 'myclub_sections_section_news_slug', __( 'Slug for section news posts', 'myclub-sections' ), [
                $this,
                'renderSectionNewsSlug'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_main', [ 'label_for' => 'myclub_sections_section_news_slug' ] );
        add_settings_field( 'myclub_sections_add_news_categories', __( 'Add news categories for section news', 'myclub-sections' ), [
                $this,
                'renderAddNewsCategories'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_main', [ 'label_for' => 'myclub_sections_add_news_categories' ] );
        add_settings_field( 'myclub_sections_delete_unused_news', __( 'Delete posts for news deleted from MyClub', 'myclub-sections' ), [
                $this,
                'renderDeleteUnusedNews'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_main', [ 'label_for' => 'myclub_sections_delete_unused_news' ] );

        # endregion

        # region tab2 title settings fields

        add_settings_field( 'myclub_sections_club_calendar_title', __( 'Title for club calendar field', 'myclub-sections' ), [
                $this,
                'renderClubCalendarTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_club_calendar_title' ] );
        add_settings_field( 'myclub_sections_club_news_title', __( 'Title for club news field', 'myclub-sections' ), [
                $this,
                'renderClubNewsTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_club_news_title' ] );
        add_settings_field( 'myclub_sections_calendar_title', __( 'Title for calendar field', 'myclub-sections' ), [
                $this,
                'renderCalendarTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_calendar_title' ] );
        add_settings_field( 'myclub_sections_description_title', __( 'Title for description field', 'myclub-sections' ), [
                $this,
                'renderDescriptionTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_description_title' ] );
        add_settings_field( 'myclub_sections_news_title', __( 'Title for news field', 'myclub-sections' ), [
                $this,
                'renderNewsTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_news_title' ] );
        add_settings_field( 'myclub_sections_coming_games_title', __( 'Title for upcoming games field', 'myclub-sections' ), [
                $this,
                'renderComingGamesTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_coming_games_title' ] );

        # endregion

        # region tab3 display settings fields

        add_settings_field( 'myclub_sections_page_description', __( 'Show section description', 'myclub-sections' ), [
                $this,
                'renderPageDescription'
        ], 'myclub_sections_settings_tab3', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_description' ] );
        add_settings_field( 'myclub_sections_page_calendar', __( 'Show section calendar', 'myclub-sections' ), [
                $this,
                'renderPageCalendar'
        ], 'myclub_sections_settings_tab3', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_calendar' ] );
        add_settings_field( 'myclub_sections_page_news', __( 'Show section news', 'myclub-sections' ), [
                $this,
                'renderPageNews'
        ], 'myclub_sections_settings_tab3', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_news' ] );
        add_settings_field( 'myclub_sections_page_coming_games', __( 'Show section upcoming games', 'myclub-sections' ), [
                $this,
                'renderPageComingGames'
        ], 'myclub_sections_settings_tab3', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_coming_games' ] );
        if ( wp_is_block_theme() ) {
            add_settings_field( 'myclub_sections_page_template', __( 'Template for section pages', 'myclub-sections' ), [
                    $this,
                    'renderPageTemplate'
            ], 'myclub_sections_settings_tab3', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_template' ] );
        }
        add_settings_field( 'myclub_sections_show_items_order', __( 'Shown items order', 'myclub-sections' ), [
                $this,
                'renderShowItemsOrder'
        ], 'myclub_sections_settings_tab3', 'myclub_sections_display_settings', [
                'label_for' => 'myclub_sections_show_items_order',
                'help_text' => __( 'Select the order in which the fields should be shown on the section pages.', 'myclub-sections' )
        ] );
        add_settings_field( 'myclub_sections_news_ingress_word_length', __( 'Number of words shown on news ingress', 'myclub-sections' ), [
                $this,
                'renderNewsIngressWordLength'
        ], 'myclub_sections_settings_tab3', 'myclub_sections_display_settings', [
                'label_for' => 'myclub_groups_news_ingress_word_length',
                'help_text' => __( 'Select the number of words that should be shown in the news blocks. 0 means all words will be shown.', 'myclub-sections' )
        ] );

        # endregion

        # region calendar display settings

        add_settings_field( 'myclub_sections_no_activities_message', __( 'Text to display for no visible activities', 'myclub-sections' ), [
                $this,
                'renderNoActivitiesMessage'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_calendar_settings', [
                'label_for' => 'myclub_sections_no_activities_message',
                'help_text' => __( 'Enter the text to be displayed in the list view of the calendar when no activities exist.', 'myclub-sections' )
        ] );

        # endregion

        # region section calendar display settings

        add_settings_field( 'myclub_sections_section_calendar_desktop_views', __( 'Section calendar desktop views', 'myclub-sections' ), [
                $this,
                'renderSectionCalendarDesktopViews'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_settings', [
                'label_for' => 'myclub_sections_section_calendar_desktop_views',
                'help_text' => __( 'Select the calendar views that should be available for the section calendar on desktop devices. You can change the order of the views by dragging and dropping them.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_section_calendar_desktop_views_default', __( 'Default view for desktop section calendar', 'myclub-sections' ), [
                $this,
                'renderSectionCalendarDesktopViewsDefault'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_settings', [
                'label_for' => 'myclub_sections_section_calendar_desktop_views_default',
                'help_text' => __( 'Select the default view for the desktop section calendar. The default view will be displayed when the section calendar is loaded.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_section_calendar_mobile_views', __( 'Section calendar mobile views', 'myclub-sections' ), [
                $this,
                'renderSectionCalendarMobileViews'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_settings', [
                'label_for' => 'myclub_sections_section_calendar_mobile_views',
                'help_text' => __( 'Select the calendar views that should be available for the section calendar on mobile devices. You can change the order of the views by dragging and dropping them.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_section_calendar_mobile_views_default', __( 'Default view for mobile section calendar', 'myclub-sections' ), [
                $this,
                'renderSectionCalendarMobileViewsDefault'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_settings', [
                'label_for' => 'myclub_sections_section_calendar_mobile_views_default',
                'help_text' => __( 'Select the default view for the mobile section calendar. The default view will be displayed when the section calendar is loaded.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_section_calendar_show_week_numbers', __( 'Show week numbers in section calendar', 'myclub-sections' ), [
                $this,
                'renderSectionCalendarWeekNumbers'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_settings', [
                'label_for' => 'myclub_sections_section_calendar_show_week_numbers',
                'help_text' => __( 'Check this option to display week numbers in the section calendar.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_section_calendar_show_subscribe_button', __( 'Show subscribe button', 'myclub-sections' ), [
                $this,
                'renderSectionCalendarShowSubscribeButton'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_settings', [
                'label_for' => 'myclub_sections_section_calendar_show_subscribe_button',
                'help_text' => __( 'Check this option to display a subscribe button in the section calendar.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_section_calendar_height', __( 'Section calendar height', 'myclub-sections' ), [
                $this,
                'renderSectionCalendarHeight'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_section_calendar_settings', [
                'label_for' => 'myclub_sections_section_calendar_height',
                'help_text' => __( 'Set the calendar height. Leave empty to use the default aspect ratio (1.35). Valid values: a positive number (pixels), "auto", or a percentage like "100%".', 'myclub-sections' )
        ] );

        # endregion

        # region club calendar display settings

        add_settings_field( 'myclub_sections_club_calendar_desktop_views', __( 'Club calendar desktop views', 'myclub-sections' ), [
                $this,
                'renderClubCalendarDesktopViews'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_settings', [
                'label_for' => 'myclub_sections_club_calendar_desktop_views',
                'help_text' => __( 'Select the calendar views that should be available for the club calendar on desktop devices. You can change the order of the views by dragging and dropping them.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_club_calendar_desktop_views_default', __( 'Default view for desktop club calendar', 'myclub-sections' ), [
                $this,
                'renderClubCalendarDesktopViewsDefault'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_settings', [
                'label_for' => 'myclub_sections_club_calendar_desktop_views_default',
                'help_text' => __( 'Select the default view for the desktop club calendar. The default view will be displayed when the club calendar is loaded.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_club_calendar_mobile_views', __( 'Club calendar mobile views', 'myclub-sections' ), [
                $this,
                'renderClubCalendarMobileViews'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_settings', [
                'label_for' => 'myclub_sections_club_calendar_mobile_views',
                'help_text' => __( 'Select the calendar views that should be available for the club calendar on mobile devices. You can change the order of the views by dragging and dropping them.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_club_calendar_mobile_views_default', __( 'Default view for mobile club calendar', 'myclub-sections' ), [
                $this,
                'renderClubCalendarMobileViewsDefault'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_settings', [
                'label_for' => 'myclub_sections_club_calendar_mobile_views_default',
                'help_text' => __( 'Select the default view for the mobile club calendar. The default view will be displayed when the club calendar is loaded.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_club_calendar_show_week_numbers', __( 'Show week numbers in club calendar', 'myclub-sections' ), [
                $this,
                'renderClubCalendarWeekNumbers'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_settings', [
                'label_for' => 'myclub_sections_club_calendar_show_week_numbers',
                'help_text' => __( 'Check this option to display week numbers in the club calendar.', 'myclub-sections' )
        ] );

        add_settings_field( 'myclub_sections_club_calendar_height', __( 'Club calendar height', 'myclub-sections' ), [
                $this,
                'renderClubCalendarHeight'
        ], 'myclub_sections_settings_tab4', 'myclub_sections_club_calendar_settings', [
                'label_for' => 'myclub_sections_club_calendar_height',
                'help_text' => __( 'Set the calendar height. Leave empty to use the default aspect ratio (1.35). Valid values: a positive number (pixels), "auto", or a percentage like "100%".', 'myclub-sections' )
        ] );

        # endregion
    }

    /**
     * Handles the AJAX request to reload news.
     *
     * This method verifies permissions and nonce for security, queues the process to reload news,
     * and returns a JSON response indicating the outcome.
     *
     * @return void
     * @since 1.0.0
     */
    public function ajaxReloadNews()
    {
        check_ajax_referer( 'myclub_admin_nonce' );

        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [
                    'message' => __( 'Permission denied', 'myclub-sections' )
            ] );
        }

        $service = new NewsService();
        $service->reloadNews();

        wp_send_json_success( [
                'message' => __( 'Successfully queued news reloading', 'myclub-sections' )
        ] );
    }

    /**
     * Handles AJAX request to reload sections in MyClub.
     *
     * This method verifies the AJAX nonce, checks the user's permissions, and initiates the reloading of sections.
     * It responds with a success or error message based on the outcome.
     *
     * @return void
     * @since 1.0.0
     */
    public function ajaxReloadSections()
    {
        check_ajax_referer( 'myclub_admin_nonce' );

        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [
                    'message' => __( 'Permission denied', 'myclub-sections' )
            ] );
        }

        $service = new SectionService();
        $service->reloadSections();
        wp_send_json_success( [
                'message' => __( 'Successfully queued section reloading', 'myclub-sections' )
        ] );
    }

    /**
     * Handles the AJAX request to synchronize the club calendar.
     *
     * This method verifies the AJAX request, checks permissions, and triggers the reload of club events.
     * If the request is valid and the user has the necessary permissions, the club calendar is reloaded,
     * and a success response is returned. Otherwise, an error response is sent.
     *
     * @return void
     * @since 1.0.0
     */
    public function ajaxSyncClubCalendar()
    {
        check_ajax_referer( 'myclub_admin_nonce' );

        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [
                    'message' => __( 'Permission denied', 'myclub-sections' )
            ] );
        }

        $service = new CalendarService();
        $service->reloadClubEvents();

        wp_send_json_success( [
                'message' => __( 'Successfully reloaded club calendar', 'myclub-sections' )
        ] );
    }

    /**
     * Add translations for the cron interval.
     *
     * @param string $label The label for the cron interval.
     * @param int $interval The interval value.
     * @return string The translated label.
     *
     * @since 1.0.0
     */
    public function applyCronIntervalLabel( string $label, int $interval ): string
    {
        // Example: French translation
        if ( $interval === 1 ) {
            return __( 'Every minute', 'myclub-sections' );
        }
        /* translators: Display string for the cron interval */
        return sprintf( __( 'Every %d minutes', 'myclub-sections' ), $interval );
    }

    /**
     * Checks the version of the MyClub Groups plugin and triggers a notice if outdated.
     *
     * This method retrieves the installed version of the MyClub Groups plugin and compares it to a minimum required version.
     * If the installed version is older than the required version, an admin notice is registered to inform the site administrator.
     *
     * @return void
     * @since 1.0.0
     */
    public function checkMyClubGroupsVersion(): void
    {
        $version = Utils::getPluginVersion( 'myclub-groups' );

        if ( empty( $version ) ) {
            return;
        }

        if ( version_compare( $version, '2.2.0', '<' ) ) {
            add_action( 'admin_notices', function () use ( $version ) {
                $this->showMyClubGroupsVersionNotice( $version );
            } );
        }
    }

    /**
     * Enqueues the JavaScript file for the admin page of MyClub Sections plugin.
     *
     * @return void
     * @since 1.0.0
     */
    public function enqueueAdminJS()
    {
        $current_page = get_current_screen();

        if ( $current_page->base === 'settings_page_myclub-sections-settings' ) {
            wp_register_script( 'myclub_sections_settings_js', $this->plugin_url . 'resources/javascript/myclub_sections_settings.js', [], MYCLUB_SECTIONS_PLUGIN_VERSION, true );
            wp_localize_script( 'myclub_sections_settings_js', 'myclub_sections_settings', [
                    'nonce' => wp_create_nonce( 'myclub_admin_nonce' )
            ] );
            wp_register_style( 'myclub_sections_settings_css', $this->plugin_url . 'resources/css/myclub_sections_settings.css', [], MYCLUB_SECTIONS_PLUGIN_VERSION );
            wp_set_script_translations( 'myclub_sections_settings_js', 'myclub-sections', $this->plugin_path . 'languages' );

            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'myclub_sections_settings_js' );
            wp_enqueue_style( 'myclub_sections_settings_css' );
        }
    }

    /**
     * Initializes admin-related functionality for MyClub Sections.
     *
     * This method handles the setup of admin-specific features by adding MyClub Sections settings and
     * verifying the compatibility of MyClub Groups version.
     *
     * @return void
     * @since 1.0.0
     */
    public function onAdminInit(): void
    {
        $this->addMyclubSectionsSettings();
        $this->checkMyClubGroupsVersion();
    }

    /**
     * Renders a checkbox for adding news categories in section news settings.
     *
     * @param array $args Arguments passed for rendering the checkbox.
     * @return void
     * @since 1.0.0
     */
    public function renderAddNewsCategories( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_add_news_categories', 'news_categories', __( 'Add news categories for section news', 'myclub-sections' ) );
    }

    /**
     * Renders the input field for the API key in the plugin settings page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderApiKey( array $args )
    {
        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_api_key" value="' . esc_attr( get_option( 'myclub_sections_api_key' ) ) . '" />';
    }

    /**
     * Renders the input field for the section calendar title setting in the admin page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderCalendarTitle( array $args )
    {
        $calendar_title = get_option( 'myclub_sections_calendar_title' );
        if ( empty( $calendar_title ) ) {
            $calendar_title = __( 'Calendar', 'myclub-sections' );
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_calendar_title" value="' . esc_attr( $calendar_title ) . '" />';
    }

    /**
     * Renders the input field for the club calendar title setting in the admin page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderClubCalendarTitle( array $args )
    {
        $calendar_title = get_option( 'myclub_sections_club_calendar_title' );
        if ( empty( $calendar_title ) ) {
            $calendar_title = __( 'Calendar', 'myclub-sections' );
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_club_calendar_title" value="' . esc_attr( $calendar_title ) . '" />';
    }

    /**
     * Renders the desktop views for the club calendar.
     *
     * This method is responsible for rendering the calendar view field specifically
     * for desktop views in the MyClub Sections plugin.
     *
     * @param array $args An associative array of arguments used for rendering the field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderClubCalendarDesktopViews( array $args )
    {
        $this->renderCalendarViewField( 'myclub_sections_club_calendar_desktop_views', $args );
    }

    /**
     * Renders the default settings for the club calendar desktop views.
     *
     * This method outputs the default view configuration field for the club calendar in desktop mode.
     *
     * @param array $args The arguments passed for rendering the calendar view default field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderClubCalendarDesktopViewsDefault( array $args )
    {
        $this->renderCalendarViewDefaultField( 'myclub_sections_club_calendar_desktop_views_default', $args );
    }

    /**
     * Renders the mobile views for the club calendar.
     *
     * This method outputs the configurable mobile views for the club calendar based on the provided arguments.
     *
     * @param array $args An associative array of arguments used to render the calendar view field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderClubCalendarMobileViews( array $args )
    {
        $this->renderCalendarViewField( 'myclub_sections_club_calendar_mobile_views', $args );
    }

    /**
     * Renders the default field for the club calendar mobile views.
     *
     * This method outputs the default configuration field for the mobile views of the club calendar in MyClub Sections.
     *
     * @param array $args Arguments passed for rendering the field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderClubCalendarMobileViewsDefault( array $args )
    {
        $this->renderCalendarViewDefaultField( 'myclub_sections_club_calendar_mobile_views_default', $args );
    }

    /**
     * Renders the club calendar week numbers checkbox.
     *
     * This method renders a checkbox for displaying week numbers in the club calendar based on the provided arguments.
     *
     * @param array $args An associative array of arguments used to configure the checkbox rendering.
     * @since 1.1.0
     *
     * @return void
     */
    public function renderClubCalendarWeekNumbers( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_club_calendar_show_week_numbers' );
    }

    /**
     * Renders the input field for the club news title setting in the admin page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderClubNewsTitle( array $args )
    {
        $club_news_title = get_option( 'myclub_sections_club_news_title' );
        if ( empty( $club_news_title ) ) {
            $club_news_title = __( 'News', 'myclub-sections' );
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_club_news_title" value="' . esc_attr( $club_news_title ) . '" />';
    }

    /**
     * Renders the input field for the coming games title setting in the admin page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderComingGamesTitle( array $args )
    {
        $coming_games_title = get_option( 'myclub_sections_coming_games_title' );
        if ( empty( $coming_games_title ) ) {
            $coming_games_title = __( 'Upcoming games', 'myclub-sections' );
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_coming_games_title" value="' . esc_attr( $coming_games_title ) . '" />';
    }

    /**
     * Renders the input field for the description title setting in the admin page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderDescriptionTitle( array $args )
    {
        $description_title = get_option( 'myclub_sections_description_title' );
        if ( empty( $description_title ) ) {
            $description_title = __( 'Description', 'myclub-sections' );
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_description_title" value="' . esc_attr( $description_title ) . '" />';
    }

    /**
     * Renders the input field for the news title setting in the admin page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderNewsTitle( array $args )
    {
        $news_title = get_option( 'myclub_sections_news_title' );
        if ( empty( $news_title ) ) {
            $news_title = __( 'News', 'myclub-sections' );
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_news_title" value="' . esc_attr( $news_title ) . '" />';
    }

    /**
     * Renders the input field for the "No Activities" message setting.
     *
     * @param array $args {
     *     An associative array of arguments passed to the function.
     *
     * @type string $label_for The ID attribute for the input field.
     * @type string $help_text Optional. Additional help text to display below the input field.
     * @type string $description Optional. Description text to display below the input field if help text is not provided.
     * }
     * @return void
     * @since 1.3.1
     */
    public function renderNoActivitiesMessage( array $args )
    {
        $no_activities_message = get_option( 'myclub_sections_no_activities_message' );
        if ( empty( $no_activities_message ) ) {
            $no_activities_message = __( 'No activities to display', 'myclub-sections' );
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_no_activities_message" value="' . esc_attr( $no_activities_message ) . '" />';
        if ( isset( $args[ 'help_text' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'help_text' ] ) . '</p>';
        } elseif ( isset( $args[ 'description' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'description' ] ) . '</p>';
        }
    }

    /**
     * Renders the dashboard widget.
     *
     * This method counts the number of section posts in WordPress and the number
     * of news items imported to WordPress from the MyClub member system. It
     * then outputs the counts in a formatted HTML string.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderDashboardWidget()
    {
        // Count the number of section posts in WordPress
        $args = array (
                'post_type'      => SectionService::MYCLUB_SECTIONS,
                'post_status'    => 'publish',
                'posts_per_page' => -1
        );
        $query = new WP_Query( $args );
        $sections_count = $query->found_posts;

        // Count the number of news items imported to WordPress
        $args = array (
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => array (
                        array (
                                'key'     => 'myclub_news_id',
                                'compare' => 'EXISTS'
                        ),
                ),
        );
        $query = new WP_Query( $args );
        $news_count = $query->found_posts;
        $allow_strong = array ( "strong" => array () );

        /* translators: 1: number of sections */
        echo wp_kses( sprintf( __( 'There is currently <strong>%1$s sections</strong> loaded from the MyClub member system.', 'myclub-sections' ), esc_attr( $sections_count ) ), $allow_strong );
        echo '<br>';
        /* translators: 1: number of news items */
        echo wp_kses( sprintf( __( 'There is currently <strong>%1$s section news items</strong> loaded from the MyClub member system.', 'myclub-sections' ), esc_attr( $news_count ) ), $allow_strong );
        if ( !wp_next_scheduled( 'wp_version_check' ) ) {
            echo '<br><br>';
            esc_html_e( 'WP Cron is not running. This is required for running the MyClub sections plugin.', 'myclub-sections' );
        }
    }

    /**
     * Renders the checkbox option for deleting unused news posts from MyClub.
     *
     * @param array $args Arguments passed for rendering the checkbox.
     * @return void
     * @since 1.0.0
     */
    public function renderDeleteUnusedNews( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_delete_unused_news', 'delete_unused_news', __( 'Delete posts for news deleted from MyClub', 'myclub-sections' ) );
    }

    /**
     * Renders the input field for setting the word length of the news ingress.
     *
     * @param array $args An array of arguments used to render the field, including the label identifier.
     * @return void
     * @since 1.3.0
     */
    public function renderNewsIngressWordLength( array $args )
    {
        $news_ingress_word_length = get_option( 'myclub_sections_news_ingress_word_length' );
        if ( empty( $news_ingress_word_length ) ) {
            $news_ingress_word_length = 0;
        }

        echo '<input type="number" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_news_ingress_word_length" value="' . esc_attr( $news_ingress_word_length ) . '" />';
        if ( isset( $args[ 'help_text' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'help_text' ] ) . '</p>';
        } elseif ( isset( $args[ 'description' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'description' ] ) . '</p>';
        }
    }

    /**
     * Renders the page calendar option for the MyClub Sections plugin.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderPageCalendar( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_page_calendar', 'calendar', __( 'Calendar', 'myclub-sections' ) );
    }

    /**
     * Renders the page coming games option for the MyClub Sections plugin.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderPageComingGames( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_page_coming_games', 'coming-games', __( 'Upcoming games', 'myclub-sections' ) );
    }

    /**
     * Renders the page description option for the MyClub Sections plugin.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderPageDescription( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_page_description', 'description', __( 'Description', 'myclub-sections' ) );
    }

    /**
     * Renders the page news option for the MyClub Sections plugin.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderPageNews( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_page_news', 'news', __( 'News', 'myclub-sections' ) );
    }

    /**
     * Renders the page template select field.
     *
     * @param array $args The arguments for rendering the page template select field.
     *                    - 'label_for': The ID attribute for the select field.
     *
     * @since 1.0.0
     */
    public function renderPageTemplate( array $args )
    {
        $templates = wp_get_theme()->get_page_templates();
        $options = array_map( function ( $name ) {
            return $name;
        }, $templates );
        echo '<select id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_page_template">';
        foreach ( $options as $value => $name ) {
            $selected = selected( get_option( 'myclub_sections_page_template' ), $value, false );
            echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_attr( $name ) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Renders the calendar desktop views section for MyClub Sections.
     *
     * This method outputs the field for configuring the desktop views in the calendar section of MyClub Sections.
     *
     * @param array $args An array of arguments for rendering the calendar view field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderSectionCalendarDesktopViews( array $args )
    {
        $this->renderCalendarViewField( 'myclub_sections_section_calendar_desktop_views', $args );
    }

    /**
     * Renders the default calendar view for desktop in the section configuration.
     *
     * This method outputs the default field for configuring calendar views specifically for desktop
     * in the MyClub Sections settings.
     *
     * @param array $args An array of arguments to customize the rendering of the field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderSectionCalendarDesktopViewsDefault( array $args )
    {
        $this->renderCalendarViewDefaultField( 'myclub_sections_section_calendar_desktop_views_default', $args );
    }

    /**
     * Renders the section calendar mobile views.
     *
     * This method renders the calendar view field for mobile-specific views of the section calendar
     * using the provided arguments.
     *
     * @param array $args An associative array of arguments used for rendering the calendar view field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderSectionCalendarMobileViews( array $args )
    {
        $this->renderCalendarViewField( 'myclub_sections_section_calendar_mobile_views', $args );
    }

    /**
     * Renders the default field for section calendar mobile views.
     *
     * This method outputs the default field configuration for the mobile views of the section calendar in MyClub Sections.
     *
     * @param array $args Arguments used to render the default field.
     * @return void
     * @since 1.1.0
     *
     */
    public function renderSectionCalendarMobileViewsDefault( array $args )
    {
        $this->renderCalendarViewDefaultField( 'myclub_sections_section_calendar_mobile_views_default', $args );
    }

    /**
     * Renders the calendar week numbers section setting.
     *
     * This method outputs a checkbox for enabling or disabling the display of week numbers in the section calendar.
     *
     * @param array $args Arguments passed for rendering the checkbox.
     * @since 1.1.0
     * @return void
     */
    public function renderSectionCalendarWeekNumbers( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_section_calendar_show_week_numbers' );
    }

    /**
     * Renders the checkbox for the "Show Subscribe Button" setting in the Section Calendar.
     *
     * This method generates a checkbox input element for configuring whether the subscribe button
     * is displayed in the section calendar settings.
     *
     * @param array $args Array of arguments containing data for rendering the checkbox.
     * @return void
     * @since 1.4.0
     */
    public function renderSectionCalendarShowSubscribeButton( array $args )
    {
        $this->renderCheckbox( $args, 'myclub_sections_section_calendar_show_subscribe_button' );
    }

    /**
     * Renders the calendar height field for a section.
     *
     * This method is responsible for rendering the height input field for the section calendar
     * in the MyClub Sections settings.
     *
     * @param array $args An array of arguments used to render the calendar height field.
     * @return void
     * @since 1.4.0
     */
    public function renderSectionCalendarHeight( array $args )
    {
        $this->renderCalendarHeightField( $args, 'myclub_sections_section_calendar_height' );
    }

    /**
     * Renders the club calendar height field.
     *
     * This method is responsible for rendering the height field
     * for the club calendar in the settings or UI.
     *
     * @param array $args An array of arguments used to render the field.
     * @return void
     * @since 1.4.0
     */
    public function renderClubCalendarHeight( array $args )
    {
        $this->renderCalendarHeightField( $args, 'myclub_sections_club_calendar_height' );
    }

    /**
     * Renders the input field for the section slug in the plugin settings page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderSectionsSlug( array $args )
    {
        $section_slug = get_option( 'myclub_sections_section_slug' );
        if ( empty( $section_slug ) ) {
            $section_slug = 'sections';
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_section_slug" value="' . esc_attr( $section_slug ) . '" />';
    }

    /**
     * Renders the input field for the section news slug setting in the admin page.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderSectionNewsSlug( array $args )
    {
        $section_news_slug = get_option( 'myclub_sections_section_news_slug' );
        if ( empty( $section_news_slug ) ) {
            $section_news_slug = 'section-news';
        }

        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="myclub_sections_section_news_slug" value="' . esc_attr( $section_news_slug ) . '" />';
    }

    /**
     * Renders the show items order for the MyClub Sections plugin.
     *
     * @param array $args The arguments for rendering the input field.
     *                    - 'label_for' (string) The ID of the input field.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderShowItemsOrder( array $args )
    {
        $items = get_option( 'myclub_sections_show_items_order', array () );
        if ( in_array( 'default', $items ) ) {
            $items = array (
                    'description',
                    'calendar',
                    'news',
                    'coming-games',
            );
        }

        $sort_names = [
                'description'  => __( 'Description', 'myclub-sections' ),
                'calendar'     => __( 'Calendar', 'myclub-sections' ),
                'coming-games' => __( 'Upcoming games', 'myclub-sections' ),
                'news'         => __( 'News', 'myclub-sections' )
        ];

        echo '<ul id="' . esc_attr( $args[ 'label_for' ] ) . '">';

        foreach ( $items as $item ) {
            echo '<li><input type="hidden" value="' . esc_attr( $item ) . '" name="myclub_sections_show_items_order[]" />' . esc_attr( $sort_names[ $item ] ) . '</li>';
        }

        echo '</ul>';
        if ( isset( $args[ 'help_text' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'help_text' ] ) . '</p>';
        } elseif ( isset( $args[ 'description' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'description' ] ) . '</p>';
        }
    }

    /**
     * Sanitizes the provided API key and verifies its validity.
     *
     * @param string $input The API key to be sanitized.
     *
     * @return string The sanitized API key, or the previously stored API key if the new key is invalid.
     * @since 1.0.0
     */
    public function sanitizeApiKey( string $input ): string
    {
        $input = sanitize_text_field( $input );

        $api = new RestApi( $input );
        if ( $api->loadMenuItems()->status !== 200 ) {
            add_settings_error( 'myclub_sections_api_key', 'invalid-api-key', __( 'Invalid API key entered', 'myclub-sections' ) );
            return get_option( 'myclub_sections_api_key' );
        } else {
            return $input;
        }
    }

    /**
     * Sanitizes the input title for the calendar field.
     *
     * @param string $input The input title to be sanitized.
     *
     * @return string The sanitized title.
     * @since 1.0.0
     */
    public function sanitizeCalendarTitle( string $input ): string
    {
        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_calendar_title', 'empty-value', __( 'You have to enter title for the calendar field', 'myclub-sections' ) );
            return get_option( 'myclub_sections_calendar_title' );
        } else {
            return sanitize_text_field( $input );
        }
    }

    /**
     * Sanitizes the input for a checkbox option.
     *
     * @param mixed $input The input to be sanitized.
     *
     * @return string The sanitized input. Returns '1' if the input is equal to '1', otherwise returns '0'.
     * @since 1.0.0
     */
    public function sanitizeCheckbox( $input ): string
    {
        return $input === '1' ? '1' : '0';
    }

    /**
     * Sanitizes the input title for the club calendar field.
     *
     * @param string $input The input title to be sanitized.
     *
     * @return string The sanitized title.
     * @since 1.0.0
     */
    public function sanitizeClubCalendarTitle( string $input ): string
    {
        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_club_calendar_title', 'empty-value', __( 'You have to enter title for the club calendar field', 'myclub-sections' ) );
            return get_option( 'myclub_sections_club_calendar_title' );
        } else {
            return sanitize_text_field( $input );
        }
    }

    /**
     * Sanitizes the provided calendar view items by removing invalid entries.
     *
     * This method ensures that only allowed calendar views are retained by filtering the input
     * array against a predefined list of permitted items.
     *
     * @param array $items The array of calendar view items to be sanitized.
     * @return array The sanitized array containing only valid calendar view items.
     * @since 1.1.0
     *
     */
    public function sanitizeCalendarViews( array $items ): array
    {
        $allowed_items = array_keys( Utils::getCalendarArray() );
        return array_intersect( Utils::sanitizeArray( $items ), $allowed_items );
    }

    /**
     * Sanitizes the default view for the calendar on the desktop.
     *
     * This method ensures that the provided input is a valid calendar view option.
     * If the input is not valid, it defaults to 'dayGridMonth'.
     *
     * @param string $input The input value representing the desired calendar view.
     * @return string The sanitized calendar view option, or 'dayGridMonth' if the input is invalid.
     * @since 1.1.0
     *
     */
    public function sanitizeCalendarDesktopViewDefault( string $input ): string
    {
        $allowed_items = array_keys( Utils::getCalendarArray() );
        $input = sanitize_text_field( $input );

        if ( array_find( $allowed_items, fn ( $item ) => $item === $input ) === false ) {
            return 'dayGridMonth';
        }

        return $input;
    }

    /**
     * Sanitizes the input for the default calendar mobile view.
     *
     * This method ensures that the provided calendar view input is valid and allowed.
     * If the input is not part of the allowed items, a default value of 'listMonth' is returned.
     *
     * @param string $input The input string representing the calendar mobile view to be sanitized.
     * @return string The sanitized calendar mobile view, either the provided valid input or the default value 'listMonth'.
     * @since 1.1.0
     *
     */
    public function sanitizeCalendarMobileViewDefault( string $input ): string
    {
        $allowed_items = array_keys( Utils::getCalendarArray() );
        $input = sanitize_text_field( $input );

        if ( array_find( $allowed_items, fn ( $item ) => $item === $input ) === false ) {
            return 'listMonth';
        }

        return $input;
    }

    /**
     * Sanitizes the input title for the club news field.
     *
     * @param string $input The input title to be sanitized.
     *
     * @return string The sanitized title.
     * @since 1.0.0
     */
    public function sanitizeClubNewsTitle( string $input ): string
    {
        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_club_news_title', 'empty-value', __( 'You must enter a title for the club news field', 'myclub-sections' ) );
            return get_option( 'myclub_sections_club_news_title' );
        } else {
            return sanitize_text_field( $input );
        }
    }

    /**
     * Sanitizes the input title for the coming games field.
     *
     * @param string $input The input title to be sanitized.
     *
     * @return string The sanitized title.
     * @since 1.0.0
     */
    public function sanitizeComingGamesTitle( string $input ): string
    {
        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_coming_games_title', 'empty-value', __( 'You must enter a title for the upcoming games field', 'myclub-sections' ) );
            return get_option( 'myclub_sections_coming_games_title' );
        } else {
            return sanitize_text_field( $input );
        }
    }

    /**
     * Sanitizes the input title for the description field.
     *
     * @param string $input The input title to be sanitized.
     *
     * @return string The sanitized title.
     * @since 1.0.0
     */
    public function sanitizeDescriptionTitle( string $input ): string
    {
        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_description_title', 'empty-value', __( 'You must enter a title for the description field', 'myclub-sections' ) );
            return get_option( 'myclub_sections_description_title' );
        } else {
            return sanitize_text_field( $input );
        }
    }

    /**
     * Sanitizes the input for the news ingress word length setting.
     *
     * This method ensures the input is a valid integer and processes it
     * to remove any potentially harmful or invalid data. It provides
     * appropriate error messages when the input is empty or invalid.
     *
     * @param string $input The raw input value for the news ingress word length.
     * @return string The sanitized and validated word length value, or the saved option as a fallback if validation fails.
     * @since 1.3.0
     */
    public function sanitizeNewsIngressWordLength( string $input ): string
    {
        $input = sanitize_text_field( wp_unslash( $input ) );

        if ( $input === '' ) {
            add_settings_error( 'myclub_sections_news_ingress_word_length', 'empty-value', __( 'You have to enter word length for the news ingress', 'myclub-sections' ) );
            return get_option( 'myclub_sections_news_ingress_word_length', '0' );
        }

        if ( ! ctype_digit( $input ) ) {
            add_settings_error(
                    'myclub_sections_news_ingress_word_length',
                    'invalid-value',
                    __( 'The news ingress word length must be a valid integer.', 'myclub-sections' )
            );

            return get_option( 'myclub_sections_news_ingress_word_length', '0' );
        }

        return $input;
    }

    /**
     * Sanitizes the input title for the news field.
     *
     * @param string $input The input title to be sanitized.
     *
     * @return string The sanitized title.
     * @since 1.0.0
     */
    public function sanitizeNewsTitle( string $input ): string
    {
        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_news_title', 'empty-value', __( 'You must enter a title for the news field', 'myclub-sections' ) );
            return get_option( 'myclub_sections_news_title' );
        } else {
            return sanitize_text_field( $input );
        }
    }

    /**
     * Sanitizes the input message related to no activities by removing unwanted or harmful characters.
     *
     * @param string $input The input message to be sanitized.
     * @return string The sanitized message.
     * @since 1.3.1
     */
    public function sanitizeNoActivitiesMessage( string $input ): string
    {
        return sanitize_text_field( wp_unslash( $input ) );
    }

    /**
     * Sanitizes the calendar height setting for sections.
     *
     * This method processes the input value to ensure it conforms to the expected format
     * for the section calendar height and associates it with the specified option key.
     *
     * @param string $input The raw input value for the section calendar height.
     * @return string The sanitized calendar height value.
     * @since 1.4.0
     */
    public function sanitizeSectionCalendarHeight( string $input ): string
    {
        return $this->sanitizeCalendarHeightValue( $input, 'myclub_sections_section_calendar_height' );
    }

    /**
     * Sanitizes the club calendar height value.
     *
     * This method processes and sanitizes the input value for the club calendar height configuration.
     * It ensures the value is cleaned and properly stored for the specified club calendar height field.
     *
     * @param string $input The raw input value for the club calendar height.
     * @return string The sanitized club calendar height value.
     * @since 1.4.0
     */
    public function sanitizeClubCalendarHeight( string $input ): string
    {
        return $this->sanitizeCalendarHeightValue( $input, 'myclub_sections_club_calendar_height' );
    }

    /**
     * Sanitizes the input for a page template option.
     *
     * @param mixed $input The input to be sanitized.
     *
     * @return string The sanitized input. If the input does not exist in the list of available templates, an error message is shown.
     * @since 1.0.0
     */
    public function sanitizePageTemplate( $input ): string
    {
        if ( wp_is_block_theme() ) {
            $templates = get_page_templates();
            $input = sanitize_text_field( $input );

            // Check if the selected template exists in the list of available templates
            if ( !in_array( $input, $templates ) ) {
                // If the template doesn't exist, output an error message and revert the setting to default
                add_settings_error( 'myclub_sections_page_template', esc_attr( 'settings_updated' ), __( 'The selected template was not found.', 'myclub-sections' ) );
                $input = '';
            }
        }

        return !empty( $input ) ? sanitize_text_field( $input ) : '';
    }

    /**
     * Sanitizes the given section slug.
     *
     * @param string $input The section slug to sanitize.
     *
     * @return string The sanitized section slug.
     * @since 1.0.0
     */
    public function sanitizeSectionSlug( string $input ): string
    {
        $input = sanitize_title( $input );

        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_section_slug', 'empty-slug', __( 'You have to enter a valid slug', 'myclub-sections' ) );
            return get_option( 'myclub_sections_section_slug' );
        } else {
            return $input;
        }
    }

    /**
     * Sanitizes the section news slug.
     *
     * @param string $input The input slug to be sanitized.
     *
     * @return string The sanitized version of the input slug.
     * @since 1.0.0
     */
    public function sanitizeSectionNewsSlug( string $input ): string
    {
        $input = sanitize_title( $input );

        if ( empty ( $input ) ) {
            add_settings_error( 'myclub_sections_section_news_slug', 'empty-slug', __( 'You have to enter a valid slug', 'myclub-sections' ) );
            return get_option( 'myclub_sections_section_news_slug' );
        } else {
            return $input;
        }
    }

    /**
     * Sanitizes the input sorted fields for displaying the fields on the sections page.
     *
     * @param array $items The items to be sanitized
     *
     * @return array The sanitized array.
     * @since 1.0.0
     */
    public function sanitizeShowItemsOrder( array $items ): array
    {
        $allowed_items = [
                'description',
                'calendar',
                'news',
                'coming-games',
        ];

        return array_intersect( Utils::sanitizeArray( $items ), $allowed_items );
    }

    /**
     * Sets up the dashboard widget for MyClub Sections.
     *
     * This method adds a dashboard widget to the WordPress admin dashboard for MyClub Sections.
     *
     * @return void
     * @since 1.0.0
     */
    public function setupDashboardWidget()
    {
        wp_add_dashboard_widget(
                'myclub_sections_dashboard_widget',
                __( 'MyClub Sections', 'myclub-sections' ),
                [
                        $this,
                        'renderDashboardWidget'
                ]
        );
    }

    /**
     * Displays a version notice for the MyClub Groups plugin.
     *
     * This method outputs a warning notice in the WordPress admin area to inform the user
     * that the current version of the MyClub Groups plugin is outdated and needs updating.
     *
     * @param string $version The current version of the MyClub Groups plugin.
     * @return void
     * @since 1.0.0
     */
    public function showMyClubGroupsVersionNotice( string $version ): void
    {

        $message = sprintf(
        /* translators: 1: The outdated version number of the MyClub Groups plugin */
                esc_html__( 'The MyClub Groups plugin version %s is outdated. Please update to version 2.2.0 or later.', 'myclub-sections' ),
                esc_attr( $version )
        );

        echo '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>';
    }

    /**
     * Updates the API key and reloads sections.
     *
     * This method refreshes the sections by triggering a reload through the SectionService.
     *
     * @return void
     * @since 1.0.0
     */
    public function updateApiKey(): void
    {
        $service = new SectionService();
        $service->reloadSections();
    }

    /**
     * Updates the page template for all posts of a specific type.
     *
     * This method queries all posts of the "MyClub Sections" type and updates their page template meta field.
     *
     * @param string|null $old_value The previous value of the page template (unused in this method).
     * @param string $new_value The new value to be set for the page template.
     * @return void
     * @since 1.0.0
     */
    public function updatePageTemplate( ?string $old_value, string $new_value ): void
    {
        $args = array (
                'post_type'      => SectionService::MYCLUB_SECTIONS,
                'posts_per_page' => -1,
        );
        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->next_post();
                update_post_meta( $query->post->ID, '_wp_page_template', $new_value );
            }
        }
    }

    /**
     * Updates the show order for MyClub Sections.
     *
     * This method retrieves all MyClub Sections posts and updates their page contents
     * based on the new order values provided. Additionally, it clears the cache for
     * each updated section page.
     *
     * @param array $old_value The previous ordering of items.
     * @param array $new_value The new ordering of items to be applied.
     * @return void
     * @since 1.0.0
     */
    public function updateShowOrder( array $old_value, array $new_value ): void
    {
        $args = array (
                'post_type'      => SectionService::MYCLUB_SECTIONS,
                'posts_per_page' => -1,
        );
        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->next_post();
                SectionService::updateSectionPageContents( $query->post->ID, Utils::sanitizeArray( $new_value ) );
                Utils::clearCacheForPage( $query->post->ID );
            }
        }
    }

    /**
     * Updates the page template for MyClub Sections.
     *
     * This method updates the page template for MyClub Sections based on the current WordPress block theme.
     * If there are available page templates, it will update the template and set the 'myclub_sections_page_template' option.
     * If the block theme is not enabled or there are no available templates, it will delete the page template meta for 'myclub-sections' post type.
     *
     * @return void
     * @since 1.0.0
     */
    public function updateThemePageTemplate()
    {
        if ( wp_is_block_theme() ) {
            $templates = wp_get_theme()->get_page_templates();

            if ( count( $templates ) ) {
                $template = key( $templates );

                $this->updatePageTemplate( null, $template );
                get_option( 'myclub_sections_page_template' ) === false ? add_option( 'myclub_sections_page_template', $template, '', 'no' ) : update_option( 'myclub_sections_page_template', $template, 'no' );
            }
        } else {
            global $wpdb;

            $wpdb->query( $wpdb->prepare( "DELETE pm FROM {$wpdb->prefix}postmeta pm INNER JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID WHERE pm.meta_key = %s AND p.post_type = %s", '_wp_page_template', 'myclub-sections' ) );
        }
    }

    /**
     * Displays an admin notice if WP Cron is not running.
     *
     * This method checks if the WordPress cron event 'wp_version_check' is scheduled.
     * If it is not, a warning notice is displayed in the WordPress admin area to alert the user
     * that WP Cron is required for the proper functionality of the MyClub sections plugin.
     *
     * @return void
     * @since 1.0.0
     */
    public function wpCronAdminNotice()
    {
        if ( !wp_next_scheduled( 'wp_version_check' ) ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php esc_html_e( 'WP Cron is not running. This is required for running the MyClub sections plugin.', 'myclub-sections' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Renders a calendar view selection field with a default value.
     *
     * This method generates a select dropdown for choosing a calendar view,
     * and ensures a valid default value is set based on the provided name.
     * The dropdown options are derived from a predefined set of valid items.
     *
     * @param string $name The name of the option to retrieve and store the selected value.
     * @param array $args Additional arguments, including the label ID for the field.
     * @return void
     * @since 1.1.0
     *
     */
    private function renderCalendarViewDefaultField( string $name, array $args )
    {
        $default = get_option( $name, '' );
        $valid_items = Utils::getCalendarArray();

        if ( empty( $default ) ) {
            $default = ( strpos( $name, 'mobile' ) !== false ) ? Utils::getCalendarMobileViewsDefault() : Utils::getCalendarDesktopViewsDefault();
        }

        // Fallback if stored value is not a valid key anymore.
        if ( !isset( $valid_items[ $default ] ) ) {
            $default = ( strpos( $name, 'mobile' ) !== false ) ? Utils::getCalendarMobileViewsDefault() : Utils::getCalendarDesktopViewsDefault();
        }

        echo '<select id="' . esc_attr( $args[ 'label_for' ] ) . '" name="' . esc_attr( $name ) . '">';

        foreach ( $valid_items as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $default, $key, false ) . '>' . esc_html( $label ) . '</option>';
        }

        echo '</select>';
        if ( isset( $args[ 'help_text' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'help_text' ] ) . '</p>';
        } elseif ( isset( $args[ 'description' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'description' ] ) . '</p>';
        }
    }

    /**
     * Renders the calendar view field for MyClub settings.
     *
     * This method outputs an interactive sortable and selectable calendar view field
     * for use in the admin interface. It displays the available calendar views and
     * their current enabled/disabled state, allowing users to reorder and toggle them.
     *
     * @param string $name The name of the option being rendered, used to store the settings.
     * @param array $args Arguments for the field, including labels and identifiers.
     * @return void
     * @since 1.1.0
     *
     */
    private function renderCalendarViewField( string $name, array $args )
    {
        $defaultArray = strpos( $name, 'mobile' ) !== false ? Utils::getCalendarMobileViews() : Utils::getCalendarDesktopViews();
        $items = get_option( $name, $defaultArray );
        if ( !is_array( $items ) ) {
            $items = array ();
        }
        $view_names = Utils::getCalendarArray();

        // Show enabled ones first (in saved order), then any remaining available keys.
        $all_keys = array_keys( $view_names );
        $ordered_keys = array_values( array_unique( array_merge( $items, $all_keys ) ) );

        echo '<ul id="' . esc_attr( $args[ 'label_for' ] ) . '" class="myclub-sortable-calendar">';

        foreach ( $ordered_keys as $key ) {
            if ( !isset( $view_names[ $key ] ) ) {
                continue;
            }

            $id = $args[ 'label_for' ] . '_' . $key;
            $is_enabled = in_array( $key, $items, true );

            echo '<li class="myclub-sortable-item" data-key="' . esc_attr( $key ) . '">';

            echo '<input type="checkbox" id="' . esc_attr( $id ) . '" class="myclub-calendar-enable" value="' . esc_attr( $key ) . '" ' . checked( $is_enabled, true, false ) . ' />';
            echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $view_names[ $key ] ) . '</label>';

            echo '<input type="hidden" class="myclub-calendar-value" name="' . $name . '[]" value="' . esc_attr( $key ) . '"' . ( $is_enabled ? '' : ' disabled="disabled"' ) . ' />';

            echo '</li>';
        }

        echo '</ul>';
        if ( isset( $args[ 'help_text' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'help_text' ] ) . '</p>';
        } elseif ( isset( $args[ 'description' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'description' ] ) . '</p>';
        }
    }

    /**
     * Renders the input field for the calendar height setting.
     *
     * This method outputs an HTML input field allowing the user to specify the height
     * of a calendar. It includes an optional help text description if provided in the arguments.
     *
     * @param array $args {
     *     Array of arguments to configure the field.
     *
     * @type string $label_for The ID of the input field.
     * @type string $help_text Optional. Help text to be displayed below the field.
     * }
     * @param string $option_name The name of the option to retrieve and update the height setting.
     *
     * @return void
     * @since 1.4.0
     */
    private function renderCalendarHeightField( array $args, string $option_name )
    {
        $height = get_option( $option_name, '' );
        echo '<input type="text" id="' . esc_attr( $args[ 'label_for' ] ) . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $height ) . '" placeholder="' . esc_attr__( 'e.g. auto, 100%, 600', 'myclub-sections' ) . '" />';
        if ( isset( $args[ 'help_text' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'help_text' ] ) . '</p>';
        }
    }

    /**
     * Sanitizes a calendar height value by validating the input and ensuring it meets the expected format.
     *
     * The valid input can be an empty string, the string "auto", a positive integer (representing pixels),
     * or a percentage value (e.g., "100%"). If the input does not meet these criteria, it triggers an error
     * and returns an empty string.
     *
     * @param string $input The user-provided value for the calendar height.
     * @param string $field_name The settings field name to associate with potential validation error messages.
     * @return string The sanitized calendar height value or an empty string in case of invalid input.
     * @since 1.4.0
     */
    private function sanitizeCalendarHeightValue( string $input, string $field_name ): string
    {
        $input = sanitize_text_field( wp_unslash( $input ) );

        if ( $input === '' || $input === 'auto' ) {
            return $input;
        }

        if ( ctype_digit( $input ) && (int) $input > 0 ) {
            return $input;
        }

        if ( preg_match( '/^\d+(\.\d+)?%$/', $input ) ) {
            return $input;
        }

        add_settings_error( $field_name, 'invalid-value', __( 'Invalid calendar height. Leave empty, or use a positive number (pixels), "auto", or a percentage like "100%".', 'myclub-sections' ) );
        return '';
    }


    /**
     * Renders a checkbox element with the given arguments and field name.
     *
     * @param array $args An array of arguments for the checkbox element.
     * @param string $field_name The name of the field associated with the checkbox.
     * @param string|null $name The name of the field in the sorting box.
     * @param string|null $display_name The display name of the field in the sorting box.
     *
     * @return void
     * @since 1.0.0
     */
    private function renderCheckbox( array $args, string $field_name, string $name = null, string $display_name = null )
    {
        $checked = get_option( $field_name ) === '1' ? ' checked="checked"' : '';
        $class = $name ? ' class="sort-item-setter"' : '';

        echo '<input type="checkbox" id="' . esc_attr( $args[ 'label_for' ] ) . '" data-name="' . esc_attr( $name ) . '" data-display-name="' . esc_attr( $display_name ) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . $checked . $class . ' />';
        if ( isset( $args[ 'help_text' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'help_text' ] ) . '</p>';
        } elseif ( isset( $args[ 'description' ] ) ) {
            echo '<p class="description">' . wp_kses_post( $args[ 'description' ] ) . '</p>';
        }
    }
}