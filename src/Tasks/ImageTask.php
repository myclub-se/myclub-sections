<?php

namespace MyClub\MyClubSections\Tasks;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\Common\BackgroundProcessing\Background_Process;
use Myclub\Common\Services\BaseImageService;
use MyClub\MyClubSections\Services\ImageService;

/**
 * Class ImageTask
 *
 * Represents an image task that creates images from external links for different types of items - currently only news.
 */
class ImageTask extends Background_Process
{
    protected $prefix = 'myclub_sections';
    protected $action = 'image_task';

    private static ?ImageTask $instance = null;

    /**
     * Initializes the ImageTask class and returns an instance of it.
     *
     * @return ImageTask The initialized instance of the ImageTask class.
     * @since 1.0.0
     */
    public static function init(): ImageTask
    {
        if ( !self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create an image from an external link.
     *
     * @param mixed $item The image to be processed.
     *
     * @return bool Indicates whether the task should be processed further.
     * @since 1.0.0
     */
    protected function task( $item ): bool
    {
        $decoded_item = json_decode( $item );

        if ( property_exists( $decoded_item, 'post_id' ) && property_exists( $decoded_item, 'image' ) ) {
            if ( $decoded_item ) {
                if ( $decoded_item->type == 'news' ) {
                    $this->addNewsImage( $decoded_item );
                }
            }
        }

        return false;
    }

    /**
     * Completes the process by updating term counts for a specified taxonomy.
     *
     * Retrieves all term IDs for the taxonomy defined in ImageService::MYCLUB_IMAGES,
     * splits them into smaller chunks, and processes each chunk to update term counts.
     *
     * @return void
     * @since 2.1.0
     */
    protected function complete()
    {
        parent::complete();

        $term_ids = get_terms( [
            'taxonomy'   => BaseImageService::MYCLUB_IMAGES,
            'fields'     => 'ids',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
            return;
        }

        wp_update_term_count_now( $term_ids, BaseImageService::MYCLUB_IMAGES );
    }

    /**
     * Adds a news image to the specified news item.
     *
     * @param object $item The item containing the necessary data for adding the image.
     *
     * @return void
     * @since 1.0.0
     */
    private function addNewsImage( object $item )
    {
        if ( property_exists( $item, 'news_id' ) ) {
            ImageService::addFeaturedImage( $item->post_id, $item->image, 'news_' . $item->news_id . '_', $item->caption, 'news' );
        }
    }
}
