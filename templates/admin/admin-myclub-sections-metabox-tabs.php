<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Renders a read-only text input field for meta data in a post editor section.
 *
 * @param int $post_id The id of the current post.
 * @param string $label The label text to display for the input field.
 * @param string $name The meta key name used to retrieve and display the value.
 *
 * @return void
 */
function myclub_sections_render_meta_data_text( int $post_id, string $label, string $name ): void
{
    $value = get_post_meta( $post_id, $name, true );

    echo '<div class="metadata-wrap">';
    echo '<p class="post-attributes-label-wrapper">';
    echo '<label class="post-attributes-label" for="' . esc_attr( $name ) . '">' . esc_attr( $label ) . '</label>';
    echo '</p>';
    echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" readonly class="widefat" />';
    echo '</div>';
}


/**
 * Render a read-only description box displayed in the meta box on section pages.
 *
 * @param int $post_id The id of the current post.
 * @param string $label The label to display for the description box.
 * @param string $name The name identifier for the meta data field.
 *
 * @return void
 */
function myclub_sections_render_meta_data_description( int $post_id, string $label, string $name ): void
{
    $value = get_post_meta( $post_id, $name, true );

    echo '<div class="metadata-wrap">';
    echo '<p class="post-attributes-label-wrapper">';
    echo '<label class="post-attributes-label" for="' . esc_attr( $name ) . '">' . esc_attr( $label ) . '</label>';
    echo '</p>';
    echo '<div id="' . esc_attr( $name ) . '" style="border-radius: 4px; border-color: rgb(140, 143, 148); border-style: solid; border-width: 1px; padding: 2px 6px; height: 200px; overflow-y: auto; background-color: #f0f0f1">' . wp_kses_post( $value ) . '</div>';
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
        myclub_sections_render_meta_data_description( $post, __( 'Description', 'myclub-sections' ), 'myclub_sections_description' );
        ?>
    </div>
    <div id="myclub-tab2" class="hidden tabs-panel">
        <?php
        require_once( $this->plugin_path . '/templates/admin/admin-myclub-sections-activities.php' );
        ?>
    </div>
</div>
