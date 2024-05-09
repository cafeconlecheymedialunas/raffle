<?php
/**
 * Main Raffle_Plugin Class
 *
 * @class Raffle_Plugin
 * @version	1.0.0
 * @since 1.0.0
 * @package	Raffle_Plugin
 * @author Matty
 */
final class Raffle_Plugin {
	/**
	 * Raffle_Plugin The single instance of Raffle_Plugin.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The plugin directory URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;

	/**
	 * The plugin directory path.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The settings object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings;
	// Admin - End

	// Post Types - Start
	/**
	 * The post types we're registering.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = array();

	/**
	 * The taxonomies we're registering.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $taxonomies = array();
	// Post Types - End
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct () {
		$this->token       = 'raffle';
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->version     = '1.0.0';

		// Admin - Start
		require_once 'class-raffle-settings.php';
			$this->settings = Raffle_Plugin_Settings::instance();

		if ( is_admin() ) {
			require_once 'class-raffle-admin.php';
			$this->admin = Raffle_Plugin_Admin::instance();
		}
		// Admin - End

		// Post Types - Start
		require_once 'class-raffle-post-type.php';
		require_once 'class-raffle-taxonomy.php';

		// Register an example post type. To register other post types, duplicate this line.
		$this->post_types['thing'] = new Raffle_Plugin_Post_Type( 'thing', __( 'Thing', 'raffle' ), __( 'Things', 'raffle' ), array( 'menu_icon' => 'dashicons-carrot' ) );

		// Register an example taxonomy, connected to our post type. To register other taxonomies, duplicate this line.
		$this->taxonomies['thing-category'] = new Raffle_Plugin_Taxonomy();
		// Post Types - End
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Main Raffle_Plugin Instance
	 *
	 * Ensures only one instance of Raffle_Plugin is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Raffle_Plugin()
	 * @return Main Raffle_Plugin instance
	 */
	public static function instance () {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'raffle', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __clone () {}

	/**
	 * Unserializing instances of this class is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __wakeup () {}

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 */
	public function install () {
		$this->log_version_number();
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 */
	private function log_version_number () {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}
}
