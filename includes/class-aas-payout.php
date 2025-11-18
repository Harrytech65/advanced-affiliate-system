<?php
// includes/class-aas-payout.php

if (!defined('ABSPATH')) exit;

class AAS_Payout {
    
    public function __construct() {
        // add_action('wp_ajax_aas_request_payout', array($this, 'request_payout'));
        add_action('wp_ajax_aas_process_payout', array($this, 'process_payout_ajax'));
        add_action('wp_ajax_aas_process_payout', array($this, 'process_payout_ajax'));
    }
    
    // public function request_payout() {
    //     check_ajax_referer('aas_frontend_nonce', 'nonce');
        
    //     if (!is_user_logged_in()) {
    //         wp_send_json_error('Unauthorized - Please log in');
    //     }
        
    //     $user_id = get_current_user_id();
    //     $affiliate = AAS_Database::get_affiliate_by_user($user_id);
        
    //     if (!$affiliate) {
    //         wp_send_json_error('You are not registered as an affiliate');
    //     }
        
    //     if ($affiliate->status !== 'active') {
    //         wp_send_json_error('Your affiliate account is not active');
    //     }
        
    //     // Get approved/paid commissions total
    //     global $wpdb;
    //     $total_commissions = $wpdb->get_var($wpdb->prepare(
    //         "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions 
    //         WHERE affiliate_id = %d AND status IN ('approved', 'paid')",
    //         $affiliate->id
    //     ));
        
    //     $available = $total_commissions - $affiliate->total_paid;
        
    //     error_log('AAS Payout Request: Affiliate #' . $affiliate->id . ' - Total: ' . $total_commissions . ' Paid: ' . $affiliate->total_paid . ' Available: ' . $available);
        
    //     $threshold = get_option('aas_payout_threshold', 50);
        
    //     if ($available < $threshold) {
    //         wp_send_json_error(sprintf(
    //             'Minimum payout is %s %s. Your available balance is %s %s',
    //             get_option('aas_currency', 'USD'),
    //             number_format($threshold, 2),
    //             get_option('aas_currency', 'USD'),
    //             number_format($available, 2)
    //         ));
    //     }
        
    //     // Check for existing pending request
    //     $existing = $wpdb->get_var($wpdb->prepare(
    //         "SELECT COUNT(*) FROM {$wpdb->prefix}aas_payouts 
    //         WHERE affiliate_id = %d AND status = 'pending'",
    //         $affiliate->id
    //     ));
        
    //     if ($existing > 0) {
    //         wp_send_json_error('You already have a pending payout request. Please wait for admin approval.');
    //     }
        
    //     // Create payout request
    //     $result = $wpdb->insert(
    //         $wpdb->prefix . 'aas_payouts',
    //         array(
    //             'affiliate_id' => $affiliate->id,
    //             'amount' => $available,
    //             'method' => $affiliate->payment_method,
    //             'status' => 'pending'
    //         ),
    //         array('%d', '%f', '%s', '%s')
    //     );
        
    //     if (!$result) {
    //         error_log('AAS Payout Error: Failed to insert - ' . $wpdb->last_error);
    //         wp_send_json_error('Failed to create payout request. Please try again.');
    //     }
        
    //     $payout_id = $wpdb->insert_id;
        
    //     error_log('AAS Payout: Successfully created payout request #' . $payout_id);
        
    //     do_action('aas_payout_requested', $payout_id, $affiliate->id);
        
    //     wp_send_json_success('Payout request submitted successfully! Admin will process it soon.');
    // }
    
    public function process_payout_ajax() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $payout_id = intval($_POST['payout_id']);
        $transaction_id = sanitize_text_field($_POST['transaction_id']);
        
        global $wpdb;
        
        // Get payout details
        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_payouts WHERE id = %d",
            $payout_id
        ));
        
        if (!$payout) {
            wp_send_json_error('Payout not found');
        }
        
        // Update payout status
        $wpdb->update(
            $wpdb->prefix . 'aas_payouts',
            array(
                'status' => 'completed',
                'transaction_id' => $transaction_id,
                'paid_at' => current_time('mysql')
            ),
            array('id' => $payout_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        // Update affiliate total paid
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}aas_affiliates SET total_paid = total_paid + %f WHERE id = %d",
            $payout->amount,
            $payout->affiliate_id
        ));
        
        // Mark approved commissions as paid
        $wpdb->update(
            $wpdb->prefix . 'aas_commissions',
            array(
                'status' => 'paid',
                'paid_at' => current_time('mysql')
            ),
            array(
                'affiliate_id' => $payout->affiliate_id,
                'status' => 'approved'
            ),
            array('%s', '%s'),
            array('%d', '%s')
        );
        
        do_action('aas_payout_completed', $payout_id, $payout->affiliate_id);
        
        wp_send_json_success('Payout processed successfully');
    }
    
    public function get_pending_payouts() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT p.*, a.affiliate_code, u.display_name, u.user_email 
            FROM {$wpdb->prefix}aas_payouts p 
            LEFT JOIN {$wpdb->prefix}aas_affiliates a ON p.affiliate_id = a.id 
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
            WHERE p.status = 'pending' 
            ORDER BY p.created_at DESC"
        );
    }
    
    public function get_affiliate_payouts($affiliate_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_payouts WHERE affiliate_id = %d ORDER BY created_at DESC",
            $affiliate_id
        ));
    }
}