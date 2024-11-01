<?php

declare(strict_types = 1);

namespace ShipSmart\Providers\Config;

use Exception;
use ShipSmart\Services\SSFW_ApiService;
use WPSteak\Providers\AbstractHookProvider;

class SSFW_Api extends AbstractHookProvider {

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'ssfw_register_rest_api' ) );
		
		add_action( 'init', array( $this, 'add_cors_http_header' ) );
	}

	public function add_cors_http_header(){
		header("Access-Control-Allow-Origin: *");
	}

	public function ssfw_register_rest_api(): void {
		register_rest_route(
			'shipsmart/v1',
			'/api/test',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'test_api' ),
					'permission_callback' => 'is_user_logged_in',
				),
			),
		);
		register_rest_route(
			'shipsmart/v1',
			'/sycroOrder',
			array(
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'sycro_with_shipsmart' ),
					'permission_callback' => 'is_user_logged_in',
				),
			),
		);
		register_rest_route(
			'shipsmart/v1',
			'/pdf',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'generate_pdf' ),
					'permission_callback' => '__return_true',
					'args' => [
						'order_id' => [
							'description' => 'ID do pedido.',
							'default' => 1,
							'type' => 'integer',
							'sanitize_callback' => 'absint',
						],
						'document_name' => [
							'description' => 'Nome do documento.',
							'type' => 'string',
							'default' => 'awb_document',
							'sanitize_callback' => 'sanitize_text_field',
						]
					]
				),
			),
		);

		register_rest_route(
			'shipsmart/v1',
			'/updateOrders',
			array(
				array(
					'methods' => 'PUT',
					'callback' => array( $this, 'update_orders' ),
					'permission_callback' => 'is_user_logged_in',
				),
			),
		);

		register_rest_route(
			'shipsmart/v1',
			'/saveBoxes',
			array(
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'save_boxes' ),
					'permission_callback' => 'is_user_logged_in',
				),
			),
		);

		register_rest_route(
			'shipsmart/v1',
			'/boxes',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'get_all_boxes' ),
					'permission_callback' => 'is_user_logged_in',
				),
			),
		);

		register_rest_route(
			'shipsmart/v1',
			'/order/items',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'get_items_by_order' ),
					'permission_callback' => 'is_user_logged_in',
					'args' => [
						'order_id' => [
							'description' => 'ID do pedido.',
							'default' => 1,
							'type' => 'integer',
							'sanitize_callback' => 'absint',
						]
					]
				),
			),
		);
	}

	public function test_api() {
		return SSFW_ApiService::valid_api_key( get_option( 'ss_apikey_shipsmart' ) );
	}

	public function get_items_by_order( \WP_REST_Request $request ) {
		$order = wc_get_order( $request->get_param( 'order_id' ) );
		$products = [];

		
		foreach ( $order->get_items() as $item_id => $item ) {
			$quantity = $item->get_quantity();
            $item_data = $item->get_data();
            $item_id = $item_data['product_id'];
			$product = wc_get_product( $item_id );

            foreach ( range( 0, $quantity - 1 ) as $index ) {
                array_push(
                    $products,
                    [
						'id' => $item_id,
						'name' => $item->get_name(),
						'description' => $product->get_title(),
						'price' => $product->get_price(),
						'hscode' => get_post_meta( $item_id, 'ss_hs_code_product', true ),
						'quantity' => 1,
						'weight' => $product->get_weight() ? $product->get_weight() : 0,
                        'measures' => [
                            'height' => $product->get_height() ? $product->get_height() : 0,
                            'width' => $product->get_width() ? $product->get_width() : 0,
                            'length' => $product->get_length() ? $product->get_length() : 0,
                        ],
                        'details' => [
                            'description' => $product->get_title(),
                            'price' => $product->get_price(),
                            'hscode' => get_post_meta( $item_id, 'ss_hs_code_product', true ),
                            'quantity' => 1,
                            'weight' => $product->get_weight() ? $product->get_weight() : 0,
                        ]
                    ]
                );
            }  
        }

		return wp_send_json(['items' => $products]);
	}

	public function get_all_boxes() {
		$index = 0;
		$boxes = [];

		while( !! $box = get_option( '_ss_box_'. $index .'_measure' ) ) {
			array_push( $boxes, $box );
			$index++;
		}

		return ['boxes' => $boxes];
	}

	public function save_boxes( \WP_REST_Request $request ) {
		return SSFW_ApiService::save_boxes( $request->get_param( 'measures' ) );
	}

	public function update_orders() {
		try {
			SSFW_ApiService::get_documents_order_callback();
			SSFW_ApiService::update_status_order_callback();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function sycro_with_shipsmart( \WP_REST_Request $request ) {
		return SSFW_ApiService::sycro_with_shipsmart( $request );
	}

	public function generate_pdf(\WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		$document_name = $request->get_param( 'document_name' );
		header("Content-type:application/pdf");
   		header(
			'Content-Disposition:attachment;filename=' . $document_name . '_' . get_post_meta( $order_id, 'ss_shipsmart_order_id', true ) . '.pdf'
		);
		echo esc_html( base64_decode( get_post_meta( $order_id, 'ss_document_' . $document_name, true ) ) );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'edit_post' );
		// return true;
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}
}
