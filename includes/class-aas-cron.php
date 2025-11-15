<?php
// includes/class-aas-cron.php

if (!defined('ABSPATH')) exit;

class AAS_Cron {
    
    public function __construct() {
        // Schedule cron job
        add_action('wp', array($this, 'schedule_events'));
        
        // Hook cron actions
        add_action('aas_daily_cron', array($this, 'auto_approve_commissions'));
    }
    
    /**
     * Schedule cron events
     */
    public function schedule_events() {
        if (!wp_next_scheduled('aas_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'aas_daily_cron');
        }
    }
    
    /**
     * Auto-approve commissions after refund period
     */
    public function auto_approve_commissions() {
        // Check if auto-approve is enabled
        if (get_option('aas_auto_approve_commissions') !== 'yes') {
            return;
        }
        
        $refund_period = intval(get_option('aas_refund_period', 30));
        
        if ($refund_period <= 0) {
            return;
        }
        
        global $wpdb;
        
        // Calculate cutoff date
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$refund_period} days"));
        
        // Get pending commissions older than refund period
        $commissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_commissions 
            WHERE status = 'pending' 
            AND created_at <= %s",
            $cutoff_date
        ));
        
        $approved_count = 0;
        
        foreach ($commissions as $commission) {
            // Check if order still exists and is not refunded
            if ($commission->order_id) {
                $order = wc_get_order($commission->order_id);
                
                // Skip if order is refunded or cancelled
                if (!$order || in_array($order->get_status(), array('refunded', 'cancelled', 'failed'))) {
                    continue;
                }
            }
            
            // Auto-approve
            $wpdb->update(
                $wpdb->prefix . 'aas_commissions',
                array('status' => 'approved'),
                array('id' => $commission->id),
                array('%s'),
                array('%d')
            );
            
            $approved_count++;
            
            do_action('aas_commission_auto_approved', $commission->id, $commission->affiliate_id);
        }
        
        if ($approved_count > 0) {
            error_log("AAS Cron: Auto-approved {$approved_count} commissions");
        }
    }
    
    /**
     * Clear scheduled events on plugin deactivation
     */
    public static function clear_scheduled_events() {
        wp_clear_scheduled_hook('aas_daily_cron');
    }
}