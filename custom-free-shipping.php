<?php
/**
 * Plugin Name: Custom Shipping Methods
 * Description: Adds custom shipping methods and free shipping for orders over 250 zł with various weight conditions
 * Version: 1.0
 * Author: TraviLabs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function custom_display_cart_weight() {
    global $woocommerce;
    $weight = $woocommerce->cart->cart_contents_weight;
    echo '<div class="custom-cart-weight-info">';
    echo '<div class="custom-cart-weight">' . sprintf( __( 'Waga koszyka: %s kg', 'custom-free-shipping' ), $weight ) . '</div>';
    echo '<div class="custom-free-shipping">' . __( 'DARMOWA WYSYŁKA POWYŻEJ 250 ZŁ DO 20KG', 'custom-free-shipping' ) . '</div>';
    echo '</div>';
}

add_action( 'woocommerce_before_cart', 'custom_display_cart_weight' );



function custom_add_shipping_methods( $methods ) {
    $methods['custom_free_shipping'] = 'WC_Custom_Free_Shipping_Method';
    $methods['pocztex_kurier'] = 'WC_Pocztex_Kurier_Method';
    $methods['inpost_kurier'] = 'WC_InPost_Kurier_Method';
    $methods['inpost_paczkomaty'] = 'WC_InPost_Paczkomaty_Method';
    $methods['orlen_paczka_paczkomaty'] = 'WC_Orlen_Paczka_Paczkomaty_Method';
    $methods['odbiór_osobisty'] = 'WC_Odbior_Osobisty_Method';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'custom_add_shipping_methods' );

function custom_shipping_methods_init() {

    if ( ! class_exists( 'WC_Odbior_Osobisty_Method' ) ) {
        class WC_Odbior_Osobisty_Method extends WC_Shipping_Method {
            public function __construct() {
                $this->id                 = 'odbiór_osobisty';
                $this->title              = __( 'Odbiór osobisty', 'custom-free-shipping' );
                $this->method_description = __( 'Odbiór osobisty w siedzibie firmy', 'custom-free-shipping' );
    
                $this->init();
            }
    
            function init() {
                $this->init_form_fields();
                $this->init_settings();
    
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }
    
            public function calculate_shipping( $package = array() ) {
                $rate = array(
                    'id'    => $this->id,
                    'label' => $this->title,
                    'cost'  => 0,
                );
                $this->add_rate( $rate );
            }
        }
    }



    if ( ! class_exists( 'WC_Custom_Free_Shipping_Method' ) ) {
        class WC_Custom_Free_Shipping_Method extends WC_Shipping_Method {
            public function __construct() {
                $this->id                 = 'custom_free_shipping';
                $this->title              = __( 'Darmowa wysyłka (Kurier)', 'custom-free-shipping' );
                $this->method_description = __( 'Free shipping for orders over 250 zł and weight up to 20 kg', 'custom-free-shipping' );

                $this->init();
            }

            function init() {
                $this->init_form_fields();
                $this->init_settings();

                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            public function calculate_shipping( $package = array() ) {
                $weight = 0;
                $total = 0;
            
                foreach ( $package['contents'] as $item_id => $values ) {
                    $_product = $values['data'];
                    $weight += $_product->get_weight() * $values['quantity'];
                    $total += $_product->get_price() * $values['quantity'];
                }
            
                $multiplier = floor( $total / 250 );
                $max_weight = 20 * $multiplier;
            
                if ( $total >= 250 && $weight <= $max_weight ) {
                    $rate = array(
                        'id'    => $this->id,
                        'label' => $this->title,
                        'cost'  => 0,
                    );
                    $this->add_rate( $rate );
                }
            }
            
        }
    }

    if ( ! class_exists( 'WC_Pocztex_Kurier_Method' ) ) {
        class WC_Pocztex_Kurier_Method extends WC_Shipping_Method {
            public function __construct() {
                $this->id                 = 'pocztex_kurier';
                $this->title              = __( 'Pocztex Kurier', 'custom-free-shipping' );
                $this->method_description = __( 'Pocztex Kurier shipping for orders up to 20 kg and not worth over 250 zł', 'custom-free-shipping' );

                $this->init();
           
            }

                function init() {
                    $this->init_form_fields();
                    $this->init_settings();
    
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
    
                public function calculate_shipping( $package = array() ) {
                    $weight = 0;
                    $total = 0;
    
                    foreach ( $package['contents'] as $item_id => $values ) {
                        $_product = $values['data'];
                        $weight += $_product->get_weight() * $values['quantity'];
                        $total += $_product->get_price() * $values['quantity'];
                    }
    
// Dla klasy WC_Pocztex_Kurier_Method, w funkcji calculate_shipping()
$max_weight = 20;
if ($weight > 0) {
    $packages = ceil($weight / $max_weight);
    $rate = array(
        'id'    => $this->id,
        'label' => $this->title . ($packages > 1 ? ' (rozbite na ' . $packages . ' paczek)' : ''),
        'cost'  => 11.99 * $packages,
    );
    $this->add_rate($rate);
}

                }
            }
        }
    
        if ( ! class_exists( 'WC_InPost_Kurier_Method' ) ) {
            class WC_InPost_Kurier_Method extends WC_Shipping_Method {
                public function __construct() {
                    $this->id                 = 'inpost_kurier';
                    $this->title              = __( 'InPost Kurier', 'custom-free-shipping' );
                    $this->method_description = __( 'InPost Kurier shipping for orders up to 25 kg, not worth over 250 zł and weight exceeding 20 kg', 'custom-free-shipping' );
    
                    $this->init();
                }
    
                function init() {
                    $this->init_form_fields();
                    $this->init_settings();
    
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
    
                public function calculate_shipping( $package = array() ) {
                    $weight = 0;
                    $total = 0;
    
                    foreach ( $package['contents'] as $item_id => $values ) {
                        $_product = $values['data'];
                        $weight += $_product->get_weight() * $values['quantity'];
                        $total += $_product->get_price() * $values['quantity'];
                    }
    
// Dla klasy WC_InPost_Kurier_Method, w funkcji calculate_shipping()
$max_weight = 25;
if ($weight > 0) {
    $packages = ceil($weight / $max_weight);
    $rate = array(
        'id'    => $this->id,
        'label' => $this->title . ($packages > 1 ? ' (rozbite na ' . $packages . ' paczek)' : ''),
        'cost'  => 16.99 * $packages,
    );
    $this->add_rate($rate);
}

                }
            }
        }
    
        if ( ! class_exists( 'WC_InPost_Paczkomaty_Method' ) ) {
            class WC_InPost_Paczkomaty_Method extends WC_Shipping_Method {
                public function __construct() {
                    $this->id                 = 'inpost_paczkomaty';
                    $this->title              = __( 'InPost Paczkomaty', 'custom-free-shipping' );
                    $this->method_description = __( 'InPost Paczkomaty shipping for orders up to 25 kg, not worth over 250 zł and weight exceeding 20 kg', 'custom-free-shipping' );
    
                    $this->init();
                }
    
                function init() {
                    $this->init_form_fields();
                    $this->init_settings();
    
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
    
                public function calculate_shipping( $package = array() ) {
                   
                    $weight = 0;
                    $total = 0;
    
                    foreach ( $package['contents'] as $item_id => $values ) {
                        $_product = $values['data'];
                        $weight += $_product->get_weight() * $values['quantity'];
                        $total += $_product->get_price() * $values['quantity'];
                    }
    
// Dla klasy WC_InPost_Paczkomaty_Method, w funkcji calculate_shipping()
$max_weight = 25;
if ($weight > 0) {
    $packages = ceil($weight / $max_weight);
    $rate = array(
        'id'    => $this->id,
        'label' => $this->title . ($packages > 1 ? ' (rozbite na ' . $packages . ' paczek)' : ''),
        'cost'  => 14.99 * $packages,
    );
    $this->add_rate($rate);
}

                }
            }
        }
    
        if ( ! class_exists( 'WC_Orlen_Paczka_Paczkomaty_Method' ) ) {
            class WC_Orlen_Paczka_Paczkomaty_Method extends WC_Shipping_Method {
                public function __construct() {
                    $this->id                 = 'orlen_paczka_paczkomaty';
                    $this->title              = __( 'Orlen Paczka paczkomaty', 'custom-free-shipping' );
                    $this->method_description = __( 'Orlen Paczka Paczkomaty shipping for orders up to 25 kg, not worth over 250 zł and weight exceeding 20 kg', 'custom-free-shipping' );
    
                    $this->init();
                }
    
                function init() {
                    $this->init_form_fields();
                    $this->init_settings();
    
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
    
                public function calculate_shipping( $package = array() ) {
                    $weight = 0;
                    $total = 0;
    
                    foreach ( $package['contents'] as $item_id => $values ) {
                        $_product = $values['data'];
                        $weight += $_product->get_weight() * $values['quantity'];
                        $total += $_product->get_price() * $values['quantity'];
                    }
    
// Dla klasy WC_Orlen_Paczka_Paczkomaty_Method, w funkcji calculate_shipping()
$max_weight = 25;
if ($weight > 0) {
    $packages = ceil($weight / $max_weight);
    $rate = array(
        'id'    => $this->id,
        'label' => $this->title . ($packages > 1 ? ' (rozbite na ' . $packages . ' paczek)' : ''),
        'cost'  => 12.99 * $packages,
    );
    $this->add_rate($rate);
}

                }
            }
        }
        
    }
    add_action( 'woocommerce_shipping_init', 'custom_shipping_methods_init' );
        


function custom_available_shipping_methods( $rates, $package ) {
    $total = 0;
    $weight = 0;

    foreach ( $package['contents'] as $item_id => $values ) {
        $_product = $values['data'];
        $weight += $_product->get_weight() * $values['quantity'];
        $total += $_product->get_price() * $values['quantity'];
    }

    $amount_needed = 250 - $total;
    $weight_limit = 20;

    // Dodaj komunikat o całkowitej wadze koszyka
    wc_add_notice( sprintf( __( '<div class="custom-shipping-notice-weight">Całkowita waga koszyka: %s kg</div>', 'custom-free-shipping' ), $weight ), 'notice' );

    if ( $total >= 250 && $weight <= $weight_limit ) {
        wc_add_notice( __( '<div class="custom-shipping-notice-free">DARMOWA WYSYŁKA DOSTĘPNA!</div>', 'custom-free-shipping' ), 'notice' );
        unset( $rates['pocztex_kurier'] );
        unset( $rates['inpost_kurier'] );
        unset( $rates['inpost_paczkomaty'] );
        unset( $rates['orlen_paczka_paczkomaty'] );
    } elseif ($amount_needed > 0) {
        wc_add_notice( sprintf( __( '<div class="custom-shipping-notice">BRAK DARMOWEJ WYSYŁKI: brakuje %s zł do darmowej wysyłki.</div>', 'custom-free-shipping' ), $amount_needed ), 'notice' );
    } else {
        wc_add_notice( __( '<div class="custom-shipping-notice-free">DARMOWA WYSYŁKA DOSTĘPNA!</div>', 'custom-free-shipping' ), 'notice' );
    }
    

    return $rates;
}

function custom_shipping_styles() {
    ?>
    <style>
        .custom-shipping-notice {
            color: #ffffff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .custom-shipping-notice-free {
            color: green;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .custom-shipping-notice-weight {
            color: #000000;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .custom-cart-weight-info {
            display: flex;
            justify-content: space-between;
        }
        .custom-cart-weight {
            color: #000000;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .custom-free-shipping {
            color: #000000;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'custom_shipping_styles' );
    