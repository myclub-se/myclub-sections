<?php

namespace MyClub\MyClubSections\Services;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\Common\Services\BaseActivityService;

class ActivityService extends BaseActivityService
{
    protected static string $activities_table_suffix = 'myclub_sections_activities';
    protected static string $activities_link_table_suffix = 'myclub_sections_post_activities';
    protected static string $activities_unique_key = 'myclub_sections_post_activity_unique';
}