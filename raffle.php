<?php
/**
 * Plugin Name: Raffle  Plugin
 * Plugin URI: http://domain.com/raffle/
 * Description: Hey there! I'm your new raffle  plugin.
 * Version: 1.0.0
 * Author: Matty Cohen
 * Author URI: http://domain.com/
 * Requires at least: 4.0.0
 * Tested up to: 4.0.0
 *
 * Text Domain: raffle
 * Domain Path: /languages/
 *
 * @package Raffle_Plugin
 * @category Core
 * @author Matty
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'classes/class-raffle.php';

/**
 * Returns the main instance of Raffle_Plugin to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Raffle_Plugin
 */
function raffle_plugin() {
	return Raffle_Plugin::instance();
}
add_action( 'plugins_loaded', 'raffle_plugin' );
