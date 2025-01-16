<?php
/**
 * Hooks.
 * 
 * Hooks that fire and interact with Northbeam API.
 * 
 * @package Northbeam
 * @since   1.0.0
 */
namespace Northbeam\Public;
class hooks {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Check for WooCommerce before firing.
        if( ! class_exists( 'WooCommerce' ) ) return;

        // Actions
        add_action( 'wp_head', [ $this, 'pixel' ] );
        add_action( 'woocommerce_thankyou', [ $this, 'new_purchase' ] );
        add_action( 'woocommerce_payment_complete', [ $this, 'order_complete' ] );

    }

    /**
     * Pixel.
     * 
     * @since   1.0.0
     */
    public function pixel() {

        // Check if enabled.
        if( ! get_option( 'northbeam_enable' ) ) return;

        // Check if pixel is enabled.
        if( ! get_option( 'northbeam_enable_pixel' ) ) return;

        // Check for client ID.
        if( empty( get_option( 'northbeam_client_id' ) ) ) return;

        // Output pixel. ?>
        <!-- Begin: Northbeam pixel -->
        <script>(function(){var t;(n=t=t||{}).A="identify",n.B="trackPageView",n.C="fireEmailCaptureEvent",n.D="fireCustomGoal",n.E="firePurchaseEvent",n.F="trackPageViewInitial";var n="//j.northbeam.io/ota-sp/<?php echo get_option( 'northbeam_client_id' ); ?>.js";function a(n){for(var e=[],t=1;t<arguments.length;t++)e[t-1]=arguments[t];r.push({fnName:n,args:e})}var e,r=[],i=((e={})[t.F]=function(n){a(t.F,n)},(i={_q:r})[t.A]=function(n,e){return a(t.A,n,e)},i[t.B]=function(){return a(t.B)},i[t.C]=function(n,e){return a(t.C,n,e)},i[t.D]=function(n,e){return a(t.D,n,e)},i[t.E]=function(n){return a(t.E,n)},window.Northbeam=i,document.createElement("script"));i.async=!0,i.src=n,document.head.appendChild(i),e.trackPageViewInitial(window.location.href);})()</script>
        <!-- End: Northbeam pixel --><?php
        
    }

    /**
     * New purchase.
     * 
     * @since   1.0.0
     */
    public function new_purchase( $order_id ) {

        // Check if enabled.
        if( ! get_option( 'northbeam_enable' ) ) return;

        // Check if firePurchaseEvent is enabled.
        if( ! get_option( 'northbeam_enable_purchase' ) ) return;

        // Get order class.
        $order = new \Northbeam\Public\order( $order_id );

        // Check if we should process.
        if( ! $order->maybe_process() ) return;

        // Check for meta.
        if( get_post_meta( $order_id, '_northbeam_purchase', true ) ) return;

        // Output purchase event. ?>
        <script type="application/javascript">window.Northbeam.firePurchaseEvent(<?php echo wp_json_encode( $order->get_purchase_event() ); ?>);</script><?php

        // Update meta.
        update_post_meta( $order_id, '_northbeam_purchase', true );

    }

    /**
     * Order complete.
     * 
     * @since   1.0.0
     */
    public function order_complete( $order_id ) {

        // Get classes.
        $order  = new \Northbeam\Public\order( $order_id );
        $api    = new \Northbeam\Public\API();

        // Do action.
        do_action( 'northbeam_order_before_process', $order, $api );

        // Continue?
        if( ! $order->maybe_process() ) return;

        // Send API request.
        $response = $api->request( 'orders', $order->get_order(), 'POST' );

        // Do action.
        do_action( 'northbeam_order_after_process', $order, $api, $response );

        // Check response.
        if( $response === 200 ) {

            // Add order note.
            $order->add_note();

        }

    }

}
