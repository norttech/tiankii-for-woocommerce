<?php
namespace TiankiiPlugin;

class Utils {

	public static function getAmount($string_number) {
		// try a float
		$numero = floatval($string_number);
		
		// if float is 0, try a Integer
		if ($numero == 0 && $string_number !== '0') {
			$numero = intval($string_number);
		}
		
		if (is_float($numero)) {
			return $numero;
		} elseif (is_int($numero)) {
			return $numero;
		} else {
			return null; // En caso de no poder determinar el tipo numérico
		}
	}

	// convert tiankii to woo status
	public static function convert_tiankii_order_status_to_woo_status( $invoice_status ) {
		switch ( $invoice_status ) {
			case 'new':
				return 'pending'; // do nothing
			case 'complete':
			case 'paid':
				return 'processing'; // its paid or complete on tiankii but still processing to be shipped on woo
			case 'expired':
				return 'expired';
			default:
				return '';
		}
	}

	public static function get_icon_image_url() {
		$images_url = WC_PAYMENT_GATEWAY_TIANKII_ASSETS . '/images/';
		$icon_file  = 'tiankii-icon@2x.png';
		$icon_style = 'style="max-height: 44px !important;max-width: none !important;"';
		$icon_url   = $images_url . $icon_file;
		return $icon_url;
	}

	public static function get_icon_image_html() {
		$icon_url   = self::get_icon_image_url();
		$icon_style = 'style="max-height: 44px !important;max-width: none !important;"';
		$icon_html  = '<img src="' . $icon_url . '" alt="Tiankii logo" ' . $icon_style . ' />';
		return $icon_html;
	}

	
	public static function getAuthorizationHeader() {
		return explode( ' ', $_SERVER['HTTP_AUTHORIZATION'] ?? array_change_key_case(apache_request_headers(), CASE_LOWER)['authorization'] ?? '' )[1];
	}
}
