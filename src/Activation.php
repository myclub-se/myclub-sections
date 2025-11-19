<?php


class Activation {
	private array $options;

	public function __construct() {
		$this->options = array (
			[
				'name'     => 'myclub_sections_version',
				'value'    => null,
				'autoload' => 'no'
			],
			[
				'name'     => 'myclub_sections_api_key',
				'value'    => null,
				'autoload' => 'no'
			],
			[
				'name'     => 'myclub_sections_section_slug',
				'value'    => 'sections',
				'autoload' => 'no'
			],
			[
				'name'     => 'myclub_sections_section_news_slug',
				'value'    => 'section-news',
				'autoload' => 'no'
			],
			[
				'name'     => 'myclub_sections_last_sections_sync',
				'value'    => null,
				'autoload' => 'no',
			],
			[
				'name'     => 'myclub_sections_last_news_sync',
				'value'    => null,
				'autoload' => 'no',
			],
		);
	}

	public function activate() {
		foreach ( $this->options as $option ) {
			$this->addOption( $option[ 'name' ], $option[ 'value' ], $option[ 'autoload' ] );
		}
	}

	public function deactivate() {
		delete_option( 'myclub_sections_api_key' );
	}

	public function uninstall() {
		foreach ( $this->options as $option ) {
			delete_option( $option[ 'name' ] );
		}
	}

	/**
	 * Adds an option to the WordPress database if it doesn't already exist.
	 *
	 * @param string $optionName The name of the option.
	 * @param mixed $default The default value for the option.
	 * @param string|null $autoload Sets if the option should be loaded.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function addOption( string $optionName, $default, string $autoload )
	{
		if ( get_option( $optionName ) === false ) {
			add_option( $optionName, $default, '', $autoload );
		}
	}
}