<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Registers the shortcode for the plugin.
 *
 * This method hooks into the 'init' action and registers the shortcode for the plugin.
 * It calls the 'registerShortcodes' method to handle the shortcode registration.
 *
 * @since 1.0.0
 */
class ShortCodes extends Base
{
    /**
     * Registers the shortcode for the plugin.
     *
     * This method hooks into the 'init' action and registers the shortcode for the plugin.
     * It calls the 'registerShortcodes' method to handle the shortcode registration.
     *
     * @since 1.0.0
     */
    public function register()
    {
        add_action( 'init', [
            $this,
            'registerShortCodes'
        ] );
    }

    /**
     * Registers the shortcodes for MyClub sections.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function registerShortCodes()
    {
        $available_blocks = Blocks::BLOCKS;

        add_shortcode( 'myclub-sections-calendar', [
            $this,
            'renderMyclubSectionsCalendar'
        ] );

        add_shortcode( 'myclub-sections-club-calendar', [
            $this,
            'renderMyclubSectionsClubCalendar'
        ] );

        add_shortcode( 'myclub-sections-club-news', [
            $this,
            'renderMyclubSectionsClubNews'
        ] );

        add_shortcode( 'myclub-sections-coming-games', [
            $this,
            'renderMyclubSectionsComingGames'
        ] );

        add_shortcode( 'myclub-sections-description', [
            $this,
            'renderMyclubSectionsDescription'
        ] );

        add_shortcode( 'myclub-sections-news', [
            $this,
            'renderMyclubSectionsNews'
        ] );

        foreach ( $available_blocks as $block ) {
            if ( file_exists( $this->plugin_path . 'blocks/build/' . $block . '/view.js' ) ) {
                wp_register_script( 'myclub-sections-' . $block . '-js', $this->plugin_url . 'blocks/build/' . $block . '/view.js', [], MYCLUB_SECTIONS_PLUGIN_VERSION, true );
            }
            if ( file_exists( $this->plugin_path . 'blocks/build/' . $block . '/style-index.css' ) ) {
                wp_register_style( 'myclub-sections-' . $block . '-css', $this->plugin_url . 'blocks/build/' . $block . '/style-index.css', [], MYCLUB_SECTIONS_PLUGIN_VERSION );
            }
        }
    }

    /**
     * Renders the MyClub sections calendar.
     *
     * @param array $attrs Optional. An array of attributes for the block. Default is an empty array.
     * @param string|null $content Optional. The block content. Default is null.
     *
     * @return string The rendered calendar HTML.
     *
     * @since 1.0.0
     */
    public function renderMyclubSectionsCalendar( array $attrs = [], string $content = null ): string
    {
        $service = new Blocks();

        wp_enqueue_script( 'myclub-sections-calendar-js' );
        wp_enqueue_style( 'myclub-sections-calendar-css' );

        return $service->renderCalendar( $this->getShortcodeAttrs( $attrs, 'myclub-sections-calendar' ), $content );
    }

    /**
     * Renders the MyClub sections calendar.
     *
     * @param array $attrs Optional. An array of attributes for the block. Default is an empty array.
     * @param string|null $content Optional. The block content. Default is null.
     *
     * @return string The rendered calendar HTML.
     *
     * @since 1.3.0
     */
    public function renderMyclubSectionsClubCalendar( array $attrs = [], string $content = null ): string
    {
        $service = new Blocks();

        wp_enqueue_script( 'myclub-sections-club-calendar-js' );
        wp_enqueue_style( 'myclub-sections-club-calendar-css' );

        return $service->renderClubCalendar( $this->getShortcodeAttrs( $attrs, 'myclub-sections-club-calendar' ), $content );
    }

    /**
     * Renders the MyClub Sections Club News block.
     *
     * @param mixed $attrs Optional. An array of attributes for the block. Default is an empty array.
     * @param string|null $content Optional. The block content. Default is null.
     *
     * @return string The rendered HTML output of the block.
     *
     * @since 1.0.0
     */
    public function renderMyclubSectionsClubNews( $attrs = [], string $content = null ): string
    {
        return $this->renderShortcode( 'myclub-sections-club-news', 'club-news', __( 'The MyClub club news block couldn\'t be found', 'myclub-sections' ), $attrs, $content );
    }

    /**
     * Renders the MyClub Sections Coming games block.
     *
     * @param mixed $attrs Optional. An array of attributes for the block. Default is an empty array.
     * @param string|null $content Optional. The block content. Default is null.
     *
     * @return string The rendered HTML output of the block.
     *
     * @since 1.0.0
     */
    public function renderMyclubSectionsComingGames( $attrs = [], string $content = null ): string
    {
        return $this->renderShortcode( 'myclub-sections-coming-games', 'coming-games', __( 'The MyClub coming games block couldn\'t be found', 'myclub-sections' ), $attrs, $content );
    }

    /**
     * Renders the MyClub Sections Club Description block.
     *
     * @param mixed $attrs Optional. An array of attributes for the block. Default is an empty array.
     * @param string|null $content Optional. The block content. Default is null.
     *
     * @return string The rendered HTML output of the block.
     *
     * @since 1.0.0
     */
    public function renderMyclubSectionsDescription( $attrs = [], string $content = null ): string
    {
        return $this->renderShortcode( 'myclub-sections-description', 'description', __( 'The MyClub description block couldn\'t be found', 'myclub-sections' ), $attrs, $content );
    }

    /**
     * Renders the MyClub Sections News block.
     *
     * @param array $attrs Optional. An array of attributes for the block. Default is an empty array.
     * @param string|null $content Optional. The block content. Default is null.
     *
     * @return string The rendered HTML output of the block.
     *
     * @since 1.0.0
     */
    public function renderMyclubSectionsNews( array $attrs = [], string $content = null ): string
    {
        return $this->renderShortcode( 'myclub-sections-news', 'news', __( 'The MyClub news block couldn\'t be found', 'myclub-sections' ), $attrs, $content );
    }

    /**
     * Retrieves the shortcode attributes for a given shortcode.
     *
     * @param array $attrs An array of attributes for the shortcode.
     * @param string $shortCode The shortcode to retrieve attributes for.
     *
     * @return array The array of shortcode attributes.
     *
     * @since 1.0.0
     */
    private function getShortcodeAttrs( array $attrs, string $shortCode ): array
    {
        return shortcode_atts( [
            'section_id' => '',
            'post_id'    => '',
        ], $attrs, $shortCode );
    }

    /**
     * Renders a shortcode block.
     *
     * @param string $short_code The shortcode identifier.
     * @param string $block_path The path of the block to render.
     * @param string $error_string The error message to display if the block file is not found.
     * @param mixed $attrs Optional. An array of attributes for the block. Default is an empty array.
     * @param string|null $content Optional. The block content. Default is null.
     *
     * @return string The rendered HTML output of the block or the error message.
     * @since 1.0.0
     */
    private function renderShortcode( string $short_code, string $block_path, string $error_string, $attrs = [], string $content = null ): string
    {
        $attributes = $this->getShortcodeAttrs( is_array( $attrs ) ? $attrs : [], $short_code );
        $file_path = $this->plugin_path . 'blocks/build/' . $block_path . '/render.php';

        wp_enqueue_script( 'myclub-sections-' . $block_path . '-js' );
        wp_enqueue_style( 'myclub-sections-' . $block_path . '-css' );

        if ( file_exists( $file_path ) ) {
            ob_start();
            require( $file_path );
            return ob_get_clean();
        } else {
            return $error_string;
        }
    }
}