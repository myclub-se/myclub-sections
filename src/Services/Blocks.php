<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Blocks
 *
 * This class extends the Base class and is used to register custom MyClub blocks and add a custom category to the block's chooser.
 */
class Blocks extends Base
{
    const BLOCKS = [
        'calendar',
        'club-calendar',
        'club-news',
        'coming-games',
        'description',
        'news'
    ];

    private array $block_args = [];
    private array $handles = [];

    /**
     * Enqueues scripts and sets script translations for registered blocks.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function enqueueScripts()
    {
        foreach ( $this->handles as $handle ) {
            wp_set_script_translations( $handle, 'myclub-sections', $this->plugin_path . 'languages' );
        }
    }

    /**
     * Renders the calendar block.
     *
     * @param array $attributes The attributes for the calendar block.
     * @param string $content The content within the calendar block.
     *
     * @return string The rendered HTML content of the calendar block.
     * @since 1.0.0
     */
    public function renderCalendar( array $attributes, string $content = '' ): string
    {
        wp_enqueue_script( 'fullcalendar-js' );
        wp_enqueue_style( 'dashicons' );

        ob_start();
        require( $this->plugin_path . 'blocks/build/calendar/render.php' );
        return ob_get_clean();
    }

    /**
     * Renders the club calendar block.
     *
     * @param array $attributes The attributes for the club calendar block.
     * @param string $content The content within the club calendar block.
     *
     * @return string The rendered HTML content of the club calendar block.
     * @since 1.3.0
     */
    public function renderClubCalendar( array $attributes, string $content = '' ): string
    {
        wp_enqueue_script( 'fullcalendar-js' );
        wp_enqueue_style( 'dashicons' );

        ob_start();
        require( $this->plugin_path . 'blocks/build/club-calendar/render.php' );
        return ob_get_clean();
    }

    /**
     * Registers custom MyClub blocks and adds a custom category to the block's chooser.
     *
     * This method is responsible for registering custom MyClub blocks and adding a custom category
     * to the blocks chooser in WordPress. It hooks into the 'init' action to register the blocks and
     * adds a filter to the 'block_categories_all' hook to add the custom category.
     *
     * @return void
     * @since 1.0.0
     *
     */
    public function register()
    {
        // Register custom MyClub blocks
        add_action( 'init', [
            $this,
            'registerBlocks'
        ] );
        // Enqueue js scripts for translations
        add_action( 'admin_enqueue_scripts', [
            $this,
            'enqueueScripts'
        ] );

        // Add custom category to blocks chooser
        add_filter( 'block_categories_all', [
            $this,
            'registerMyclubCategory'
        ] );
    }


    /**
     * Registers all blocks for the plugin.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function registerBlocks()
    {
        $this->block_args = [
            'calendar'      => [
                'description'     => __( 'Display calendar for a selected section', 'myclub-sections' ),
                'render_callback' => [
                    $this,
                    'renderCalendar'
                ],
                'title'           => __( 'MyClub Section Calendar', 'myclub-sections' )
            ],
            'club-calendar' => [
                'description'     => __( 'Display calendar for the entire Club', 'myclub-sections' ),
                'render_callback' => [
                    $this,
                    'renderClubCalendar'
                ],
                'title'           => __( 'MyClub Club Calendar', 'myclub-sections' )
            ],
            'club-news'     => [
                'description' => __( 'Display news for the entire Club', 'myclub-sections' ),
                'title'       => __( 'MyClub Club News', 'myclub-sections' )
            ],
            'coming-games'  => [
                'description' => __( 'Display coming games for the selected section', 'myclub-sections' ),
                'title'       => __( 'MyClub Section coming games', 'myclub-sections' )
            ],
            'description'   => [
                'description' => __( 'Display description for a selected section', 'myclub-sections' ),
                'title'       => __( 'MyClub Section Description', 'myclub-sections' )
            ],
            'news'          => [
                'description' => __( 'Display news for a selected section', 'myclub-sections' ),
                'title'       => __( 'MyClub Section News', 'myclub-sections' )
            ]
        ];

        foreach ( Blocks::BLOCKS as $block ) {
            $this->registerBlock( $block );
        }

        wp_register_script( 'fullcalendar-js', $this->plugin_url . 'resources/javascript/fullcalendar.6.1.20.min.js', [], '6.1.20', true );
    }

    /**
     * Registers a custom block category for the plugin.
     *
     * @param array $categories The existing block categories list.
     * @return array The updated block categories list.
     * @since 1.0.0
     */
    public function registerMyclubCategory( array $categories ): array
    {
        $categories[] = array (
            'slug'  => 'myclub',
            'title' => 'MyClub'
        );

        return $categories;
    }

    /**
     * Registers a single block for the plugin.
     *
     * @param string $block The name of the block to register.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function registerBlock( string $block )
    {
        $block_type = register_block_type( $this->plugin_path . 'blocks/build/' . $block, $this->block_args[ $block ] );

        if ( !$block_type ) {
            error_log( "Unable to register block $block" );
        } else {
            array_push( $this->handles, ...$block_type->view_script_handles, ...$block_type->editor_script_handles );
        }
    }
}