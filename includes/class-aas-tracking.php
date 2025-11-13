<?php
// includes/class-aas-tracking.php

if (!defined('ABSPATH')) exit;

class AAS_Tracking {
    
    private $cookie_name = 'aas_ref';
    
    public function __construct() {
        add_action('init', array($this, 'track_visit'));
        add_action('template_redirect', array($this, 'handle_affiliate_link'));
    }
    
    public function handle_affiliate_link() {
        // Check for affiliate parameter in URL
        $ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
        
        if (empty($ref)) {
            return;
        }
        
        // Get affiliate by code
        $affiliate = AAS_Database::get_affiliate_by_code($ref);
        
        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }
        
        // Set cookie
        $this->set_affiliate_cookie($affiliate->id);
        
        // Log referral
        AAS_Database::log_referral($affiliate->id, array(
            'referrer_url' => wp_get_referer(),
            'landing_url' => home_url($_SERVER['REQUEST_URI'])
        ));
        
        // Remove ref parameter and redirect
        $redirect_url = remove_query_arg('ref');
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    public function track_visit() {
        // If affiliate cookie exists, track the visit
        if (isset($_COOKIE[$this->cookie_name])) {
            $affiliate_id = intval($_COOKIE[$this->cookie_name]);
            
            // Validate affiliate still active
            $affiliate = $this->get_affiliate($affiliate_id);
            if ($affiliate && $affiliate->status === 'active') {
                // Track page view if needed
                do_action('aas_track_visit', $affiliate_id);
            }
        }
    }
    
    public function set_affiliate_cookie($affiliate_id) {
        $duration = get_option('aas_cookie_duration', 30);
        $expire = time() + ($duration * DAY_IN_SECONDS);
        
        setcookie(
            $this->cookie_name,
            $affiliate_id,
            $expire,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        
        $_COOKIE[$this->cookie_name] = $affiliate_id;
    }
    
    public function get_affiliate_from_cookie() {
        if (isset($_COOKIE[$this->cookie_name])) {
            $affiliate_id = intval($_COOKIE[$this->cookie_name]);
            return $this->get_affiliate($affiliate_id);
        }
        return null;
    }
    
    private function get_affiliate($affiliate_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_affiliates WHERE id = %d",
            $affiliate_id
        ));
    }
    
    public function clear_affiliate_cookie() {
        setcookie($this->cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        unset($_COOKIE[$this->cookie_name]);
    }
}