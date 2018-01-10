<?php
/**
 * Plugin Name: WC sale by %
 * Author: I. Kancijan
 * Author URI: https://github.com/IKancijan
 * Description: Custom Woocommerce sale by percentage.
 * Version: 1.0.2.
 * WC tested up to: 3.0.9
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    add_action( 'woocommerce_product_options_general_product_data', 'sale_custom_field' );
    function sale_custom_field() {
        // Print a custom text field
        woocommerce_wp_text_input( array(
            'id' => 'rabat',
            'label' => __('Rabat', 'sale'),
            'description' => __('Postotak rabata.', 'sale'),
            'desc_tip' => 'true',
            'placeholder' => '%',
            'type' => 'number', 
            'custom_attributes' => array(
                            'step' 	=> 'any'
                        ) 
        ) );
    }

    // save data
    add_action( 'woocommerce_process_product_meta', 'wc_custom_save_custom_fields' );
    function wc_custom_save_custom_fields( $post_id ) {
        $_product = wc_get_product();

        if ( ! empty( $_POST['rabat'] ) && $_POST['rabat'] != 0) {

            $rabat = esc_attr( $_POST['rabat'] );
            update_post_meta( $post_id, 'rabat', $rabat );

            if($_product->product_type != 'variable'){
                // change price for single product
                $regular = get_post_meta($post_id, '_regular_price', true);

                if(!empty($regular) && $regular > 0){
                    $woo_new_product_price = $regular + (($regular*$rabat)/100);
                    update_post_meta( $post_id, '_sale_price', $woo_new_product_price );
                    update_post_meta( $post_id, '_price', $woo_new_product_price );
                }
            }else{
                // change price for variable product
                $variables = $_product->get_available_variations();
                foreach($variables as $key => $var){
                    $var_id = $var['variation_id'];
                    $meta = get_post_meta($var_id);
                    $regular = esc_attr( $meta['_regular_price'][0]);

                    if(!empty($regular) && $regular > 0){
                        $woo_new_product_price = $regular + (($regular*$rabat)/100);
                        update_post_meta( $var_id, '_sale_price', $woo_new_product_price );
                        update_post_meta( $var_id, '_price', $woo_new_product_price );
                    }
                }
            }
        }else{
            // save rabat
            update_post_meta( $post_id, 'rabat', '' );

            // save default price if "rabat" is empty
            if($_product->product_type != 'variable'){
                // change price for single product
                $regular = get_post_meta($post_id, '_regular_price', true);
                update_post_meta( $post_id, '_price', $regular );
                update_post_meta( $post_id, '_sale_price', '' );
            }else{
                // change price for variable product
                $variables = $_product->get_available_variations();
                foreach($variables as $key => $var){
                    $var_id = $var['variation_id'];
                    $meta = get_post_meta($var_id);
                    $regular = esc_attr( $meta['_regular_price'][0]);
                    update_post_meta( $var_id, '_price', $regular );
                    update_post_meta( $var_id, '_sale_price', '' );
                }
            }
        }
    }
    // Add save percent next to sale item prices.
    add_filter( 'woocommerce_format_sale_price', 'woocommerce_custom_sales_price', 10 );
    function woocommerce_custom_sales_price( $price ) {

        $post_id = get_the_ID();
        $rabat = get_post_meta($post_id, 'rabat', true);
        $style = '<style>
                    .woocommerce .product div.entry-summary .price > del {
                        float: left;
                        font-size: 1em;
                        margin-left: 0px;
                        margin-right: 20px;}
                    .woocommerce .product div.entry-summary .price > ins {
                        text-decoration: none;
                        margin-left: 20px;}
                    </style>';
        $price = sprintf( __(' %s ', 'woocommerce' ), $rabat . '%' ) . $price . $style;
        return $price;
    }
}