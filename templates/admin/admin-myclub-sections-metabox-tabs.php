<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Render a read only text input box displayed in the meta box on section pages.
 *
 * @return void
 */
function myclub_sections_render_meta_data_text( $post, $label, $name )
{
    $value = get_post_meta( $post, $name, true );

    echo '<div class="metadata-wrap">';
    echo '<p class="post-attributes-label-wrapper">';
    echo '<label class="post-attributes-label" for="' . esc_attr( $name ) . '">' . esc_attr( $label ) . '</label>';
    echo '</p>';
    echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" readonly class="widefat" />';
    echo '</div>';
}

/**
 * Render a read only textarea displayed in the meta box on section pages.
 *
 * @return void
 */
function myclub_sections_render_meta_data_textarea( $post, $label, $name )
{
    $value = get_post_meta( $post, $name, true );

    echo '<div class="metadata-wrap">';
    echo '<p class="post-attributes-label-wrapper">';
    echo '<label class="post-attributes-label" for="' . esc_attr( $name ) . '">' . esc_attr( $label ) . '</label>';
    echo '</p>';
    echo '<textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" readonly class="widefat" rows="10">' . esc_attr( $value ) . '</textarea>';
    echo '</div>';
}

$post = get_the_ID();

?>
<div id="myclub-tabs">
    <ul>
        <li class="tabs"><a href="#myclub-tab1"><?php esc_attr_e( 'Standard information', 'myclub-sections' ) ?></a>
        </li>
        <li class="tabs"><a href="#myclub-tab2"><?php esc_attr_e( 'Activities', 'myclub-sections' ) ?></a></li>
    </ul>
    <div id="myclub-tab1" class="tabs-panel">
        <?php
        // All of these fields are readonly and will not be saved on post save.
        myclub_sections_render_meta_data_text( $post, __( 'MyClub section id', 'myclub-sections' ), 'myclub_sections_id' );
        myclub_sections_render_meta_data_textarea( $post, __( 'Description', 'myclub-sections' ), 'myclub_sections_description' );
        ?>
    </div>
    <div id="myclub-tab3" class="hidden tabs-panel">
        <?php
        require_once( $this->plugin_path . '/templates/admin/admin-myclub-sections-activities.php' );
        ?>
    </div>
</div>
