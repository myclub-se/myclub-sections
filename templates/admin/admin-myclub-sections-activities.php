<?php

use MyClub\MyClubSections\Services\ActivityService;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$myclub_sections_admin_activities = ActivityService::listPostActivities( get_the_ID() );

?>

<div class="activity-box">
    <table class="activities-table">
        <tr>
            <th><?php esc_attr_e( 'Name', 'myclub-sections' ); ?></th>
            <th><?php esc_attr_e( 'Day', 'myclub-sections' ); ?></th>
            <th><?php esc_attr_e( 'Start time', 'myclub-sections' ); ?></th>
            <th><?php esc_attr_e( 'End time', 'myclub-sections' ); ?></th>
            <th><?php esc_attr_e( 'Location', 'myclub-sections' ); ?></th>
        </tr>
        <?php
        if ( !empty( $myclub_sections_admin_activities ) ) {
            foreach ( $myclub_sections_admin_activities as $myclub_sections_admin_activity ) { ?>
                <tr>
                    <td><?php echo esc_attr( str_replace( 'u0022', '"', $myclub_sections_admin_activity->title ) . ' (' . $myclub_sections_admin_activity->type . ')' ); ?></td>
                    <td><?php echo esc_attr( $myclub_sections_admin_activity->day ); ?></td>
                    <td><?php echo esc_attr( substr( $myclub_sections_admin_activity->start_time, 0, 5 ) ); ?></td>
                    <td><?php echo esc_attr( substr( $myclub_sections_admin_activity->end_time, 0, 5 ) ); ?></td>
                    <td><?php echo esc_attr( $myclub_sections_admin_activity->location ); ?></td>
                </tr>
            <?php }
        }
        ?>
    </table>
</div>