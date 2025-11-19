<?php

/*
Plugin Name: Myclub Sections
Plugin URI: https://github.com/myclub-se/myclub-sections
Description: Retrieves section information from the MyClub member administration platform. Generates pages for sections defined in the MyClub platform.
Version: 1.0
Requires at least: 6.4
Tested up to: 6.8.1
Requires PHP: 7.4
Author: MyClub AB
Author URI: https://www.myclub.se
Text Domain: myclub-sections
Domain Path: /languages
License: GPLv2 or later
*/

use MyClub\MyClubSections\Activation;

defined( 'ABSPATH' ) or die( 'Access denied' );

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	exit( "This plugin requires PHP 7.4 or higher. You're still on PHP " . PHP_VERSION );
}

if ( file_exists( plugin_dir_path( __FILE__ ) . '/lib/autoload.php' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/lib/autoload.php' );
}

define( 'MYCLUB_SECTIONS_PLUGIN_VERSION', '1.0' );


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

	function myclub_sections_uninstall() {
		$activation = new Activation();
		$activation->uninstall();
	}

	register_uninstall_hook( __FILE, 'myclub_sections_uninstall' );
}