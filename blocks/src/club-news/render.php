<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$myclub_sections_club_news_header = get_option( 'myclub_sections_club_news_title' ) ?: __( 'News', 'myclub-sections' );
$myclub_sections_ingress_word_length = (int) get_option( 'myclub_sections_news_ingress_word_length' ) ?: 0;

?>

<div class="myclub-sections-club-news" id="news">
    <div class="myclub-sections-club-news-container">
        <h3 class="myclub-sections-header"><?php echo esc_html( $myclub_sections_club_news_header ); ?></h3>

        <?php

        $myclub_sections_club_news_category = get_term_by( 'name', __( 'Club news', 'myclub-sections' ), 'category' );

        $myclub_sections_club_news_posts = get_posts( array (
                'post_type'   => 'post',
                'post_status' => 'publish',
                'orderby'     => 'date',
                'order'       => 'DESC',
                'numberposts' => 3,
                'tax_query'   => array (
                        array (
                                'taxonomy' => 'category',
                                'field'    => 'term_id',
                                'terms'    => $myclub_sections_club_news_category ? $myclub_sections_club_news_category->term_id : 0,
                        ),
                ),
        ) );

        if ( !empty( $myclub_sections_club_news_posts ) ) {
        ?>
        <div class="myclub-sections-club-news-list">

            <?php
            foreach ( $myclub_sections_club_news_posts as $post ) {
                $myclub_sections_club_news_image_caption = get_the_post_thumbnail_caption( $post->ID );
                $myclub_sections_image_html = get_the_post_thumbnail(
                        $post->ID,
                        'medium_large',
                        array(
                                'alt' => esc_attr( $post->post_title ),
                        )
                );
                ?>
                <div class="myclub-club-news-item">
                    <h4>
                        <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
                    </h4>
                    <?php if ( $myclub_sections_image_html ) { ?>
                        <div class="myclub-news-image">
                            <?php echo wp_kses_post( $myclub_sections_image_html ); ?>
                        </div>
                    <?php } ?>

                    <?php if ( $myclub_sections_club_news_image_caption ) { ?>
                        <div class="myclub-club-news-image-caption"><?php echo esc_html( $myclub_sections_club_news_image_caption ); ?></div>
                    <?php } ?>
                    <div class="myclub-news-ingress">
                    <?php
                    $myclub_sections_club_news_content = $post->post_excerpt ?: $post->post_content;

                    // Render Gutenberg blocks if any, and shortcodes
                    $myclub_sections_club_news_content = do_blocks( $myclub_sections_club_news_content );
                    $myclub_sections_club_news_content = do_shortcode( $myclub_sections_club_news_content );

                    if ( $myclub_sections_ingress_word_length > 0 ) {
                        $myclub_sections_club_news_content = wp_trim_words( wp_strip_all_tags( $myclub_sections_club_news_content ), $myclub_sections_ingress_word_length, '...' );
                    }

                    // Output safely
                    echo wp_kses_post( $myclub_sections_club_news_content );
                    ?>
                    </div>
                </div>
                <?php
            }
            if ( $myclub_sections_club_news_category ) {
                $myclub_sections_club_news_category_link = get_category_link( $myclub_sections_club_news_category->term_id );

                if ( !is_wp_error( $myclub_sections_club_news_category_link ) ) {
                    $myclub_sections_club_news_query = new WP_Query( array (
                            'category__in' => array ( $myclub_sections_club_news_category->term_id ),
                            'post_type'    => 'post',
                            'post_status'  => 'publish',
                    ) );

                    if ( $myclub_sections_club_news_query->found_posts > 3 ) {
                        echo '<div class="myclub-more-club-news"><a href="' . esc_url( $myclub_sections_club_news_category_link ) . '">' . esc_attr__( 'Show more news', 'myclub-sections' ) . '</a></div>';
                    }
                }
            }
            echo '</div>';
            } else {
                echo '<div class="no-news">' . esc_attr__( 'No news found', 'myclub-sections' ) . '</div>';
            }
            ?>
        </div>
    </div>
