<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$valid_tabs = [
        'tab1',
        'tab2',
        'tab3',
        'tab4'
];
$active_tab = !empty( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'tab1';
$valid_action_tabs = [
        'tab1',
        'tab2'
];

if ( !in_array( $active_tab, $valid_tabs ) ) {
    $active_tab = 'tab1';
}

function myclub_sections_allow_code_html( $translated_string )
{
    echo wp_kses( $translated_string, array ( 'code' => array () ) );
}

?>

<div class="wrap">
    <h1><?php esc_html_e( 'MyClub Sections settings', 'myclub-sections' ) ?></h1>
    <div class="nav-tab-wrapper">
        <a href="?page=myclub-sections-settings&tab=tab1"
           class="nav-tab<?php echo $active_tab == 'tab1' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'General settings', 'myclub-sections' ) ?></a>
        <a href="?page=myclub-sections-settings&tab=tab2"
           class="nav-tab<?php echo $active_tab == 'tab2' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Display settings', 'myclub-sections' ) ?></a>
        <a href="?page=myclub-sections-settings&tab=tab3"
           class="nav-tab<?php echo $active_tab == 'tab3' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Gutenberg blocks', 'myclub-sections' ) ?></a>
        <a href="?page=myclub-sections-settings&tab=tab4"
           class="nav-tab<?php echo $active_tab == 'tab4' ? ' nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Shortcodes', 'myclub-sections' ) ?></a>
    </div>

    <form method="post" action="options.php" id="myclub-sections-settings-form">
        <?php
        if ( $active_tab === 'tab1' ) {
            settings_fields( 'myclub_sections_settings_tab1' );
            do_settings_sections( 'myclub_sections_settings_tab1' );
        } else if ( $active_tab === 'tab2' ) {
            settings_fields( 'myclub_sections_settings_tab2' );
            do_settings_sections( 'myclub_sections_settings_tab2' );
        } else if ( $active_tab === 'tab3' ) {
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
                <li><code>[myclub-sections-description]</code>
                    - <?php myclub_sections_allow_code_html( __( 'The description shortcode will display the section page description. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the description from or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
                <li><code>[myclub-sections-news]</code>
                    - <?php myclub_sections_allow_code_html( __( 'The news shortcode will display the section page news. The available attributes are <code>post_id</code> which can be set to the WordPress post id of the section page that you want to get the news for or <code>section_id</code> which is the MyClub section id for the section page. The default is to use the current page.', 'myclub-sections' ) ) ?>
                </li>
            </ul>
        <?php } ?>
        <?php if ( in_array( $active_tab, $valid_action_tabs ) ) { ?>
            <div>
                <?php if ( $active_tab === 'tab1' ) { ?>
                    <button type="button" id="myclub-reload-news-button" class="button">
                        <?php esc_attr_e( 'Reload news', 'myclub-sections' ) ?>
                    </button>
                    <button type="button" id="myclub-reload-sections-button" class="button">
                        <?php esc_attr_e( 'Reload sections', 'myclub-sections' ) ?>
                    </button>
                    <button type="button" id="myclub-sync-club-calendar-button" class="button">
                        <?php esc_attr_e( 'Resync club calendar', 'myclub-sections' ) ?>
                    </button>
                <?php }
                submit_button( esc_html__( 'Save Changes' ), 'primary', 'save', false ); ?>
            </div>
        <?php } ?>
    </form>
</div>