<?php
/**
 * Class Test_Raffle_Plugin
 *
 * @package Raffle_Plugin
 */

/**
 * Sample test case.
 */
class Test_Raffle_Plugin extends WP_UnitTestCase {
	public function set_up() {
        parent::set_up();
        
        // Mock that we're in WP Admin context.
		// See https://wordpress.stackexchange.com/questions/207358/unit-testing-in-the-wordpress-backend-is-admin-is-true
        set_current_screen( 'edit-post' );
        
        $this->raffle_plugin = new Raffle_Plugin();
    }

    public function tear_down() {
        parent::tear_down();
    }

	public function test_has_correct_token() {
		$has_correct_token = ( 'raffle' === $this->raffle_plugin->token );
		
		$this->assertTrue( $has_correct_token );
	}

	public function test_has_admin_interface() {
		$has_admin_interface = ( is_a( $this->raffle_plugin->admin, 'Raffle_Plugin_Admin' ) );
		
		$this->assertTrue( $has_admin_interface );
	}

	public function test_has_settings_interface() {
		$has_settings_interface = ( is_a( $this->raffle_plugin->settings, 'Raffle_Plugin_Settings' ) );
		
		$this->assertTrue( $has_settings_interface );
	}

	public function test_has_post_types() {
		$has_post_types = ( 0 < count( $this->raffle_plugin->post_types ) );
		
		$this->assertTrue( $has_post_types );
	}

	public function test_has_load_plugin_textdomain() {
		$has_load_plugin_textdomain = ( is_int( has_action( 'init', [ $this->raffle_plugin, 'load_plugin_textdomain' ] ) ) );
		
		$this->assertTrue( $has_load_plugin_textdomain );
	}
}
