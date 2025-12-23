<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Utils;

$myclub_sections_description_header = get_option( 'myclub_sections_description_title' ) ?: __( 'Description', 'myclub-sections' );

if ( !empty( $attributes ) ) {
    $post_id = Utils::getPostId( $attributes );
}

if ( empty ( $post_id ) || $post_id == 0 ) {
    echo esc_html__( 'No section page found. Invalid post_id or section_id.', 'myclub-sections' );
} else {
    $myclub_sections_description = get_post_meta( $post_id, 'myclub_sections_description', true );
}

if ( empty( $myclub_sections_description ) ) {
    return;
}

?>
<div class="myclub-sections-description" id="description">
    <div class="myclub-sections-description-container">
        <h3 class="myclub-sections-header"><?php echo esc_html( $myclub_sections_description_header ) ?></h3>
        <?php

        echo wp_kses_post( $myclub_sections_description );
        ?>
    </div>
</div>
