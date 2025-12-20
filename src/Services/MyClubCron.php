<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles scheduling and execution of cron jobs related to club operations.
 */
class MyClubCron
{
    const REFRESH_CLUB_CALENDAR_HOOK = 'myclub_sections_refresh_club_calendar';
    const REFRESH_NEWS_HOOK = 'myclub_sections_refresh_news';
    const REFRESH_SECTIONS_HOOK = 'myclub_sections_refresh_sections';

    /**
     * Registers the necessary actions for initializing schedules, refreshing club calendar, news, and sections.
     *
     * @return void
     * @since 1.0.0
     */
    public function register(): void
    {
        add_action( 'init', [
            $this,
            'setupSchedule'
        ] );
        add_action( MyClubCron::REFRESH_CLUB_CALENDAR_HOOK, [
            $this,
            'reloadClubCalendar'
        ] );
        add_action( MyClubCron::REFRESH_NEWS_HOOK, [
            $this,
            'reloadNews'
        ] );
        add_action( MyClubCron::REFRESH_SECTIONS_HOOK, [
            $this,
            'reloadSections'
        ] );
    }

    /**
     * Deactivates scheduled hooks related to the plugin by clearing them.
     *
     * @return void
     * @since 1.0.0
     */
    public function deactivate(): void
    {
        if ( wp_next_scheduled( MyClubCron::REFRESH_CLUB_CALENDAR_HOOK ) ) {
            wp_clear_scheduled_hook( MyClubCron::REFRESH_CLUB_CALENDAR_HOOK );
        }
        if ( wp_next_scheduled( MyClubCron::REFRESH_NEWS_HOOK ) ) {
            wp_clear_scheduled_hook( MyClubCron::REFRESH_NEWS_HOOK );
        }
        if ( wp_next_scheduled( MyClubCron::REFRESH_SECTIONS_HOOK ) ) {
            wp_clear_scheduled_hook( MyClubCron::REFRESH_SECTIONS_HOOK );
        }
    }

    /**
     * Sets up scheduled hooks for recurring tasks if they are not already scheduled.
     *
     * @return void
     * @since 1.0.0
     */
    public function setupSchedule(): void
    {
        if ( !wp_next_scheduled( MyClubCron::REFRESH_CLUB_CALENDAR_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', MyClubCron::REFRESH_CLUB_CALENDAR_HOOK );
        }

        if ( !wp_next_scheduled( MyClubCron::REFRESH_NEWS_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', MyClubCron::REFRESH_NEWS_HOOK );
        }

        if ( !wp_next_scheduled( MyClubCron::REFRESH_SECTIONS_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', MyClubCron::REFRESH_SECTIONS_HOOK );
        }
    }

    public function reloadClubCalendar(): void
    {
        $service = new CalendarService();
        $service->reloadClubEvents();
    }

    /**
     * Reloads the news by reloading them from the MyClub backend.
     *
     * @return void
     * @since 1.0.0
     */
    public function reloadNews()
    {
        $service = new NewsService();
        $service->reloadNews();
    }

    /**
     * Reloads all sections by invoking the SectionService.
     *
     * @return void
     * @since 1.0.0
     */
    public function reloadSections()
    {
        $service = new SectionService();
        $service->reloadSections();
    }
}