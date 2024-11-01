<?php

// phpcs:ignoreFile

declare(strict_types = 1);

namespace ShipSmart\Services;

use ShipSmart\Services\SSFW_Box;
use ShipSmart\Services\SSFW_Shipping_Method_Service;

class SSFW_ApiService {
	public const API_BASE_URL = 'https://shipsmart.com.br/shipapi/api';

	public static function wc_get_cart_item_data_hash( $product ) {
		return md5(
			wp_json_encode(
				apply_filters(
					'woocommerce_cart_item_data_to_validate',
					array(
						'type'       => $product->get_type(),
						'attributes' => 'variation' === $product->get_type() ? $product->get_variation_attributes() : '',
					),
					$product
				)
			)
		);
	}

	public static function valid_api_key( string $api_key ) {
		$args = [
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json'
			),
			'body' => '
				{
					"typeRequest":"doc",
					"shipper":{
						"country":"US",
						"postalCode":"05424010",
						"city":"TIROL"
					},
					"receiver":{
						"country":"US",
						"postalCode":"05424010",
						"city":"TIROL"
					},
					"boxes":[
						{
							"id":1,
							"measures":{
								"height":1,
								"width":1,
								"depth":1
							},
							"weight":0.756,
							"price":99.99
						},
						{
							"id":2,
							"measures":{
								"height":1,
								"width":1,
								"depth":1
							},
							"weight":1,
							"price":59.99
						}
					],
					"options":{
						"insured":true,
						"currencyCode":"USD"
					}
				}
			',
			'data_format' => 'body'
		];

		$url = self::API_BASE_URL . "/v3/cotation/landed-cost";

		$response = wp_remote_post( $url, $args );
		$response_json = json_decode( wp_remote_retrieve_body( $response ), true );

		return $response_json['status'] === 'SUCCESS';
	}

	public static function ssfw_cotation_request( array $config, $items = [], $order = null, $boxes = [] ): array {
		$cart_items = count($items) ? $items : WC()->cart->get_cart();

		$body_request = self::parse_cotation_body( $cart_items, $config, $order, $boxes );

		$args = [
			'headers' => array(
				'Authorization' => 'Bearer ' . get_option( 'ss_apikey_shipsmart' ),
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $body_request ),
			'data_format' => 'body'
		];

		$url = self::API_BASE_URL . "/v3/cotation/landed-cost";

		write_log( 'Argumentos para a cotação:' );
		write_log( $args );
		
		if ( isset( $body_request['boxes']['error'] ) ) {
			return [
				'status' => 'ERROR',
				'messages' => [
					'text' => $body_request['boxes']['error']
				]
			];
		}

		$response = wp_remote_post( $url, $args );

		write_log( 'Retorno da cotação:' );
		write_log( json_decode( wp_remote_retrieve_body( $response ), true ) );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	public static function parse_cotation_body( $items, $config, $order = null, $boxes = [] ): array {
		global $woocommerce;
		$order_data = $order ? $order->get_data() : null;

		if ( ! $order_data ) {
			$customer_city = $woocommerce->customer->get_shipping_city();
			$customer_postalcode = $woocommerce->customer->get_shipping_postcode();
			$customer_country = $woocommerce->customer->get_shipping_country();
		}

		$payload = [];
		$country = get_option( 'woocommerce_default_country' );
		$split_country = explode( ":", $country );
		$store_country = $split_country[0];

		$payload['typeRequest'] = "not_doc";
		$payload['pagar'] = $config['taxable'] === 'yes' ? 'ddp' : 'dap';

		$payload['shipper'] = [
			"country" => $store_country,
			"postalCode" => get_option( 'woocommerce_store_postcode' ),
			"city" => get_option( 'woocommerce_store_city' )
		];

		$payload['receiver'] = [
			"country" => $order_data ? $order_data['billing']['country'] : $customer_country,
			"postalCode" =>  $order_data ? $order_data['billing']['postcode'] : $customer_postalcode,
			"city" =>  $order_data ? $order_data['billing']['city'] : $customer_city
		];

		$payload['options'] = [
			"insured" => $config['insurance'] === 'yes',
			"taxable" => true,
			"currencyCode" => get_option( 'woocommerce_currency' )
		];

		if ( count($boxes) ) {
			$payload['boxes'] = $boxes;
		} else {
			$payload['boxes'] = SSFW_Box::get_boxes_by_cart( $items, false, '', $config['box_dimension'] );
		}

		return $payload;
	}

	public static function send_order( $order_id, $note_key = '', $boxes = [] ) {
		$url = self::API_BASE_URL . "/v2/cotation/create/order";
		$body_request = self::parse_order_body( $order_id, $note_key, $boxes );

		if ( ! $body_request ) {
			return false;
		}


		if ( isset( $body_request['boxes']['error'] ) ) {
			return [
				'status' => 'ERROR',
				'messages' => [
					'text' => $body_request['boxes']['error']
				]
			];
		}

		$args = [
			'headers' => array(
				'Authorization' => 'Bearer ' . get_option( 'ss_apikey_shipsmart' ),
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $body_request ),
			'data_format' => 'body'
		];

		write_log( 'Argumentos para o pedido na Ship Smart:' );
		write_log( $args );

		$response = wp_remote_post( $url, $args );

		write_log( 'Retorno da Ship Smart:' );
		write_log( json_decode( wp_remote_retrieve_body( $response ), true ) );

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	public static function parse_order_body( $order_id, $note_key = '', $boxes = [] ) {
		$products = [];
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();
		$payload = [];
		$shipping_method = null;
		$box_dimension = [];
		$federalTaxId = '';
		
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$product->quantity = $item->get_quantity();
			array_push( $products, $product );
		}

		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$shipping_method_id = $item->get_method_id();
			$shipping_method = SSFW_Shipping_Method_Service::get_shipping_method( $shipping_method_id );			
		}

		if ( ! $note_key && $shipping_method->get_option( 'is_federal_tax_id' ) === 'yes' ) {
			return false;
		}

		if ( $shipping_method->get_option( 'is_federal_tax_id' ) === 'yes' ) {
			$federalTaxId = $shipping_method->get_option( 'federal_tax_id' );
		}

		if ( count($boxes) ) {
			$payload['boxes'] = $boxes;
		} else {
			$box_id = $shipping_method->get_option( 'box_default' );
	
			if ( $box_id ) {
				$box_dimension = get_option( '_ss_box_'. ( $box_id - 1 ) .'_measure' );
				$box_dimension = $box_dimension ? $box_dimension : [];
			}

			if ( $shipping_method->get_option( 'is_federal_tax_id' ) === 'yes' ) {
				$federalTaxId = $shipping_method->get_option( 'federal_tax_id' );
				$payload['boxes'] = SSFW_Box::get_boxes_by_cart( $products, true, $note_key, $box_dimension );
			} else {
				$payload['boxes'] = SSFW_Box::get_boxes_by_cart( $products, true, '', $box_dimension );
			}
		}

		$country = get_option( 'woocommerce_default_country' );
		$split_country = explode( ":", $country );
		$store_country = $split_country[0];
		$store_state = preg_replace( '/:/' , '', $split_country[1] );		

		$payload['pagar'] = $shipping_method->get_option('taxable') === 'yes' ? 'ddp' : 'dap';

		$payload['shipper'] = [
			"name" => $shipping_method->get_option( 'name' ),
			"phone" => $shipping_method->get_option( 'phone' ),
			"email" => $shipping_method->get_option( 'email' ),
			"address" => get_option( 'woocommerce_store_address' ),
			"city" => get_option( 'woocommerce_store_city' ),
			"state" => $store_state,
			"postalCode" => get_option( 'woocommerce_store_postcode' ),
			"countryCode" => $store_country,
			"countryName" => WC()->countries->countries[$store_country],
			"federalTaxId" => $federalTaxId,
		];

		$payload['receiver'] = [
			"name" => $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'],
			"phone" => $order_data['billing']['phone'],
			"email" => $order_data['billing']['email'],
			"address" =>  $order_data['billing']['address_1'],
			"city" => $order_data['billing']['city'],
			"state" => $order_data['billing']['state'],
			"postalCode" => $order_data['billing']['postcode'],
			"countryCode" => $order_data['billing']['country'],
			"countryName" => WC()->countries->countries[$order_data['billing']['country']]
		];

		$payload['options'] = [
			"insured" => $shipping_method->get_option('insurance') === 'yes',
			"taxable" => true,
			"currencyCode" => get_option( 'woocommerce_currency' )
		];

		$response_cotation = self::ssfw_cotation_request(
			[
				'insurance' => $shipping_method->get_option('insurance'),
				'taxable' => $shipping_method->get_option('taxable')
			],
			$products,
			$order,
			$boxes
		);

		if ( $response_cotation['status'] === 'SUCCESS' ) {
			$payload['cost'] = [
				"currencyCode" => $response_cotation['messages']['result']['currencyCode'],
				"freight" => $response_cotation['messages']['result']['costFreight'],
				"tax" => $response_cotation['messages']['result']['costTax'],
				"insurance" => $response_cotation['messages']['result']['costInsurance']
			];
		}

		return $payload;
	}

	public static function update_post_meta( $post_id, $meta_key, $meta_value ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = %d && meta_key = %s;";
		$wpdb->get_results( $wpdb->prepare( $query, $post_id, $meta_key ) );

		if ( $wpdb->num_rows ) {
			$query = "UPDATE {$wpdb->prefix}postmeta SET meta_value=%s WHERE post_id = %d && meta_key = %s;";
			$wpdb->query( $wpdb->prepare( $query, $meta_value, $post_id, $meta_key ) );
		} else {
			$query = "INSERT INTO {$wpdb->prefix}postmeta (post_id,meta_key,meta_value) VALUES (%d, %s, %s);";
			$wpdb->query( $wpdb->prepare( $query, $post_id, $meta_key, $meta_value ) );        
		}
	}

	public static function sycro_with_shipsmart( \WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		$note_key = wc_clean( $request->get_param( 'note_key' ) );
		$boxes = $request->get_param( 'boxes' );

		self::update_post_meta( $order_id, 'ss_note_key', $note_key );

		$response = self::send_order( $order_id, $note_key, $boxes );

        if ( ! $response ) {
            self::update_post_meta( $order_id, 'ss_status_order', 'error' );
            self::update_post_meta( $order_id, 'ss_message', 'Por favor, insira a chave de nota deste pedido!' );
            return true;
        }

        if ( $response['status'] !== 'SUCCESS' ) {
            self::update_post_meta( $order_id, 'ss_status_order', 'error' );
            self::update_post_meta( $order_id, 'ss_message', $response['messages']['text'] );
            return true;
        }

        self::update_post_meta( $order_id, 'ss_status_order', 'success' );
        self::update_post_meta( $order_id, 'ss_message', 'Pedido sicronizado com a Ship Smart com sucesso!' );
        self::update_post_meta( $order_id, 'ss_shipsmart_order_id', $response['messages']['result']['order'] );

		return true;
	}

	public static function update_status_order_callback() {

		$args = [
			'headers' => array(
				'Authorization' => 'Bearer ' . get_option( 'ss_apikey_shipsmart' ),
				'Content-Type' => 'application/json'
			),
		];

		$orders = wc_get_orders( ['return' => 'ids'] );

		foreach ( $orders as $order_id ) {
			if ( get_post_meta( $order_id, 'ss_status_category', true ) !== 'ENTREGUE' && get_post_meta( $order_id, 'ss_shipsmart_order_id', true )) {
				$url = self::API_BASE_URL . "/v3/order/status/" . get_post_meta( $order_id, 'ss_shipsmart_order_id', true );

				$response = wp_remote_get( $url, $args );

				$response_json = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $response_json['status'] ) && $response_json['status'] !== 'SUCCESS' ) {
					self::update_post_meta( $order_id, 'ss_status_category', 'Aviso' );
					self::update_post_meta( $order_id, 'ss_status_description', $response_json['messages']['text'] );
					self::update_post_meta( $order_id, 'ss_status_date', date('d:m:Y - H:i:s') );
				} else {
					self::update_post_meta( $order_id, 'ss_status_category', $response_json['messages']['result']['category'] );
					self::update_post_meta( $order_id, 'ss_status_description', $response_json['messages']['result']['description'] );
					self::update_post_meta( $order_id, 'ss_status_date', $response_json['messages']['result']['date_time'] );
				}
			}
		}
	}

	public static function get_documents_order_callback() {
		$orders = wc_get_orders( ['return' => 'ids'] );

		foreach ( $orders as $order_id ) {
			$shipsmart_order_id = get_post_meta( $order_id, 'ss_shipsmart_order_id', true );

			if ( $shipsmart_order_id ) {
				$body = [
					"orders" => [
						$shipsmart_order_id
					]
				];
				
				$args = [
					'headers' => array(
						'Authorization' => 'Bearer ' . get_option( 'ss_apikey_shipsmart' ),
						'Content-Type' => 'application/json'
					),
					'body' => json_encode( $body ),
					'data_format' => 'body'
				];
		
				$url = self::API_BASE_URL . "/v2/order/docs";
				
				$response = wp_remote_post( $url, $args );
				
				$response_json = json_decode( wp_remote_retrieve_body( $response ), true );
				foreach ( $response_json as $order ) {
					if (  count( $order ) && is_array( $order[$shipsmart_order_id] ) ) {
						self::update_post_meta( $order_id, 'ss_code_tracking', $order[$shipsmart_order_id]['awbNumber'] );
						self::update_post_meta( $order_id, 'ss_document_packinlist', $order[$shipsmart_order_id]['pathPackinglist'] );
						self::update_post_meta( $order_id, 'ss_document_invoice', $order[$shipsmart_order_id]['pathInvoice'] );
						self::update_post_meta( $order_id, 'ss_document_awb_document', $order[$shipsmart_order_id]['awbDocument'] );
						self::update_post_meta( $order_id, 'ss_document_path_declaration', $order[$shipsmart_order_id]['pathDeclaration'] );
					} else {
						self::update_post_meta( $order_id, 'ss_code_tracking', 'Dados indisponíveis' );
						self::update_post_meta( $order_id, 'ss_document_packinlist', '' );
						self::update_post_meta( $order_id, 'ss_document_invoice', '' );
						self::update_post_meta( $order_id, 'ss_document_awb_document', '' );
						self::update_post_meta( $order_id, 'ss_document_path_declaration', '' );
					}
				}
			}
		}
	}

	public static function save_boxes( array $measures ) {
		$index = 0;

		while( !! $box = get_option( '_ss_box_'. $index .'_measure' ) ) {
			delete_option( '_ss_box_'. $index .'_measure' );
			$index++;
		}

		$index = 0;

		foreach ( $measures as $index => $measure ) {
			$key = '_ss_box_' . $index . '_measure';
			update_option( $key, $measure, true );
		}

		return true;
	}
}
