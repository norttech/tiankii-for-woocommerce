<?php
namespace TiankiiPlugin;

use TiankiiPlugin\CurlWrapper;

/**
 * For calling Tiankii API
 */
class API {

	protected $store_id;
	protected $api_url = TIANKII_API_URL;
	protected $api_key = TIANKII_API_KEY;

	public function __construct( $store_id ) {
		$this->store_id = $store_id;
	}

	public function createInvoice( $amount, $currency, $order_id ) {

		error_log( "TIANKII: URL $this->api_url" );

		$c     		 = new CurlWrapper();
		$order 		 = wc_get_order( $order_id );
		$billing 	 = $order->get_address( 'billing' );
		$customer_id = $order->get_customer_id();

		error_log( "TIANKII: amount in smallest unit $amount $currency" );

		$headers = array(
			'Content-Type' => 'application/json',
			'Api_key' => $this->api_key
		);

		$key      = $order->get_order_key();
		$site_url = site_url();

		$posData = array(
			'order_id' => $order_id,
			'uniq_key'  => $key
		);

		$buyer = array(
			'customerId' 	=> "$customer_id",
			'buyerName' 	=> $billing[ 'first_name' ] .' '. $billing[ 'last_name' ],
			'buyerEmail' 	=> $billing[ 'email' ],
			'buyerPhone' 	=> $billing[ 'phone' ],
			'buyerCountry' 	=> $billing[ 'country' ],
			'buyerZip' 		=> $billing[ 'postcode' ],
			'buyerState' 	=> $billing[ 'state' ],
			'buyerCity' 	=> $billing[ 'city' ],
			'buyerAddress1' => $billing[ 'address_1' ],
			'buyerAddress2' => $billing[ 'address_2' ] 
		);

		$metadata = array(
			'orderId' => "$order_id",
			'description' => "#$order_id". ' Payment WooCommerce.',
			'posData' => json_encode($posData)
		);


		$data = array(
			'amount'    => $amount,
			'currency'  => $currency,
			'storeId'   => trim($this->store_id),
			'metadata'  => $metadata,
			'buyer'		=> $buyer
		);

		$response = $c->post( "$this->api_url/v1/invoice", array(), json_encode( $data ), $headers );

		error_log( 'Send invoice status ===>' . $response['status'] );

		return $response;
	}

	public function check_invoice_status( $order_id ) {
		error_log( 'TIANKII: check_invoice_status' );

		$c       = new CurlWrapper();
		$order   = wc_get_order( $order_id );
		$headers = array(
			'Content-Type' => 'application/json',
			'Api_key' => $this->api_key
		);
		$invoice_id = $order->get_meta( 'tiankii_invoiceId', $order_id, true );
		
		error_log( "TIANKII: check_invoice_status invoice_id = $invoice_id" );

		$data = array(
			'invoiceId'  => trim($invoice_id),
		);
		
		$response = $c->get( "$this->api_url/v1/invoice/status", array($data), $headers );
		error_log( 'Check order status ===>' . $response['status'] );
		return $response;
	}
}
