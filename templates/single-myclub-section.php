<?php
/*
 * Template Name: MyClub Section Page
 * Template Post Type: myclub-sections
 * Description: Template used to display the section pages in a non-block based theme.
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

the_post();

the_content();

get_footer();
