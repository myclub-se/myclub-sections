<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Api\RestApi;
use stdClass;
use WP_Query;


class Sections
{
    protected RestApi $api;

    public function __construct()
    {
        $this->api = new RestApi();
    }

    protected function getAllSectionIds(): stdClass
    {
        $response = $this->api->loadSections();
        $return_value = new stdClass();
        $return_value->ids = [];
        $return_value->success = false;

        if ( $response->status !== 200 ) {
            return $return_value;
        }

        $return_value->ids = array_column( $response->result->results, 'id' );
        $return_value->success = true;
        return $return_value;
    }

    /**
     * Retrieves the post ID of the section post with the given myclub_sections_id.
     *
     * @param string $myclub_sections_id The myclub_sections_id to search for.
     *
     * @return int|false The ID of the section post if found, false otherwise.
     */
    protected function getSectionPostId( string $myclub_sections_id )
    {
        $args = array (
            'post_type'      => SectionService::MYCLUB_SECTIONS,
            'meta_query'     => array (
                array (
                    'key'     => 'myclub_sections_id',
                    'value'   => $myclub_sections_id,
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => 1
        );

        $query = new WP_Query( $args );
        $id = false;

        if ( $query->have_posts() ) {
            $id = $query->posts[ 0 ]->ID;
        }

        unset( $query );
        return $id;
    }
}
