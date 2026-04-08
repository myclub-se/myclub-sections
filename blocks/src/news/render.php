<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Services\NewsService;
use MyClub\MyClubSections\Utils;

$myclub_sections_news_header = get_option( 'myclub_sections_news_title' ) ?: __( 'News', 'myclub-sections' );
$myclub_sections_ingress_word_length = (int) get_option( 'myclub_sections_news_ingress_word_length' ) ?: 0;

?>
<div class="myclub-sections-news" id="news">
    <div class="myclub-sections-news-container">
        <h3 class="myclub-sections-header"><?php echo esc_html( $myclub_sections_news_header ) ?></h3>
        <?php

        if ( !empty( $attributes ) ) {
            $post_id = Utils::getPostId( $attributes );
        }

        if ( empty ( $post_id ) || $post_id == 0 ) {
            echo esc_html__( 'No section page found. Invalid post_id or section_id.', 'myclub-sections' );
        } else {
        $myclub_sections_section_id = get_post_meta( $post_id, 'myclub_sections_id', true );
        ?>
        <?php
        if ( !empty( $myclub_sections_section_id ) ) {
        $myclub_sections_query_args = array (
                'taxonomy'   => NewsService::MYCLUB_SECTIONS_NEWS,
                'meta_query' => [
                        [
                                'key'     => 'myclub_sections_id',
                                'value'   => $myclub_sections_section_id,
                                'compare' => '='
                        ]
                ]
        );

        $myclub_sections_news_terms = get_terms( $myclub_sections_query_args );
        if ( !empty( $myclub_sections_news_terms ) ) {
        $myclub_sections_news_term_id = $myclub_sections_news_terms[ 0 ]->term_id;
        $myclub_sections_news_args = array (
                'post_type'   => 'post',
                'post_status' => 'publish',
                'tax_query'   => array (
                        array (
                                'taxonomy' => NewsService::MYCLUB_SECTIONS_NEWS,
                                'field'    => 'term_id',
                                'terms'    => array ( $myclub_sections_news_term_id )
                        ),
                ),
                'orderby'     => 'date',
                'order'       => 'DESC',
                'numberposts' => 3
        );

        $myclub_sections_news_posts = get_posts( $myclub_sections_news_args );

        if ( !empty( $myclub_sections_news_posts ) ) {
        ?>
        <div class="myclub-sections-news-list">
            <?php
            foreach ( $myclub_sections_news_posts as $post ) {
                $myclub_sections_news_image_caption = get_the_post_thumbnail_caption( $post->ID );
                $myclub_sections_image_html = get_the_post_thumbnail(
                        $post->ID,
                        'medium_large',
                        array(
                                'alt' => esc_attr( $post->post_title ),
                        )
                );
                ?>
                <div class="myclub-news-item">
                    <h4>
                        <a href="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
                    </h4>
                    <?php if ( $myclub_sections_image_html ) { ?>
                        <div class="myclub-news-image">
                            <?php echo wp_kses_post( $myclub_sections_image_html ); ?>
                        </div>
                    <?php }
                    if ( $myclub_sections_news_image_caption ) { ?>
                        <div class="myclub-news-image-caption"><?php echo esc_html( $myclub_sections_news_image_caption ); ?></div>
                    <?php } ?>
                    <div class="myclub-news-ingress">
                    <?php
                    $myclub_sections_news_content = $post->post_excerpt ?: $post->post_content;

                    // Render Gutenberg blocks if any, and shortcodes
                    $myclub_sections_news_content = do_blocks( $myclub_sections_news_content );
                    $myclub_sections_news_content = do_shortcode( $myclub_sections_news_content );

                    if ( $myclub_sections_ingress_word_length > 0 ) {
                        $myclub_sections_news_content = wp_trim_words( wp_strip_all_tags( $myclub_sections_news_content ), $myclub_sections_ingress_word_length, '...' );
                    }

                    // Output safely
                    echo wp_kses_post( $myclub_sections_news_content );
                    ?>
                    </div>
                </div>
                <?php
            }
            $myclub_sections_news_term_link = get_term_link( $myclub_sections_news_term_id, NewsService::MYCLUB_SECTIONS_NEWS );

            $myclub_sections_news_args = array (
                    'post_type'   => 'post',
                    'post_status' => 'publish',
                    'tax_query'   => array (
                            array (
                                    'taxonomy' => NewsService::MYCLUB_SECTIONS_NEWS,
                                    'field'    => 'term_id',
                                    'terms'    => array ( $myclub_sections_news_term_id )
                            ),
                    ),
            );

            $myclub_sections_news_term_query = new WP_Query( $myclub_sections_news_args );
            $myclub_sections_terms_total_posts = $myclub_sections_news_term_query->found_posts;

            if ( !is_wp_error( $myclub_sections_news_term_link ) && $myclub_sections_terms_total_posts > 3 ) {
                echo '<div class="myclub-more-news"><a href="' . esc_url( $myclub_sections_news_term_link ) . '">' . esc_attr__( 'Show more news', 'myclub-sections' ) . '</a></div>';
            }
            echo '</div>';
            } else {
                echo '<div class="no-news">' . esc_attr__( 'No news found', 'myclub-sections' ) . '</div>';
            }
            } else {
                echo '<div class="no-news">' . esc_attr__( 'No news found', 'myclub-sections' ) . '</div>';
            }
            } else {
                echo '<div class="no-news">' . esc_attr__( 'No news found', 'myclub-sections' ) . '</div>';
            }
            }
            ?>
        </div>
    </div>
