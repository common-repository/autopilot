<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Autopilot
 * @subpackage Autopilot/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Autopilot
 * @subpackage Autopilot/admin
 * @author     ortto <help@ortto.com>
 */
class Autopilot_Admin {
	const OPTION_NAME = "ap3_options";

	const CONNECT_URL = "https://woocommerce-integration-api-us.ortto.app/-/installations/connect";
	const WEBHOOK_URL = "https://woocommerce-integration-api-us.ortto.app/-/webhook/";

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The plugin options.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array $options
	 */
	private $options;

	/**
	 * The plugin connect url.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $connect_url
	 */
	private $connect_url;

	/**
	 * Is woocommerce installed
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $connect_url
	 */
	private $is_wc;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->options     = get_option( self::OPTION_NAME );
	}

	/**
	 * @since    1.0.0
	 */
	public function init() {
		if ( empty( $this->options["code"] ) ) {
			$this->options["code"] = wp_generate_password( 12, false );
			update_option( self::OPTION_NAME, $this->options, true );
		}

		// disable plugin for them to reconnect in order to update configuration
		if ( empty( $this->options["capture_js_url"] ) || empty( $this->options["capture_api_url"] ) ) {
			$this->options["tracking_key"] = "";
			update_option( self::OPTION_NAME, $this->options, true );
		}

		if ( function_exists( 'is_plugin_active' ) ) {
			$this->is_wc = is_plugin_active( 'woocommerce/woocommerce.php' );
		} else {
			$this->is_wc = in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
		}

		$code               = $_GET['code'] ?? "";
		$uid                = $_GET['uid'] ?? "";
		$instance           = $_GET['instance'] ?? "";
		$company_name       = $_GET['company_name'] ?? "";
		$capture_api_url    = $_GET['capture_api_url'] ?? "";
		$capture_js_url     = $_GET['capture_js_url'] ?? "";
		$woocommerce_js_url = $_GET['woocommerce_js_url'] ?? "";

		if ( $code != "" && $this->options['code'] == $code && $uid != "" && $instance != "" && $company_name != "" && $capture_api_url != "" && $capture_js_url != "" && $woocommerce_js_url != "" ) {
			$this->options["tracking_key"]       = $uid;
			$this->options["instance"]           = $instance;
			$this->options["company_name"]       = $company_name;
			$this->options["capture_api_url"]    = $capture_api_url;
			$this->options["capture_js_url"]     = $capture_js_url;
			$this->options["woocommerce_js_url"] = $woocommerce_js_url;
			update_option( self::OPTION_NAME, $this->options, true );
		}

		$current_user      = wp_get_current_user();
		$this->connect_url = self::CONNECT_URL .
		                     '?code=' . urlencode( $this->options['code'] ) .
		                     '&shop=' . urlencode( home_url() ) .
		                     '&ctx=' . ( $this->is_wc ? "wc" : "wp" ) .
		                     '&email=' . urlencode( $current_user->user_email ) .
		                     '&fname=' . urlencode( $current_user->user_firstname ) .
		                     '&lname=' . urlencode( $current_user->user_lastname );

		register_setting( 'ap3_settings_group', self::OPTION_NAME, [ $this, 'sanitize_settings' ] );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Autopilot_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Autopilot_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/autopilot-admin.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Autopilot_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Autopilot_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/autopilot-admin.js', [ 'jquery' ], $this->version, false );
	}

	/**
	 * @since    1.0.0
	 */
	public function display_admin() {
		require_once 'partials/autopilot-admin-display.php';
	}

	/**
	 * @since    1.0.0
	 */
	public function display_help() {
		require_once 'partials/autopilot-help-display.php';
	}

	/**
	 * Add plugin menu item.
	 *
	 * @since    1.0.0
	 */
	public function menu() {
		add_menu_page( 'Ortto',
			'Ortto',
			'manage_options',
			'autopilot_settings',
			[ $this, 'display_admin' ],
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI1LjIuMywgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHdpZHRoPSIxNnB4IiBoZWlnaHQ9IjEycHgiIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA4LjMgNi4xIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA4LjMgNi4xOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+Cgo8cGF0aCBjbGFzcz0ic3QwIiBkPSJNNS4zLDBjMC45LDAsMS42LDAuMywyLjEsMC45QzgsMS40LDguMywyLjIsOC4zLDMuMVM4LDQuNyw3LjQsNS4yQzYuOSw1LjgsNi4yLDYuMSw1LjMsNi4xSDMKCWMtMC45LDAtMS42LTAuMy0yLjEtMC44bDAsMEMwLjMsNC43LDAsNCwwLDMuMXMwLjMtMS42LDAuOC0yLjJDMS40LDAuMywyLjEsMCwzLDBINS4zeiBNMy4xLDEuN2MtMC44LDAtMS40LDAuNi0xLjQsMS40CglzMC42LDEuNCwxLjQsMS40YzAuOCwwLDEuNC0wLjYsMS40LTEuNFMzLjgsMS43LDMuMSwxLjd6Ii8+Cjwvc3ZnPgo=' );
		// add_submenu_page( 'autopilot_settings', 'Help', 'Help', 'manage_options', 'autopilot_help', [ $this, 'display_help' ] );
	}

	/**
	 * Add plugin connect error.
	 *
	 * @since    1.0.0
	 */
	public function connect_error() {
		if ( isset( $_GET['ap3err'] ) && isset( $_GET['page'] ) && $_GET['page'] == 'autopilot_settings' ) {
			$error_message = filter_input( INPUT_GET, 'ap3err', FILTER_SANITIZE_STRING );
			$error_message = htmlspecialchars( $error_message, ENT_QUOTES, 'UTF-8' );
			echo '<div class="error fade"><p>' . $error_message . '. There was an error while trying to connect. Please try again later or visit our <a target="_blank" href="https://help.ortto.com/user/latest/data-sources/configuring-a-new-data-source/e-commerce-integrations/woocommerce.html">help center</a> if the problem persists.</p></div>' . "\n";
		}
	}

	/**
	 * Add plugin connect error.
	 *
	 * @since    1.0.0
	 */
	public function connected_notice() {
		if ( isset( $_GET['code'] ) && isset( $_GET['uid'] ) && $this->options['code'] == $_GET['code'] && isset( $_GET['page'] ) && $_GET['page'] == 'autopilot_settings' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Successfully connected to Ortto. Enable tracking script if you haven’t already.</p></div>' . "\n";
		}
	}

	/**
	 * Add plugin notice.
	 *
	 * @since    1.0.0
	 */
	public function notice() {
		if ( $this->options['disable_alert'] ?? false ) {
			return;
		}
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'autopilot_settings' ) {
			return;
		}
		if ( empty( $this->options['tracking_key'] ) ) {
			echo '<div class="error fade"><p><b>Ortto: </b>Plugin is activated. <a href="' . admin_url() . 'admin.php?page=autopilot_settings">Connect to Autopilot</a> to complete the setup.</p></div>' . "\n";
		} elseif ( ! ( $this->options['enable_tracking'] ?? false ) ) {
			echo '<div class="error fade"><p><b>Ortto: </b>Capture script is disabled. Enable it in the <a href="' . admin_url() . 'admin.php?page=autopilot_settings">plugin settings</a> to recover abandoned carts and display capture widgets your store.</p></div>' . "\n";
		}
	}

	/**
	 * Send custom webhooks on create.
	 *
	 * @since    1.0.0
	 */
	public function on_create( $term_id, $tt_id = '', $taxonomy = '' ) {
		switch ( $taxonomy ) {
			case 'product_cat':
				$this->send_webhook( $term_id, 'category', 'created' );
				break;
		};
	}

	/**
	 * Send custom webhooks on edit.
	 *
	 * @since    1.0.0
	 */
	public function on_edit( $term_id, $tt_id = '', $taxonomy = '' ) {
		switch ( $taxonomy ) {
			case 'product_cat':
				$this->send_webhook( $term_id, 'category', 'updated' );
				break;
		};
	}

	/**
	 * Send custom webhooks on delete.
	 *
	 * @since    1.0.0
	 */
	public function on_delete( $term_id, $tt_id = '', $taxonomy = '' ) {
		switch ( $taxonomy ) {
			case 'product_cat':
				$this->send_webhook( $term_id, 'category', 'deleted' );
				break;
		};
	}

	/**
	 * Send custom webhooks.
	 *
	 * @since    1.0.0
	 */
	private function send_webhook( $id, $resource, $event ) {
		if ( ! $this->is_wc ) {
			return;
		}
		if ( empty( $this->options["code"] ) ) {
			return;
		}

		$key    = $this->options["code"];
		$source = home_url();

		$options = [
			'http' => [
				'header'  => "Content-type: application/json\r\n" .
				             "X-WC-Webhook-Source: $source\r\n" .
				             "X-WC-Webhook-Topic: term.$event\r\n" .
				             "X-WC-Webhook-Resource: term\r\n" .
				             "X-WC-Webhook-Event: $event\r\n" .
				             "X-WC-Webhook-Signature: $key\r\n" .
				             "X-WC-Webhook-ID: $key\r\n" .
				             "X-WC-Webhook-Delivery-ID: $id\r\n",
				'method'  => 'POST',
				'content' => json_encode( [
					"term_id"     => $id,
					"term"        => $resource,
					"event"       => $event,
					"instance_id" => $this->options["instance"],
				] ),
			],
		];
		$context = stream_context_create( $options );
		file_get_contents( self::WEBHOOK_URL . $this->options["instance"], false, $context );
	}

	/**
	 * @since    1.0.0
	 */
	public function sanitize_settings( $input ) {
		$sanitary_values = [];
		if ( isset( $input['tracking_key'] ) ) {
			$sanitary_values['tracking_key'] = sanitize_text_field( $input['tracking_key'] );
		}
		if ( isset( $input['capture_api_url'] ) ) {
			$sanitary_values['capture_api_url'] = sanitize_text_field( $input['capture_api_url'] );
		}
		if ( isset( $input['capture_js_url'] ) ) {
			$sanitary_values['capture_js_url'] = sanitize_text_field( $input['capture_js_url'] );
		}
		if ( isset( $input['woocommerce_js_url'] ) ) {
			$sanitary_values['woocommerce_js_url'] = sanitize_text_field( $input['woocommerce_js_url'] );
		}
		if ( isset( $input['company_name'] ) ) {
			$sanitary_values['company_name'] = sanitize_text_field( $input['company_name'] );
		}
		if ( isset( $input['instance'] ) ) {
			$sanitary_values['instance'] = sanitize_text_field( $input['instance'] );
		}
		if ( isset( $input['code'] ) ) {
			$sanitary_values['code'] = sanitize_text_field( $input['code'] );
		}
		$sanitary_values['disable_alert']   = isset( $input['disable_alert'] );
		$sanitary_values['enable_tracking'] = isset( $input['enable_tracking'] );
		$sanitary_values['sms_checkout']    = isset( $input['sms_checkout'] );

		return $sanitary_values;
	}
}
