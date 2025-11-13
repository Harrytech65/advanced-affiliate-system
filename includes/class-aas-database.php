<?php
// includes/class-aas-database.php

if (!defined('ABSPATH')) exit;

class AAS_Database {
    
    // Get affiliate by user ID
    public static function get_affiliate_by_user($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_affiliates WHERE user_id = %d",
            $user_id
        ));
    }
    
    // Get affiliate by code
    public static function get_affiliate_by_code($code) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_affiliates WHERE affiliate_code = %s",
            $code
        ));
    }
    
    // Create affiliate
    public static function create_affiliate($data) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => 0,
            'affiliate_code' => self::generate_affiliate_code(),
            'status' => 'pending',
            'commission_rate' => get_option('aas_commission_rate', 10)
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $wpdb->insert(
            $wpdb->prefix . 'aas_affiliates',
            $data,
            array('%d', '%s', '%s', '%f')
        );
        
        return $wpdb->insert_id;
    }
    
    // Update affiliate
    public static function update_affiliate($affiliate_id, $data) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'aas_affiliates',
            $data,
            array('id' => $affiliate_id),
            null,
            array('%d')
        );
    }
    
    // Log referral
    public static function log_referral($affiliate_id, $data) {
        global $wpdb;
        
        $defaults = array(
            'affiliate_id' => $affiliate_id,
            'visit_id' => md5(uniqid(rand(), true)),
            'ip_address' => self::get_ip_address(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $wpdb->insert(
            $wpdb->prefix . 'aas_referrals',
            $data
        );
        
        return $data['visit_id'];
    }
    
    // Create commission
    public static function create_commission($data) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'pending',
            'type' => 'sale'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $wpdb->insert(
            $wpdb->prefix . 'aas_commissions',
            $data
        );
        
        return $wpdb->insert_id;
    }
    
    // Get affiliate commissions
    public static function get_commissions($affiliate_id, $status = null) {
        global $wpdb;
        
        $where = $wpdb->prepare("WHERE affiliate_id = %d", $affiliate_id);
        
        if ($status) {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}aas_commissions {$where} ORDER BY created_at DESC"
        );
    }
    
    // Get affiliate stats
    public static function get_affiliate_stats($affiliate_id) {
        global $wpdb;
        
        $stats = array();
        
        // Total clicks
        $stats['clicks'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aas_referrals WHERE affiliate_id = %d",
            $affiliate_id
        ));
        
        // Total commissions
        $stats['total_commissions'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE affiliate_id = %d",
            $affiliate_id
        ));
        
        // Pending commissions
        $stats['pending_commissions'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE affiliate_id = %d AND status = 'pending'",
            $affiliate_id
        ));
        
        // Paid commissions
        $stats['paid_commissions'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE affiliate_id = %d AND status = 'paid'",
            $affiliate_id
        ));
        
        // Conversion count
        $stats['conversions'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aas_commissions WHERE affiliate_id = %d",
            $affiliate_id
        ));
        
        // Conversion rate
        $stats['conversion_rate'] = $stats['clicks'] > 0 ? round(($stats['conversions'] / $stats['clicks']) * 100, 2) : 0;
        
        return $stats;
    }
    
    // Generate unique affiliate code
    private static function generate_affiliate_code() {
        global $wpdb;
        
        do {
            $code = 'AFF' . strtoupper(wp_generate_password(8, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_affiliates WHERE affiliate_code = %s",
                $code
            ));
        } while ($exists);
        
        return $code;
    }
    
    // Get IP address
    private static function get_ip_address() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
}