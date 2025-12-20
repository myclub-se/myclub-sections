<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class i18n extends Base
{
    /**
     * Registers the plugin's functionality.
     * This method is responsible for registering the plugin's functionality by adding an action hook to the 'plugins_loaded' event.
     *
     * @return void
     * @since 1.0.0
     */
    public function register()
    {
        add_action( 'plugins_loaded', [
            $this,
            'loadPluginTextDomain'
        ] );
    }

    /**
     * Loads the text domain for the plugin.
     *
     * @return void
     * @since 1.0.0
     */
    public function loadPluginTextDomain()
    {
        $result = load_plugin_textdomain(
            'myclub-sections',
            false,
            plugin_basename( dirname( __FILE__, 3 ) ) . '/languages/'
        );

        if ( !$result ) {
            error_log( 'Failed to load text domain for myclub-sections.' );
        }
    }
}
