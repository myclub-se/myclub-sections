<?php

namespace MyClub\MyClubSections;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\Common\BaseUtils;
use MyClub\MyClubSections\Services\SectionService;


/**
 * Utility class for managing images, URLs, cache, and posts in a WordPress environment.
 */
class Utils extends BaseUtils
{
    /**
     * Get the post ID based on the given attributes.
     *
     * @param array $attributes The attributes used to determine the post ID.
     *                         Supported attributes:
     *                         - post_id: The specific post ID to retrieve.
     *                         - section_id: The section ID used to retrieve the post ID from the database.
     *
     * @return int The retrieved post ID.
     * @since 1.0.0
     */
    static function getPostId( array $attributes ): int
    {
        if ( !empty( $attributes[ 'post_id' ] ) ) {
            $post_id = (int)$attributes[ 'post_id' ];
        } else if ( !empty( $attributes[ 'section_id' ] ) ) {
            $args = array (
                'post_type'  => SectionService::MYCLUB_SECTIONS,
                'meta_key'   => 'myclub_sections_id',
                'meta_value' => $attributes[ 'section_id' ]
            );
            $posts = get_posts( $args );

            // If posts were found.
            if ( !empty( $posts ) ) {
                $post_id = $posts[ 0 ]->ID;
            }
        }

        return empty( $post_id ) ? 0 : $post_id;
    }

    /**
     * Delete a post and related attachments and metadata from the WordPress database.
     *
     * @param int $post_id The ID of the post to delete.
     *
     * @return void
     * @since 1.0.0
     */
    static function deletePost( int $post_id )
    {
        if ( has_post_thumbnail( $post_id ) ) {
            $attachment_id = get_post_thumbnail_id( $post_id );
            delete_post_thumbnail( $post_id );
            wp_delete_attachment( $attachment_id, true );
        }

        wp_delete_post( $post_id, true );

        $other_cached_post_ids = Utils::getOtherCachedPosts( $post_id );

        foreach ( $other_cached_post_ids as $cached_post_id ) {
            Utils::clearCacheForPage( $cached_post_id );
        }

        Utils::clearCacheForPage( $post_id );
    }

    /**
     * Retrieves a list of post IDs that contain specific club calendar content within their post content.
     *
     * The method searches for posts where the content matches specified strings and applies a filter for published status.
     *
     * @return array An array of post IDs that match the specified conditions.
     * @since 1.0.0
     */
    static function getClubCalendarPosts(): array
    {
        global $wpdb;

        $like_clauses = [
            "post_content LIKE 'wp:myclub-sections/club-calendar'",
            "post_content LIKE '[myclub-sections-club-calendar]'"
        ];

        // Combine conditions with 'OR' and prepare query
        $where_clause = implode( ' OR ', $like_clauses );
        $post_status = 'publish';

        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE ($where_clause) AND post_status = %s",
            $post_status
        );

        return $wpdb->get_col( $query );
    }

    /*
     * Retrieves a list of post IDs matching specific content (shortcodes, blocks, or both).
     *
     * @param int|null $post_id The post ID to match in content (optional).
     * @param string|null $section_id The section ID to match in content (optional).
     *
     * @return array An array of matching post IDs, or an empty array if no matches are found.
     * @since 1.0.0
     */
    static function getOtherCachedPosts( ?int $post_id = null, ?string $section_id = null ): array
    {
        global $wpdb;

        if ( !$post_id && !$section_id ) {
            return [];
        }

        $like_clauses = [];

        if ( $post_id ) {
            $like_clauses[] = $wpdb->prepare(
                "post_content LIKE %s",
                '%post_id="' . esc_sql( $post_id ) . '"%'
            );
            $like_clauses[] = $wpdb->prepare(
                "post_content LIKE %s",
                '%"post_id":"' . esc_sql( $post_id ) . '"%'
            );
        }

        if ( $section_id ) {
            $like_clauses[] = $wpdb->prepare(
                "post_content LIKE %s",
                '%section_id="' . esc_sql( $section_id ) . '"%'
            );
            $like_clauses[] = $wpdb->prepare(
                "post_content LIKE %s",
                '%"section":"' . esc_sql( $section_id ) . '"%'
            );
        }

        // Combine conditions with 'OR' and prepare query
        $where_clause = implode( ' OR ', $like_clauses );
        $post_type_exclusion = esc_sql( SectionService::MYCLUB_SECTIONS );
        $post_status = 'publish';

        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE ($where_clause) AND post_status = %s AND post_type != %s",
            $post_status,
            $post_type_exclusion
        );

        return $wpdb->get_col( $query );
    }
}