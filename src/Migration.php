<?php

namespace MyClub\MyClubSections;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles database migrations for the MyClub Sections plugin.
 */
class Migration
{
    const VERSION_OPTION = 'myclub_sections_version';
    const CURRENT_VERSION = MYCLUB_SECTIONS_PLUGIN_VERSION;

    /**
     * Checks if any migrations need to be executed by comparing the installed version
     * with the current version. If the installed version is less than the current version,
     * it triggers the migration process.
     *
     * @return void
     * @since 1.0.0
     */
    public static function checkMigrations()
    {
        $installed_version = get_option( self::VERSION_OPTION, '1.3.5' );

        // Normalize unexpected values (e.g., NULL stored in the DB)
        if ( !is_string( $installed_version ) || $installed_version === '' ) {
            $installed_version = '1.0.0';
        }

        // Also ensure CURRENT_VERSION is a non-empty string before comparing
        $current_version = ( is_string( self::CURRENT_VERSION ) && self::CURRENT_VERSION !== '' )
            ? self::CURRENT_VERSION
            : '1.0.0';

        if ( version_compare( $installed_version, $current_version, '<' ) ) {
            self::migrate( $installed_version );
        }
    }

    /**
     * Migrates the system to ensure it is compatible with the current version.
     *
     * @param string $installed_version The currently installed version of the system.
     * @return void
     * @since 1.0.0
     */
    public static function migrate( string $installed_version )
    {
        update_option( self::VERSION_OPTION, self::CURRENT_VERSION );
    }
}