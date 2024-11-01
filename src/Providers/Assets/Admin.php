<?php declare(strict_types = 1);

namespace ShipSmart\Providers\Assets;

use WPSteak\Providers\AbstractHookProvider;
use WPSteak\Services\Assets;

class Admin extends AbstractHookProvider {

	use Assets;

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		$this->add_action( 'admin_enqueue_scripts', 'enqueue' );
	}

	protected function enqueue(): void {
		$handle = "{$this->plugin->get_slug()}-admin";

		$this->enqueue_script(
			$handle,
			$this->plugin->get_url( 'dist/admin.js' ),
			$this->plugin->get_path( 'dist/admin.js' ),
			['jquery', 'wp-i18n', 'wp-api'],
			true,
		);

		$this->enqueue_style(
			$handle,
			$this->plugin->get_url( 'dist/styles/admin.css' ),
			$this->plugin->get_path( 'dist/styles/admin.css' ),
		);

		wp_localize_script(
			$handle,
			'wpApiSettings',
			array(
				'root' => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'imageRefreshUrl' => plugin_dir_url( PLUGIN_DIR . '/dist/images/refresh-icon.6e7225bab3.png' )
			)
		);

		if ( ! function_exists( 'wp_set_script_translations' ) ) {
			return;
		}

		/**
		 * The `.json` file must be on following format: domain-locale-handler.json
		 * You can generate this file with `po2json`
		 */
		wp_set_script_translations(
			$handle,
			$this->plugin->get_slug(),
			$this->plugin->get_path( 'languages' ),
		);
	}

}
