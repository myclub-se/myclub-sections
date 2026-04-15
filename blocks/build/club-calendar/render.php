<?php

if ( !defined( 'ABSPATH' ) ) exit;

use MyClub\MyClubSections\Services\CalendarService;
use MyClub\MyClubSections\Utils;

$myclub_sections_club_calendar_header = get_option( 'myclub_sections_club_calendar_title' );
$myclub_sections_calendar_desktop_views = get_option( 'myclub_sections_club_calendar_desktop_views', Utils::getCalendarDesktopViews() );
$myclub_sections_calendar_desktop_views_default = get_option( 'myclub_sections_club_calendar_desktop_views_default', Utils::getCalendarDesktopViewsDefault() );
$myclub_sections_calendar_mobile_views = get_option( 'myclub_sections_club_calendar_mobile_views', Utils::getCalendarMobileViews() );
$myclub_sections_calendar_mobile_views_default = get_option( 'myclub_sections_club_calendar_mobile_views_default', Utils::getCalendarMobileViewsDefault() );
$myclub_sections_calendar_show_week_numbers = get_option( 'myclub_sections_club_calendar_show_week_numbers', '1' );
$myclub_sections_no_activities_message = get_option( 'myclub_sections_no_activities_message', esc_attr__( 'No activities to display', 'myclub-sections' ) );

?>
<div class="myclub-sections-club-calendar">
    <div class="myclub-sections-club-calendar-container">
        <h3 class="myclub-sections-header"><?php echo esc_attr( $myclub_sections_club_calendar_header ) ?></h3>
        <?php

        $myclub_sections_club_calendar_activities = CalendarService::ListActivities();

        $myclub_sections_club_calendar_labels = [
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
        foreach ( $myclub_sections_club_calendar_activities as $myclub_sections_club_calendar_activity ) {
            $myclub_sections_club_calendar_activity->title = str_replace( '&quot;', 'u0022', $myclub_sections_club_calendar_activity->title );
        }
        ?>
        <div id="club-calendar-div"
             data-events="<?php echo esc_attr( wp_json_encode( $myclub_sections_club_calendar_activities, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT ) ); ?>"
             data-labels="<?php echo esc_attr( wp_json_encode( $myclub_sections_club_calendar_labels, JSON_UNESCAPED_UNICODE ) ); ?>"
             data-locale="<?php echo esc_attr( get_locale() ); ?>"
             data-calendar-desktop="<?php echo esc_attr( join( ',', $myclub_sections_calendar_desktop_views ) ); ?>"
             data-calendar-desktop-default="<?php echo esc_attr( $myclub_sections_calendar_desktop_views_default ); ?>"
             data-calendar-mobile="<?php echo esc_attr( join( ',', $myclub_sections_calendar_mobile_views ) ); ?>"
             data-calendar-mobile-default="<?php echo esc_attr( $myclub_sections_calendar_mobile_views_default ); ?>"
             data-calendar-week-numbers="<?php echo esc_attr( $myclub_sections_calendar_show_week_numbers ); ?>"
             data-first-day-of-week="<?php echo esc_attr( get_option( 'start_of_week', 1 ) ); ?>"
             data-no-events-content="<?php echo esc_attr( $myclub_sections_no_activities_message ); ?>"
        ></div>
    </div>
    <div class="club-calendar-modal" id="club-calendar-modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>