<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\MyClubSections\Services\NewsService;
use MyClub\MyClubSections\Utils;

$news_title = get_option( 'myclub_sections_news_title' ) ?: __( 'News', 'myclub-sections' );

?>
<div class="myclub-sections-news" id="news">
    <div class="myclub-sections-news-container">
        <h3 class="myclub-sections-header"><?php echo esc_html( $news_title ) ?></h3>
        <?php

        if ( !empty( $attributes ) ) {
            $post_id = Utils::getPostId( $attributes );
        }

        if ( empty ( $post_id ) || $post_id == 0 ) {
            echo esc_html__( 'No section page found. Invalid post_id or section_id.', 'myclub-sections' );
        } else {
        $meta = get_post_meta( $post_id, 'myclub_sections_id' );

        ?>
        <?php
        if ( !empty( $meta ) ) {
        $myclub_section_id = $meta[ 0 ];
        $query_args = array (
                'taxonomy'   => NewsService::MYCLUB_SECTIONS_NEWS,
                'meta_query' => [
                        [
                                'key'     => 'myclub_sections_id',
                                'value'   => $myclub_section_id,
                                'compare' => '='
                        ]
                ]
        );

        $terms = get_terms( $query_args );
        if ( !empty( $terms ) ) {
        $term_id = $terms[ 0 ]->term_id;
        $args = array (
                'post_type'   => 'post',
                'post_status' => 'publish',
                'tax_query'   => array (
                        array (
                                'taxonomy' => NewsService::MYCLUB_SECTIONS_NEWS,
                                'field'    => 'term_id',
                                'terms'    => array ( $term_id )
                        ),
                ),
                'orderby'     => 'date',
                'order'       => 'DESC',
                'numberposts' => 3
        );

        $posts = get_posts( $args );

        if ( !empty( $posts ) ) {
        ?>
        <div class="myclub-sections-news-list">
            <?php
            foreach ( $posts as $post ) {
                $image_url = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
                $image_caption = get_the_post_thumbnail_caption( $post->ID );
                ?>
                <div class="myclub-news-item">
                    <h4>
                        <a href="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
                    </h4>
                    <?php if ( $image_url ) { ?>
                        <div class="myclub-news-image">
                            <img src="<?php echo esc_url( $image_url ); ?>"
                                 alt="<?php echo esc_html( $post->post_title ); ?>"/>
                            <?php if ( $image_caption ) { ?>
                                <div class="myclub-news-image-caption"><?php echo esc_html( $image_caption ); ?></div>
                            <?php } ?>
                        </div>
                    <?php }
                    $content = $post->post_excerpt ?: $post->post_content;

                    // Render Gutenberg blocks if any, and shortcodes
                    $content = do_blocks( $content );
                    $content = do_shortcode( $content );

                    // Output safely
                    echo wp_kses_post( $content );
                    ?>
                </div>
                <?php
            }
            $term_link = get_term_link( $term_id, NewsService::MYCLUB_SECTIONS_NEWS );

            $args = array (
                    'post_type'   => 'post',
                    'post_status' => 'publish',
                    'tax_query'   => array (
                            array (
                                    'taxonomy' => NewsService::MYCLUB_SECTIONS_NEWS,
                                    'field'    => 'term_id',
                                    'terms'    => array ( $term_id )
                            ),
                    ),
            );

            $query = new WP_Query( $args );
            $total_posts = $query->found_posts;

            if ( !is_wp_error( $term_link ) && $total_posts > 3 ) {
                echo '<div class="myclub-more-news"><a href="' . esc_url( $term_link ) . '">' . esc_attr__( 'Show more news', 'myclub-sections' ) . '</a></div>';
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
