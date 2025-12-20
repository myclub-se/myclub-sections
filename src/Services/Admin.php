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
                'addMyclubSectionsSettings'
        ] );

        add_action( 'update_option_myclub_sections_api_key', [
                $this,
                'updateApiKey'
        ], 10, 0 );

        add_action( 'update_option_myclub_sections_show_item_order', [
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

    public function adminSettings()
    {
        return require_once $this->plugin_path . '/templates/admin/admin-settings.php';
    }

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
        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_last_news_sync', [
                'default' => NULL
        ] );
        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_last_sections_sync', [
                'default' => NULL
        ] );
        register_setting( 'myclub_sections_settings_tab1', 'myclub_sections_last_club_calendar_sync', [
                'default' => NULL
        ] );

        # endregion

        # region tab2 settings

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
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_page_description', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_page_calendar', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_page_news', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeCheckbox'
                ],
                'default'           => '1'
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_page_template', [
                'sanitize_callback' => [
                        $this,
                        'sanitizePageTemplate'
                ],
                'default'           => ''
        ] );
        register_setting( 'myclub_sections_settings_tab2', 'myclub_sections_show_items_order', [
                'sanitize_callback' => [
                        $this,
                        'sanitizeShowItemsOrder'
                ],
                'default'           => array (
                        'default',
                )
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
        add_settings_section( 'myclub_sections_sync', __( 'Synchronization information', 'myclub-sections' ), function () {
        }, 'myclub_sections_settings_tab1' );

        # endregion

        # region tab2 sections

        add_settings_section( 'myclub_sections_title_settings', __( 'Title settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here you can set the titles for the fields that are displayed on the section pages. The titles are used in the Gutenberg blocks and shortcodes. You cannot leave the title field empty.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab2' );
        add_settings_section( 'myclub_sections_display_settings', __( 'Display settings', 'myclub-sections' ), function () {
            echo '<p>';
            esc_attr_e(
                    'Here you can set the display options for the section pages. You select which fields should be visible and then in which order. On a Gutenberg theme you can also choose which template should be used for the section pages.',
                    'myclub-sections'
            );
            echo '</p>';
        }, 'myclub_sections_settings_tab2' );

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
        add_settings_field( 'myclub_sections_last_news_sync', __( 'News last synchronized', 'myclub-sections' ), [
                $this,
                'renderNewsLastSync'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_sync' );
        add_settings_field( 'myclub_sections_last_sections_sync', __( 'Sections last synchronized', 'myclub-sections' ), [
                $this,
                'renderSectionsLastSync'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_sync' );
        add_settings_field( 'myclub_sections_last_club_calendar_sync', __( 'Club calendar last synchronized', 'myclub-sections' ), [
                $this,
                'renderClubCalendarLastSync'
        ], 'myclub_sections_settings_tab1', 'myclub_sections_sync' );

        # endregion

        # region tab2 title settings fields

        add_settings_field( 'myclub_sections_calendar_title', __( 'Title for calendar field', 'myclub-sections' ), [
                $this,
                'renderCalendarTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_calendar_title' ] );
        add_settings_field( 'myclub_sections_club_calendar_title', __( 'Title for club calendar field', 'myclub-sections' ), [
                $this,
                'renderClubCalendarTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_club_calendar_title' ] );
        add_settings_field( 'myclub_sections_club_news_title', __( 'Title for club news field', 'myclub-sections' ), [
                $this,
                'renderClubNewsTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_club_news_title' ] );
        add_settings_field( 'myclub_sections_description_title', __( 'Title for description field', 'myclub-sections' ), [
                $this,
                'renderDescriptionTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_description_title' ] );
        add_settings_field( 'myclub_sections_news_title', __( 'Title for news field', 'myclub-sections' ), [
                $this,
                'renderNewsTitle'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_title_settings', [ 'label_for' => 'myclub_sections_news_title' ] );

        # endregion

        # region tab2 display settings fields

        add_settings_field( 'myclub_sections_page_description', __( 'Show section description', 'myclub-sections' ), [
                $this,
                'renderPageDescription'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_description' ] );
        add_settings_field( 'myclub_sections_page_calendar', __( 'Show section calendar', 'myclub-sections' ), [
                $this,
                'renderPageCalendar'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_calendar' ] );
        add_settings_field( 'myclub_sections_page_news', __( 'Show section news', 'myclub-sections' ), [
                $this,
                'renderPageNews'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_news' ] );
        if ( wp_is_block_theme() ) {
            add_settings_field( 'myclub_sections_page_template', __( 'Template for section pages', 'myclub-sections' ), [
                    $this,
                    'renderPageTemplate'
            ], 'myclub_sections_settings_tab2', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_page_template' ] );
        }
        add_settings_field( 'myclub_sections_show_items_order', __( 'Shown items order', 'myclub-sections' ), [
                $this,
                'renderShowItemsOrder'
        ], 'myclub_sections_settings_tab2', 'myclub_sections_display_settings', [ 'label_for' => 'myclub_sections_show_items_order' ] );
        # endregion

    }

    public function ajaxReloadNews()
    {
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

    public function ajaxReloadSections()
    {
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

    public function ajaxSyncClubCalendar()
    {
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
            wp_register_style( 'myclub_sections_settings_css', $this->plugin_url . 'resources/css/myclub_sections_settings.css', [], MYCLUB_SECTIONS_PLUGIN_VERSION );
            wp_set_script_translations( 'myclub_sections_settings_js', 'myclub-sections', $this->plugin_path . 'languages' );

            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'myclub_sections_settings_js' );
            wp_enqueue_style( 'myclub_sections_settings_css' );
        }
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
     * Renders the date and time field for the last sync of the club calendar.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderClubCalendarLastSync()
    {
        $this->renderDateTimeField( 'myclub_sections_last_club_calendar_sync' );
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
     * Renders the last news sync field in the MyClub Sections plugin.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderNewsLastSync()
    {
        $this->renderDateTimeField( 'myclub_sections_last_news_sync' );
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
     * Renders the last sections sync field in the MyClub Sections plugin.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderSectionsLastSync()
    {
        $this->renderDateTimeField( 'myclub_sections_last_sections_sync' );
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
            );
        }

        $sort_names = [
                'description' => __( 'Description', 'myclub-sections' ),
                'calendar'    => __( 'Calendar', 'myclub-sections' ),
                'news'        => __( 'News', 'myclub-sections' )
        ];

        echo '<ul id="' . esc_attr( $args[ 'label_for' ] ) . '">';

        foreach ( $items as $item ) {
            echo '<li><input type="hidden" value="' . esc_attr( $item ) . '" name="myclub_sections_show_items_order[]" />' . esc_attr( $sort_names[ $item ] ) . '</li>';
        }

        echo '</ul>';
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
        return $input === '1' ?: '0';
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
            add_settings_error( 'myclub_sections_section_news_slug', 'empty-slug', __( 'You have to enter a valid slug', 'myclub-section' ) );
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
                'news'
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

    public function updateApiKey(): void
    {
        $service = new SectionService();
        $service->reloadSections();
    }

    public function updatePageTemplate( string $old_value, string $new_value ): void
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
    }

    /**
     * Renders a datetime field.
     *
     * @param string $field_name The name of the option field.
     *
     * @return void
     * @since 1.0.0
     */
    private function renderDateTimeField( string $field_name )
    {
        $last_sync = esc_attr( get_option( $field_name ) );
        $cron_job_name = '';
        $output = '';

        if ( $field_name === 'myclub_sections_last_news_sync' ) {
            $cron_job_name = 'myclub_sections_refresh_news_task_cron';
            $cron_job_type = __( 'news', 'myclub-sections' );
        }

        if ( $field_name === 'myclub_sections_last_sections_sync' ) {
            $cron_job_name = 'myclub_sections_refresh_sections_task_cron';
            $cron_job_type = __( 'sections', 'myclub-sections' );
        }

        if ( !empty( $cron_job_name ) && isset( $cron_job_type ) ) {
            $next_scheduled = wp_next_scheduled( $cron_job_name );
            if ( $next_scheduled ) {
                $output = sprintf( __( 'The %1$s update task is currently running.', 'myclub-sections' ), esc_attr( $cron_job_type ) );
            }
        }

        if ( empty ( $output ) ) {
            $output = empty( $last_sync ) ? __( 'Not synchronized yet', 'myclub-sections' ) : Utils::formatDateTime( $last_sync );
        }

        echo '<div id="' . $field_name . '">' . esc_attr( $output ) . '</div>';
    }
}