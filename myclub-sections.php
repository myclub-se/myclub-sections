<?php

/*
Plugin Name: MyClub Sections
Plugin URI: https://github.com/myclub-se/myclub-sections
Description: Retrieves section information from the MyClub member administration platform. Generates pages for sections defined in the MyClub platform.
Version: 1.3.2
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Author: MyClub AB
Author URI: https://www.myclub.se
Text Domain: myclub-sections
Domain Path: /languages
License: GPLv2 or later
*/

defined( 'ABSPATH' ) or die( 'Access denied' );

use MyClub\MyClubSections\Migration;
use MyClub\MyClubSections\Activation;
use MyClub\MyClubSections\Services;
use MyClub\MyClubSections\Tasks\ImageTask;
use MyClub\MyClubSections\Tasks\RefreshNewsTask;
use MyClub\MyClubSections\Tasks\RefreshSectionsTask;

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	exit( "This plugin requires PHP 7.4 or higher. You're still on PHP " . PHP_VERSION );
}

if ( file_exists( plugin_dir_path( __FILE__ ) . '/lib/autoload.php' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/lib/autoload.php' );
}

define( 'MYCLUB_SECTIONS_PLUGIN_VERSION', '1.3.2' );

ImageTask::init();
RefreshNewsTask::init();
RefreshSectionsTask::init();
Services\ActivityService::init();

if ( file_exists( plugin_dir_path( __FILE__ ) . '/src/Activation.php' ) ) {
	function myclub_sections_activate() {
		$activation = new Activation();
		$activation->activate();
	}

	register_activation_hook( __FILE__, 'myclub_sections_activate' );

	function myclub_sections_deactivate() {
		$activation = new Activation();
		$activation->deactivate();
	}

	register_deactivation_hook( __FILE__, 'myclub_sections_deactivate' );
}

if ( file_exists( plugin_dir_path( __FILE__ ) . '/src/Migration.php' ) ) {
    function myclub_sections_migration() {
        Migration::checkMigrations();
    }

    add_action( 'plugins_loaded', 'myclub_sections_migration' );
}

if ( file_exists( plugin_dir_path(__FILE__) . '/src/Services.php' ) ) {
    Services::registerServices();
}