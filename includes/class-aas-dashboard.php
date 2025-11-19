<?php
// includes/class-aas-dashboard.php

if (!defined('ABSPATH')) exit;

class AAS_Dashboard {
    
    public function __construct() {
        add_shortcode('aas_dashboard', array($this, 'render_dashboard'));
        add_shortcode('aas_registration', array($this, 'render_registration'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aas_register_affiliate', array($this, 'register_affiliate'));
        add_action('wp_ajax_aas_request_payout', array($this, 'handle_payout_request'));
    }
    
    public function enqueue_scripts() {
        if (is_page()) {
            wp_enqueue_style('aas-frontend', AAS_PLUGIN_URL . 'assets/css/frontend.css', array(), AAS_VERSION);
            wp_enqueue_script('aas-frontend', AAS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), AAS_VERSION, true);
            wp_localize_script('aas-frontend', 'aas_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aas_frontend_nonce')
            ));
        }
    }
    
    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your affiliate dashboard.', 'advanced-affiliate') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $affiliate = AAS_Database::get_affiliate_by_user($user_id);
        
        if (!$affiliate) {
            return '<p>' . __('You are not registered as an affiliate.', 'advanced-affiliate') . ' <a href="' . get_permalink(get_option('aas_affiliate_registration_page_id')) . '">' . __('Apply now', 'advanced-affiliate') . '</a></p>';
        }
        
        if ($affiliate->status === 'pending') {
            return '<div class="aas-notice aas-pending"><p>' . __('Your affiliate application is pending approval.', 'advanced-affiliate') . '</p></div>';
        }
        
        if ($affiliate->status === 'rejected') {
            return '<div class="aas-notice aas-error"><p>' . __('Your affiliate application was rejected.', 'advanced-affiliate') . '</p></div>';
        }
        
        $stats = AAS_Database::get_affiliate_stats($affiliate->id);
        $commissions = AAS_Database::get_commissions($affiliate->id);
        
        ob_start();
        include AAS_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }
    
    public function render_registration($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to apply as an affiliate.', 'advanced-affiliate') . ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Log in', 'advanced-affiliate') . '</a></p>';
        }
        
        $user_id = get_current_user_id();
        $affiliate = AAS_Database::get_affiliate_by_user($user_id);
        
        if ($affiliate) {
            return '<p>' . __('You are already registered as an affiliate.', 'advanced-affiliate') . ' <a href="' . get_permalink(get_option('aas_affiliate_dashboard_page_id')) . '">' . __('View Dashboard', 'advanced-affiliate') . '</a></p>';
        }
        
        ob_start();
        include AAS_PLUGIN_DIR . 'templates/registration.php';
        return ob_get_clean();
    }
    
    public function register_affiliate() {
        check_ajax_referer('aas_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in');
        }
        
        $user_id = get_current_user_id();
        
        // Check if already registered
        $existing = AAS_Database::get_affiliate_by_user($user_id);
        if ($existing) {
            wp_send_json_error('You are already registered');
        }
        
        $payment_email = sanitize_email($_POST['payment_email']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        
        if (!is_email($payment_email)) {
            wp_send_json_error('Invalid email address');
        }
        
        $auto_approve = get_option('aas_auto_approve', 'no');
        $status = $auto_approve === 'yes' ? 'active' : 'pending';
        
        $affiliate_id = AAS_Database::create_affiliate(array(
            'user_id' => $user_id,
            'payment_email' => sanitize_email($_POST['payment_email']),
            'payment_method' => sanitize_text_field($_POST['payment_method']),
            'country' => sanitize_text_field($_POST['country']),
            'bank_name' => sanitize_text_field($_POST['bank_name']),
            'account_holder_name' => sanitize_text_field($_POST['account_holder_name']),
            'account_number' => sanitize_text_field($_POST['account_number']),
            'routing_code' => sanitize_text_field($_POST['routing_code']),
            'bank_address' => sanitize_textarea_field($_POST['bank_address']),
            'upi_id' => sanitize_text_field($_POST['upi_id']),
            'other_payment_details' => sanitize_textarea_field($_POST['other_payment_details']),
            'status' => $status
        ));
        
        if ($affiliate_id) {
            do_action('aas_affiliate_registered', $affiliate_id, $user_id);
            wp_send_json_success(array(
                'message' => $status === 'active' ? 'Registration approved!' : 'Application submitted!',
                'redirect' => get_permalink(get_option('aas_affiliate_dashboard_page_id'))
            ));
        } else {
            wp_send_json_error('Registration failed');
        }
    }

    public function handle_payout_request() {
        check_ajax_referer('aas_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to request a payout');
        }
        
        $user_id = get_current_user_id();
        $affiliate = AAS_Database::get_affiliate_by_user($user_id);
        
        if (!$affiliate) {
            wp_send_json_error('You are not registered as an affiliate');
        }
        
        if ($affiliate->status !== 'active') {
            wp_send_json_error('Your affiliate account is not active');
        }
        
        global $wpdb;
        
        // Get current balance from affiliates table
        $balance_data = $wpdb->get_row($wpdb->prepare(
            "SELECT total_earnings, total_paid, (total_earnings - total_paid) as available 
            FROM {$wpdb->prefix}aas_affiliates WHERE id = %d",
            $affiliate->id
        ));

        $available = $balance_data->available;
        $threshold = get_option('aas_payout_threshold', 50);
        
        if ($available < $threshold) {
            wp_send_json_error(sprintf(
                'Minimum payout is %s %s. Your available balance is %s %s',
                get_option('aas_currency', 'USD'),
                number_format($threshold, 2),
                get_option('aas_currency', 'USD'),
                number_format($available, 2)
            ));
        }
        
        // Check for existing pending payout
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aas_payouts 
            WHERE affiliate_id = %d AND status = 'pending'",
            $affiliate->id
        ));
        
        if ($existing > 0) {
            wp_send_json_error('You already have a pending payout request');
        }
        
        // Create payout request
        $result = $wpdb->insert(
            $wpdb->prefix . 'aas_payouts',
            array(
                'affiliate_id' => $affiliate->id,
                'amount' => $available,
                'method' => $affiliate->payment_method,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s', '%s')
        );
        
        if (!$result) {
            error_log('Payout Insert Error: ' . $wpdb->last_error);
            wp_send_json_error('Failed to create payout request');
        }
        
        do_action('aas_payout_requested', $wpdb->insert_id, $affiliate->id);
        
        wp_send_json_success('Payout request submitted successfully! Admin will process it soon.');
    }
}