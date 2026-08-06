<?php

use MyClub\MyClubSections\Services\ActivityService;
use MyClub\MyClubSections\Utils;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="myclub-sections-coming-games" id="coming-games">
    <div class="myclub-sections-coming-games-container">
        <h3><?php echo esc_attr( get_option( 'myclub_sections_coming_games_title' ) ); ?></h3>

        <?php

        if ( !empty( $attributes ) ) {
            $post_id = Utils::getPostId( $attributes );
        }

        if ( empty ( $post_id ) || $post_id == 0 ):
            echo esc_html__( 'No section page found. Invalid post_id or section_id.', 'myclub-sections' );
        else:
            $myclub_sections_coming_games_activities = array_values(
                    array_filter(
                            ActivityService::listPostActivities( $post_id ), function ( $activity ) {
                        return $activity->day >= wp_date( 'Y-m-d' ) && $activity->base_type === 'match';
                    }
                    )
            );
            ?>
            <div class="coming-games-list">
                <?php
                if ( !empty( $myclub_sections_coming_games_activities ) ):
                    $myclub_sections_coming_games_hidden_added = false;
                    foreach ( $myclub_sections_coming_games_activities as $myclub_sections_coming_games_key => $myclub_sections_coming_games_activity ) { ?>
                        <div class="myclub-sections-coming-game">
                            <div class="title">
                                <div class="section-name"><?php echo esc_attr( $myclub_sections_coming_games_activity->calendar_name ); ?></div>
                                <?php echo esc_attr( $myclub_sections_coming_games_activity->title ); ?>
                            </div>
                            <div class="venue"><?php echo esc_attr( $myclub_sections_coming_games_activity->location ); ?></div>
                            <div class="date">
                                <?php echo esc_attr( $myclub_sections_coming_games_activity->day ); ?>
                                <div class="time"><?php echo esc_attr( substr( $myclub_sections_coming_games_activity->start_time, 0, 5 ) . ' - ' . substr( $myclub_sections_coming_games_activity->end_time, 0, 5 ) ); ?></div>
                            </div>
                        </div>
                        <?php
                        if ( $myclub_sections_coming_games_key === 3 ):
                            $myclub_sections_coming_games_hidden_added = true;
                            ?>
                            <div class="hidden extended-list">

                        <?php
                        endif;
                    }

                    if ( $myclub_sections_coming_games_hidden_added ):
                        ?>
                        </div>
                        <div class="coming-game-show-more"><?php echo esc_attr__( 'Show more', 'myclub-sections' ); ?></div>
                        <div class="coming-game-show-less hidden"><?php echo esc_attr__( 'Show less', 'myclub-sections' ); ?></div>
                    <?php
                    endif;
                else:
                    ?>
                    <div class="myclub-sections-no-coming-games"><?php echo esc_attr__( 'No upcoming games', 'myclub-sections' ); ?></div>
                <?php
                endif;
                ?>
            </div>
        <?php
        endif;
        ?>
    </div>
</div>
