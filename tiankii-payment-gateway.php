<?php

/*
 * Plugin Name: Bitcoin Payments by tiankii⚡️
 * Plugin URI: https://github.com/TiankiiApp/tiankii-for-woocommerce
 * Description: The easiest and fastest way to accept Bitcoin payments in your WooCommerce store.
 * Version: 1.0.5
 * Author: tiankii
 * Author URI: https://pay.tiankii.com
 * Text Domain: tiankii-payment-gateway
 * License: MIT
 */

add_action( 'plugins_loaded', 'tiankii_server_init' );

define( 'TIANKII_APP_URL', getenv( 'TIANKII_APP_URL' ) ? getenv( 'TIANKII_APP_URL' ) : 'https://pay.tiankii.com' );
define( 
	'TIANKII_API_KEY', 
	getenv( 
		'TIANKII_API_KEY' ) ? 
		getenv( 'TIANKII_API_KEY' ) :
		'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc1BPUyI6dHJ1ZSwiaWF0IjoxNjk0NDY5Mjg1LCJleHAiOjE2OTQ0NzI4ODV9.X1oy0bCZ3lgi3m7MMcZlOg8M8KdgPm3Ow1Wih42Rv9E' 
);

define( 'TIANKII_API_URL', getenv( 'TIANKII_API_URL' ) ? 
getenv( 'TIANKII_API_URL' ) : 'https://tk-api-ezsqwhuiaa-uk.a.run.app' );

define('TIANKII_PAY_URL', TIANKII_APP_URL);
define('TIANKII_PAY_CODE', 'woo_commerce');

define( 'TIANKII_WOOCOMMERCE_VERSION', '1.0.0' );

define( 'WC_PAYMENT_GATEWAY_TIANKII_FILE', __FILE__ );
define( 'WC_PAYMENT_GATEWAY_TIANKII_URL', plugins_url( '', WC_PAYMENT_GATEWAY_TIANKII_FILE ) );
define( 'WC_PAYMENT_GATEWAY_TIANKII_ASSETS', WC_PAYMENT_GATEWAY_TIANKII_URL . '/assets' );

require_once __DIR__ . '/includes/hooks.php';
register_activation_hook( __FILE__, 'tiankii_message_on_plugin_activate' );
add_action( 'admin_notices', 'tiankii_admin_notices' );

require_once __DIR__ . '/includes/init.php';

use TiankiiPlugin\Utils;
use TiankiiPlugin\API;

// This is the entry point of the plugin, where everything is registered/hooked up into WordPress.
function tiankii_server_init() {

	if (!class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	// Register the gateway, essentially a controller that handles all requests.
	add_filter( 'woocommerce_payment_gateways', 'add_tiankii_server_gateway' );
	function add_tiankii_server_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Tiankii_Server';
		return $methods;
	}

	// Defined here, because it needs to be defined after WC_Payment_Gateway is already loaded.
	class WC_Gateway_Tiankii_Server extends WC_Payment_Gateway {

		public $api;

		public function __construct() {
			global $woocommerce;

			$this->id                 = 'tiankii';
			$this->has_fields         = false;
			$this->method_title       = 'Tiankii';
			$this->method_description = __( 'Bitcoin payments made easy. Accept lightning payments in one unified Tiankii Checkout experience.', 'tiankii-payment-gateway' );

			$this->init_form_fields();
			$this->init_settings();

			$this->title       = $this->get_option( 'title' );
			$this->description = 'The payment method description which a customer sees at the checkout of your store.';

			$url     = $this->get_option( 'tiankii_server_url' );
			$store_id = $this->get_option('tiankii_store_id');

			$this->api = new API( $store_id );

			if ( $this->get_option( 'payment_image' ) == 'yes' ) {
				$this->icon = Utils::get_icon_image_url();
			}

			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array(
					$this,
					'process_admin_options',
				)
			);
			add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'check_payment' ) );
		}

		/**
		 * Render admin options/settings.
		 */
		public function admin_options() {
			?>
			<h3>
				<?php esc_html_e( 'Tiankii', 'tiankii-payment-gateway' ); ?>
			</h3>
			<p>
				<?php
					echo wp_kses(
						__(
							"Accept bitcoin lightning payments instantly through your hosted Tiankii Checkout. Enable the Woo connection on the Tiankii Connectors page then paste the provided storeID into the field below. <a href='https://' target='_blank' rel='noreferrer'>Setup Guide</a>",
							'tiankii-payment-gateway'
						),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					);
				?>
			</p>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
			<?php
		}

		/**
		 * Generate config form fields, shown in admin->WooCommerce->Settings.
		 */
		public function init_form_fields() {
			$host = $_SERVER['HTTP_HOST'];

			$this->form_fields = array(
				'enabled'         => array(
					'title'       => __('Enable Tiankii Payments', 'tiankii-payment-gateway'),
					'label'       => __('Enable payments via tiankii Checkout.', 'tiankii-payment-gateway'),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
				'title'           => array(
					'title'       => __('Payment Method Name', 'tiankii-payment-gateway'),
					'type'        => 'text',
					'description' => __('The payment method title which a customer sees at the checkout of your store.', 'tiankii-payment-gateway'),
					'default'     => __('Bitcoin Payment via tiankii ⚡️', 'tiankii-payment-gateway'),
				),
				'description'     => array(
					'title'       => __('Description', 'tiankii-payment-gateway'),
					'type'        => 'text',
					'description' => __('The payment method description which a customer sees at the checkout of your store.', 'tiankii-payment-gateway'),
					'placeholder' => __('Powered by Tiankii', 'tiankii-payment-gateway'),
					'default'     => __('You will be redirected to tiankii checkout to complete your purchase.', 'tiankii-payment-gateway')
				),
				'payment_image'   => array(
					'title'       => __('Show checkout Image', 'tiankii-payment-gateway'),
					'label'       => __('Show Tiankii image on checkout', 'tiankii-payment-gateway'),
					'type'        => 'checkbox',
					'description' => Utils::get_icon_image_html(),
					'default'     => __('yes', 'tiankii-payment-gateway'),
				),
				'tiankii_store_id' => array(
					'title'       => __('Tiankii StoreID', 'tiankii-payment-gateway'),
					'description' =>
						sprintf(
							/* translators: %s: URL to Woo store connection settings */
							__("Enter the Tiankii StoreID from your <a href='%s' target='_blank' rel='noopener noreferrer'>Tiankii connector settings</a>.", 'tiankii-payment-gateway'),
							esc_url(TIANKII_APP_URL . '/connectors/woo_commerce?wp_address=' . $host)
						),
					'type'        => 'text',
					'default'     => '',
				),
			);
		}

		/**
		 * Output for thank you page.
		 */
		public function thankyou() {
			error_log( 'thankyou called' );
			$description = $this->get_description();
			if ( $description ) {
				echo esc_html(wpautop(wptexturize( $description )));
			}
		}

		/**
		 * Called from checkout page, when "Place order" hit, through AJAX.
		 *
		 * Call Tiankii API to create an invoice, and store the invoice in the order metadata.
		 */
		public function process_payment( $order_id ) {
			$tiankii_url     = TIANKII_APP_URL;
			$tiankii_pay_url = TIANKII_PAY_URL;

			$order    = wc_get_order( $order_id );
			$amount   = $order->get_total();
			$currency = $order->get_currency();
			error_log( "TIANKII: Amount - $amount Currency: $currency" );

			$total_in_smallest_unit = Utils::getAmount( $amount );

			error_log( "TIANKII: currency in smallest unit $total_in_smallest_unit $currency" );

			// Call the TIANKII public api to create the invoice
			$r = $this->api->createInvoice( $total_in_smallest_unit, $currency, $order_id );
			
			if ( 201 === $r['status'] ) {
				$resp   = $r['response'];
				$status = $r['status'];
				error_log( "TIANKII: process_payment status $status" );

				// Access the orderId field
				$invoice_id = $r['response']['invoiceId'];
				error_log( "invoiceId => $invoice_id" );

				// save tiankii metadata in woocommerce
				$order->add_meta_data( 'tiankii_invoiceId', $invoice_id, true );
				$order->add_meta_data( 'tiankii_order_link', "$tiankii_url/i/$invoice_id", true );
				//$order->update_status( 'processing', 'Send to Tiankii ckeckout', true );
				$order->save();

				$pay_code  = TIANKII_PAY_CODE;
				$order_key = $order->get_order_key();
				$pay_order  = array(
					'order_id'  => "$order_id",
					'order_key' => "$order_key",
					'callback'  => home_url(),
					'callCancel' => urlencode($order->get_cancel_order_url()) 
				);
				$pay_order_encode  = base64_encode(json_encode($pay_order));
				$checkout_page_id  = get_option('woocommerce_checkout_page_id');
				$checkout_page_url = get_permalink($checkout_page_id);
				$back_url          = urlencode($checkout_page_url);
				$redirect_url      = "$tiankii_pay_url/i/$invoice_id?backUrl=$back_url&payCode=$pay_code&payOrder=$pay_order_encode";
				return array(
					'result'   => 'success',
					'redirect' => $redirect_url,
				);
			} else {
				error_log( 'TIANKII: API failure. Status: ' . $r['status'] );
				return array(
					'result'   => 'failure',
					'messages' => array( $r['response']['message'] ),
					'status' => $r['status']
				);
			}
		}
	}

	/**
	 * Custom REST Endpoints
	 */
	function register_custom_tiankii_rest_endpoints() {
		function tiankii_server_add_update_status_callback( $data ) {
			error_log( 'TIANKII: webhook tiankii_server_add_update_status_callback' );
			$order_id  = $data['id'];			
			$store_id  = ''; //Utils::getAuthorizationHeader();
			$api       = new API( $store_id );
			$get_invoice_status = $api->check_invoice_status( $order_id );

			if (200 !== $get_invoice_status['status'] ) {
				return new WP_Error(
					'server_error',
					'Could not get invoice status',
					array( 'status' => 500 )
				);
			}

			$order = wc_get_order( $order_id );

			// check invoice status
			$invoice_status = $get_invoice_status['response']['status'];
			error_log( "TIANKII: order status update from tiankii api - $invoice_status" );
			$woo_order_status = Utils::convert_tiankii_order_status_to_woo_status( $invoice_status );
			error_log( "TIANKII: wooStatus - $woo_order_status" );
	
			switch ( $woo_order_status ) {
				case 'processing':
					if (!$order->has_status( 'completed' ) ) {
						$order->update_status( 'completed', 'Order status updated via API.', true );
						$order->add_order_note( 'Payment is settled.' );
						$order->payment_complete();
						$order->save();
					}
					error_log( 'PAID' );
					echo ( wp_json_encode(
						array(
							'result'   => 'success',
							'redirect_url' => $order->get_checkout_order_received_url(),
							'paid'     => true,
						)
					) );
					break;
				case 'expired':
					$order->update_status( 'wc-failed', 'Order status updated via API.', true );
					$order->save();
					error_log( "TIANKII: update status - $woo_order_status" );
					
					break;
				case 'pending':
					return new WP_REST_Response( 'Order still on pending.', 200 );
					break;
				default:
					return new WP_REST_Response( 'Invalid order status.', 400 );
			}
			return new WP_REST_Response();
		}
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'tiankii_server/tiankii/v1',
					'/update_status/(?P<id>\d+)',
					array(
						'methods'             => 'GET',
						'callback'            => 'tiankii_server_add_update_status_callback',
						'permission_callback' => function ( WP_REST_Request $request ) {
							// verify order key
							$order_id       = $request['id'];
							$order_key = $request->get_param( 'key' ); // Assuming the order key is passed as a parameter
							if ( ! $order_id || ! $order_key ) {
								return false;
							}
							$order = wc_get_order( $order_id );
							if ( ! $order ) {
								return false;
							}
							// Check if the provided order key matches the order
							$order_keys_match = hash_equals( $order->get_order_key(), $order_key );
							// error_log( "Do keys match: $order_keys_match" );
	
							return $order_keys_match;
						},
					)
				);
			}
		);
	}

	register_custom_tiankii_rest_endpoints();
	 
	/**
	 * Filters and Actions
	 */
	function register_tiankii_filters_and_actions() {

		// Settings Link
		function tiankii_add_settings_link( $links ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=tiankii' ) . '">' . __( 'Settings', 'tiankii-payment-gateway' ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		// Setup Guide Link
		function tiankii_add_meta_links( $links, $file ) {
			$plugin_base = plugin_basename( __FILE__ );
			if ( $file == $plugin_base ) {
				// Add your custom links
				$new_links = array(
					'<a href="https://">' . __( 'Setup Guide', 'tiankii-payment-gateway' ) . '</a>',
					// Add more links as needed
				);
				$links = array_merge( $links, $new_links );
			}
			return $links;
		}

		// Set the cURL timeout to 15 seconds. When requesting a lightning invoice
		// If using Tor, a short timeout can result in failures.
		function tiankii_server_http_request_args( $r ) {
			// called on line 237
			$r['timeout'] = 15;
			return $r;
		}

		function tiankii_server_http_api_curl( $handle ) {
			curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 15 );
			curl_setopt( $handle, CURLOPT_TIMEOUT, 15 );
		}

		function add_custom_tiankii_order_status() {
			register_post_status(
				'wc-underpaid',
				array(
					'label'                     => 'Underpaid',
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					// Translators: %s: number of orders with 'Underpaid' status
					'label_count'               => _n_noop( 'Underpaid (%s)', 'Underpaid (%s)' ),
				)
			);
			register_post_status(
				'wc-overpaid',
				array(
					'label'                     => 'Overpaid',
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					// Translators: %s: number of orders with 'Overpaid' status
					'label_count'               => _n_noop( 'Overpaid (%s)', 'Overpaid (%s)' ),
				)
			);
			register_post_status(
				'wc-btc-pending',
				array(
					'label'                     => 'BTC Pending',
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					// Translators: %s: number of orders with 'BTC Pending' status
					'label_count'               => _n_noop( 'BTC Pending (%s)', 'BTC Pending (%s)' ),
				)
			);
		}

		function add_custom_tiankii_order_statuses( $order_statuses ) {
			$new_order_statuses = array();
			// add new order status after processing
			foreach ( $order_statuses as $key => $status ) {
				$new_order_statuses[ $key ] = $status;

				if ( 'wc-processing' === $key ) {
					$new_order_statuses['wc-underpaid']   = 'Underpaid';
					$new_order_statuses['wc-overpaid']    = 'Overpaid';
					$new_order_statuses['wc-btc-pending'] = 'BTC Pending';
				}
			}
			return $new_order_statuses;
		}

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tiankii_add_settings_link' );
		add_filter( 'plugin_row_meta', 'tiankii_add_meta_links', 10, 2 );
		add_filter( 'http_request_args', 'tiankii_server_http_request_args', 100, 1 );
		add_action( 'http_api_curl', 'tiankii_server_http_api_curl', 100, 1 );
		add_action( 'init', 'add_custom_tiankii_order_status' );
		add_filter( 'wc_order_statuses', 'add_custom_tiankii_order_statuses' );

		if ( class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once __DIR__ . '/includes/blocks-checkout.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					// error_log( 'TIANKII: PaymentMethodRegistry' );
					$payment_method_registry->register( new WC_Gateway_Tiankii_Blocks_Support() );
				}
			);
		}
	}

	register_tiankii_filters_and_actions();
}
