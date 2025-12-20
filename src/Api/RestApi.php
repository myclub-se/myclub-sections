<?php

namespace MyClub\MyClubSections\Api;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MyClub\Common\Api\BaseRestApi;


/**
 * Class RestApi
 *
 * Provides methods to interact with the MyClub backend API, including retrieving club calendar,
 * menu items, other teams, section details, news, and executing GET requests.
 */
class RestApi extends BaseRestApi
{
    protected string $apiKeyOptionName = 'myclub_sections_api_key';

    /**
     * Constructor for the class.
     *
     * Initializes the object with the provided API key or retrieves the API key from the options if not provided.
     *
     * @param string|null $apiKey The API key to be used. Default is null.
     *
     * @return void
     * @since 1.0.0
     */
    public function __construct( string $apiKey = null )
    {
        parent::__construct( 'MyClub Sections WordPress', MYCLUB_SECTIONS_PLUGIN_VERSION, $apiKey );
    }
}