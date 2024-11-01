<?php

declare( strict_types = 1 );

namespace ShipSmart\Providers\Config;

use ShipSmart\Services\SSFW_ApiService;
use WPSteak\Providers\AbstractHookProvider;

// phpcs:ignoreFile
class SSFW_Order extends AbstractHookProvider {

    public function register_hooks(): void {
        add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_order_meta_data' ), 10 );
        add_action( 'woocommerce_thankyou', array( $this, 'action_woocommerce_thankyou' ), 10, 1 );
        add_action( 'admin_notices', array( $this, 'empty_note_key_error' ) );
        add_action( 'add_meta_boxes', array( $this, 'shipsmart_box' ) );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'ss_order_column' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'ss_order_column_values' ) );
        add_action( 'update_status_orders_cron', array( $this, 'execute_update_status_order' ) );
        add_action( 'get_documents_orders_cron', array( $this, 'execute_get_documents_order' ) );
        add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'get_meta_description_in_order_page' ), 20, 3 );
        add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'add_meta_description_in_order_page' ), 20, 4 ); 
        add_action( 'init', array( $this, 'update_status_orders' ) );
        add_action( 'init', array( $this, 'get_documents_orders' ) );
    }
    public function get_meta_description_in_order_page(  $key, $meta, $item ) {
        if ( 'freight' === $meta->key ) { 
            $key = __( get_option( 'ss_checkout_freight_' . get_current_user_id() ), 'shipsmart');
        } else if ( 'taxable' === $meta->key ) {
            $key = __( get_option( 'ss_checkout_taxable_' . get_current_user_id() ), 'shipsmart');
        } else if ( 'insurance' === $meta->key ) {
            $key = __( get_option( 'ss_checkout_insurance_' . get_current_user_id() ), 'shipsmart');
        }
     
        return $key;
    }

    public function add_meta_description_in_order_page( &$item, $package_key, $package, $order ) {
        $item->add_meta_data( 'freight', get_option( 'ss_checkout_freight_cost_' . get_current_user_id() ), true );
        $item->add_meta_data( 'taxable', get_option( 'ss_checkout_taxable_cost_' . get_current_user_id() ), true );
        $item->add_meta_data( 'insurance', get_option( 'ss_checkout_insurance_cost_' . get_current_user_id() ), true );
    }

    public function update_status_orders(): void {
        if ( wp_next_scheduled( 'update_status_orders_cron' ) ) {
            return;
        }

        wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', 'update_status_orders_cron' );
    }

    public function get_documents_orders(): void {
        if ( wp_next_scheduled( 'get_documents_orders_cron' ) ) {
            return;
        }

        wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', 'get_documents_orders_cron' );
    }

    public function execute_update_status_order() {
        SSFW_ApiService::get_documents_order_callback();
    }
    
    public function execute_get_documents_order() {
        SSFW_ApiService::update_status_order_callback();
    }

    public function ss_order_column_values( $column ) {
        global $post;

        if ( 'ss_tracking_status' === $column && get_post_meta( $post->ID, 'ss_shipsmart_order_id', true ) ) {
            $ss_status_description =  get_post_meta( $post->ID, 'ss_status_description', true ) ? get_post_meta( $post->ID, 'ss_status_description', true ) : 'Aguardando retorno da Shipsmart';
            $ss_status_category = get_post_meta( $post->ID, 'ss_status_category', true ) ? get_post_meta( $post->ID, 'ss_status_category', true ) : 'Aguardando';
            echo '<mark class="order-status status-processing tips" data-tip="' . esc_html( $ss_status_description ) . '">';
            echo '<span>' . esc_html(  $ss_status_category ) . '</span>';
            echo '</mark>';
        }
    }

    public function ss_order_column( $columns ) {
        $new_columns = array();

        foreach ( $columns as $column_name => $column_info ) {
    
            if ( 'order_total' === $column_name ) {
                $new_columns['ss_tracking_status'] = 'Ship Smart - Frete';
            }

            $new_columns[ $column_name ] = $column_info;
        }

        return $new_columns;
    }

    public function shipsmart_box() {
        add_meta_box(
            'shipsmart_box',
            'Dados Frete - ShipSmart',
            array( $this, 'shipsmart_box_callback' ),
            'shop_order',
            'normal',
            'core'
        );
    }

    public function shipsmart_box_callback() {
        global $post;
        
        if ( ! isset( $post->ID ) ) {
            return;
        }

        $link_tracking = 'https://www.shipsmart.com.br/sistema/tracking/?order_id=' . get_post_meta( $post->ID, 'ss_shipsmart_order_id', true );
        $status_category = get_post_meta( $post->ID, 'ss_status_category', true ) ? get_post_meta( $post->ID, 'ss_status_category', true ) : 'Aguardando retorno da Shipsmart';
        $status_date = get_post_meta( $post->ID, 'ss_status_date', true ) ? get_post_meta( $post->ID, 'ss_status_date', true ) : 'Aguardando retorno da Shipsmart';
        $status_description = get_post_meta( $post->ID, 'ss_status_description', true ) ? get_post_meta( $post->ID, 'ss_status_description', true ) : 'Aguardando retorno da Shipsmart';
        $code_tracking = get_post_meta( $post->ID, 'ss_code_tracking', true ) ? get_post_meta( $post->ID, 'ss_code_tracking', true ) : 'Aguardando retorno da Shipsmart';
        $document_packinlist = get_post_meta( $post->ID, 'ss_document_packinlist', true ) ? get_post_meta( $post->ID, 'ss_document_packinlist', true ) : '';
        $document_invoice = get_post_meta( $post->ID, 'ss_document_invoice', true ) ? get_post_meta( $post->ID, 'ss_document_invoice', true ) : '';
        $document_awb_document = get_post_meta( $post->ID, 'ss_document_awb_document', true ) ? '/wp-json/shipsmart/v1/pdf?&order_id=' . $post->ID  : '';
        $document_path_declarition = get_post_meta( $post->ID, 'ss_document_path_declaration', true ) ? get_post_meta( $post->ID, 'ss_document_awb_document', true ) : '';
        $index = 0;

        echo '<div class="ShipSmart__order-notekey">';

        woocommerce_wp_text_input(
            array(
                'id' => 'note_key',
                'label' => 'Chave da Nota:',
                'value' => get_post_meta( $post->ID, 'ss_note_key', true ),
                'class'   => 'ShipSmart__order-input',
                'wrapper_class' => 'ShipSmart__order-field form-field-wide',
            )
        );

        echo '<div class="ShipSmart__box">';
        
        echo '<div class="ShipSmart__box-options">';
        echo '<select onchange="handleChangeBoxOption()" class="ShipSmart__order-boxes">';
        echo '<option value="0">Caixa Padrão</option>';

        while( !! $box = get_option( '_ss_box_'. $index .'_measure' ) ) {
            $option_name = $box['weight'] . 'kg - ' . $box['height'] .  'cm - ' . $box['width'] . 'cm - ' . $box['length'] . 'cm';
            $index++;
            echo '<option value=' . esc_attr( $index ) . '>Caixa ' . esc_html( $index ) . ':' . esc_html( $option_name ) . '</option>';
        }

        echo '</select>';
        echo '<button type="button" class="add_note button" id="order_box_plus" onclick="createBox()">Adicionar Caixa</button>';
        echo '</div>';
        
        
        echo '<div class="ShipSmart__box-content"  id="boxes_content">';
        echo '</div>';
        echo '</div>';

        echo '</div>';

        if ( get_post_meta( $post->ID, 'ss_shipsmart_order_id', true ) ) {
            echo '<hr>';
    
            echo '<div class="ShipSmart__order-status">';
            echo '<div class="ShipSmart__order-group">';
            echo '<h2 class="ShipSmart__order-group--label">Status do Pedido:</h2>';
            echo '<span class="ShipSmart__order-group--field">' . esc_html( $status_category ) . '</span>';
            echo '</div>';
            echo '<div class="ShipSmart__order-group">';
            echo '<h2 class="ShipSmart__order-group--label">Última atualização:</h2>';
            echo '<span class="ShipSmart__order-group--field">' . esc_html( $status_date ) . '</span>';
            echo '</div>';
            echo '<div class="ShipSmart__order-group">';
            echo '<h2 class="ShipSmart__order-group--label">Etapa do Status:</h2>';
            echo '<span class="ShipSmart__order-group--field">' . esc_html( $status_description ) . '</span>';
            echo '</div>';
            echo '</div>';
    
            echo '<hr>';
    
            echo '<div class="ShipSmart__order-documents">';
            echo '<h2 class="ShipSmart__order-documents-title">Documentação e Restramento</h2>';
            echo '<div class="ShipSmart__order-group">';
            echo '<h2 class="ShipSmart__order-group--label">Código de rastreamento:</h2>';
            echo '<span class="ShipSmart__order-code-tracking">' . esc_html( $code_tracking ) . '</span>';
            echo '</div>';
    
            echo '<a href="' . esc_attr( $link_tracking ) . '" target="__blank" class="ShipSmart__order-group">';
            echo '<span class="ShipSmart__order-group--link">Ir para a Ship</span>';
            echo '</a>';
    
            echo '<h2 class="ShipSmart__order-documents-title">Imprimir 1 via da nota fiscal</h2>';
            
            if ( $document_packinlist ) {
                echo '<div class="ShipSmart__order-group">';
                echo '<a href="' . esc_attr( $document_packinlist ) . '" target="__blank" class="ShipSmart__order-group--link">Packinlist</a>';
                echo '<span class="ShipSmart__order-group--field">Favor Imprimir 1 via - Não obrigatório.</span>';
                echo '</div>';
            }
    
            if ( $document_invoice ) {
                echo '<div class="ShipSmart__order-group">';
                echo '<a href="' . esc_attr( $document_invoice ) . '" target="__blank" class="ShipSmart__order-group--link">Invoice</a>';
                echo '<span class="ShipSmart__order-group--field">Favor Imprimir 5 via e assinar.</span>';
                echo '</div>';
            }
    
            if ( $document_awb_document ) {
                echo '<div class="ShipSmart__order-group">';
                echo '<a href="' . esc_attr( $document_awb_document ) . '" target="__blank" class="ShipSmart__order-group--link">AWB Document</a>';
                echo '<span class="ShipSmart__order-group--field">Favor Imprimir 1 via e assinar.</span>';
                echo '</div>';
            }
    
            if ( $document_path_declarition ) {
                echo '<div class="ShipSmart__order-group">';
                echo '<a href="' . esc_attr( $document_path_declarition ) . '" target="__blank" class="ShipSmart__order-group--link">Declaração de caminho</a>';
                echo '<span class="ShipSmart__order-group--field">Favor Imprimir 1 via e assinar (em caso apenas de pessoa física).</span>';
                echo '</div>';
            }
    
            echo '</div>';
        }


        woocommerce_wp_hidden_input( array(
            'id' => 'woocomercer_order_id',
            'value' => $post->ID
        ) );

        woocommerce_wp_hidden_input( array(
            'id' => 'ss_note_key',
            'value' => get_post_meta( $post->ID, 'ss_note_key', true )
        ) );

        echo '<button type="button" class="add_note button ShipSmart__order-button" id="sycro_with_shipsmart">';
        echo '<span>Sicronizar</span>';
        echo '</button>';
    }

    public function empty_note_key_error() {
        global $post;

        if ( ! isset( $post->ID ) ) {
            return;
        }

        if ( $post->ID === $_GET['post'] && get_post_meta( $post->ID, 'ss_status_order', true ) === 'error') {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html( get_post_meta( $post->ID, 'ss_message', true ), 'shipsmart' ); ?></p>
            </div>
            <?php
        } else if ( get_post_meta( $post->ID, 'ss_status_order', true ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html( get_post_meta( $post->ID, 'ss_message', true ), 'shipsmart' ); ?></p>
            </div>
            <?php
            update_post_meta( $post->ID, 'ss_status_order', '' );
        }
    }

    public function action_woocommerce_thankyou( $order_id ) {
        $response = SSFW_ApiService::send_order( $order_id );

        if ( $response !== false && $response['status'] !== 'SUCCESS' ) {
            update_post_meta( $order_id, 'ss_status_order', 'error' );
            update_post_meta( $order_id, 'ss_message', $response['messages']['text'] );
        }
    }

    public function order_meta_data( $order ) {
        woocommerce_wp_text_input( array(
            'id' => 'note_key',
            'label' => 'Chave da Nota:',
            'value' => get_post_meta( $order->get_id(), 'ss_note_key', true ),
            'wrapper_class' => 'form-field-wide'
        ) );
    }

    public function save_order_meta_data( $order_id ) {
        update_post_meta( $order_id, 'ss_note_key', wc_clean( $_POST[ 'note_key' ] ) );
    }
}
