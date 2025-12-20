<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Api\RestApi;
use MyClub\MyClubSections\Utils;

class CalendarService
{
    private RestApi $api;

    public function __construct()
    {
        $this->api = new RestApi();
    }

    public function __destruct()
    {
        unset( $this->api );
    }

    /**
     * Retrieves a list of activities from the specified database table.
     *
     * @return array Array of activities as retrieved from the database. Returns an empty array if an error occurs.
     * @since 1.3.0
     */
    static public function ListActivities(): array
    {
        return ActivityService::listClubActivities() ?? [];
    }

    /**
     * Retrieves and processes a list of club events from the external API.
     * Updates, creates, or deletes activities based on the response data.
     *
     * @return void
     * @since 1.3.0
     */
    public function reloadClubEvents(): void
    {
        $response = $this->api->loadClubCalendar();

        if ( !is_wp_error( $response ) && $response->status === 200 ) {
            $update = false;
            $activity_ids = array ();

            foreach ( $response->result->results as $activity ) {
                $update = ActivityService::createOrUpdateActivity( $activity, [ "show_on_club_calendar" => 1 ] ) || $update;
                $activity_ids[] = $activity->uid;
            }

            $deletable_ids = array_diff( ActivityService::listClubActivityIds(), $activity_ids );

            if ( !empty( $deletable_ids ) ) {
                $update = true;
                foreach ( $deletable_ids as $id ) {
                    if ( count( ActivityService::listActivityPostIds( $id ) ) ) {
                        $activity = ActivityService::getActivity( $id );
                        ActivityService::createOrUpdateActivity( $activity, [ "show_on_club_calendar" => 0 ] );
                    } else {
                        ActivityService::deleteActivity( $id );
                    }
                }

                unset( $deletable_ids );
            }

            if ( $update ) {
                $post_ids = Utils::getClubCalendarPosts();

                foreach ( $post_ids as $post_id ) {
                    Utils::clearCacheForPage( $post_id );
                }

                unset( $post_ids );
            }

            Utils::updateOrCreateOption( 'myclub_sections_last_club_calendar_sync', gmdate( "c" ), 'no' );
        }

        unset( $response );

        if ( function_exists( 'gc_collect_cycles' ) ) {
            gc_collect_cycles();
        }
    }
}