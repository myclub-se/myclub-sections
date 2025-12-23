<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Tasks\RefreshSectionsTask;
use MyClub\MyClubSections\Utils;
use stdClass;
use WP_Query;

/**
 * Service class for managing and processing sections.
 *
 * This class extends the Sections base class and provides a set of methods to
 * manipulate, refresh, and clean up section-related data and content in the database.
 */
class SectionService extends Sections
{
    const MYCLUB_SECTIONS = 'myclub-sections';

    private bool $content_or_data_updated = false;

    /**
     * Class constructor.
     *
     * @return void
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retrieves the content for a MyClub section post.
     *
     * @param int $post_id The ID of the post.
     * @param mixed $selected_blocks Optional. The selected blocks to include in the content. Default is null.
     * @return string The new content of the MyClub section post.
     * @since 1.0.0
     */
    public static function createPostContent( int $post_id, $selected_blocks = null ): string
    {
        $option_names = [
            'calendar'     => 'myclub_sections_page_calendar',
            'coming-games' => 'myclub_sections_page_coming_games',
            'description'  => 'myclub_sections_page_description',
            'news'         => 'myclub_sections_page_news',
        ];

        $post_id_string = ' {"post_id":"' . $post_id . '"}';

        if ( empty( $selected_blocks ) ) {
            $selected_blocks = get_option( 'myclub_sections_show_items_order' );

            if ( in_array( 'default', $selected_blocks ) ) {
                $selected_blocks = array (
                    'description',
                    'calendar',
                    'news',
                    'coming-games',
                );
            }
        }

        $content = '';

        foreach ( $selected_blocks as $block ) {
            if ( get_option( $option_names[ $block ], true ) ) {
                $content .= '<!-- wp:myclub-sections/' . $block . $post_id_string . ' /-->';
            }
        }

        return $content;
    }

    /**
     * Deletes all sections of the specified post type.
     *
     * This method queries all posts of a specific type defined in SectionService::MYCLUB_SECTIONS.
     * It iterates through each post and deletes it using the Utils::deletePost() method.
     *
     * @return void
     * @since 1.0.0
     */
    public function deleteAllSections(): void
    {
        $args = array (
            'post_type'      => SectionService::MYCLUB_SECTIONS,
            'posts_per_page' => -1,
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->next_post();

                $post_id = $query->post->ID;

                Utils::deletePost( $post_id );
            }
        }

        unset( $args, $query );
    }

    /**
     * Reloads all sections by retrieving their IDs and processing them.
     *
     * This method fetches all section IDs and, if successful, initializes a refresh task
     * to process each section by pushing the IDs into the task's queue. Once all sections
     * are queued, the task is dispatched for execution. If the retrieval of section IDs
     * is unsuccessful, an error message is logged in the options.
     *
     * @return void
     * @since 1.0.0
     */
    public function reloadSections(): void
    {
        $section_ids = $this->getAllSectionIds();

        if ( $section_ids->success ) {
            $process = RefreshSectionsTask::init();

            foreach ( $section_ids->ids as $id ) {
                $process->push_to_queue( $id );
            }

            $process->save()->dispatch();
        } else {
            Utils::updateOrCreateOption( 'myclub_sections_last_sections_sync', __( "Unable to load sections", "myclub-sections" ), 'no' );
        }

        unset ( $section_ids );
    }

    public function updateSectionPage( string $section_id ): void
    {
        $this->content_or_data_updated = false;

        $page_template = get_option( 'myclub_sections_page_template' );
        $response = $this->api->loadSection( $section_id );

        if ( !is_wp_error( $response ) && $response->status === 200 ) {
            $section = $response->result;
            $post_id = $this->getSectionPostId( $section_id );

            if ( !$post_id ) {
                $post_id = wp_insert_post( $this->createPostArgs( $section, 0, $page_template ) );

                if ( $post_id && !is_wp_error( $post_id ) ) {
                    $this::updateSectionPageContents( $post_id, null, false );
                }
            } else {
                $post_id = wp_update_post( $this->createPostArgs( $section, $post_id, $page_template ) );
            }

            if ( $post_id && !is_wp_error( $post_id ) ) {
                $this->addActivities( $post_id, $section );
                update_post_meta( $post_id, 'myclub_sections_last_updated', gmdate( "c" ) );
                update_post_meta( $post_id, '_wp_page_template', $page_template );
            }

            if ( $this->content_or_data_updated ) {
                $other_cached_post_ids = Utils::getOtherCachedPosts( $post_id, $section_id );

                foreach ( $other_cached_post_ids as $other_post_id ) {
                    Utils::clearCacheForPage( $other_post_id );
                }

                Utils::clearCacheForPage( $post_id );
            }

            unset( $section );
        }

        unset( $response );

        if ( function_exists( 'gc_collect_cycles' ) ) {
            gc_collect_cycles();
        }
    }

    /** Updates the content and page template of a section page.
     *
     * @param int $post_id The post ID of the section page.
     * @param mixed $page_contents The new content for the section page.
     * @param bool $clear_cache Clear cache if set
     * @return void
     */
    public static function updateSectionPageContents( int $post_id, $page_contents, bool $clear_cache = true )
    {
        $post_content = array (
            'ID'           => $post_id,
            'post_content' => SectionService::createPostContent( $post_id, $page_contents ),
        );

        // Update the post into the database
        $result = wp_update_post( $post_content, true );

        if ( is_wp_error( $result ) ) {
            error_log( "Unable to update post $post_id" );
            error_log( $result->get_error_message() );
        }

        if ( $clear_cache ) {
            Utils::clearCacheForPage( $post_id );
        }
    }

    /**
     * Removes unused section pages from the database.
     *
     * This method identifies section pages in the database that are no longer in use based on their IDs.
     * It retrieves all existing section IDs from the database, compares them with the current list of section IDs,
     * and deletes any section pages with IDs not matching the current list.
     *
     * @return void
     * @since 1.0.0
     */
    public function removeUnusedSectionPages(): void
    {
        $section_ids = $this->getAllSectionIds();

        if ( !$section_ids->success ) {
            global $wpdb;

            $existing_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT pm.meta_value FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON (p.ID = pm.post_id) WHERE pm.meta_key= %s and p.post_type = %s",
                    'myclub_sections_id', SectionService::MYCLUB_SECTIONS
                )
            );

            $old_ids = array_diff( $existing_ids, $section_ids->ids );

            if ( count( $old_ids ) ) {
                $args = array (
                    'post_type'      => SectionService::MYCLUB_SECTIONS,
                    'meta_query'     => array (
                        array (
                            'key'     => 'myclub_sections_id',
                            'value'   => $old_ids,
                            'compare' => 'IN'
                        ),
                    ),
                    'posts_per_page' => -1
                );

                $query = new WP_Query( $args );

                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->next_post();
                        wp_delete_post( $query->post->ID, true );
                    }
                }

                unset( $old_ids, $args, $query );
            }

            unset ( $existing_ids );

        }

        unset ( $section_ids );
    }

    /**
     * Adds activities to a post and updates or removes as necessary.
     *
     * This method processes the given activities within a section, associates them with the specified post,
     * and determines whether they need to be created, updated, or removed. It ensures that only the activities
     * provided in the section are retained for the post, removing any other previously associated activities.
     * Updates a flag if any changes are made.
     *
     * @param int $post_id The ID of the post to associate the activities with.
     * @param stdClass $section An object containing the activities to process, where each activity includes properties such as `uid` and `post_id`.
     * @return void
     * @since 1.0.0
     */
    private function addActivities( int $post_id, stdClass $section ): void
    {
        $remote_ids = array ();
        $update = false;

        foreach ( $section->activities as $activity ) {
            $remote_ids[] = $activity->uid;
            $activity->post_id = $post_id;
            $update = ActivityService::createOrUpdateActivity( $activity ) || $update;
        }

        $deletable_ids = array_diff( ActivityService::listPostActivityIds( $post_id ), $remote_ids );

        foreach ( $deletable_ids as $id ) {
            ActivityService::removeActivityFromPost( $post_id, $id );
            $update = true;
        }

        if ( $update ) {
            $this->content_or_data_updated = true;
        }

        unset( $deletable_ids, $remote_ids );
    }

    /**
     * Creates and returns an array of arguments for creating or updating a post.
     *
     * This method generates post arguments including details such as title, name, status, type, content,
     * page template, and meta fields. It optionally updates an existing post if the provided post ID is valid.
     * It also compares existing post data with the new arguments and updates a flag
     * if differences are detected.
     *
     * @param stdClass $section An object representing the section, containing properties such as
     *                               name, id, and description.
     * @param int $post_id An optional post ID. If provided, the method will update the post
     *                               with the given ID.
     * @param string $page_template The page template to assign to the post, applicable if the site uses
     *                               a block theme.
     *
     * @return array An associative array containing the arguments for creating or updating a WordPress post.
     * @since 1.0.0
     */
    private function createPostArgs( stdClass $section, int $post_id, string $page_template ): array
    {
        $args = [
            'post_title'    => sanitize_text_field( $section->name ),
            'post_name'     => sanitize_title( $section->name ),
            'post_status'   => 'publish',
            'post_type'     => SectionService::MYCLUB_SECTIONS,
            'post_content'  => $post_id ? $this->createPostContent( $post_id ) : '',
            'page_template' => wp_is_block_theme() ? $page_template : '',
            'meta_input'    => [
                'myclub_sections_id'          => sanitize_text_field( $section->id ),
                'myclub_sections_description' => wp_kses_post( $section->description )
            ]
        ];

        if ( $post_id ) {
            $args[ 'ID' ] = $post_id;
            $existing_post = get_post( $post_id );
            $existing_meta = get_post_meta( $post_id );

            // Compare title
            if ( $existing_post && $existing_post->post_title !== $args[ 'post_title' ] ) {
                $this->content_or_data_updated = true;
            }

            // Compare content
            if ( $existing_post && $existing_post->post_content !== $args[ 'post_content' ] ) {
                $this->content_or_data_updated = true;
            }

            if ( $existing_meta ) {
                if ( isset( $existing_meta[ '_wp_page_template' ][ 0 ] ) &&
                    $existing_meta[ '_wp_page_template' ][ 0 ] !== $args[ 'page_template' ] ) {
                    $this->content_or_data_updated = true;
                }

                // Compare meta fields
                foreach ( $args[ 'meta_input' ] as $key => $value ) {
                    if ( !isset( $existing_meta[ $key ][ 0 ] ) || $existing_meta[ $key ][ 0 ] !== $value ) {
                        $this->content_or_data_updated = true;
                    }
                }
            } else {
                $this->content_or_data_updated = true;
            }
        }
        return $args;
    }
}