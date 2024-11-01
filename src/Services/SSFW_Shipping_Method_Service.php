<?php

// phpcs:ignoreFile

declare(strict_types = 1);

namespace ShipSmart\Services;

use WC_Shipping_Zone;
use WC_Shipping_Zones;

class SSFW_Shipping_Method_Service {
    public static function get_shipping_method( $shipping_id ) {
        $zone_ids = array_keys( array('') + WC_Shipping_Zones::get_zones() );

		foreach ( $zone_ids as $zone_id ) 
		{
			$shipping_zone = new WC_Shipping_Zone($zone_id);
			
			$shipping_methods = $shipping_zone->get_shipping_methods( true, 'values' );
			foreach ( $shipping_methods as $shipping_method ) 
			{
				if ( $shipping_method->id === $shipping_id ) {
					return $shipping_method;
				}
			}
		}
    } 
}
