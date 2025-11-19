<?php
// includes/class-aas-woocommerce.php

if (!defined('ABSPATH')) exit;

class AAS_WooCommerce {
    
    public function __construct() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        add_action('woocommerce_order_status_completed', array($this, 'order_completed'));
        add_action('woocommerce_order_status_processing', array($this, 'order_processing')); // Add this
        add_action('woocommerce_order_status_refunded', array($this, 'order_refunded'));
        add_action('woocommerce_order_status_cancelled', array($this, 'order_cancelled'));
        add_action('woocommerce_order_status_refunded', array($this, 'handle_refund'), 10, 1);
        add_action('woocommerce_order_status_cancelled', array($this, 'handle_refund'), 10, 1);
    }
    
    public function order_processing($order_id) {
        // For testing - create commission on processing too
        $this->order_completed($order_id);
    }
    
    public function order_completed($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            error_log('AAS: Order not found - ' . $order_id);
            return;
        }
        
        // Get order total
        $order_total = $order->get_total();
        
        error_log('AAS: Order completed #' . $order_id . ' Total: ' . $order_total);
        
        // Check if order is from affiliate themselves
        if ($this->is_self_referral($order)) {
            error_log('AAS: Self-referral detected for order #' . $order_id);
            return;
        }
        
        // Get affiliate from cookie
        $tracking = new AAS_Tracking();
        $affiliate = $tracking->get_affiliate_from_cookie();
        
        if (!$affiliate) {
            error_log('AAS: No affiliate cookie found for order #' . $order_id);
            return;
        }
        
        error_log('AAS: Affiliate found - ID: ' . $affiliate->id . ', Code: ' . $affiliate->affiliate_code);
        
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
        // Check if refund handling is enabled
        if (get_option('aas_handle_refunds', 'yes') !== 'yes') {
            error_log('AAS: Refund handling disabled');
            return;
        }
        
        global $wpdb;
        
        error_log('AAS: Handling commission reversal for order #' . $order_id . ' Reason: ' . $reason);
        
        // Find commission for this order
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_commissions WHERE order_id = %d AND status != 'rejected'",
            $order_id
        ));
        
        if (!$commission) {
            error_log('AAS: No commission found for order #' . $order_id);
            return;
        }
        
        error_log('AAS: Found commission #' . $commission->id . ' Status: ' . $commission->status . ' Amount: ' . $commission->amount);
        
        // If already paid, create a deduction (negative commission)
        if ($commission->status === 'paid') {
            $deduction_id = AAS_Database::create_commission(array(
                'affiliate_id' => $commission->affiliate_id,
                'order_id' => $order_id,
                'amount' => -$commission->amount, // Negative amount
                'status' => 'approved',
                'type' => 'reversal',
                'description' => sprintf(__('Commission reversal - Order %s (Original Commission #%d)', 'advanced-affiliate'), $reason, $commission->id)
            ));
            
            error_log('AAS: Created deduction commission #' . $deduction_id);
            
            // Update original commission note
            $wpdb->update(
                $wpdb->prefix . 'aas_commissions',
                array('description' => $commission->description . ' [REFUNDED]'),
                array('id' => $commission->id)
            );
        } else {
            // If pending or approved, just reject it
            $wpdb->update(
                $wpdb->prefix . 'aas_commissions',
                array(
                    'status' => 'rejected',
                    'description' => $commission->description . sprintf(' [REJECTED: Order %s]', $reason)
                ),
                array('id' => $commission->id),
                array('%s', '%s'),
                array('%d')
            );
            
            error_log('AAS: Rejected commission #' . $commission->id);
        }
        
        // Update affiliate earnings
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}aas_affiliates SET total_earnings = total_earnings - %f WHERE id = %d",
            $commission->amount,
            $commission->affiliate_id
        ));
        
        error_log('AAS: Updated affiliate earnings');
        
        do_action('aas_commission_reversed', $commission->id, $order_id, $reason);
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
    public function handle_refund($order_id) {
        error_log('AAS Refund: Processing order #' . $order_id);
        
        // Check if refund handling is enabled
        if (get_option('aas_handle_refunds', 'yes') !== 'yes') {
            error_log('AAS Refund: Disabled in settings');
            return;
        }
        
        global $wpdb;
        
        // Get commission for this order
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_commissions WHERE order_id = %d",
            $order_id
        ));
        
        if (!$commission) {
            error_log('AAS Refund: No commission found for order #' . $order_id);
            return;
        }
        
        error_log('AAS Refund: Found commission #' . $commission->id . ' Status: ' . $commission->status);
        
        // Check refund period (default 30 days)
        $refund_period = get_option('aas_refund_period', 30);
        $commission_date = strtotime($commission->created_at);
        $days_since = floor((time() - $commission_date) / 86400);
        
        if ($days_since > $refund_period) {
            error_log('AAS Refund: Outside refund period (' . $days_since . ' days)');
            // Optionally notify admin
            return;
        }
        
        // Handle based on commission status
        if ($commission->status === 'pending') {
            // Just delete pending commission
            $wpdb->delete(
                $wpdb->prefix . 'aas_commissions',
                array('id' => $commission->id),
                array('%d')
            );
            error_log('AAS Refund: Deleted pending commission #' . $commission->id);
            
        } elseif ($commission->status === 'approved') {
            // Deduct from affiliate's balance
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}aas_affiliates 
                SET total_earnings = total_earnings - %f 
                WHERE id = %d",
                $commission->amount,
                $commission->affiliate_id
            ));
            
            // Mark commission as refunded
            $wpdb->update(
                $wpdb->prefix . 'aas_commissions',
                array(
                    'status' => 'refunded',
                    'description' => $commission->description . ' [REFUNDED]'
                ),
                array('id' => $commission->id),
                array('%s', '%s'),
                array('%d')
            );
            
            error_log('AAS Refund: ✅ Deducted ' . $commission->amount . ' from affiliate #' . $commission->affiliate_id);
            
            // Send notification to affiliate
            do_action('aas_commission_refunded', $commission->id, $commission->affiliate_id);
            
        } elseif ($commission->status === 'paid') {
            // Commission already paid - mark as refunded but keep record
            $wpdb->update(
                $wpdb->prefix . 'aas_commissions',
                array(
                    'status' => 'refunded_paid',
                    'description' => $commission->description . ' [REFUNDED AFTER PAYOUT]'
                ),
                array('id' => $commission->id),
                array('%s', '%s'),
                array('%d')
            );
            
            error_log('AAS Refund: ⚠️ Commission #' . $commission->id . ' was already paid out!');
            
            // Notify admin to manually recover
            do_action('aas_refund_after_payout', $commission->id, $commission->affiliate_id);
        }
    }
}