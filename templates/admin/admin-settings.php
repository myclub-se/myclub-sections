<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Utils;

const MYCLUB_SECTIONS_VALID_ACTIONS_TABS = [
        'tab1',
        'tab2'
];

const MYCLUB_SECTIONS_VALID_TABS = [
        'tab1',
        'tab2',
        'tab3',
        'tab4'
];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- UI-only tab state, no data modification.
$myclub_sections_active_tab = sanitize_text_field( wp_unslash( $_GET[ 'tab' ] ?? 'tab1' ) );

if ( !in_array( $myclub_sections_active_tab, MYCLUB_SECTIONS_VALID_TABS ) ) {
    $myclub_sections_active_tab = 'tab1';
}

function myclub_sections_allow_code_html( $translated_string )
{
    echo wp_kses( $translated_string, array ( 'code' => array () ) );
}

/**
 * Renders a label containing the last synchronization time or status message for a specific field.
 *
 * This method determines the last synchronization time for the specified field, or provides a
 * status message if a related cron job is running. It then outputs the result in a div element.
 *
 * @param string $field_name The name of the field to retrieve the last synchronization data for.
 * @return void This method does not return a value. It directly outputs the content to the browser.
 */
function renderDateTimeLabel( string $field_name ): void
{
    $last_sync = esc_attr( get_option( $field_name ) );
    $cron_job_name = '';
    $output = '';

    if ( $field_name === 'myclub_sections_last_news_sync' ) {
        $cron_job_name = 'myclub_sections_refresh_news_task_cron';
        $cron_job_type = __( 'news', 'myclub-sections' );
    }

    if ( $field_name === 'myclub_sections_last_sections_sync' ) {
        $cron_job_name = 'myclub_sections_refresh_sections_task_cron';
        $cron_job_type = __( 'sections', 'myclub-sections' );
    }

    if ( !empty( $cron_job_name ) && isset( $cron_job_type ) ) {
        $next_scheduled = wp_next_scheduled( $cron_job_name );
        if ( $next_scheduled ) {
            /* translators: 1: the type of update cron job that is running */
            $output = sprintf( __( 'The %1$s update task is currently running.', 'myclub-sections' ), esc_attr( $cron_job_type ) );
        }
    }

    if ( empty ( $output ) ) {
        $output = empty( $last_sync ) ? __( 'Not synchronized yet', 'myclub-sections' ) : Utils::formatDateTime( $last_sync );
    }

    echo '<div id="' . esc_attr( $field_name ) . '">' . esc_html( $output ) . '</div>';
}

?>

<div class="wrap">
    <h1><?php esc_html_e( 'MyClub Sections settings', 'myclub-sections' ) ?></h1>
    <div class="nav-tab-wrapper">
        <a href="?page=myclub-sections-settings&tab=tab1"
           class="nav-tab<?php echo $myclub_sections_active_tab == 'tab1' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'General settings', 'myclub-sections' ) ?></a>
        <a href="?page=myclub-sections-settings&tab=tab2"
           class="nav-tab<?php echo $myclub_sections_active_tab == 'tab2' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Display settings', 'myclub-sections' ) ?></a>
        <a href="?page=myclub-sections-settings&tab=tab3"
           class="nav-tab<?php echo $myclub_sections_active_tab == 'tab3' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Gutenberg blocks', 'myclub-sections' ) ?></a>
        <a href="?page=myclub-sections-settings&tab=tab4"
           class="nav-tab<?php echo $myclub_sections_active_tab == 'tab4' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Shortcodes', 'myclub-sections' ) ?></a>
    </div>

    <form method="post" action="options.php" id="myclub-sections-settings-form">
        <?php
        if ( $myclub_sections_active_tab === 'tab1' ) {
            settings_fields( 'myclub_sections_settings_tab1' );
            do_settings_sections( 'myclub_sections_settings_tab1' );
        } else if ( $myclub_sections_active_tab === 'tab2' ) {
            settings_fields( 'myclub_sections_settings_tab2' );
            do_settings_sections( 'myclub_sections_settings_tab2' );
        } else if ( $myclub_sections_active_tab === 'tab3' ) {
            ?> <h2><?php esc_attr_e( 'Gutenberg blocks', 'myclub-sections' ) ?></h2>
            <div><?php esc_attr_e( 'Here are the Gutenberg blocks available from the MyClub sections plugin', 'myclub-sections' ) ?></div>
            <div><?php esc_attr_e( 'The section Gutenberg blocks require a post_id or a section_id parameter (the club blocks do not). The post_id parameter is the ID of the MyClub sections page that the plugin creates for the section. The section_id parameter is found on the MyClub sections page under the MyClub section information tab - the property `MyClub section id`', 'myclub-sections' ) ?></div>
            <ul>
                <li><strong><?php esc_attr_e( 'Calendar', 'myclub-sections' ) ?></strong>
                    - <?php myclub_sections_allow_code_html( __( 'The calendar block will display a section calendar. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the calendar from or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
                <li><strong><?php esc_attr_e( 'Club calendar', 'myclub-sections' ) ?></strong>
                    - <?php esc_html_e( "The club calendar block will display the club calendar. This block doesn't require any attributes.", 'myclub-sections' ) ?>
                </li>
                <li><strong><?php esc_attr_e( 'Club news', 'myclub-sections' ) ?></strong>
                    - <?php esc_html_e( "The club news block will display all club news. This block doesn't require any attributes.", 'myclub-sections' ) ?>
                </li>
                <li><strong><?php esc_attr_e( 'Upcoming games', 'myclub-sections' ) ?></strong>
                    - <?php myclub_sections_allow_code_html( "The coming-games block will display the upcoming games for a section. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the activities from or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.", 'myclub-sections' ) ?>
                </li>
                <li><strong><?php esc_attr_e( 'Description', 'myclub-sections' ) ?></strong>
                    - <?php myclub_sections_allow_code_html( __( 'The description block will display the section page description. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the description from or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
                <li><strong><?php esc_attr_e( 'News', 'myclub-sections' ) ?></strong>
                    - <?php myclub_sections_allow_code_html( __( 'The news block will display the section page news. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the news for or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
            </ul>
            <?php
        } else { ?>
            <h2><?php esc_attr_e( 'Shortcodes', 'myclub-sections' ) ?></h2>
            <div><?php esc_attr_e( 'Here are the shortcodes available from the MyClub sections plugin', 'myclub-sections' ) ?></div>
            <div><?php esc_attr_e( 'The section shortcodes require a post_id or a section_id parameter (the club shortcodes do not). The post_id parameter is the ID of the MyClub sections page that the plugin creates for the section. The section_id parameter is found on the MyClub sections page under the MyClub section information tab - the property `MyClub section id`', 'myclub-sections' ) ?></div>
            <ul>
                <li><code>[myclub-sections-calendar]</code>
                    - <?php myclub_sections_allow_code_html( __( 'The calendar shortcode will display a section calendar. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the calendar from or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
                <li><code>[myclub-sections-club-calendar]</code>
                    - <?php esc_html_e( "The club calendar shortcode will display the club calendar. This block doesn't require any attributes.", 'myclub-sections' ) ?>
                </li>
                <li><code>[myclub-sections-club-news]</code>
                    - <?php esc_html_e( "The club news shortcode will display all club news. This block doesn't require any attributes.", 'myclub-sections' ) ?>
                </li>
                <li><code>[myclub-sections-coming-games]</code>
                    - <?php myclub_sections_allow_code_html( __( 'The coming-games shortcode will display the upcoming games for a section. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the activities from or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
                <li><code>[myclub-sections-description]</code>
                    - <?php myclub_sections_allow_code_html( __( 'The description shortcode will display the section page description. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the description from or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
                <li><code>[myclub-sections-news]</code>
                    - <?php myclub_sections_allow_code_html( __( 'The news shortcode will display the section page news. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the news for or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
            </ul>
        <?php } ?>
        <?php if ( in_array( $myclub_sections_active_tab, MYCLUB_SECTIONS_VALID_ACTIONS_TABS ) ) { ?>
            <?php if ( $myclub_sections_active_tab === 'tab1' ) { ?>
                <h2><?php esc_html_e( 'Synchronization information', 'myclub-sections' ) ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'News last synchronized', 'myclub-sections' ) ?></th>
                            <td>
                                <?php renderDateTimeLabel( 'myclub_sections_last_news_sync' ) ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Sections last synchronized', 'myclub-sections' ) ?></th>
                            <td>
                                <?php renderDateTimeLabel( 'myclub_sections_last_sections_sync' ) ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Club calendar last synchronized', 'myclub-sections' ) ?></th>
                            <td>
                                <?php renderDateTimeLabel( 'myclub_sections_last_club_calendar_sync' ) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div>
                    <button type="button" id="myclub-reload-news-button" class="button">
                        <?php esc_attr_e( 'Reload news', 'myclub-sections' ) ?>
                    </button>
                    <button type="button" id="myclub-reload-sections-button" class="button">
                        <?php esc_attr_e( 'Reload sections', 'myclub-sections' ) ?>
                    </button>
                    <button type="button" id="myclub-sync-club-calendar-button" class="button">
                        <?php esc_attr_e( 'Resync club calendar', 'myclub-sections' ) ?>
                    </button>
                    <?php submit_button( esc_html__( 'Save Changes' ), 'primary', 'save', false ); ?>
                </div>
            <?php }
            }
        ?>
    </form>
</div>