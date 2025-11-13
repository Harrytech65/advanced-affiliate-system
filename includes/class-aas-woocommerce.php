<?php
// includes/class-aas-woocommerce.php

if (!defined('ABSPATH')) exit;

class AAS_WooCommerce {
    
    public function __construct() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        add_action('woocommerce_order_status_completed', array($this, 'order_completed'));
        add_action('woocommerce_order_status_refunded', array($this, 'order_refunded'));
        add_action('woocommerce_order_status_cancelled', array($this, 'order_cancelled'));
    }
    
    public function order_completed($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Get order total
        $order_total = $order->get_total();
        
        // Check if order is from affiliate themselves
        if ($this->is_self_referral($order)) {
            return;
        }
        
        // Process commission
        do_action('aas_order_completed', $order_id, $order_total);
    }
    
    public function order_refunded($order_id) {
        $this->handle_commission_reversal($order_id, 'refunded');
    }
    
    public function order_cancelled($order_id) {
        $this->handle_commission_reversal($order_id, 'cancelled');
    }
    
    private function handle_commission_reversal($order_id, $reason) {
        global $wpdb;
        
        // Find commission for this order
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_commissions WHERE order_id = %d AND status != 'rejected'",
            $order_id
        ));
        
        if (!$commission) {
            return;
        }
        
        // If already paid, create a deduction
        if ($commission->status === 'paid') {
            AAS_Database::create_commission(array(
                'affiliate_id' => $commission->affiliate_id,
                'order_id' => $order_id,
                'amount' => -$commission->amount,
                'status' => 'approved',
                'type' => 'reversal',
                'description' => sprintf(__('Reversal due to order %s', 'advanced-affiliate'), $reason)
            ));
        } else {
            // Mark as rejected
            $wpdb->update(
                $wpdb->prefix . 'aas_commissions',
                array('status' => 'rejected'),
                array('id' => $commission->id),
                array('%s'),
                array('%d')
            );
        }
        
        // Update affiliate earnings
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}aas_affiliates SET total_earnings = total_earnings - %f WHERE id = %d",
            $commission->amount,
            $commission->affiliate_id
        ));
    }
    
    private function is_self_referral($order) {
        $tracking = new AAS_Tracking();
        $affiliate = $tracking->get_affiliate_from_cookie();
        
        if (!$affiliate) {
            return false;
        }
        
        // Check if order user is the affiliate
        $order_user_id = $order->get_user_id();
        
        if ($order_user_id && $order_user_id == $affiliate->user_id) {
            return true;
        }
        
        return false;
    }
}