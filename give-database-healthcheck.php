<?php
/**
 * Plugin Name: Give - Database HealthCheck
 * Plugin URI: https://github.com/WordImpress/Give-Database-Healthcheck
 * Description: The most robust, flexible, and intuitive way to accept donations on WordPress.
 * Author: WordImpress
 * Author URI: https://wordimpress.com
 * Version: 0.0.2
 * Text Domain: give-database-healthcheck
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/WordImpress/Give-Database-Healthcheck
 *
 */

final class Give_Database_HealthCheck {
	/**
	 * Instance.
	 *
	 * @since
	 * @access private
	 * @var
	 */
	static private $instance;

	/**
	 * Singleton pattern.
	 *
	 * @since
	 * @access private
	 */
	private function __construct() {
	}


	/**
	 * Get instance.
	 *
	 * @since
	 * @access static
	 * @return Give_Database_HealthCheck
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			self::$instance = new static();

			self::$instance->constants();
			self::$instance->files();
		}

		return self::$instance;
	}

	/**
	 * Constant
	 *
	 * @since 0.0.1
	 */
	private function constants() {
		define( 'GIVE_DATABASE_HealthCheck_DIR', plugin_dir_path( __FILE__ ) );
		define( 'GIVE_DATABASE_HealthCheck_VERSION', '0.0.2' );
	}

	/**
	 * Files
	 *
	 * @since 0.0.1
	 */
	private function files() {
		require_once GIVE_DATABASE_HealthCheck_DIR . 'admin/upgrades.php';
	}
}

Give_Database_HealthCheck::get_instance();