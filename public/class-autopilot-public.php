<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Autopilot
 * @subpackage Autopilot/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Autopilot
 * @subpackage Autopilot/public
 * @author     ortto <help@ortto.com>
 */
class Autopilot_Public {
	const OPTION_NAME = "ap3_options";
	const WC_EVENT_PATH = '-/events/wc-event';
	const API_URL = "https://capture-api-us.ortto.app/";
	const SCRIPT_URL = "https://s.autopilotapp.com/app.js";
	const WOOCOMMERCE_SCRIPT_URL = "https://cdn2l.ink/app-woocommerce.js";

	const EVENT_CART_VIEWED = 'cart_viewed';
	const EVENT_CHECkOUT_STARTED = 'checkout_started';

	const DATE_FORMAT = "Y-m-d\TH:i:s";

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
	 * @since    1.0.0
	 * @access   private
	 * @var      boolean $is_enabled
	 */
	private $is_enabled;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->options     = get_option( self::OPTION_NAME );
		$this->is_enabled  = ! empty( $this->options['tracking_key'] ) && ( $this->options['enable_tracking'] ?? false );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/autopilot-public.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		if ( $this->is_enabled ) {
			if ( empty( $this->options["capture_js_url"] ) ) {
				$this->options["capture_js_url"] = self::SCRIPT_URL;
			}
			if ( empty( $this->options["woocommerce_js_url"] ) ) {
				$this->options["woocommerce_js_url"] = self::WOOCOMMERCE_SCRIPT_URL;
			}
			if ( empty( $this->options["capture_api_url"] ) ) {
				$this->options["capture_api_url"] = self::API_URL;
			}

			$ap3Options = [
				              "customer" => $this->customer_to_array(),
				              "api_url"  => $this->options["capture_api_url"],
			              ] + $this->options;

			$scriptFile = plugin_dir_url( __FILE__ ) . 'js/autopilot-public.js';
			$scriptDeps = [ 'jquery' ];

			wp_register_script( $this->plugin_name, $scriptFile, $scriptDeps, $this->version, true );
			wp_enqueue_script( $this->plugin_name );
			wp_localize_script( $this->plugin_name, 'ap3Options', $ap3Options );

			if ( function_exists( "WC" ) && function_exists( "is_product" ) && is_product() ) {
				$this->product_viewed();
			}
		}
	}

	public function enrich_webhooks( $payload, $resource, $resource_id, $id ) {
		switch ( $resource ) {
			// case 'product':
			// 	return $this->enrich_product_webhooks( $payload, $resource, $resource_id, $id );
			case 'order':
				return $this->enrich_order_webhooks( $payload, $resource, $resource_id, $id );
			case 'customer':
				return $this->enrich_customer_webhooks( $payload, $resource, $resource_id, $id );
			default:
				return $payload;
		}
	}

	/**
	 * @param WP_Comment|int $id
	 *
	 * @since    1.0.0
	 */
	public function comment_insert( $id ) {
		if ( ! $this->is_enabled ) {
			return;
		}
		$comment = $id;
		if ( ! $comment instanceof WP_Comment ) {
			$comment = get_comment( $id );
		}
		if ( ! $comment instanceof WP_Comment ) {
			return;
		}
		$this->on_comment( $comment, "review_created" );
	}

	/**
	 * @param WP_Comment|int $id
	 *
	 * @since    1.0.0
	 */
	public function comment_modify( $id ) {
		if ( ! $this->is_enabled ) {
			return;
		}
		$comment = $id;
		if ( ! $comment instanceof WP_Comment ) {
			$comment = get_comment( $id );
		}
		if ( ! $comment instanceof WP_Comment ) {
			return;
		}
		$this->on_comment( $comment, "review_modified" );
	}

	/**
	 * @since    1.0.0
	 */
	public function add_to_cart() {
		if ( ! $this->is_enabled ) {
			return;
		}
		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return;
		}
		$customer = $this->customer_to_array( $cart->get_customer() );
		$data     = [
			"event_type"  => "cart_product_added",
			"api_path"    => self::WC_EVENT_PATH,
			"shop_domain" => $this->get_shop_domain(),
			"cart"        => $this->cart_to_array( $cart ),
			"customer"    => $customer,
		];

		$options = [
			'http' => [
				'header'  => "Content-type: application/json",
				'method'  => 'POST',
				'content' => json_encode( [
					"h"  => $this->options['tracking_key'],
					"e"  => $customer['email'] ?? "",
					"s"  => $_COOKIE['ap3c'] ?? "",
					"wc" => $data,
				] ),
			],
		];
		$context = stream_context_create( $options );
		if ( isset( $customer['email'] ) || $_COOKIE['ap3c'] ) {
			$apiURL = $this->options["capture_api_url"];
			if ( empty( $apiURL ) ) {
				$apiURL = self::API_URL;
			}
			file_get_contents( $apiURL . self::WC_EVENT_PATH, false, $context );
		}
	}

	/**
	 * @since    1.0.0
	 */
	public function after_cart_contents() {
		if ( ! $this->is_enabled ) {
			return;
		}
		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return;
		}

		$cart_array = $this->cart_to_array( $cart );
		if ( empty( $cart_array["capture_cart_items"] ) ) {
			return;
		}

		$event_type = self::EVENT_CART_VIEWED;
		$event      = [
			"event_type"  => $event_type,
			"api_path"    => self::WC_EVENT_PATH,
			"shop_domain" => $this->get_shop_domain(),
			"cart"        => $cart_array,
			"customer"    => $this->customer_to_array( $cart->get_customer() ),
		];

		wp_localize_script( $this->plugin_name, 'ap3Event', $event );
	}

	/**
	 * @since    1.0.0
	 */
	public function after_checkout_form() {
		if ( ! $this->is_enabled ) {
			return;
		}
		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return;
		}

		$cart_array = $this->cart_to_array( $cart );
		if ( empty( $cart_array["capture_cart_items"] ) ) {
			return;
		}

		$event_type = self::EVENT_CHECkOUT_STARTED;
		$event      = [
			"event_type"  => $event_type,
			"api_path"    => self::WC_EVENT_PATH,
			"shop_domain" => $this->get_shop_domain(),
			"cart"        => $cart_array,
			"customer"    => $this->customer_to_array( $cart->get_customer() ),
		];

		wp_localize_script( $this->plugin_name, 'ap3Event', $event );
	}

	/**
	 * @since    1.0.0
	 */
	private function product_viewed() {
		$event_type = 'product_viewed';
		$event      = [
			"event_type"  => $event_type,
			"api_path"    => self::WC_EVENT_PATH,
			"shop_domain" => $this->get_shop_domain(),
			'product'     => $this->product_to_array( wc_get_product() ),
			"customer"    => $this->customer_to_array(),
		];

		wp_localize_script( $this->plugin_name, 'ap3Event', $event );
	}

	/**
	 * @param WC_Cart $cart
	 *
	 * @return array
	 * @since    1.0.0
	 */
	private function cart_to_array( $cart ) {
		$items = $this->items_to_array( $cart->get_cart() );

		return [
			"total"              => doubleval( $cart->get_total( null ) ),
			"subtotal"           => doubleval( $cart->get_subtotal() ),
			"shipping"           => doubleval( $cart->get_shipping_total() ),
			"taxes"              => doubleval( $cart->get_taxes() ),
			"currency"           => get_woocommerce_currency(),
			"capture_cart_items" => $items,
			"coupons"            => $cart->get_applied_coupons(),
			"cart_url"           => wc_get_cart_url(),
			"checkout_url"       => wc_get_checkout_url(),
		];
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 * @since    1.0.0
	 */
	private function order_to_array( WC_Order $order ): array {
		$items = $this->items_to_array( $order->get_items() );

		return array_merge( $order->get_data(),
			[
				'id'                          => (int) $order->get_id(),
				'parent_id'                   => (int) $order->get_parent_id(),
				'number'                      => $order->get_order_number(),
				'currency'                    => $order->get_currency(),
				"capture_cart_items"          => $items,
				'checkout_payment_url'        => $order->get_checkout_payment_url(),
				'checkout_order_received_url' => $order->get_checkout_order_received_url(),
				'cancel_order_url'            => $order->get_cancel_order_url(),
				'view_order_url'              => $order->get_view_order_url(),
				'order_key'                   => $order->get_order_key(),
				'created_via'                 => $order->get_created_via(),
				'version'                     => $order->get_version(),
				'status'                      => $order->get_status(),
				'discount_total'              => $order->get_discount_total(),
				'discount_tax'                => $order->get_discount_tax(),
				'shipping_total'              => $order->get_shipping_total(),
				'shipping_tax'                => $order->get_shipping_tax(),
				'cart_tax'                    => $order->get_cart_tax(),
				'total'                       => $order->get_total(),
				'total_tax'                   => $order->get_total_tax(),
				'prices_include_tax'          => $order->get_prices_include_tax(),
				'customer_id'                 => $order->get_customer_id(),
				'customer_ip_address'         => $order->get_customer_ip_address(),
				'customer_user_agent'         => $order->get_customer_user_agent(),
				'customer_note'               => $order->get_customer_note(),
				'billing'                     => [
					'first_name' => $order->get_billing_first_name(),
					'last_name'  => $order->get_billing_last_name(),
					'company'    => $order->get_billing_company(),
					'address1'   => $order->get_billing_address_1(),
					'address2'   => $order->get_billing_address_2(),
					'city'       => $order->get_billing_city(),
					'state'      => $order->get_billing_state(),
					'postcode'   => $order->get_billing_postcode(),
					'country'    => $order->get_billing_country(),
					'email'      => $order->get_billing_email(),
					'phone'      => $order->get_billing_phone(),
				],
				'shipping'                    => [
					'first_name' => $order->get_shipping_first_name(),
					'last_name'  => $order->get_shipping_last_name(),
					'company'    => $order->get_shipping_company(),
					'address1'   => $order->get_shipping_address_1(),
					'address2'   => $order->get_shipping_address_2(),
					'city'       => $order->get_shipping_city(),
					'state'      => $order->get_shipping_state(),
					'postcode'   => $order->get_shipping_postcode(),
					'country'    => $order->get_shipping_country(),
				],
				'payment_method'              => $order->get_payment_method(),
				'payment_method_title'        => $order->get_payment_method_title(),
				'transaction_id'              => $order->get_transaction_id(),
				'cart_hash'                   => $order->get_cart_hash(),
				'refunds'                     => $order->get_refunds(),
				'meta_data'                   => $order->get_meta_data(),

				'date_created_gmt'   => ( $order->get_date_created() ? get_gmt_from_date( $order->get_date_created(), self::DATE_FORMAT ) : '' ),
				'date_modified_gmt'  => ( $order->get_date_modified() ? get_gmt_from_date( $order->get_date_modified(), self::DATE_FORMAT ) : '' ),
				'date_paid_gmt'      => ( $order->get_date_paid() ? get_gmt_from_date( $order->get_date_paid(), self::DATE_FORMAT ) : '' ),
				'date_completed_gmt' => ( $order->get_date_completed() ? get_gmt_from_date( $order->get_date_completed(), self::DATE_FORMAT ) : '' ),
			],
			[
				// remove this from capture payload
				'line_items'     => [],
				'tax_lines'      => [],
				'shipping_lines' => [],
				'fee_lines'      => [],
				'coupon_lines'   => [],
				'refunds'        => [],
			] );
	}

	/**
	 * @param WC_Order_Item[] $items
	 *
	 * @return array
	 * @since    1.0.0
	 */
	private function items_to_array( $items = [] ) {
		if ( ! is_iterable( $items ) ) {
			return [];
		}
		$lineItems = [];
		foreach ( $items as $cart_item ) {
			if ( $cart_item instanceof WC_Order_Item ) {
				$cart_item = $cart_item->get_data();
			}
			if ( $cart_item['quantity'] === 0 ) {
				continue;
			}

			$product = $this->product_to_array( wc_get_product( $cart_item['product_id'] ) );
			if ( empty( $product ) ) {
				continue;
			}

			$lineItems[] = array_merge(
				$cart_item,
				$product,
				[
					'product_id'   => (int) $cart_item['product_id'],
					'variation_id' => (int) $cart_item['variation_id'],

					'quantity'          => (int) $cart_item['quantity'],
					'line_subtotal'     => doubleval( $cart_item['line_subtotal'] ?? 0 ),
					'line_subtotal_tax' => doubleval( $cart_item['line_subtotal_tax'] ?? 0 ),
					'line_total'        => doubleval( $cart_item['line_total'] ?? 0 ),
					'line_tax'          => doubleval( $cart_item['line_tax'] ?? 0 ),
					'item_price'        => doubleval( ( $cart_item['line_subtotal'] ?? 0 ) / $cart_item['quantity'] ),
					'item_tax'          => doubleval( ( $cart_item['line_subtotal_tax'] ?? 0 ) / $cart_item['quantity'] ),
				] );
		}

		return $lineItems;
	}

	/**
	 * @param WC_Customer|null $customer
	 *
	 * @return array
	 * @since    1.0.0
	 */
	private function customer_to_array( $customer = null ) {
		if ( ! class_exists( "WC_Customer" ) ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id == 0 ) {
				$commenter = wp_get_current_commenter();
				if ( ! empty( $commenter['comment_author_email'] ) ) {
					return [
						'email' => $commenter['comment_author_email'],
					];
				}
			}

			return null;
		}
		if ( ! $customer instanceof WC_Customer ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id == 0 ) {
				$commenter = wp_get_current_commenter();
				if ( ! empty( $commenter['comment_author_email'] ) ) {
					return [
						'email' => $commenter['comment_author_email'],
					];
				}

				return null;
			}
			$customer = new WC_Customer( $current_user_id );
		}

		$last_order = $customer->get_last_order();

		return [
			'id'                  => (int) $customer->get_id(),
			'email'               => $customer->get_email(),
			'first_name'          => $customer->get_first_name(),
			'last_name'           => $customer->get_last_name(),
			'username'            => $customer->get_username(),
			'avatar_url'          => $customer->get_avatar_url(),
			'date_created_gmt'    => ( $customer->get_date_created() ? get_gmt_from_date( $customer->get_date_created(), self::DATE_FORMAT ) : '' ),
			'date_modified_gmt'   => ( $customer->get_date_modified() ? get_gmt_from_date( $customer->get_date_modified(), self::DATE_FORMAT ) : '' ),
			'last_order_id'       => is_object( $last_order ) ? $last_order->get_id() : null,
			'date_last_order_gmt' => is_object( $last_order ) ? ( $last_order->get_date_created() ? get_gmt_from_date( $last_order->get_date_created(),
				self::DATE_FORMAT ) : '' ) : '',
			'orders_count'        => (int) $customer->get_order_count(),
			'total_spent'         => doubleval( $customer->get_total_spent() ),
			'billing'             => [
				'first_name' => $customer->get_billing_first_name(),
				'last_name'  => $customer->get_billing_last_name(),
				'company'    => $customer->get_billing_company(),
				'address1'   => $customer->get_billing_address_1(),
				'address2'   => $customer->get_billing_address_2(),
				'city'       => $customer->get_billing_city(),
				'state'      => $customer->get_billing_state(),
				'postcode'   => $customer->get_billing_postcode(),
				'country'    => $customer->get_billing_country(),
				'email'      => $customer->get_billing_email(),
				'phone'      => $customer->get_billing_phone(),
			],
			'shipping'            => [
				'first_name' => $customer->get_shipping_first_name(),
				'last_name'  => $customer->get_shipping_last_name(),
				'company'    => $customer->get_shipping_company(),
				'address1'   => $customer->get_shipping_address_1(),
				'address2'   => $customer->get_shipping_address_2(),
				'city'       => $customer->get_shipping_city(),
				'state'      => $customer->get_shipping_state(),
				'postcode'   => $customer->get_shipping_postcode(),
				'country'    => $customer->get_shipping_country(),
			],
		];
	}

	/**
	 * @param WC_Product|null $product
	 *
	 * @return array
	 * @since    1.0.0
	 */
	private function product_to_array( $product = null ) {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$parent_product_id = $product->get_parent_id();
		if ( $product->get_parent_id() == 0 ) {
			$parent_product_id = $product->get_id();
		}

		$categories = get_the_terms( $product->get_id(), 'product_cat' );
		if ( empty( $categories ) ) {
			$categories = [];
		}

		$tags = get_the_terms( $product->get_id(), 'product_tag' );
		if ( empty( $tags ) ) {
			$tags = [];
		}

		return array_merge( $product->get_data(),
			[
				'parent_id'          => (int) $product->get_parent_id(),
				'id'                 => (int) $product->get_id(),
				'product_id'         => (int) $parent_product_id,
				'variation_id'       => (int) $product->get_id(),
				'variations'         => $product->get_children(),
				'slug'               => $product->get_slug(),
				'sku'                => $product->get_sku(),
				'name'               => $product->get_name(),
				'url'                => $product->get_permalink(),
				'description'        => $product->get_description(),
				'short_description'  => $product->get_short_description(),
				'weight'             => (string) $product->get_weight(),
				'regular_price'      => (string) $product->get_regular_price(),
				'sale_price'         => (string) $product->get_sale_price(),
				'price'              => (string) $product->get_price(),
				'attributes'         => $product->get_attributes(),
				'attribute'          => $product->get_attribute( 'pa_attribute-name' ),
				'custom_meta'        => $product->get_meta( '_custom_meta_key', true ),
				'categories'         => $categories, // (array) wp_list_pluck( $categories, 'name' ),
				'tags'               => $tags, // (array) wp_list_pluck( $tags, 'name' ),
				'image_url'          => (string) wp_get_attachment_url( get_post_thumbnail_id( $product->get_id() ) ),
				'permalink'          => $product->get_permalink(),
				'type'               => $product->get_type(),
				'status'             => $product->get_status(),
				'catalog_visibility' => $product->get_catalog_visibility(),
				'featured'           => $product->get_featured(),
				'purchasable'        => $product->is_purchasable(),
				'on_sale'            => $product->is_on_sale(),
				'virtual'            => $product->get_virtual(),
				'downloadable'       => $product->get_downloadable(),
				'stock_quantity'     => (int) $product->get_stock_quantity(),
				'stock_status'       => $product->get_stock_status(),
				'reviews_allowed'    => $product->get_reviews_allowed(),
				'backorders_allowed' => $product->backorders_allowed(),
				'date_created_gmt'   => ( $product->get_date_created() ? get_gmt_from_date( $product->get_date_created(), self::DATE_FORMAT ) : '' ),
				'date_modified_gmt'  => ( $product->get_date_modified() ? get_gmt_from_date( $product->get_date_modified(), self::DATE_FORMAT ) : '' ),
				'meta_data'          => $product->get_meta_data(),
			] );
	}

	/**
	 * @param WP_Comment $comment
	 * @param string     $event_type
	 *
	 * @since    1.0.0
	 */
	private function on_comment( $comment, $event_type ) {
		if ( $comment->comment_type != "review" ) {
			return;
		}

		$customer = [
			'email' => $comment->comment_author_email,
		];
		if ( class_exists( 'WC_Customer' ) && $comment->user_id !== 0 ) {
			$customer = $this->customer_to_array( new WC_Customer( $comment->user_id ) );
		}

		$rating = 0;
		$meta   = get_comment_meta( $comment->comment_ID );
		if ( isset( $meta["rating"] ) ) {
			$rating = (int) ( is_array( $meta["rating"] ) ? $meta["rating"][0] : $meta["rating"] );
		} elseif ( ! empty( $_POST ) ) {
			$rating = (int) ( is_array( $_POST["rating"] ) ? $_POST["rating"][0] : $_POST["rating"] );
		}
		if ( $rating === 0 ) {
			return;
		}
		$date = DateTime::createFromFormat( "Y-m-d H:i:s", $comment->comment_date_gmt );

		$data = [
			"event_type"  => $event_type,
			"api_path"    => self::WC_EVENT_PATH,
			"shop_domain" => $this->get_shop_domain(),
			"review"      => array_merge( $meta,
				[
					"id"                => (int) $comment->comment_ID,
					"product_id"        => (int) $comment->comment_post_ID,
					"reviewer"          => $comment->comment_author,
					"reviewer_email"    => $comment->comment_author_email,
					"review"            => $comment->comment_content,
					"rating"            => $rating,
					"verified"          => (bool) ( $meta["verified"] ?? false ),
					'date_created_gmt'  => $date ? $date->format( self::DATE_FORMAT ) : "",
					'date_modified_gmt' => ( new \DateTime() )->format( self::DATE_FORMAT ),
					"status"            => wp_get_comment_status( $comment ),

				] ),
			"customer"    => $customer,
			"comment"     => $comment->to_array(),
		];

		$options = [
			'http' => [
				'header'  => "Content-type: application/json",
				'method'  => 'POST',
				'content' => json_encode( [
					"h"  => $this->options['tracking_key'],
					"e"  => $customer['email'] ?? "",
					"s"  => $_COOKIE['ap3c'] ?? "",
					"wc" => $data,
				] ),
			],
		];
		$context = stream_context_create( $options );
		if ( isset( $customer['email'] ) || $_COOKIE['ap3c'] ) {
			if ( empty( $this->options["capture_api_url"] ) ) {
				$this->options["capture_api_url"] = self::API_URL;
			}
			$apiURL = $this->options["capture_api_url"];
			if ( empty( $apiURL ) ) {
				$apiURL = self::API_URL;
			}
			file_get_contents( $apiURL . self::WC_EVENT_PATH, false, $context );
		}
	}

	private function enrich_product_webhooks( $payload, $resource, $resource_id, $id ) {
		$product = wc_get_product( $resource_id );
		if ( $product->get_type() !== 'variable' ) {
			return $payload;
		}
		if ( ! $product instanceof WC_Product ) {
			return $payload;
		}

		// Remove the filter to eliminate the recursion calls.
		remove_filter( 'woocommerce_webhook_payload', [ $this, 'enrich_webhooks' ], 10 );

		$webhook    = wc_get_webhook( $id );
		$variations = [];
		foreach ( $payload['variations'] as $variation ) {
			$variations[] = $webhook->build_payload( $variation );
		}
		$payload['ap3_variations'] = $variations;

		// Add the filter again and return the payload.
		add_filter( 'woocommerce_webhook_payload', [ $this, 'enrich_webhooks' ], 10, 4 );

		return $payload;
	}

	private function enrich_order_webhooks( $payload, $resource, $resource_id, $id ) {
		$order = wc_get_order( $resource_id );
		if ( ! $order instanceof WC_Order ) {
			return $payload;
		}

		$total_spent = doubleval( wc_get_customer_total_spent( $order->get_customer_id() ) );
		if ( $total_spent > 0 ) {
			$payload['customer_total_spent'] = $total_spent;
		}

		return $payload;
	}

	private function enrich_customer_webhooks( $payload, $resource, $resource_id, $id ) {
		$total_spent = doubleval( wc_get_customer_total_spent( $resource_id ) );
		if ( $total_spent > 0 ) {
			$payload['total_spent'] = $total_spent;
		}

		return $payload;
	}

	private function get_shop_domain() {
		$urlParts = parse_url( wc_get_page_permalink( 'shop' ) );

		return $urlParts['scheme'] . "://" . $urlParts['host'] . "/";
	}
}
