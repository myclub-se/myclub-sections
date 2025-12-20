<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\Common\Services\BaseImageService;

class Taxonomy extends Base
{

    /**
     * Registers actions and filters for initializing custom post types, enqueueing admin scripts,
     * modifying body classes, and customizing the single template rendering.
     *
     * @return void
     * @since 1.0.0
     */
    public function register()
    {
        add_action( 'init', [
            $this,
            'initCPT'
        ], 5 );
        add_action( 'admin_enqueue_scripts', [
            $this,
            'enqueueScripts'
        ] );
        add_filter( 'body_class', [
            $this,
            'addBodyClass'
        ] );
        add_filter( 'single_template', [
            $this,
            'showSingleSection'
        ], 20 );;
    }

    /**
     * Enqueue the required scripts and css for displaying the section pages custom posts.
     *
     * @return void
     * @since 1.0.0
     */
    public function enqueueScripts()
    {
        $current_page = get_current_screen();

        if ( $current_page->post_type === SectionService::MYCLUB_SECTIONS ) {
            // Register admin scripts and styles
            wp_register_style( 'myclub_sections_tabs_css', $this->plugin_url . 'resources/css/myclub_sections.css', [], MYCLUB_SECTIONS_PLUGIN_VERSION );
            wp_register_script( 'myclub_sections_tabs_ui', $this->plugin_url . 'resources/javascript/myclub_sections_tabs.js', [ 'jquery' ], MYCLUB_SECTIONS_PLUGIN_VERSION, true );

            wp_enqueue_style( 'myclub_sections_tabs_css' );
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'myclub_sections_tabs_ui' );
        }
    }

    /**
     * Initializes custom post types, custom meta fields, and taxonomies used in the application.
     *
     * This method sets up the following:
     * - Registers the "Sections" custom post type with specific capabilities, supports, and meta fields.
     * - Adds custom meta fields for storing additional data related to sections.
     * - Registers the "Section news" taxonomy for posts, with restricted capabilities and custom rewrite rules.
     * - Defines the "Image Types" taxonomy for attachments to classify different types of images.
     * - Updates taxonomy terms and manages associated counts for attachments.
     * - Ensures that the rewrite rules are flushed after making changes.
     *
     * @return void
     * @since 1.0.0
     */
    public function initCPT()
    {
        $slug = get_option( 'myclub_sections_section_slug' );
        if ( empty( $slug ) ) {
            $slug = 'sections';
        }

        $section_news_slug = get_option( 'myclub_sections_section_news_slug' );
        if ( empty( $section_news_slug ) ) {
            $section_news_slug = 'section-news';
        }

        register_post_type(
            SectionService::MYCLUB_SECTIONS,
            [
                'public'               => true,
                'labels'               => [
                    'name'          => __( 'Sections', 'myclub-sections' ),
                    'singular_name' => __( 'Section', 'myclub-sections' )
                ],
                'capabilities'         => [
                    'create_posts'           => 'do_not_allow',
                    'delete_posts'           => 'do_not_allow',
                    'delete_published_posts' => 'do_not_allow',
                ],
                'map_meta_cap'         => true,
                'has_archive'          => false,
                'menu_icon'            => 'dashicons-networking',
                'rewrite'              => [
                    'slug'       => $slug,
                    'with_front' => false,
                    'feeds'      => false,
                    'pages'      => true
                ],
                'register_meta_box_cb' => [
                    $this,
                    'registerMetaBox'
                ],
                'show_in_rest'         => true,
                'show_in_nav_menus'    => true,
                'supports'             => [
                    'custom-fields',
                    'title',
                    'editor',
                    'page-attributes'
                ]
            ]
        );

        register_post_meta( SectionService::MYCLUB_SECTIONS, 'myclub_sections_id', [
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string'
        ] );

        register_post_meta( SectionService::MYCLUB_SECTIONS, 'myclub_sections_description', [
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string'
        ] );

        register_taxonomy( NewsService::MYCLUB_SECTIONS_NEWS, 'post', [
            'capabilities' => [
                'edit_terms'   => 'do_not_allow',
                'delete_terms' => 'do_not_allow'
            ],
            'label'        => __( 'Section news', 'myclub-sections' ),
            'rewrite'      => array (
                'slug'       => $section_news_slug,
                'with_front' => false
            ),
            'show_in_rest' => true,
        ] );

        register_taxonomy(
            BaseImageService::MYCLUB_IMAGES,
            'attachment',
            [
                'labels'                => [
                    'name'          => __( 'Image Types', 'myclub-sections' ),
                    'singular_name' => __( 'Image Type', 'myclub-sections' ),
                    'search_items'  => __( 'Search Image Types', 'myclub-sections' ),
                    'all_items'     => __( 'All Image Types', 'myclub-sections' ),
                    'edit_item'     => __( 'Edit Image Type', 'myclub-sections' ),
                    'update_item'   => __( 'Update Image Type', 'myclub-sections' ),
                    'add_new_item'  => __( 'Add New Image Type', 'myclub-sections' ),
                    'new_item_name' => __( 'New Image Type Name', 'myclub-sections' ),
                    'menu_name'     => __( 'Image Types', 'myclub-sections' ),
                    'back_to_items' => __( 'Back to Image Types', 'myclub-sections' ),
                    'not_found'     => __( 'No image types found.', 'myclub-sections' ),
                ],
                'public'                => false,
                'show_ui'               => true,
                'show_admin_column'     => true,
                'hierarchical'          => false,
                'show_in_rest'          => true,
                'rewrite'               => false,
                'update_count_callback' => function ( $terms, $taxonomy ) {
                    global $wpdb;

                    $term_taxonomy_ids = array_map( 'intval', (array)$terms );
                    if ( empty( $term_taxonomy_ids ) ) {
                        return;
                    }

                    // Build counts per term_taxonomy_id for attachments in allowed statuses
                    $in = implode( ',', $term_taxonomy_ids );
                    $sql = "
                        SELECT tr.term_taxonomy_id AS ttid, COUNT(*) AS cnt
                        FROM {$wpdb->term_relationships} tr
                        INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                        INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
                        WHERE tt.taxonomy = %s
                          AND tr.term_taxonomy_id IN ($in)
                          AND p.post_type = 'attachment'
                          AND p.post_status IN ('inherit','private','publish')
                        GROUP BY tr.term_taxonomy_id
                    ";

                    $rows = $wpdb->get_results( $wpdb->prepare( $sql, $taxonomy ), ARRAY_A );
                    $by_ttid = [];
                    foreach ( (array)$rows as $row ) {
                        $by_ttid[ (int)$row[ 'ttid' ] ] = (int)$row[ 'cnt' ];
                    }

                    foreach ( $term_taxonomy_ids as $ttid ) {
                        $count = isset( $by_ttid[ $ttid ] ) ? $by_ttid[ $ttid ] : 0;
                        $wpdb->update(
                            $wpdb->term_taxonomy,
                            [ 'count' => $count ],
                            [ 'term_taxonomy_id' => $ttid ],
                            [ '%d' ],
                            [ '%d' ]
                        );
                    }

                    // Clear term caches so the admin UI reflects new counts
                    $term_ids = $wpdb->get_col( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id IN ($in)" );
                    if ( $term_ids ) {
                        clean_term_cache( array_map( 'intval', $term_ids ), $taxonomy, true );
                    }
                },
            ]
        );

        $name_map = [
            'news' => __( 'News images', 'myclub-sections' ),
        ];

        foreach ( array_keys( $name_map ) as $term ) {
            if ( !term_exists( $term, BaseImageService::MYCLUB_IMAGES ) ) {
                wp_insert_term(
                    $name_map[ $term ] ?? ucfirst( $term ),
                    BaseImageService::MYCLUB_IMAGES,
                    [ 'slug' => $term ]
                );
            } else {
                $term_obj = get_term_by( 'slug', $term, BaseImageService::MYCLUB_IMAGES );
                if ( $term_obj && !is_wp_error( $term_obj ) ) {
                    $desired_name = $name_map[ $term ];
                    if ( $term_obj->name !== $desired_name ) {
                        wp_update_term( $term_obj->term_id, BaseImageService::MYCLUB_IMAGES, [ 'name' => $desired_name ] );
                    }
                }
            }
        }

        flush_rewrite_rules();
    }

    /**
     * Adds the body class for MyClub Sections posts.
     *
     * @param array $classes The array of body classes.
     * @return array The updated array of body classes.
     * @since 1.0.0
     */
    public function addBodyClass( array $classes ): array
    {
        global $post;

        if ( isset( $post ) && SectionService::MYCLUB_SECTIONS == $post->post_type ) {
            $classes[] = $post->post_name;
        }

        return $classes;
    }

    /**
     * Render the meta box for MyClub sections.
     *
     * This function includes and renders the template file for the admin metabox tabs of MyClub sections.
     *
     * @return void
     * @since 1.0.0
     */
    public function renderMetaBox()
    {
        return require_once( "$this->plugin_path/templates/admin/admin-myclub-sections-metabox-tabs.php" );
    }

    /**
     * Register the custom meta box for the section pages custom posts.
     *
     * @return void
     * @since 1.0.0
     */
    public function registerMetaBox()
    {
        add_meta_box( 'myclub-sections-meta', __( 'MyClub section information', 'myclub-sections' ), [
            $this,
            'renderMetaBox'
        ], 'myclub-sections', 'normal', 'high' );
    }

    /**
     * Displays the single section template file.
     *
     * @param mixed $single The current single template file.
     * @return string The single section template file path or the current single template file if the condition is not met.
     */
    public function showSingleSection( $single ): string
    {
        if ( !wp_is_block_theme() ) {
            $templateName = 'single-myclub-section.php';

            if ( is_singular( SectionService::MYCLUB_SECTIONS ) ) {
                if ( $template = locate_template( $templateName ) ) {
                    return $template;
                } else {
                    return $this->plugin_path . 'templates/' . $templateName;
                }
            }
        }

        return $single;
    }
}