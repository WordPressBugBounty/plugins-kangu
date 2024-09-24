<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : $package['destination']['postcode'];
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );
$calculator_text          = '';
?>

<tr class="woocommerce-shipping-totals-custom shipping">
    <th class="woocommerce-shipping-totals-custom-th"><?php echo wp_kses_post( $package_name ); ?></th>
	<td data-title="<?php echo esc_attr( $package_name ); ?>">
		<?php if ( $available_methods ) : ?>        
            <?php if (!empty($available_methods['delivery'])): ?>
            <div class="box-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" height="18px">
                    <path d="M624 368h-16V251.9c0-19-7.7-37.5-21.1-50.9L503 117.1C489.6 103.7 471 96 452.1 96H416V56c0-30.9-25.1-56-56-56H56C25.1 0 0 25.1 0 56v304c0 30.9 25.1 56 56 56h8c0 53 43 96 96 96s96-43 96-96h128c0 53 43 96 96 96s96-43 96-96h48c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-464 96c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm208-96H242.7c-16.6-28.6-47.2-48-82.7-48s-66.1 19.4-82.7 48H56c-4.4 0-8-3.6-8-8V56c0-4.4 3.6-8 8-8h304c4.4 0 8 3.6 8 8v312zm48-224h36.1c6.3 0 12.5 2.6 17 7l73 73H416v-80zm64 320c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm80-100.9c-17.2-25.9-46.6-43.1-80-43.1-24.7 0-47 9.6-64 24.9V272h144v91.1z"></path>
                </svg>&nbsp;
                <span><b><?php echo esc_html__( 'Envio a domicílio' ); ?></b></span>
            </div>
            <div class="box-shipping">
                <ul class="woocommerce-shipping-methods shipping_options_price">
                    <?php foreach($available_methods['delivery'] as $method): ?>
                    <li class="list-item">
                        <span class="float-left">
                            <?php if ( 1 < count( $available_methods['delivery'] ) ): ?>
                            
                            <?php printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // WPCS: XSS ok. ?>
                            <?php else: ?>
                            <?php printf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ) ); // WPCS: XSS ok. ?>
                            <?php endif; ?>

                            <?php printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr( sanitize_title( $method->id ) ), wp_kses_post(!empty($method->get_meta_data()['shipping_label']) ? $method->get_meta_data()['shipping_label'] : $method->label) ); // WPCS: XSS ok. ?>
                            <?php if (!empty($method->get_meta_data()['deadline'])): ?>
                            <p><b><?php echo esc_html($method->meta_data['deadline']); ?></b></p>
                            <?php elseif (!empty($method->get_meta_data()['_delivery_forecast'])): 
                                $delivery_forecast = $method->meta_data['_delivery_forecast'] ? intval( $method->meta_data['_delivery_forecast'] ) : 0;
                            ?>
                            <p><b><?php echo esc_html( sprintf( _n( 'Delivery within %d working day', 'Delivery within %d working days', $delivery_forecast, 'woocommerce-correios' ), $delivery_forecast ) ); ?></b></p>
                            <?php endif; ?>
                        </span>
                        <span class="float-right"><b><?php echo wc_price($method->cost); ?></b></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($available_methods['pickup'])): ?>
            <div class="box-title">
                <span class="float-left">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" height="20px">
                        <path d="M192 0C85.903 0 0 86.014 0 192c0 71.117 23.991 93.341 151.271 297.424 18.785 30.119 62.694 30.083 81.457 0C360.075 285.234 384 263.103 384 192 384 85.903 297.986 0 192 0zm0 464C64.576 259.686 48 246.788 48 192c0-79.529 64.471-144 144-144s144 64.471 144 144c0 54.553-15.166 65.425-144 272zm-80-272c0-44.183 35.817-80 80-80s80 35.817 80 80-35.817 80-80 80-80-35.817-80-80z"></path>
                    </svg>
                    <b><?php echo esc_html__( 'Pontos de retira Kangu' ); ?></b>
                </span>
                <span class="float-right" style="margin-right:15px;"><b><?php echo wc_price($available_methods['pickup'][0]->cost); ?></b></span>
            </div>
            <div class="accordion-menu">
                <ul class="woocommerce-shipping-methods shipping_options_price">
                    <li>
                        <input type="checkbox" checked>
                        <i class="arrow"></i>
                        <h2>
                            <b><?php echo esc_html($available_methods['pickup'][0]->meta_data['deadline']); ?></b> <br>
                            <a><?php echo esc_html__( 'Escolher Ponto' ); ?></a>
                        </h2>
                        <?php foreach($available_methods['pickup'] as $method): ?>
                        <p>
                            <span class="float-left">
                            <?php if ( 1 < count( $available_methods['pickup'] ) ): ?>
                                <?php printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // WPCS: XSS ok. ?>
                                <?php else: ?>
                                <?php printf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ) ); // WPCS: XSS ok. ?>
                            <?php endif; ?>

                            <?php printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr( sanitize_title( $method->id ) ), wp_kses_post($method->meta_data['point_label']) ); // WPCS: XSS ok. ?> <br>
                            <span class="address-point"><?php echo esc_html($method->meta_data['point_address']); ?></span>
                            <b><?php echo esc_html($method->meta_data['point_distance']); ?></b>
                            </span>
                        </p>
                        <?php endforeach; ?>
                    </li>
                </ul>
            </div>
            <?php endif; ?>

			<?php if ( is_cart() ) : ?>
				<p class="woocommerce-shipping-destination">
					<?php
					if ( $formatted_destination ) {
						// Translators: $s shipping destination.
						printf( esc_html__( 'Shipping to %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' );
						$calculator_text = esc_html__( 'Change address', 'woocommerce' );
					} else {
						echo wp_kses_post( apply_filters( 'woocommerce_shipping_estimate_html', __( 'Shipping options will be updated during checkout.', 'woocommerce' ) ) );
					}
					?>
				</p>
			<?php endif; ?>
			<?php
		elseif ( ! $has_calculated_shipping || ! $formatted_destination ) :
			if ( is_cart() && 'no' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', __( 'Shipping costs are calculated during checkout.', 'woocommerce' ) ) );
			} else {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'woocommerce' ) ) );
			}
		elseif ( ! is_cart() ) :
			echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) );
		else :
			// Translators: $s shipping destination.
			echo wp_kses_post( apply_filters( 'woocommerce_cart_no_shipping_available_html', sprintf( esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ) ) );
			$calculator_text = esc_html__( 'Enter a different address', 'woocommerce' );
		endif;
		?>

		<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents">' . esc_html( $package_details ) . '</p>'; ?>
		<?php endif; ?>

		<?php if ( $show_shipping_calculator ) : ?>
			<?php woocommerce_shipping_calculator( $calculator_text ); ?>
		<?php endif; ?>
	</td>
</tr>
