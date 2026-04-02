<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define the version constant which is normally defined in the main plugin file
if ( ! defined( 'MYCLUB_SECTIONS_PLUGIN_VERSION' ) ) {
    define( 'MYCLUB_SECTIONS_PLUGIN_VERSION', '1.2.1' );
}

// Load the autoloader so we can use our Service classes
if ( file_exists( __DIR__ . '/lib/autoload.php' ) ) {
	require_once __DIR__ . '/lib/autoload.php';
}

use MyClub\MyClubSections\Activation;

/**
 * Perform the cleanup.
 * We instantiate the Activation class and call its uninstall method, 
 * which handles options and data removal.
 */
$myclub_sections_activation = new Activation();
$myclub_sections_activation->uninstall();
