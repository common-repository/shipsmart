<?php

namespace ShipSmart\Providers\ShipSmart;

use ShipSmart\Services\SSFW_ApiService;
use WC_Shipping_Method;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

add_filter( 'woocommerce_shipping_methods', function( $methods ) {
    $methods['ss_shipping_method'] = SSFW_Shipping_Method::class;
    return $methods;
} );

add_action( 'woocommerce_shipping_init', function() {
    if ( ! class_exists( 'Shipping_Method' ) ) {
        class SSFW_Shipping_Method extends WC_Shipping_Method {
            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct( $instance_id = 0 ) {
                $this->id = 'ss_shipping_method';
                $this->instance_id = absint( $instance_id );
                $this->method_title = __( 'ShipSmart' );
                $this->title = __( 'ShipSmart' );
                $this->method_description = __(
                    'Solução para logística crossborder. Calculo de frete, impostos, gestão de pedidos e envios internacionais.',
                    'shipsmart'
                );
                $this->enabled = "yes";
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal'
                );
                $this->init();
            }

            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init() {
                $index = 0;
                $boxes = [0 => 'Padrão da Ship Smart'];

                while( !! $box = get_option( '_ss_box_'. $index .'_measure' ) ) {
                    $option_name = $box['weight'] . 'kg - ' . $box['height'] .  'cm - ' . $box['width'] . 'cm - ' . $box['length'] . 'cm';
                    $boxes[++$index] = $option_name;
                }

                $this->instance_form_fields = array(
                    'is_federal_tax_id' => array(
                        'title' 		=> __( 'Loja com CNPJ?' ),
                        'type' 			=> 'checkbox',
                        'label'         => 'Cadastrar CNPJ',
                        'default'		=> false,
                    ),
                    'federal_tax_id' => array(
                        'title'         => __( 'CNPJ' ),
                        'type' 			=> 'text',
                        'default'		=> __( '0000000000000' ),
                    ),
                    'name' => array(
                        'title'         => __( 'Nome da Loja' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Ship Smart Seller' ),
                    ),
                    'phone' => array(
                        'title'         => __( 'Telefone da Loja' ),
                        'type' 			=> 'text',
                        'default'		=> __( '11111111111' ),
                    ),
                    'email' => array(
                        'title'         => __( 'E-mail da Loja' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'shipsmart@config.com' ),
                    ),
                    'percentual_discount' => array(
                        'title' 		=> __( 'Aplicar desconto no frete?' ),
                        'type' 			=> 'number',
                        'default'		=> 0,
                    ),
                    'insurance' => array(
                        'title' 		=> __( 'Com ou sem seguro?' ),
                        'type' 			=> 'checkbox',
                        'label'         => 'Aplicar seguro',
                        'default'		=> false,
                    ),
                    'discount_insurance' => array(
                        'title' 		=> __( 'Aplicar desconto no seguro?' ),
                        'type' 			=> 'number',
                        'default'		=> 0,
                    ),
                    'taxable' => array(
                        'title' 		=> __( 'Com ou sem Duty and Tax?' ),
                        'type' 			=> 'checkbox',
                        'label'         => 'Aplicar taxas',
                        'default'		=> true,
                    ),
                    'discount_taxable' => array(
                        'title' 		=> __( 'Aplicar' ),
                        'type' 			=> 'number',
                        'default'		=> 0,
                    ),
                    'view_taxable' => array(
                        'title' 		=> __( 'Exibir previsão de taxa (s/ Duty and Tax)' ),
                        'label' 		=> __( 'Exibir previsão de taxa?' ),
                        'type' 			=> 'checkbox',
                        'default'		=> false,
                        'class'         => ''
                    ),
                    'box_default' => array(
                        'title' 		=> __( 'Caixa padrão?' ),
                        'type' 			=> 'select',
                        'default'		=> false,
                        'options'       => $boxes
                    ),
                    'plus_date_final' => array(
                        'title' 		=> __( 'Somar dias adicionais no prazo total?' ),
                        'type' 			=> 'number',
                        'default'		=> __( '0' ),
                    ),
                    'title' => array(
                        'title' 		=> __( 'Título do frete' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Frete ShipSmart' ),
                    ),
                    'freight_title' => array(
                        'title' 		=> __( 'Nome do frete' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Valor frete' ),
                    ),
                    'insurance_name' => array(
                        'title' 		=> __( 'Nome do seguro' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Taxa do seguro' ),
                    ),
                    'taxable_name' => array(
                        'title' 		=> __( 'Nome da taxa' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Outras taxas' ),
                    ),
                    'predict_taxable' => array(
                        'title' 		=> __( 'Texto de previsão de taxa' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Previsão da taxa (não cobrado na compra)' ),
                    ),
                    'total_price_name' => array(
                        'title' 		=> __( 'Texto valor total do frete' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Total do frete' ),
                    ),
                    'predict_days_name' => array(
                        'title' 		=> __( 'Texto previsão entrega' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'Seu produto será entregue em' ),
                    ),
                    'days_text' => array(
                        'title' 		=> __( 'Texto rótulo dias de frete' ),
                        'type' 			=> 'text',
                        'default'		=> __( 'dias' ),
                    ),
                );

                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            /**
             * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
             *
             * @param array $package Package array.
             */
            public function calculate_shipping( $package = array() ) {
                $box_id = $this->get_option('box_default');
                $box_dimension = [];

                if ( $box_id ) {
                    $box_dimension = get_option( '_ss_box_'. ( $box_id - 1 ) .'_measure' );
                    $box_dimension = $box_dimension ? $box_dimension : [];
                }

                $response_cotation = SSFW_ApiService::ssfw_cotation_request(
                    [
                        'taxable' => $this->get_option( 'taxable' ),
                        'insurance' => $this->get_option( 'insurance' ),
                        'box_dimension' => $box_dimension
                    ]
                );

                update_option( 'ss_cart_' . \get_current_user_id(), $response_cotation );

                if ( $response_cotation['status'] !== 'SUCCESS' ) {
                    return;
                }

                $costTax = $response_cotation["messages"]["result"]["costTax"];
                $costInsurance = $response_cotation["messages"]["result"]["costInsurance"];
                $costFreight =  $response_cotation["messages"]["result"]["costFreight"];
                $discount_insurance = $this->get_option( 'discount_insurance' )
                    ? $this->get_option( 'discount_insurance' )
                    : 0;
                $discount_taxable = $this->get_option( 'discount_taxable' )
                    ? $this->get_option( 'discount_insurance' )
                    : 0;
                $percentual_discount = $this->get_option( 'percentual_discount' )
                    ? $this->get_option( 'percentual_discount' )
                    : 0;
                $costInsurance -= $discount_insurance * $costInsurance / 100;
                $costTax -= $discount_taxable * $costTax / 100;
                $costFreight -= $percentual_discount * $costFreight / 100;
                $total_cost = $costFreight;

                if ( $this->get_option( 'insurance' ) === 'yes' ) {
                    $total_cost += $costInsurance;
                }

                if ( $this->get_option( 'taxable' ) === 'yes' ) {
                    $total_cost += $costTax;
                }
                
                $rate = array(
                    'label' => 'shipsmart',
                    'cost' => $total_cost,
                );

                $this->add_rate( $rate );
            }
        }
    }
} );

add_filter(
    'woocommerce_cart_shipping_method_full_label',
    function( $label, $method ) {
        $zone_ids = array_keys( array('') + WC_Shipping_Zones::get_zones() );
        $shipping_name = '';
        $taxable_name = '';
        $insurance_name = '';
        $response = get_option( 'ss_cart_' . \get_current_user_id() );
        $current_symbol = get_woocommerce_currency_symbol();

        if ( $response['status'] !== 'SUCCESS' ) {
            return __( $response['messages']['text'], 'app' );
        }

        $costTax = $response["messages"]["result"]["costTax"];
        $costInsurance = $response["messages"]["result"]["costInsurance"];
        $costFreight =  $response["messages"]["result"]["costFreight"];
        $amount = $response["messages"]["result"]["amount"];
        $totalTransitDays = $response["messages"]["result"]["totalTransitDays"];

        if ( preg_match( '/shipsmart/', $label ) ) {
            foreach ( $zone_ids as $zone_id ) 
            {
                $shipping_zone = new WC_Shipping_Zone($zone_id);
            
                $shipping_methods = $shipping_zone->get_shipping_methods( true, 'values' );

                foreach ( $shipping_methods as $shipping_method ) 
                {
                    if ( $shipping_method->id === 'ss_shipping_method' ) {
                        $shipping_name = $shipping_method->get_option( 'title' );
                        $freight_title = $shipping_method->get_option( 'freight_title' );
                        $taxable_name = $shipping_method->get_option( 'taxable_name' );
                        $predict_taxable = $shipping_method->get_option( 'predict_taxable' );
                        $insurance_name = $shipping_method->get_option( 'insurance_name' );
                        
                        update_option( 'ss_checkout_freight_cost_' . get_current_user_id(), $costFreight );
                        update_option( 'ss_checkout_taxable_cost_' . get_current_user_id(), $costTax );
                        update_option( 'ss_checkout_insurance_cost_' . get_current_user_id(), $costInsurance );
                        update_option( 'ss_checkout_freight_' . get_current_user_id(), $freight_title );
                        update_option( 'ss_checkout_taxable_' . get_current_user_id(), $taxable_name );
                        update_option( 'ss_checkout_insurance_' . get_current_user_id(), $insurance_name );

                        $discount_insurance = $shipping_method->get_option( 'discount_insurance' )
                            ? $shipping_method->get_option( 'discount_insurance' )
                            : 0;
                        $discount_taxable = $shipping_method->get_option( 'discount_taxable' )
                            ? $shipping_method->get_option( 'discount_insurance' )
                            : 0;
                        $percentual_discount = $shipping_method->get_option( 'percentual_discount' )
                            ? $shipping_method->get_option( 'percentual_discount' )
                            : 0;
                        
                        $costTax -= ( $costTax * $discount_taxable ) / 100;
                        $costInsurance -= ( $costInsurance * $discount_insurance ) / 100;
                        $costFreight -= ( $costFreight * $percentual_discount ) / 100;
                        $amount = $costFreight;
                        $totalTransitDays += $shipping_method->get_option( 'plus_date_final' )
                            ? $shipping_method->get_option( 'plus_date_final' )
                            : 0;

                        $label = '
                        <div class="ShipSmart__checkout-title">
                            <h1>
                                ' . esc_html( $shipping_name ) . '
                            </h1>
                        </div>
                        <div class="ShipSmart__checkout-description">
                            <div class="ShipSmart__checkout-labels">
                                <span>' . esc_html( $freight_title ) . ': ' . esc_html( $current_symbol . round( $costFreight, 2 ) ) . '</span>';
                        
                        if ( $shipping_method->get_option( 'insurance' ) === 'yes' ) {
                            $label .= '<span>'. esc_html( $insurance_name ) . ': ' . esc_html(  $current_symbol . round( $costInsurance, 2 ) ) . '</span>';
                            $amount += $costInsurance; 
                        }

                        if ( $shipping_method->get_option( 'taxable' ) === 'yes' ) {
                            $label .= '<span>'. esc_html( $taxable_name ) . ': ' . esc_html( $current_symbol . round( $costTax, 2 ) ) . '</span>';
                            $amount += $costTax;
                        } else if ( $shipping_method->get_option( 'taxable' ) !== 'yes'
                            && $shipping_method->get_option( 'view_taxable' ) === 'yes' ) {
                            $label .= '<span>'. esc_html( $predict_taxable ) . ': ' . esc_html( $current_symbol . round( $costTax, 2 ) ) . '</span>';
                        }

                        $label .= '<span class="ShipSmart__checkout-total">' . esc_html( $shipping_method->get_option( 'total_price_name' ) ) . ': ' . esc_html( $current_symbol . round( $amount, 2) ) . '</span>
                                <span>' . esc_html( $shipping_method->get_option( 'predict_days_name' ) ) . ': ' . esc_html( $totalTransitDays ) . ' ' . esc_html( $shipping_method->get_option( 'days_text' ) ) . '</span>
                            </div>
                        </div>';

                        return $label;
                    }
                }
            }
        }

        return $label;
    },
    10,
    2
);
