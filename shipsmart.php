<?php

// phpcs:ignoreFile

declare(strict_types = 1);

/**
 * Ship Smart
 *
 * @package App
 *
 * Plugin Name: Shipping with ShipSmart for WooCommerce
 * Description: Solução para logística crossborder. Calculo de frete, impostos, gestão de pedidos e envios internacionais.
 * Version: 1.0.0
 * Author: Apiki
 * Author URI: https://apiki.com/
 * Text Domain: app
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

use ShipSmart\Entities\ShipSmart;
use Cedaro\WP\Plugin\Plugin;
use Cedaro\WP\Plugin\PluginFactory;


if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

require_once ( __DIR__ . '/src/Providers/Config/SSFW_Shipping_Method.php' );

/**
 * Retrieve the main plugin instance.
 */
function shipsmart(): Plugin {
	static $instance;

	if ( null === $instance ) {
		$instance = PluginFactory::create( 'app' );
	}

	return $instance;
}

if ( ! defined( 'PLUGIN_DIR' ) ) {
	define( 'PLUGIN_DIR',  __DIR__ );
}

$container = new League\Container\Container();

/* register the reflection container as a delegate to enable auto wiring. */
$container->delegate(
	( new League\Container\ReflectionContainer() )->cacheResolutions(),
);

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$plugin = shipsmart();

$plugin->set_container( $container );
$plugin->register_hooks( $container->get( Cedaro\WP\Plugin\Provider\I18n::class ) );
$plugin->register_hooks( $container->get( WPSteak\Providers\I18n::class ) );

$config = ( require __DIR__ . '/config.php' );

foreach ( $config['service_providers'] as $service_provider ) {
	$container->addServiceProvider( $service_provider );
}

foreach ( $config['hook_providers'] as $hook_provider ) {
	$plugin->register_hooks( $container->get( $hook_provider ) );
}


/**
 * Verificação da ativação do plugin Woocommerce
 */
register_activation_hook( __FILE__, 'ssfw_admin_notice_activation_hook' );

function ssfw_admin_notice_activation_hook() {
	update_option( 'ssfw_admin_activation', true );

	if ( ! defined( 'WC_VERSION' ) ) {
		update_option( 'ssfw_admin_desactivation', true );
	}
}

/**
 * Mensagens pós ativação do plugin.
 */
add_action( 'admin_notices', 'ssfw_admin_notice_activation' );

function ssfw_admin_notice_activation() {
	if ( get_option( 'ssfw_admin_desactivation' ) ) {
		?>
			<div class="notice notice-error is-dismissible">
            	<p>Primeiramente ative o plugin do Woocomerce!</p>
        	</div>
		<?php
		delete_option( 'ssfw_admin_desactivation' );
		deactivate_plugins( '/shipsmart-plugin/shipsmart.php' );
		return;
	}

	if ( get_option( 'ssfw_admin_activation' ) ) {
		?>
			<div class="updated notice is-dismissible">
            	<p>Obrigado por ativar o nosso plugin ShipSmart!</p>
				<?php if ( ! get_option( ShipSmart::PREFIX_NAME . 'apikey_shipsmart' ) ) { ?>
					<p>Você ainda não cadastrou sua API KEY, clique <a href="<?php echo esc_url( admin_url( 'admin.php?page=shipsmart' ) ); ?>">aqui</a> para adicioná-la.</p>				
				<?php } ?>
        	</div>
		<?php
		delete_option( 'ssfw_admin_activation' );
	}
}

/**
 * Adicionar a opção 'Settings' nas ações do plugin.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ssfw_add_actions_plugin' );

function ssfw_add_actions_plugin( $links ) {
	if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
		return array_merge( 
			array( 'settings' => '<a href="' . admin_url( 'admin.php?page=shipsmart' ) . '">Configurações</a>' ),
			$links
		);
	} else {
		return $links;
	}
}

if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

register_deactivation_hook( __FILE__, 'ssfw_deactivation' );
 
function ssfw_deactivation() {
    wp_clear_scheduled_hook( 'update_status_orders_cron' );
    wp_clear_scheduled_hook( 'get_documents_orders_cron' );
}
