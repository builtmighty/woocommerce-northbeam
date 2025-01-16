<?php
/**
 * Settings.
 * 
 * WooCommerce Admin settings for Northbeam.
 * 
 * @package Northbeam
 * @since   1.0.0
 */
namespace Northbeam\Private;
class settings {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Actions
        add_action( 'woocommerce_settings_northbeam', [ $this, 'northbeam_settings' ] );
        add_action( 'woocommerce_update_options_northbeam', [ $this, 'save_northbeam_settings' ] );

        // Filters.
        add_filter( 'woocommerce_settings_tabs_array', [ $this, 'northbeam_tab' ], 50 );

    }

    /**
     * Settings tab.
     */
    public function northbeam_tab( $tabs ) {

        // Add.
        $tabs['northbeam'] = __( 'Northbeam', 'woo-northbeam' );

        // Return.
        return $tabs;

    }

    /**
     * Amelia settings.
     */
    public function northbeam_settings() {

        // Get.
        woocommerce_admin_fields( $this->get_northbeam_settings() );

    }

    /**
     * Save settings.
     */
    public function save_northbeam_settings() {

        // Update.
        woocommerce_update_options( $this->get_northbeam_settings() );

    }

    /**
     * Get settings.
     */
    public function get_northbeam_settings() {

        // Set.
        $settings = [
            'northbeam_title' => [
                'name'      => __( 'Northbeam', 'woo-northbeam' ),
                'type'      => 'title',
                'desc'      => '',
                'id'        => 'northbeam_section_title'
            ],
            [
                'name'      => __( 'Enable', 'woo-northbeam' ),
                'type'      => 'checkbox',
                'desc'      => __( 'Enable Northbeam', 'woo-northbeam' ),
                'id'        => 'northbeam_enable'
            ],
            [
                'name'      => __( 'Enable Pixel', 'woo-northbeam' ),
                'type'      => 'checkbox',
                'desc'      => __( 'Enable Northbeam Pixel', 'woo-northbeam' ),
                'id'        => 'northbeam_enable_pixel'
            ],
            [
                'name'      => __( 'Enable firePurchaseEvent', 'woo-northbeam' ),
                'type'      => 'checkbox',
                'desc'      => __( 'Enable Northbeam firePurchaseEvent', 'woo-northbeam' ),
                'id'        => 'northbeam_enable_purchase'
            ],
            [
                'name'      => __( 'Client ID', 'woo-northbeam' ),
                'type'      => 'text',
                'desc'      => '',
                'id'        => 'northbeam_client_id'
            ],
            [
                'name'      => __( 'Authorization Key', 'woo-northbeam' ),
                'type'      => 'password',
                'desc'      => '',
                'id'        => 'northbeam_auth_key'
            ],
            [
                'name'      => __( 'Logging', 'woo-northbeam' ),
                'type'      => 'checkbox',
                'desc'      => 'Enable logging',
                'id'        => 'northbeam_logging'
            ],
            'northbeam_end' => [
                'type'      => 'sectionend',
                'id'        => 'northbeam_section_end'
            ],
        ];

        // Return.
        return apply_filters( 'wc_settings_tab_northbeam_tab', $settings );

    }

}