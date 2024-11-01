<?php

declare( strict_types = 1 );

namespace ShipSmart\Providers\Config;

use WPSteak\Providers\AbstractHookProvider;

// phpcs:ignoreFile
class SSFW_Product extends AbstractHookProvider {
    public function register_hooks(): void {
        add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_hs_code_field' ), 10, 0 );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_hs_code_field' ) );
        add_filter( "woocommerce_product_export_column_names", array($this, 'sv_wc_csv_export_modify_column_headers_example'), 10 );
        add_filter( 'woocommerce_product_export_product_default_columns', array($this, 'sv_wc_csv_export_modify_column_headers_example') );
        add_filter( 'woocommerce_product_export_product_column_hs_code', array($this, 'export_meta_data'), 10, 2 );
    }

    public function sv_wc_csv_export_modify_column_headers_example( $column_headers ) {
        $column_headers['hs_code'] = 'HS Code';

        return $column_headers;
    }

    public function export_meta_data( $value, $product ) {
        $hs_code = get_post_meta( $product->get_id(), 'ss_hs_code_product' );

        if ( count( $hs_code ) ) {
            return $hs_code[0];
        }

        return $hs_code;
    }

    public function add_hs_code_field() {
        $args = array(
            'id' => 'hs_code_product',
            'label' => __( 'HS Code', 'shipsmart' ),
            'class' => 'ShipSmart__product-input',
            'value' => get_post_meta( get_the_ID(), 'ss_hs_code_product', true ),
            'desc_tip' => true,
            'description' => __( 'Coloque o HS Code aqui.', 'shipsmart' ),
        );
        woocommerce_wp_text_input( $args );
    }

    public function save_hs_code_field( $post_id ) {
        $inputs_post = filter_input_array( INPUT_POST );
        $product = wc_get_product( $post_id );
        $title = isset( $inputs_post['hs_code_product'] ) ? $inputs_post['hs_code_product'] : '';
        $product->update_meta_data( 'ss_hs_code_product', sanitize_text_field( $title ) );
        $product->save();
    }

}