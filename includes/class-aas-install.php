<?php
// includes/class-aas-install.php

if (!defined('ABSPATH')) exit;

class AAS_Install {
    
    public static function activate() {
        self::create_tables();
        self::create_pages();
        self::set_default_options();
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        
        // Affiliates table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aas_affiliates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            affiliate_code varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            commission_rate decimal(10,2) DEFAULT 0,
            
            -- Personal Info
            website_url varchar(255),
            promotion_method text,
            
            -- Payment Info
            payment_email varchar(100),
            payment_method varchar(50),
            country varchar(10),
            
            -- Bank Details
            bank_name varchar(200),
            account_holder_name varchar(200),
            account_number varchar(100),
            routing_code varchar(50),
            bank_address text,
            
            -- UPI/Other
            upi_id varchar(100),
            other_payment_details text,
            
            total_earnings decimal(10,2) DEFAULT 0,
            total_paid decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY affiliate_code (affiliate_code),
            KEY user_id (user_id)
        ) $charset;";
        
        // Referrals/Visits table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aas_referrals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            visit_id varchar(32),
            ip_address varchar(45),
            referrer_url text,
            landing_url text,
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY visit_id (visit_id)
        ) $charset;";
        
        // Commissions table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aas_commissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            order_id bigint(20),
            amount decimal(10,2) NOT NULL,
            commission_rate decimal(10,2),
            status varchar(20) DEFAULT 'pending',
            type varchar(50) DEFAULT 'sale',
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset;";
        
        // Payouts table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aas_payouts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            method varchar(50),
            transaction_id varchar(100),
            status varchar(20) DEFAULT 'pending',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id)
        ) $charset;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }
    
    private static function create_pages() {
        $pages = array(
            'affiliate_dashboard' => array(
                'title' => __('Affiliate Dashboard', 'advanced-affiliate'),
                'content' => '[aas_dashboard]'
            ),
            'affiliate_registration' => array(
                'title' => __('Become an Affiliate', 'advanced-affiliate'),
                'content' => '[aas_registration]'
            )
        );
        
        foreach ($pages as $key => $page) {
            $page_id = get_option('aas_' . $key . '_page_id');
            if (!$page_id || !get_post($page_id)) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed'
                ));
                update_option('aas_' . $key . '_page_id', $page_id);
            }
        }
    }
    
    private static function set_default_options() {
        $defaults = array(
            'aas_commission_type' => 'percentage',
            'aas_commission_rate' => '10',
            'aas_cookie_duration' => '30',
            'aas_payout_method' => 'manual',
            'aas_payout_threshold' => '50',
            'aas_auto_approve' => 'no',
            'aas_currency' => 'USD',
            'aas_require_approval' => 'yes'
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}