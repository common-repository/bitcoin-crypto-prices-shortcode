<?php
/*
Plugin Name: Bitcoin & Crypto Prices Shortcode
Plugin URI: https://www.kryptovergleich.org/
Description: Simple shortcode to insert realtime prices of Bitcoin, Ethereum and XRP (Ripple) into your pages and posts. Monetize your traffic by adding affiliate links into the "Buy" boxes.
Author: kryptovergleich.org
Author URI: https://www.kryptovergleich.org/
Text Domain: kvp
Version: 1.0.0
*/

if ( ! defined( 'ABSPATH' ) ) { return; } // Exit if accessed directly

/**
 * Define common constants
 */
if ( ! defined( 'KVP_DIR_URL' ) )  define( 'KVP_DIR_URL',  plugins_url( '', __FILE__ ) );
if ( ! defined( 'KVP_DIR_PATH' ) ) define( 'KVP_DIR_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'KVP_VERSION' ) )  define( 'KVP_VERSION', '1.0.0' );

// Include required files
require_once KVP_DIR_PATH . '/settings.php';

if ( ! class_exists( 'Kryptovergleich_Plugin' ) ) {
	class Kryptovergleich_Plugin {

		public function __construct() {
			// Enqueue scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Register shortcode
			add_shortcode( 'kvp_shortcode', array( $this, 'main_shortcode' ) );

			// Update exchange rates action
			add_action( 'wp_ajax_nopriv_update_exchange_rates', array( $this, 'update_exchange_rates' ) );
			add_action( 'wp_ajax_update_exchange_rates', array( $this, 'update_exchange_rates' ) );

			// Update coins rates action
			add_action( 'wp_ajax_nopriv_update_coins_rates', array( $this, 'update_rates' ) );
			add_action( 'wp_ajax_update_coins_rates', array( $this, 'update_rates' ) );

			// Update coins rates action
			add_action( 'wp', array( $this, 'update_data' ) );
		}

		public function enqueue_scripts() {
			wp_register_style( 'kvp-styles', KVP_DIR_URL . '/assets/css/style.css', array(), KVP_VERSION );
			wp_register_style( 'kvp-sprites', KVP_DIR_URL . '/assets/css/sprite.css', array(), KVP_VERSION );
		}

		public function main_shortcode( $atts, $content = '' ) {
			$atts = shortcode_atts( array(
				'coins' => 'BTC, ETH, XRP'
			), $atts );

			wp_enqueue_style( 'kvp-styles' );
			wp_enqueue_style( 'kvp-sprites' );

			$options 	 = get_option( 'kv_plugin' );
			$crypto_data = get_option( 'crypto_table_data', '' );

			$currencies  = array(
				'eur' => '&euro;',
				'usd' => '&#36;'
			);

			$coins = explode(',', $atts['coins']);
			$coins = array_map('trim', $coins);

			$currency 	   = isset( $options['currency'] ) ? trim( $options['currency'] ) : 'eur';
			$exchange_link = isset( $options['button_url'] ) ? trim( $options['button_url'] ) : '#';
			$button_text   = isset( $options['button_text'] ) ? trim( $options['button_text'] ) : 'Buy';
			
			ob_start();
			if ( ! empty( $crypto_data ) ) {
				?>
				<div class="coin-tags-wrapper">
					<div class="coin-tags-container">
						<?php foreach ( $crypto_data as $key => $coin ) {
							if ( in_array( $coin['symbol'], $coins ) ) {
								$item_class = $coin['changePercent24Hr'] > 0 ? 'top' : 'down';
								$item_class = $coin['changePercent24Hr'] == 0 ? '' : $item_class;

								if ( $currency == 'eur' ) {
									$price = number_format( $coin['eur_price'], 2, '.', ',' ) . $currencies[$currency];
								} else {
									$price = $currencies[$currency] . number_format( $coin['eur_price'], 2, '.', ',' );
								} ?>
								<div class="coin-tag-entry">
									<a target="_blank" href="<?php echo esc_url( $exchange_link ); ?>" rel="nofollow">
										<div class="entry-name">
											<span class="sprite sprite-<?php echo esc_attr( strtolower( $coin['id'] ) ); ?>"></span>
											<div><?php echo esc_html( $coin['symbol'] ); ?></div>
											<div><?php echo esc_html( $coin['name'] ); ?></div>
										</div>
									</a>
									<div class="d-flex align-items-center">
										<a target="_blank" href="<?php echo esc_url( $exchange_link ); ?>" rel="nofollow">
											<div class="entry-price">
												<div class="price"><?php echo esc_html( $price ); ?></div>
												<div class="price-change <?php echo esc_attr( $item_class ); ?>"><?php echo esc_html( round( $coin['changePercent24Hr'], 2 ) ); ?>%</div>
												<div class="buy-button">
													<span class="buy-now" rel="nofollow"><?php echo esc_html( $button_text ); ?></span>
												</div>
											</div>
										</a>
									</div>
								</div>
							<?php } ?>
						<?php } ?>
					</div>
					<div class="kvp-copy">Powered by <a href="https://www.kryptovergleich.org/" target="_blank">Kryptovergleich</a></div>
				</div>
				<?php
			}
			return ob_get_clean();
		}


		public function update_crypto_data() {

			$crypto_data = wp_remote_get( 'https://api.coincap.io/v2/assets' );
			if ( ! is_wp_error( $crypto_data ) && ! empty( $crypto_data['body'] ) ) {
				$rate = $this->get_exchange_rates();
				$option_array = array();
				$crypto_array = json_decode( $crypto_data['body'], true );
				$crypto_array = $crypto_array['data'];
				
				foreach ( $crypto_array as $key => $crypto ) {
					$option_array[ $key ] = $crypto;
					$option_array[ $key ]['eur_price'] = $crypto['priceUsd'] * $rate;
				}

				if ( ! empty( $option_array ) ) {
					update_option( 'crypto_table_data', $option_array );
				}
			}
		}


		private function get_exchange_rates( $forse_update = false ) {
			$currency_option = get_option( 'currency_rate' );
			$current_time 	 = date('dmyH');

			if ( ! empty( $currency_option ) && is_array( $currency_option ) && ! $forse_update ) {
				if ( $currency_option['date'] == $current_time ) {
					return $currency_option['rate'];
				}
			}

			$options = get_option( 'kv_plugin' );

			// check if API key exist
			if ( ! empty( $options['fixer_key'] ) ) {
				$response = wp_remote_get('http://data.fixer.io/api/latest?access_key=' . $options['fixer_key'] . '&format=1');

				// check if success request
				if ( ! is_wp_error( $response ) ) {

					// check if success responce
					if( wp_remote_retrieve_response_code( $response ) === 200 ) {
						$body = wp_remote_retrieve_body( $response );

						$rates_array  = json_decode( $body, true );

						// create options array
						$option_array = array(
							'date' => $current_time,
							'rate' => round(1 / $rates_array['rates']['USD'], 4),
						);

						// save options array
						update_option( 'currency_rate', $option_array );
						
						return $option_array['rate'];
					}
				}
			}

			return null;
		}

		public function update_exchange_rates() {
			$result = $this->get_exchange_rates( true );
			if ( ! $result ) {
				esc_html_e( 'Some error happened. Please check your API or try again later.', 'kvp' );
			}
		}

		public function update_data() {
			$options = get_option( 'kv_plugin' );
			if ( empty( $options ) || ( isset( $options['data_grabing'] ) && $options['data_grabing'] == 'automaticaly' ) ) {
				$current_date = date('dmy');
				$last_update  = get_option( 'kv_last_update' );

				if ( empty( $last_update ) || $last_update != $current_date ) {
					$this->get_exchange_rates( true );
					$this->update_crypto_data();

					update_option( 'kv_last_update', $current_date );
				}
			}
		}
	}
}
new Kryptovergleich_Plugin();





