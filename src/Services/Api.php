<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Utils;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

class Api
{

    /**
     * Registers the necessary hooks for the REST API.
     *
     * This method ties the 'rest_api_init' action to the 'registerRoutes' method
     * of this class, ensuring that the routes are registered during the
     * initialization of the REST API.
     *
     * @return void
     * @since 1.0.0
     */
    public function register()
    {
        add_action( 'rest_api_init', [
            $this,
            'registerRoutes'
        ] );
    }

    /**
     * Registers custom REST API routes for the application.
     *
     * This method defines multiple routes under the namespace 'myclub/sections/v1'.
     * Each route handles specific endpoints, their allowed HTTP methods, associated
     * callback functions, required permissions, and request argument validation.
     *
     * @return void
     * @since 1.0.0
     */
    public function registerRoutes()
    {
        register_rest_route( 'myclub/sections/v1', '/club-activities', [
            'methods'             => 'GET',
            'callback'            => [
                $this,
                'returnClubActivities'
            ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            }
        ] );

        register_rest_route( 'myclub/sections/v1', '/options', [
            'methods'             => 'GET',
            'callback'            => [
                $this,
                'returnOptions'
            ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            }
        ] );

        register_rest_route( 'myclub/sections/v1', '/sections', [
            'methods'             => 'GET',
            'callback'            => [
                $this,
                'returnSections'
            ],
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            }
        ] );

        register_rest_route( 'myclub/sections/v1', '/sections/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [
                $this,
                'returnSection'
            ],
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
            'args'                => array (
                'id' => array (
                    'validate_callback' => function ( $param, $request, $key ) {
                        return is_numeric( $param );
                    }
                ),
            )
        ] );
    }

    /**
     * Get the list of club activities.
     *
     * Retrieves the list of club activities using the CalendarService. The activities data is fetched and returned as a response.
     *
     * @return WP_REST_Response The response containing the list of club activities.
     */
    public function returnClubActivities(): WP_REST_Response
    {
        return new WP_REST_Response( CalendarService::ListActivities(), 200 );
    }

    /**
     * Returns an array of options.
     *
     * This method retrieves the values of the following options from the database and returns them as an array:
     * - 'myclub_sections_calendar_title' option
     * - 'myclub_sections_club_calendar_title' option
     * - 'myclub_sections_club_news_title' option
     * - 'myclub_sections_description_title' option
     * - 'myclub_sections_news_title' option
     *
     * @return WP_REST_Response An array containing the values of the retrieved options.
     *
     * @since 1.0.0
     */
    public function returnOptions(): WP_REST_Response
    {
        $default_desktop_calendar_views = Utils::getCalendarDesktopViews();
        $default_desktop_calendar_views_default = Utils::getCalendarDesktopViewsDefault();
        $default_mobile_calendar_views = Utils::getCalendarMobileViews();
        $default_mobile_calendar_views_default = Utils::getCalendarMobileViewsDefault();

        return new WP_REST_Response( [
            'myclub_sections_calendar_title'                         => esc_attr( get_option( 'myclub_sections_calendar_title' ) ),
            'myclub_sections_club_calendar_title'                    => esc_attr( get_option( 'myclub_sections_club_calendar_title' ) ),
            'myclub_sections_club_news_title'                        => esc_attr( get_option( 'myclub_sections_club_news_title' ) ),
            'myclub_sections_description_title'                      => esc_attr( get_option( 'myclub_sections_description_title' ) ),
            'myclub_sections_news_title'                             => esc_attr( get_option( 'myclub_sections_news_title' ) ),
            'myclub_sections_club_calendar_desktop_views'            => esc_attr( join( ',', get_option( 'myclub_sections_club_calendar_desktop_views', $default_desktop_calendar_views ) ) ),
            'myclub_sections_club_calendar_desktop_views_default'    => esc_attr( get_option( 'myclub_sections_club_calendar_desktop_views_default', $default_desktop_calendar_views_default ) ),
            'myclub_sections_club_calendar_mobile_views'             => esc_attr( join( ',', get_option( 'myclub_sections_club_calendar_mobile_views', $default_mobile_calendar_views ) ) ),
            'myclub_sections_club_calendar_mobile_views_default'     => esc_attr( get_option( 'myclub_sections_club_calendar_mobile_views_default', $default_mobile_calendar_views_default ) ),
            'myclub_sections_club_calendar_show_week_numbers'        => esc_attr( get_option( 'myclub_sections_club_calendar_show_week_numbers', '1' ) === '1' ),
            'myclub_sections_section_calendar_desktop_views'         => esc_attr( join( ',', get_option( 'myclub_sections_section_calendar_desktop_views', $default_desktop_calendar_views ) ) ),
            'myclub_sections_section_calendar_desktop_views_default' => esc_attr( get_option( 'myclub_sections_section_calendar_desktop_views_default', $default_desktop_calendar_views_default ) ),
            'myclub_sections_section_calendar_mobile_views'          => esc_attr( join( ',', get_option( 'myclub_sections_section_calendar_mobile_views', $default_mobile_calendar_views ) ) ),
            'myclub_sections_section_calendar_mobile_views_default'  => esc_attr( get_option( 'myclub_sections_section_calendar_mobile_views_default', $default_mobile_calendar_views_default ) ),
            'myclub_sections_section_calendar_show_week_numbers'     => esc_attr( get_option( 'myclub_sections_section_calendar_show_week_numbers', '1' ) ),
        ], 200 );
    }

    /**
     * Retrieves a section from the database.
     *
     * This method retrieves a section from the database based on the provided ID. The ID is obtained from the request object.
     * If the section is not found or is not published, a WP_Error object is returned. Otherwise, a WP_REST_Response object
     * containing the section details is returned.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response|WP_Error The section details as a WP_REST_Response object if found, or a WP_Error object if not found.
     *
     * @since 1.0.0
     *
     */
    public function returnSection( WP_REST_Request $request ): WP_REST_Response
    {
        $post = get_post( $request[ 'id' ] );

        if ( empty( $post ) || $post->post_status !== 'publish' || $post->post_type !== SectionService::MYCLUB_SECTIONS ) {
            return new WP_REST_Response(
                [
                    'message' => __( 'There is no MyClub Section post with that ID', 'myclub-sections' )
                ], 404
            );
        }

        $post_id = $post->ID;

        return new WP_REST_Response( [
            'activities'  => Utils::prepareActivitiesJson( ActivityService::listPostActivities( $post_id ) ),
            'description' => get_post_meta( $post_id, 'myclub_sections_description', true ),
            'title'       => get_the_title( $post_id ),
        ], 200 );
    }

    /**
     * Get the list of MyClub sections.
     *
     * Retrieves the list of MyClub sections by querying the WordPress database. The sections are retrieved by their post type called "myclub-sections". The query retrieves all published section
     * posts and returns them as an array of objects with "post_id" and "post_title" properties.
     *
     * @return WP_REST_Response The response that contains the list of MyClub sections.
     *
     * @since 1.0.0
     */
    public function returnSections(): WP_REST_Response
    {
        $args = array (
            'post_type'      => SectionService::MYCLUB_SECTIONS,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'title',
            'order'          => 'ASC'
        );

        $myclub_sections_query = new WP_Query( $args );

        $myclub_sections = array ();

        if ( $myclub_sections_query->have_posts() ) {
            foreach ( $myclub_sections_query->posts as $post_id ) {
                $myclub_sections[] = array (
                    'id'    => $post_id,
                    'title' => get_the_title( $post_id ),
                );
            }
        }

        return new WP_REST_Response( $myclub_sections, 200 );
    }
}