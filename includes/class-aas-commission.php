<?php
// includes/class-aas-commission.php

if (!defined('ABSPATH')) exit;

class AAS_Commission {
    
    public function __construct() {
        add_action('aas_order_completed', array($this, 'process_commission'), 10, 2);
    }
    
    public function process_commission($order_id, $order_total) {
        error_log('AAS Commission: Processing order #' . $order_id . ' Total: ' . $order_total);
        
        // Get affiliate from cookie
        $tracking = new AAS_Tracking();
        $affiliate = $tracking->get_affiliate_from_cookie();
        
        if (!$affiliate || $affiliate->status !== 'active') {
            error_log('AAS Commission: Invalid affiliate or not active');
            return false;
        }
        
        error_log('AAS Commission: Affiliate OK - ID: ' . $affiliate->id);
        
        // Check if commission already created for this order
        if ($this->commission_exists($order_id)) {
            error_log('AAS Commission: Already exists for order #' . $order_id);
            return false;
        }
        
        // Calculate commission
        $commission_amount = $this->calculate_commission($order_total, $affiliate);
        
        error_log('AAS Commission: Calculated amount: ' . $commission_amount);
        
        if ($commission_amount <= 0) {
            error_log('AAS Commission: Amount is zero or negative');
            return false;
        }
        
        // Create commission record
        $commission_id = AAS_Database::create_commission(array(
            'affiliate_id' => $affiliate->id,
            'order_id' => $order_id,
            'amount' => $commission_amount,
            'commission_rate' => $affiliate->commission_rate,
            'status' => 'pending',
            'type' => 'sale',
            'description' => sprintf(__('Commission for Order #%d', 'advanced-affiliate'), $order_id)
        ));
        
        error_log('AAS Commission: Created! ID: ' . $commission_id);
        
        // Update affiliate total earnings
        $this->update_affiliate_earnings($affiliate->id, $commission_amount);
        
        // Send notification
        do_action('aas_commission_created', $commission_id, $affiliate->id);
        
        return $commission_id;
    }
    
    private function calculate_commission($amount, $affiliate) {
        $commission_type = get_option('aas_commission_type', 'percentage');
        $rate = $affiliate->commission_rate > 0 ? $affiliate->commission_rate : get_option('aas_commission_rate', 10);
        
        if ($commission_type === 'percentage') {
            return ($amount * $rate) / 100;
        } else {
            return $rate; // Fixed amount
        }
    }
    
    private function commission_exists($order_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aas_commissions WHERE order_id = %d",
            $order_id
        ));
        return $count > 0;
    }
    
    private function update_affiliate_earnings($affiliate_id, $amount) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}aas_affiliates SET total_earnings = total_earnings + %f WHERE id = %d",
            $amount,
            $affiliate_id
        ));
    }
    
    public function approve_commission($commission_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'aas_commissions',
            array('status' => 'approved'),
            array('id' => $commission_id),
            array('%s'),
            array('%d')
        );
        
        do_action('aas_commission_approved', $commission_id);
        
        return $result;
    }
    
    public function reject_commission($commission_id) {
        global $wpdb;
        
        // Get commission details
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_commissions WHERE id = %d",
            $commission_id
        ));
        
        if (!$commission) {
            return false;
        }
        
        // Update status
        $result = $wpdb->update(
            $wpdb->prefix . 'aas_commissions',
            array('status' => 'rejected'),
            array('id' => $commission_id),
            array('%s'),
            array('%d')
        );
        
        // Reduce affiliate earnings
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}aas_affiliates SET total_earnings = total_earnings - %f WHERE id = %d",
            $commission->amount,
            $commission->affiliate_id
        ));
        
        do_action('aas_commission_rejected', $commission_id);
        
        return $result;
    }
    
    public function mark_commission_paid($commission_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'aas_commissions',
            array(
                'status' => 'paid',
                'paid_at' => current_time('mysql')
            ),
            array('id' => $commission_id),
            array('%s', '%s'),
            array('%d')
        );
        
        do_action('aas_commission_paid', $commission_id);
        
        return $result;
    }
}