<?php
// includes/class-aas-admin.php

if (!defined('ABSPATH')) exit;

class AAS_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_aas_approve_affiliate', array($this, 'approve_affiliate'));
        add_action('wp_ajax_aas_reject_affiliate', array($this, 'reject_affiliate'));
        add_action('wp_ajax_aas_activate_affiliate', array($this, 'activate_affiliate'));
        add_action('wp_ajax_aas_deactivate_affiliate', array($this, 'deactivate_affiliate'));
        add_action('wp_ajax_aas_delete_affiliate', array($this, 'delete_affiliate'));
        add_action('wp_ajax_aas_approve_commission', array($this, 'approve_commission'));
        add_action('wp_ajax_aas_reject_commission', array($this, 'reject_commission'));
        add_action('wp_ajax_aas_mark_paid_commission', array($this, 'mark_paid_commission'));
        add_action('wp_ajax_aas_get_commission_details', array($this, 'get_commission_details'));
        add_action('wp_ajax_aas_process_payout', array($this, 'process_payout_ajax'));
    }
    
    public function add_menu_pages() {
        add_menu_page(
            __('Affiliates', 'advanced-affiliate'),
            __('Affiliates', 'advanced-affiliate'),
            'manage_options',
            'aas-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'aas-dashboard',
            __('Dashboard', 'advanced-affiliate'),
            __('Dashboard', 'advanced-affiliate'),
            'manage_options',
            'aas-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'aas-dashboard',
            __('All Affiliates', 'advanced-affiliate'),
            __('All Affiliates', 'advanced-affiliate'),
            'manage_options',
            'aas-affiliates',
            array($this, 'affiliates_page')
        );
        
        add_submenu_page(
            'aas-dashboard',
            __('Commissions', 'advanced-affiliate'),
            __('Commissions', 'advanced-affiliate'),
            'manage_options',
            'aas-commissions',
            array($this, 'commissions_page')
        );
        
        add_submenu_page(
            'aas-dashboard',
            __('Payouts', 'advanced-affiliate'),
            __('Payouts', 'advanced-affiliate'),
            'manage_options',
            'aas-payouts',
            array($this, 'payouts_page')
        );
        
        add_submenu_page(
            'aas-dashboard',
            __('Settings', 'advanced-affiliate'),
            __('Settings', 'advanced-affiliate'),
            'manage_options',
            'aas-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'aas-') !== false) {
            wp_enqueue_style('aas-admin', AAS_PLUGIN_URL . 'assets/css/admin.css', array(), AAS_VERSION);
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
            wp_enqueue_script('aas-admin', AAS_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'chart-js'), AAS_VERSION, true);
            wp_localize_script('aas-admin', 'aas_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aas_admin_nonce')
            ));
        }
    }
    
    public function dashboard_page() {
        include AAS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public function affiliates_page() {
        global $wpdb;
        
        // Handle edit action
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            include AAS_PLUGIN_DIR . 'templates/admin/edit-affiliate.php';
            return;
        }
        
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        
        $where = "WHERE 1=1";
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }
        
        $affiliates = $wpdb->get_results(
            "SELECT a.*, u.user_email, u.display_name 
            FROM {$wpdb->prefix}aas_affiliates a 
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
            {$where} 
            ORDER BY a.created_at DESC"
        );
        
        include AAS_PLUGIN_DIR . 'templates/admin/affiliates.php';
    }
    
    public function commissions_page() {
        global $wpdb;
        
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        
        $where = "WHERE 1=1";
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND c.status = %s", $status_filter);
        }
        
        $commissions = $wpdb->get_results(
            "SELECT c.*, a.affiliate_code, u.display_name 
            FROM {$wpdb->prefix}aas_commissions c 
            LEFT JOIN {$wpdb->prefix}aas_affiliates a ON c.affiliate_id = a.id 
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
            {$where} 
            ORDER BY c.created_at DESC 
            LIMIT 100"
        );
        
        include AAS_PLUGIN_DIR . 'templates/admin/commissions.php';
    }
    
    public function payouts_page() {
        include AAS_PLUGIN_DIR . 'templates/admin/payouts.php';
    }
    
    public function settings_page() {
        if (isset($_POST['aas_save_settings']) && check_admin_referer('aas_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'advanced-affiliate') . '</p></div>';
        }
        
        include AAS_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    private function save_settings() {
        $settings = array(
            'aas_commission_type',
            'aas_commission_rate',
            'aas_cookie_duration',
            'aas_payout_method',
            'aas_payout_threshold',
            'aas_auto_approve',
            'aas_require_approval',
            'aas_refund_period',
            'aas_auto_approve_commissions',
            'aas_handle_refunds'
        );
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }
    }

    
    public function approve_affiliate() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $affiliate_id = intval($_POST['affiliate_id']);
        
        AAS_Database::update_affiliate($affiliate_id, array('status' => 'active'));
        
        wp_send_json_success('Affiliate approved');
    }
    
    public function reject_affiliate() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $affiliate_id = intval($_POST['affiliate_id']);
        
        AAS_Database::update_affiliate($affiliate_id, array('status' => 'rejected'));
        
        wp_send_json_success('Affiliate rejected');
    }
    
    public function activate_affiliate() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $affiliate_id = intval($_POST['affiliate_id']);
        
        AAS_Database::update_affiliate($affiliate_id, array('status' => 'active'));
        
        do_action('aas_affiliate_activated', $affiliate_id);
        
        wp_send_json_success('Affiliate activated successfully');
    }
    
    public function deactivate_affiliate() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $affiliate_id = intval($_POST['affiliate_id']);
        
        AAS_Database::update_affiliate($affiliate_id, array('status' => 'inactive'));
        
        do_action('aas_affiliate_deactivated', $affiliate_id);
        
        wp_send_json_success('Affiliate deactivated successfully');
    }
    
    public function delete_affiliate() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $affiliate_id = intval($_POST['affiliate_id']);
        
        global $wpdb;
        
        // Delete all related data
        $wpdb->delete($wpdb->prefix . 'aas_referrals', array('affiliate_id' => $affiliate_id));
        $wpdb->delete($wpdb->prefix . 'aas_commissions', array('affiliate_id' => $affiliate_id));
        $wpdb->delete($wpdb->prefix . 'aas_payouts', array('affiliate_id' => $affiliate_id));
        $wpdb->delete($wpdb->prefix . 'aas_affiliates', array('id' => $affiliate_id));
        
        do_action('aas_affiliate_deleted', $affiliate_id);
        
        wp_send_json_success('Affiliate deleted successfully');
    }
    
    public function approve_commission() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $commission_id = intval($_POST['commission_id']);
        
        $commission_handler = new AAS_Commission();
        $commission_handler->approve_commission($commission_id);
        
        wp_send_json_success('Commission approved');
    }
    
    public function reject_commission() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $commission_id = intval($_POST['commission_id']);
        
        $commission_handler = new AAS_Commission();
        $commission_handler->reject_commission($commission_id);
        
        wp_send_json_success('Commission rejected');
    }
    
    public function mark_paid_commission() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $commission_id = intval($_POST['commission_id']);
        
        $commission_handler = new AAS_Commission();
        $commission_handler->mark_commission_paid($commission_id);
        
        wp_send_json_success('Commission marked as paid');
    }
    public function get_commission_details() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $commission_id = intval($_POST['commission_id']);
        
        global $wpdb;
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, a.affiliate_code, u.display_name, u.user_email
            FROM {$wpdb->prefix}aas_commissions c
            LEFT JOIN {$wpdb->prefix}aas_affiliates a ON c.affiliate_id = a.id
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE c.id = %d",
            $commission_id
        ));
        
        if (!$commission) {
            wp_send_json_error('Commission not found');
        }
        
        $currency = get_option('aas_currency', 'USD');
        
        $html = '<table class="form-table">';
        $html .= '<tr><th>ID:</th><td>' . esc_html($commission->id) . '</td></tr>';
        $html .= '<tr><th>Affiliate:</th><td><strong>' . esc_html($commission->display_name) . '</strong> (' . esc_html($commission->affiliate_code) . ')</td></tr>';
        $html .= '<tr><th>Amount:</th><td><strong>' . $currency . ' ' . number_format($commission->amount, 2) . '</strong></td></tr>';
        $html .= '<tr><th>Commission Rate:</th><td>' . number_format($commission->commission_rate, 2) . '%</td></tr>';
        $html .= '<tr><th>Type:</th><td>' . ucfirst($commission->type) . '</td></tr>';
        $html .= '<tr><th>Status:</th><td><span class="aas-status-badge aas-status-' . esc_attr($commission->status) . '">' . ucfirst($commission->status) . '</span></td></tr>';
        
        if ($commission->order_id) {
            $html .= '<tr><th>Order:</th><td><a href="' . admin_url('post.php?post=' . $commission->order_id . '&action=edit') . '" target="_blank">Order #' . $commission->order_id . '</a></td></tr>';
        }
        
        $html .= '<tr><th>Description:</th><td>' . esc_html($commission->description) . '</td></tr>';
        $html .= '<tr><th>Created:</th><td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($commission->created_at)) . '</td></tr>';
        
        if ($commission->paid_at) {
            $html .= '<tr><th>Paid At:</th><td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($commission->paid_at)) . '</td></tr>';
        }
        
        $html .= '</table>';
        
        wp_send_json_success(array('html' => $html));
    }
    public function process_payout_ajax() {
        check_ajax_referer('aas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $payout_id = intval($_POST['payout_id']);
        $transaction_id = sanitize_text_field($_POST['transaction_id']);
        
        $payout_handler = new AAS_Payout();
        $result = $payout_handler->process_payout_admin($payout_id, $transaction_id);
        
        if ($result) {
            wp_send_json_success('Payout processed successfully');
        } else {
            wp_send_json_error('Failed to process payout');
        }
    }
}
