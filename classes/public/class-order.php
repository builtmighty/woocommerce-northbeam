<?php
/**
 * Order.
 * 
 * Order interactions and prepartion for use with Northbeam API.
 * 
 * @package Northbeam
 * @since   1.0.0
 */
namespace Northbeam\Public;
class order {

    /**
     * Order ID.
     * 
     * @since   1.0.0
     */
    public $order_id;
    public $order;

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct( $order_id ) {

        // Set order ID.
        $this->order_id = $order_id;

        // Get order.
        $this->order = wc_get_order( $order_id );

    }

    /**
     * Maybe process order.
     * 
     * @since   1.0.0
     */
    public function maybe_process() {

        // Check if enabled.
        if( ! get_option( 'northbeam_enable' ) ) return false;

        // Return.
        return true;

    }

    /**
     * Purchase event order data.
     * 
     * @return  array   Order data formatted for the Northbeam firePurchaseEvent.
     * @since   1.0.0
     */
    public function get_purchase_event() {

        // Set data.
        $order_data = [
            'id'            => (string)$this->order->get_id(),
            'totalPrice'    => (float)$this->order->get_total(),
            'shippingPrice' => (float)$this->order->get_shipping_total(),
            'taxPrice'      => (float)$this->order->get_total_tax(),
            'coupons'       => (string)implode( ',', $this->order->get_coupon_codes() ),
            'currency'      => (string)get_woocommerce_currency(),
            'customerId'    => (string)$this->order->get_customer_id(),
            'lineItems'     => (array)$this->get_purchase_items(),
        ];

        // Return.
        return apply_filter( 'northbeam_purchase_event', $order_data );

    }

    /**
     * Get order.
     * 
     * @return  array   Order data formatted for the Northbeam API.
     * @since   1.0.0
     */
    public function get_order() {

        // Order data.
        $order_data = [
            'order_id'                  => (string)$this->order->get_id(),
            'customer_id'               => (string)$this->order->get_customer_id(),
            'time_of_purchase'          => $this->order->get_date_created()->format( 'c' ),
            'tax'                       => (float)$this->order->get_total_tax(),
            'currency'                  => (string)get_woocommerce_currency(),
            'is_recurring_order'        => (boolean)false,
            'purchase_total'            => (float)$this->order->get_total(),
            'shipping_cost'             => (float)$this->order->get_shipping_total(),
            'customer_email'            => (string)$this->order->get_billing_email(),
            'customer_phone_number'     => (string)$this->order->get_billing_phone(),
            'customer_name'             => (string)$this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(),
            'customer_ip_address'       => (string)$this->order->get_customer_ip_address(),
            'discount_codes'            => (array)$this->order->get_coupon_codes(),
            'discount_amount'           => (float)$this->order->get_total_discount(),
            'customer_shipping_address' => (array)$this->get_customer_data(),
            'products'                  => (array)$this->get_order_items(),
        ];

        // Check if WooCommerce Subscriptions exists.
        if( class_exists( 'WC_Subscriptions' ) ) {

            // Check if order is renewal order.
            if( WC_Subscriptions_Renewal_Order::is_renewal( $this->order ) ) {

                // Set as renewal order.
                $order_data['is_recurring_order'] = (boolean)true;

            }

        }

        // Return order.
        return [ apply_filters( 'northbeam_order', $order_data ) ];

    }

    /**
     * Get order items.
     * 
     * @since   1.0.0
     */
    public function get_order_items() {

        // Set products.
        $products = [];

        // Loop.
        foreach( $this->order->get_items() as $item_id => $item ) {

            // Get product.
            $product = $item->get_product();

            // Set product data.
            $product_data = [
                'id'        => (string)$product->get_id(),
                'name'      => (string)$product->get_name(),
                'quantity'  => (int)$item->get_quantity(),
                'price'     => (float)$product->get_price(),
            ];

            // Check if variation.
            if( $item->get_variation_id() ) {

                // Get variation.
                $variation = wc_get_product( $item->get_variation_id() );

                // Check if variation price is set.
                if( ! empty( $variation->get_price() ) ) {

                    // Update the price.
                    $product_data['price'] = (float)$variation->get_price();

                }

                // Set.
                $product_data['variant_id']     = (string)$variation->get_id();
                $product_data['variant_name']   = (string)$variation->get_name();

            }

            // Add to products.
            $products[] = apply_filters( 'northbeam_order_product', $product_data );

        }

        // Return.
        return apply_filters( 'northbeam_order_products', $products );

    }

    /**
     * Get customer data.
     * 
     * @since   1.0.0
     */
    public function get_customer_data() {

        // Return.
        return apply_filters( 'northbeam_order_customer', [
            'address1'      => (string)$this->order->get_shipping_address_1(),
            'address2'      => (string)$this->order->get_shipping_address_2(),
            'city'          => (string)$this->order->get_shipping_city(),
            'state'         => (string)$this->order->get_shipping_state(),
            'zip'           => (string)$this->order->get_shipping_postcode(),
            'country_code'  => (string)$this->country_code( $this->order->get_shipping_country() ),
        ] );

    }

    /**
     * Purchase items.
     * 
     * @since   1.0.0
     */
    public function get_purchase_items() {

        // Set order items.
        $order_items = [];

        // Loop.
        foreach( $this->order->get_items() as $item_id => $item ) {

            // Get product.
            $product = $item->get_product();

            // Check product type.
            if( $product->get_type() == 'variation' ) {

                // Get child.
                $child = wc_get_product( $product->get_parent_id() );

            }

            // Set order item.
            $order_item = [
                'productId'     => (string)$product->get_sku(),
                'variantId'     => (string)( ! empty( $child ) ) ? $child->get_sku() : '',
                'productName'   => (string)$product->get_name(),
                'variantName'   => (string)( ! empty( $child ) ) ? $child->get_name() : '',
                'price'         => (float)$item->get_total(),
                'quantity'      => (float)$item->get_quantity(),
            ];

            // Add to order items.
            $order_items[] = apply_filters( 'northbeam_purchase_item', $order_item );

        }

        // Return.
        return apply_filters( 'northbeam_purchase_items', $order_items );

    }

    /**
     * Add note.
     * 
     * @since   1.0.0
     */
    public function add_note() {

        // Add note.
        $this->order->add_order_note( 'Order sent and received by Northbeam API.', false );

    }

    /**
     * Country codes.
     * 
     * @param   string  WooCommerce's 2 letter country code.
     * @return  string  ISO 3-letter country code.
     * @since   1.0.0
     */
    public function country_code( $code ) {

        // Set countryes.
        $countries = [
            'AF' => 'AFG', // Afghanistan
            'AX' => 'ALA', // Aland Islands
            'AL' => 'ALB', // Albania
            'DZ' => 'DZA', // Algeria
            'AS' => 'ASM', // American Samoa
            'AD' => 'AND', // Andorra
            'AO' => 'AGO', // Angola
            'AI' => 'AIA', // Anguilla
            'AQ' => 'ATA', // Antarctica
            'AG' => 'ATG', // Antigua and Barbuda
            'AR' => 'ARG', // Argentina
            'AM' => 'ARM', // Armenia
            'AW' => 'ABW', // Aruba
            'AU' => 'AUS', // Australia
            'AT' => 'AUT', // Austria
            'AZ' => 'AZE', // Azerbaijan
            'BS' => 'BHS', // Bahamas
            'BH' => 'BHR', // Bahrain
            'BD' => 'BGD', // Bangladesh
            'BB' => 'BRB', // Barbados
            'BY' => 'BLR', // Belarus
            'BE' => 'BEL', // Belgium
            'BZ' => 'BLZ', // Belize
            'BJ' => 'BEN', // Benin
            'BM' => 'BMU', // Bermuda
            'BT' => 'BTN', // Bhutan
            'BO' => 'BOL', // Bolivia
            'BQ' => 'BES', // Bonaire, Saint Estatius and Saba
            'BA' => 'BIH', // Bosnia and Herzegovina
            'BW' => 'BWA', // Botswana
            'BV' => 'BVT', // Bouvet Islands
            'BR' => 'BRA', // Brazil
            'IO' => 'IOT', // British Indian Ocean Territory
            'BN' => 'BRN', // Brunei
            'BG' => 'BGR', // Bulgaria
            'BF' => 'BFA', // Burkina Faso
            'BI' => 'BDI', // Burundi
            'KH' => 'KHM', // Cambodia
            'CM' => 'CMR', // Cameroon
            'CA' => 'CAN', // Canada
            'CV' => 'CPV', // Cape Verde
            'KY' => 'CYM', // Cayman Islands
            'CF' => 'CAF', // Central African Republic
            'TD' => 'TCD', // Chad
            'CL' => 'CHL', // Chile
            'CN' => 'CHN', // China
            'CX' => 'CXR', // Christmas Island
            'CC' => 'CCK', // Cocos (Keeling) Islands
            'CO' => 'COL', // Colombia
            'KM' => 'COM', // Comoros
            'CG' => 'COG', // Congo
            'CD' => 'COD', // Congo, Democratic Republic of the
            'CK' => 'COK', // Cook Islands
            'CR' => 'CRI', // Costa Rica
            'CI' => 'CIV', // Côte d\'Ivoire
            'HR' => 'HRV', // Croatia
            'CU' => 'CUB', // Cuba
            'CW' => 'CUW', // Curaçao
            'CY' => 'CYP', // Cyprus
            'CZ' => 'CZE', // Czech Republic
            'DK' => 'DNK', // Denmark
            'DJ' => 'DJI', // Djibouti
            'DM' => 'DMA', // Dominica
            'DO' => 'DOM', // Dominican Republic
            'EC' => 'ECU', // Ecuador
            'EG' => 'EGY', // Egypt
            'SV' => 'SLV', // El Salvador
            'GQ' => 'GNQ', // Equatorial Guinea
            'ER' => 'ERI', // Eritrea
            'EE' => 'EST', // Estonia
            'ET' => 'ETH', // Ethiopia
            'FK' => 'FLK', // Falkland Islands
            'FO' => 'FRO', // Faroe Islands
            'FJ' => 'FIJ', // Fiji
            'FI' => 'FIN', // Finland
            'FR' => 'FRA', // France
            'GF' => 'GUF', // French Guiana
            'PF' => 'PYF', // French Polynesia
            'TF' => 'ATF', // French Southern Territories
            'GA' => 'GAB', // Gabon
            'GM' => 'GMB', // Gambia
            'GE' => 'GEO', // Georgia
            'DE' => 'DEU', // Germany
            'GH' => 'GHA', // Ghana
            'GI' => 'GIB', // Gibraltar
            'GR' => 'GRC', // Greece
            'GL' => 'GRL', // Greenland
            'GD' => 'GRD', // Grenada
            'GP' => 'GLP', // Guadeloupe
            'GU' => 'GUM', // Guam
            'GT' => 'GTM', // Guatemala
            'GG' => 'GGY', // Guernsey
            'GN' => 'GIN', // Guinea
            'GW' => 'GNB', // Guinea-Bissau
            'GY' => 'GUY', // Guyana
            'HT' => 'HTI', // Haiti
            'HM' => 'HMD', // Heard Island and McDonald Islands
            'VA' => 'VAT', // Holy See (Vatican City State)
            'HN' => 'HND', // Honduras
            'HK' => 'HKG', // Hong Kong
            'HU' => 'HUN', // Hungary
            'IS' => 'ISL', // Iceland
            'IN' => 'IND', // India
            'ID' => 'IDN', // Indonesia
            'IR' => 'IRN', // Iran
            'IQ' => 'IRQ', // Iraq
            'IE' => 'IRL', // Republic of Ireland
            'IM' => 'IMN', // Isle of Man
            'IL' => 'ISR', // Israel
            'IT' => 'ITA', // Italy
            'JM' => 'JAM', // Jamaica
            'JP' => 'JPN', // Japan
            'JE' => 'JEY', // Jersey
            'JO' => 'JOR', // Jordan
            'KZ' => 'KAZ', // Kazakhstan
            'KE' => 'KEN', // Kenya
            'KI' => 'KIR', // Kiribati
            'KP' => 'PRK', // Korea, Democratic People\'s Republic of
            'KR' => 'KOR', // Korea, Republic of (South)
            'KW' => 'KWT', // Kuwait
            'KG' => 'KGZ', // Kyrgyzstan
            'LA' => 'LAO', // Laos
            'LV' => 'LVA', // Latvia
            'LB' => 'LBN', // Lebanon
            'LS' => 'LSO', // Lesotho
            'LR' => 'LBR', // Liberia
            'LY' => 'LBY', // Libya
            'LI' => 'LIE', // Liechtenstein
            'LT' => 'LTU', // Lithuania
            'LU' => 'LUX', // Luxembourg
            'MO' => 'MAC', // Macao S.A.R., China
            'MK' => 'MKD', // Macedonia
            'MG' => 'MDG', // Madagascar
            'MW' => 'MWI', // Malawi
            'MY' => 'MYS', // Malaysia
            'MV' => 'MDV', // Maldives
            'ML' => 'MLI', // Mali
            'MT' => 'MLT', // Malta
            'MH' => 'MHL', // Marshall Islands
            'MQ' => 'MTQ', // Martinique
            'MR' => 'MRT', // Mauritania
            'MU' => 'MUS', // Mauritius
            'YT' => 'MYT', // Mayotte
            'MX' => 'MEX', // Mexico
            'FM' => 'FSM', // Micronesia
            'MD' => 'MDA', // Moldova
            'MC' => 'MCO', // Monaco
            'MN' => 'MNG', // Mongolia
            'ME' => 'MNE', // Montenegro
            'MS' => 'MSR', // Montserrat
            'MA' => 'MAR', // Morocco
            'MZ' => 'MOZ', // Mozambique
            'MM' => 'MMR', // Myanmar
            'NA' => 'NAM', // Namibia
            'NR' => 'NRU', // Nauru
            'NP' => 'NPL', // Nepal
            'NL' => 'NLD', // Netherlands
            'AN' => 'ANT', // Netherlands Antilles
            'NC' => 'NCL', // New Caledonia
            'NZ' => 'NZL', // New Zealand
            'NI' => 'NIC', // Nicaragua
            'NE' => 'NER', // Niger
            'NG' => 'NGA', // Nigeria
            'NU' => 'NIU', // Niue
            'NF' => 'NFK', // Norfolk Island
            'MP' => 'MNP', // Northern Mariana Islands
            'NO' => 'NOR', // Norway
            'OM' => 'OMN', // Oman
            'PK' => 'PAK', // Pakistan
            'PW' => 'PLW', // Palau
            'PS' => 'PSE', // Palestinian Territory
            'PA' => 'PAN', // Panama
            'PG' => 'PNG', // Papua New Guinea
            'PY' => 'PRY', // Paraguay
            'PE' => 'PER', // Peru
            'PH' => 'PHL', // Philippines
            'PN' => 'PCN', // Pitcairn
            'PL' => 'POL', // Poland
            'PT' => 'PRT', // Portugal
            'PR' => 'PRI', // Puerto Rico
            'QA' => 'QAT', // Qatar
            'RE' => 'REU', // Reunion
            'RO' => 'ROU', // Romania
            'RU' => 'RUS', // Russia
            'RW' => 'RWA', // Rwanda
            'BL' => 'BLM', // Saint Barth&eacute;lemy
            'SH' => 'SHN', // Saint Helena
            'KN' => 'KNA', // Saint Kitts and Nevis
            'LC' => 'LCA', // Saint Lucia
            'MF' => 'MAF', // Saint Martin (French part)
            'SX' => 'SXM', // Sint Maarten / Saint Matin (Dutch part)
            'PM' => 'SPM', // Saint Pierre and Miquelon
            'VC' => 'VCT', // Saint Vincent and the Grenadines
            'WS' => 'WSM', // Samoa
            'SM' => 'SMR', // San Marino
            'ST' => 'STP', // S&atilde;o Tom&eacute; and Pr&iacute;ncipe
            'SA' => 'SAU', // Saudi Arabia
            'SN' => 'SEN', // Senegal
            'RS' => 'SRB', // Serbia
            'SC' => 'SYC', // Seychelles
            'SL' => 'SLE', // Sierra Leone
            'SG' => 'SGP', // Singapore
            'SK' => 'SVK', // Slovakia
            'SI' => 'SVN', // Slovenia
            'SB' => 'SLB', // Solomon Islands
            'SO' => 'SOM', // Somalia
            'ZA' => 'ZAF', // South Africa
            'GS' => 'SGS', // South Georgia/Sandwich Islands
            'SS' => 'SSD', // South Sudan
            'ES' => 'ESP', // Spain
            'LK' => 'LKA', // Sri Lanka
            'SD' => 'SDN', // Sudan
            'SR' => 'SUR', // Suriname
            'SJ' => 'SJM', // Svalbard and Jan Mayen
            'SZ' => 'SWZ', // Swaziland
            'SE' => 'SWE', // Sweden
            'CH' => 'CHE', // Switzerland
            'SY' => 'SYR', // Syria
            'TW' => 'TWN', // Taiwan
            'TJ' => 'TJK', // Tajikistan
            'TZ' => 'TZA', // Tanzania
            'TH' => 'THA', // Thailand    
            'TL' => 'TLS', // Timor-Leste
            'TG' => 'TGO', // Togo
            'TK' => 'TKL', // Tokelau
            'TO' => 'TON', // Tonga
            'TT' => 'TTO', // Trinidad and Tobago
            'TN' => 'TUN', // Tunisia
            'TR' => 'TUR', // Turkey
            'TM' => 'TKM', // Turkmenistan
            'TC' => 'TCA', // Turks and Caicos Islands
            'TV' => 'TUV', // Tuvalu     
            'UG' => 'UGA', // Uganda
            'UA' => 'UKR', // Ukraine
            'AE' => 'ARE', // United Arab Emirates
            'GB' => 'GBR', // United Kingdom
            'US' => 'USA', // United States
            'UM' => 'UMI', // United States Minor Outlying Islands
            'UY' => 'URY', // Uruguay
            'UZ' => 'UZB', // Uzbekistan
            'VU' => 'VUT', // Vanuatu
            'VE' => 'VEN', // Venezuela
            'VN' => 'VNM', // Vietnam
            'VG' => 'VGB', // Virgin Islands, British
            'VI' => 'VIR', // Virgin Island, U.S.
            'WF' => 'WLF', // Wallis and Futuna
            'EH' => 'ESH', // Western Sahara
            'YE' => 'YEM', // Yemen
            'ZM' => 'ZMB', // Zambia
            'ZW' => 'ZWE', // Zimbabwe
        ];

        // Filter.
        $countries = apply_filters( 'northbeam_country_codes', $countries );

        // Return.
        return ( isset( $countries ) ) ? $countries[ $code ] : 'USA';

    }

}