<?php

// phpcs:ignoreFile

declare(strict_types = 1);

namespace ShipSmart\Services;

class SSFW_Box {

    public static $max_dimensions = [
        'height' => 80,
        'width' => 80,
        'length' => 120,
    ];

    public static $max_weight = 300;

    public static function get_boxes_by_cart( $items, $is_order = false, $note_key = '', $box_dimension_default = null ): array {

        if ( ! count( $items ) ) {
            return [];
        }

        if ( count( $box_dimension_default ) ) {
            self::$max_dimensions['height'] = $box_dimension_default['height'];
            self::$max_dimensions['width'] = $box_dimension_default['width'];
            self::$max_dimensions['length'] = $box_dimension_default['length'];
            self::$max_weight = $box_dimension_default['weight'];
        }

        $boxes_dimensions = self::get_boxes_dimensions( $items );
        $box_id = 1;

        if ( ! count( $boxes_dimensions ) ) {
            return ['error' => __('Não foi possível armazenar os produtos na caixa cadastrada, consulte o Administrador da loja.')];
        }

        return array_map(
            function( $box ) use ( $box_id, $is_order, $note_key, $box_dimension_default ) {
                $response =  [
                    'id' => $box_id,
                    'measures' => [
                        "height" => count( $box_dimension_default ) ? $box_dimension_default['height'] : $box['measures']['height'],
                        "width" => count( $box_dimension_default ) ? $box_dimension_default['width'] : $box['measures']['width'],
                        "depth" => count( $box_dimension_default ) ? $box_dimension_default['length'] : $box['measures']['length']
                    ],
                    'items' => $box['items'],
                    'weight' => self::get_sum_property_items( $box['items'], 'weight' ),
                    'price' => self::get_sum_property_items( $box['items'], 'price' )
                ];


                if ( $is_order ) {
                    $response = array_merge(
                        $response,
                        [
                            "invoiceKey" => $note_key,
                            "quantidade" => count( $box['items'] )
                        ]
                    );
                } else {
                    $response = array_merge(
                        $response,
                        [
                            'qtdTotal' => count( $box['items'] ),
                        ]
                    );
                }

                $box_id++;

                return $response;
            },
            $boxes_dimensions
        );
    }

    public static function get_boxes_dimensions( $items ): array {
        $list_items = self::create_list_items( $items );
        
        return self::create_box_dimensions( $list_items );
    }
    
    public static function create_list_items( $items ): array {
        $list_items = [];

        foreach ( $items as $item ) {
            $quantity = is_array( $item ) ? $item['quantity'] : $item->quantity;
            $item_data = is_array( $item ) ? $item['data'] : $item;
            $item_id = is_array($item) ? $item['product_id'] : $item->get_id();

            foreach ( range( 0, $quantity - 1 ) as $index ) {
                array_push(
                    $list_items,
                    [
                        'measures' => [
                            'height' => $item_data->get_height() ? $item_data->get_height() : 0,
                            'width' => $item_data->get_width() ? $item_data->get_width() : 0,
                            'length' => $item_data->get_length() ? $item_data->get_length() : 0,
                        ],
                        'items' => [
                            'description' => $item_data->get_title(),
                            'price' => $item_data->get_price(),
                            'hscode' => get_post_meta( $item_id, 'ss_hs_code_product', true ),
                            'quantity' => 1,
                            'weight' => $item_data->get_weight() ? $item_data->get_weight() : 0,
                        ]
                    ]
                );
            }  
        }
        return $list_items;
    }

    public static function create_box_dimensions( $dimensions ) {
        $volume_total = self::calculate_total_volume( $dimensions );
        $boxes_dimensions = [];
        $exceed_limit = true;
        
        while ( $exceed_limit ) {
            $box_dimension = self::calculate_total_dimension( $dimensions, $volume_total );
            $exceed_limit = self::exceed_limit_dimensions( $box_dimension );
            
            if ( $exceed_limit ) {
                $new_dimensions = self::reduce_items_by_dimensions( $dimensions );
                
                if ( ! count( $new_dimensions ) ) {
                    break; 
                }

                $box_dimension = $new_dimensions['dimension'];

                $dimensions = array_slice(
                    $dimensions,
                    $new_dimensions['number_of_dimensions']
                );
            }

            array_push(
                $boxes_dimensions,
                $box_dimension
            );
        }

        return $boxes_dimensions;
    }

    public static function exceed_limit_dimensions( $dimensions ): bool {
        $current_max_dimension = self::$max_dimensions;
        $current_box_dimension = $dimensions['measures'];
        arsort( $current_box_dimension );
        arsort( $current_max_dimension );

        foreach ( range( 0, 2 ) as $index ) {
            if ( array_pop($current_box_dimension) > array_pop($current_max_dimension) ) {
                return true;
            }
        }

        if ( self::get_weight_of_box( $dimensions ) > self::$max_weight['weight'] ) {
            return true;
        }

        return false;
    }

    public static function reduce_items_by_dimensions( $dimensions ) {
        array_pop( $dimensions );
        
        $current_volume = self::calculate_total_volume( $dimensions );
        $current_dimensions = self::calculate_total_dimension( $dimensions, $current_volume );
        
        if ( ! count( $current_dimensions ) ) {
            return [];
        }

        if ( ! self::exceed_limit_dimensions( $current_dimensions ) ) {
            return ['number_of_dimensions' => count($dimensions), 'dimension' => $current_dimensions] ;
        }

        return self::reduce_items_by_dimensions( $dimensions );
    }

    public static function calculate_total_dimension( $dimensions, $volume_total ) {
        $max_values = [];
        $items = [];

        if ( count( $dimensions ) === 1 ) {
            return [
                'measures' => [
                    'height' => $dimensions[0]['measures']['height'],
                    'width' => $dimensions[0]['measures']['width'],
                    'length' => $dimensions[0]['measures']['length']
                ],
                'items' => [
                    $dimensions[0]['items']
                ]
            ];
        }

        foreach ( $dimensions as $dimensional ) {
            array_push( $max_values, max($dimensional['measures'] ) );
            array_push( $items, $dimensional['items'] );
        }
        
        arsort( $max_values );

        if ( ! count( $max_values ) ) {
            return [];
        }

        $first_max_value = $max_values[0];
        $second_max_value = $max_values[1];

        $last_dimensional = ( $volume_total / $first_max_value ) / $second_max_value;
        

        return [
            'measures' => [
                'height' => $first_max_value,
                'width' => $second_max_value,
                'length' => floatval( $last_dimensional )
            ],
            'items' => $items
        ];
    }
    
    public static function calculate_total_volume( $dimensions ) {
        return array_reduce(
            $dimensions,
            function( $carry, $item ) {
                $carry += $item['measures']['height'] * $item['measures']['width'] * $item['measures']['length'];
                return $carry;
            }
        );
    }

    public static function get_items_of_cart( $cart ): array {
		return array_values(
			array_map(
				function( $item ) {
					return [
						'description' => $item['data']->get_title(),
						'hscode' => get_post_meta( $item['product_id'], 'ss_hs_code_product', true ),
						'quantity' => $item['quantity'],
						'weight' => $item['data']->get_weight(),
						'price' => $item['data']->get_price()
					];
				},
				$cart
			)
		);
	}

    public static function get_sum_property_items( $items, $property ) {
        return array_reduce(
            $items,
            function( $carry, $item ) use ( $property ) {
                $carry += $item[$property];
                return $carry;
            }
        );
    }

    public static function get_weight_of_box( $box ): float {
        return array_reduce(
            $box['items'],
            function( $carry, $item ) {
                $carry += $item['weight'];
                return $carry;
            }
        );
    }

}
