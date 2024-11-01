<?php

declare(strict_types = 1);

/**
 * Config.
 *
 * Define configurations for this plugin,
 * use it for define your service providers and hooks providers,
 * the classes will be loaded in order as defined.
 *
 * @package App
 */

return [
	'service_providers' => [
		ShipSmart\Services\Meta\ServiceProvider::class,
	],
	'hook_providers' => [
		ShipSmart\Providers\Assets\Admin::class,
		ShipSmart\Providers\Assets\Editor::class,
		ShipSmart\Providers\Assets\Login::class,
		ShipSmart\Providers\Assets\Theme::class,
		ShipSmart\Providers\Config\SSFW_Menu::class,
		ShipSmart\Providers\Config\SSFW_Api::class,
		ShipSmart\Providers\Config\SSFW_Product::class,
		ShipSmart\Providers\Config\SSFW_Order::class,
	],
];
