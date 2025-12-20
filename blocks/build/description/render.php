<?php

use MyClub\MyClubSections\Utils;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$description_title = get_option( 'myclub_sections_description_title' ) ?: __( 'Description', 'myclub-sections' );

if ( !empty( $attributes ) ) {
    $post_id = Utils::getPostId( $attributes );
}

if ( empty ( $post_id ) || $post_id == 0 ) {
    echo esc_html__( 'No section page found. Invalid post_id or section_id.', 'myclub-sections' );
} else {
    $meta = get_post_meta( $post_id, 'myclub_sections_description', true );
}

if ( empty( $meta ) ) {
    return;
}

?>
<div class="myclub-sections-description" id="description">
    <div class="myclub-sections-description-container">
        <h3 class="myclub-sections-header"><?php echo esc_html( $description_title ) ?></h3>
        <?php

        echo wp_kses_post( $meta );
        ?>
    </div>
</div>
