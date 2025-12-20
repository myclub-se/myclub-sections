<?php

if ( !defined( 'ABSPATH' ) ) exit;

use MyClub\MyClubSections\Services\CalendarService;

$header = get_option( 'myclub_sections_club_calendar_title' );

?>
<div class="myclub-sections-club-calendar">
    <div class="myclub-sections-club-calendar-container">
        <h3 class="myclub-sections-header"><?php echo esc_attr( $header ) ?></h3>
        <?php

        $activities = CalendarService::ListActivities();

        $labels = [
                'calendar'       => __( 'Calendar', 'myclub-sections' ),
                'description'    => __( 'Information', 'myclub-sections' ),
                'name'           => __( 'Name', 'myclub-sections' ),
                'when'           => __( 'When', 'myclub-sections' ),
                'location'       => __( 'Location', 'myclub-sections' ),
                'meetUpLocation' => __( 'Gathering location', 'myclub-sections' ),
                'meetUpTime'     => __( 'Gathering time', 'myclub-sections' ),
                'today'          => __( 'today', 'myclub-sections' ),
                'day'            => __( 'day', 'myclub-sections' ),
                'month'          => __( 'month', 'myclub-sections' ),
                'week'           => __( 'week', 'myclub-sections' ),
                'list'           => __( 'list', 'myclub-sections' ),
                'weekText'       => __( 'W', 'myclub-sections' ),
                'weekTextLong'   => __( 'Week', 'myclub-sections' ),
        ];
        foreach ( $activities as $activity ) {
            $activity->title = str_replace( '&quot;', 'u0022', $activity->title );
            $activity->description = str_replace( '&quot;', 'u0022', $activity->description );
        }
        ?>
        <div id="club-calendar-div"
             data-events="<?php echo esc_attr( wp_json_encode( $activities, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT ) ); ?>"
             data-labels="<?php echo esc_attr( wp_json_encode( $labels, JSON_UNESCAPED_UNICODE ) ); ?>"
             data-locale="<?php echo esc_attr( get_locale() ); ?>"
             data-first-day-of-week="<?php echo esc_attr( get_option( 'start_of_week', 1 ) ); ?>"></div>
    </div>
    <div class="club-calendar-modal" id="club-calendar-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>