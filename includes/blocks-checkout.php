<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use TiankiiPlugin\Utils;

/**
 * Payments Blocks integration
 */
final class WC_Gateway_Tiankii_Blocks_Support extends AbstractPaymentMethodType {


	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Tiankii
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'tiankii';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_tiankii_settings', array() );
		$this->gateway  = new WC_Gateway_Tiankii_Server();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/js/frontend/blocks.js';
		$script_asset_path = WC_PAYMENT_GATEWAY_TIANKII_ASSETS . '/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '1.2.0',
			);
		$script_url        = WC_PAYMENT_GATEWAY_TIANKII_ASSETS . $script_path;

		wp_register_script(
			'wc-tiankii-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// if ( function_exists( 'wp_set_script_translations' ) ) {
		// wp_set_script_translations( 'wc-tiankii-payments-blocks', 'tiankii-payment-gateway', WC_Dummy_Payments::plugin_abspath() . 'languages/' );
		// }

		return array( 'wc-tiankii-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway->get_option( 'title' ),
			'description' => 'Powered by Tiankii', // TODO hardcode for now because it wont read from disabled setting
			'showImage'   => $this->gateway->get_option( 'payment_image' ),
			'image'       => Utils::get_icon_image_url(),
		);
	}
}
