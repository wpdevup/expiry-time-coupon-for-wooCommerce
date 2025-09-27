<?php
/**
 * Plugin Name:       Expiry Time Coupon for WooCommerce
 * Description:       Adds a time-of-day component to WooCommerce coupon expiry that works together with the coupon expiry date.
 * Version:           1.0.0
 * Author:            man4toman
 * Author URI:        https://profiles.wordpress.org/man4toman/
 * License:           GPLv3
 * Text Domain:       expiry-time-coupon-for-woocommerce
 * Requires Plugins:  woocommerce
 *
 * @package EDC_Expiry_Date_Coupon
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'EDC_Expiry_Date_Coupon' ) ) {

	class EDC_Expiry_Date_Coupon {

		/**
		 * Init hooks.
		 */
		public function __construct() {
			add_action( 'woocommerce_coupon_options', [$this, 'create_expiry_timepicker'], 10, 2 );
			add_action( 'woocommerce_coupon_options_save', [$this, 'save_expiry_timepicker'], 10, 2 );
			add_filter( 'woocommerce_coupon_validate_expiry_date', [$this, 'check_expiry_timepicker'], 999, 3 );
			add_action( 'admin_enqueue_scripts', [$this, 'enqueue_timepicker'] );
		}
		
		public function enqueue_timepicker( $hook ) {
			if ( 'post.php' !== $hook && 'post-new.php' !== $hook )
				return;

			global $post;
			if ( empty( $post ) || 'shop_coupon' !== $post->post_type ) 
				return;

			wp_enqueue_style( 'jquery-ui-css', plugin_dir_url( __FILE__ ) . 'assets/css/jquery-ui.css', [], '1.13.2', 'all' );
			wp_enqueue_script( 'jquery-ui-timepicker', plugin_dir_url( __FILE__ ) . 'assets/js/jquery-ui-timepicker-addon.min.js', ['jquery', 'jquery-ui-datepicker'], '1.6.3', true );
			wp_enqueue_style( 'jquery-ui-timepicker', plugin_dir_url( __FILE__ ) . 'assets/css/jquery-ui-timepicker-addon.min.css', [], '1.6.3', 'all' );
		}

		/**
		 * Render "Coupon expiry time" field (HH:MM) and store seconds in hidden meta.
		 *
		 * @param int           $coupon_id Coupon post ID.
		 * @param WC_Coupon|nil $coupon    Coupon object.
		 * @return void
		 */
		public function create_expiry_timepicker( $coupon_id = 0, $coupon = null ) {
			$current_seconds = ( $coupon instanceof WC_Coupon )
				? (int) $coupon->get_meta( 'edc_expiry_time' )
				: (int) get_post_meta( $coupon_id, 'edc_expiry_time', true );
			?>
			<script type="text/javascript">
				jQuery(function($){
					var $hidden  = $('#edc_expiry_time');
					var $picker  = $('#edc_expiry_time_picker');
					var seconds  = parseInt($hidden.val(), 10);
					var hhmm     = '';

					if (Number.isInteger(seconds) && seconds > 0) {
						var mins = seconds / 60;
						var hrs  = Math.floor(mins / 60);
						mins     = Math.floor(mins % 60);
						hhmm     = (hrs   < 10 ? '0' + hrs  : hrs)  + ':' + (mins < 10 ? '0' + mins : mins);
					}

					$picker.val(hhmm);
					if ($.fn.timepicker) {
						$picker.timepicker({
							timeInput: true,
							isRTL: (wp.i18n && wp.i18n.isRTL) ? wp.i18n.isRTL() : false
						});
					}

					$picker.on('change', function(){
						var v = $(this).val();
						if (v && v.indexOf(':') > -1) {
							var parts = v.split(':');
							var h = parseInt(parts[0], 10);
							var m = parseInt(parts[1], 10);
							if (Number.isInteger(h) && Number.isInteger(m) && h >= 0 && h < 24 && m >= 0 && m < 60) {
								$hidden.val( (h * 3600) + (m * 60) );
								return;
							}
						}
						$hidden.val('');
					});
				});
			</script>
			<?php
			woocommerce_wp_hidden_input(['id' => 'edc_expiry_time']);
			woocommerce_wp_text_input(
				[
					'id'                => 'edc_expiry_time_picker',
					'label'             => __( 'Coupon expiry time', 'expiry-time-coupon-for-woocommerce' ),
					'placeholder'       => esc_attr__( 'HH:MM', 'expiry-time-coupon-for-woocommerce' ),
					'description'       => __( 'Valid until this time on the final day, Enter the time the coupon should stop being valid. Example: 09:15', 'expiry-time-coupon-for-woocommerce' ),
					'type'              => 'text',
					'desc_tip'          => true,
					'custom_attributes' => [
						'autocomplete' => 'off',
						'inputmode'    => 'numeric',
						'pattern'      => '^[0-2][0-9]:[0-5][0-9]$',
					],
				]
			);
		}

		/**
		 * Combine WooCommerce coupon expiry date with custom time (in seconds).
		 *
		 * @param bool          $expired   Whether the coupon is expired before our check.
		 * @param WC_Coupon|nil $coupon    Coupon object.
		 * @param WC_Discounts  $discounts Discounts object (unused).
		 * @return bool
		 */
		public function check_expiry_timepicker( $expired = false, $coupon = null, $discounts = null ) {
			if ( ! ( $coupon instanceof WC_Coupon ) )
				return $expired;

			$expiry_time_seconds = (int) $coupon->get_meta( 'edc_expiry_time' );

			if ( $expiry_time_seconds <= 0 )
				return $expired;

			$expiry_date = $coupon->get_date_expires();
			if ( empty( $expiry_date ) )
				return $expired;

			$ts = $expiry_date->getTimestamp();
			if ( ! $ts )
				return $expired;

			$final_expiry_ts = $ts + $expiry_time_seconds;

			return ( time() > $final_expiry_ts ) ? true : false;
		}

		/**
		 * Save the custom expiry time (seconds).
		 *
		 * @param int           $post_id Coupon post ID.
		 * @param WC_Coupon|nil $coupon  Coupon object.
		 * @return void
		 */
		public function save_expiry_timepicker( $post_id = 0, $coupon = null ) {
			if ( empty( $post_id ) )
				return;

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Uses WooCommerce save hook in admin.
			if ( isset( $_POST['edc_expiry_time'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$expiry_time = sanitize_text_field( wp_unslash( $_POST['edc_expiry_time'] ) );
				update_post_meta( $post_id, 'edc_expiry_time', $expiry_time );

				if ( $coupon instanceof WC_Coupon )
					$coupon->save();
			}
		}
	}
}

new EDC_Expiry_Date_Coupon();