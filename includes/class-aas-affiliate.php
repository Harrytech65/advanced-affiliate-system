<?php
// includes/class-aas-affiliate.php

if (!defined('ABSPATH')) exit;

class AAS_Affiliate {
    
    private $affiliate_id;
    private $data;
    
    public function __construct($affiliate_id = 0) {
        if ($affiliate_id > 0) {
            $this->affiliate_id = $affiliate_id;
            $this->load();
        }
    }
    
    private function load() {
        global $wpdb;
        $this->data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aas_affiliates WHERE id = %d",
            $this->affiliate_id
        ));
    }
    
    public function get_id() {
        return $this->affiliate_id;
    }
    
    public function get_code() {
        return $this->data->affiliate_code ?? '';
    }
    
    public function get_link($url = '') {
        if (empty($url)) {
            $url = home_url();
        }
        return add_query_arg('ref', $this->get_code(), $url);
    }
    
    public function get_status() {
        return $this->data->status ?? '';
    }
    
    public function is_active() {
        return $this->get_status() === 'active';
    }
    
    public function get_commission_rate() {
        return $this->data->commission_rate ?? 0;
    }
    
    public function get_total_earnings() {
        return $this->data->total_earnings ?? 0;
    }
    
    public function get_total_paid() {
        return $this->data->total_paid ?? 0;
    }
    
    public function get_balance() {
        return $this->get_total_earnings() - $this->get_total_paid();
    }
    
    public function get_stats() {
        return AAS_Database::get_affiliate_stats($this->affiliate_id);
    }
    
    public function get_commissions($status = null) {
        return AAS_Database::get_commissions($this->affiliate_id, $status);
    }
    
    public function get_payouts() {
        $payout_handler = new AAS_Payout();
        return $payout_handler->get_affiliate_payouts($this->affiliate_id);
    }
    
    public function activate() {
        return AAS_Database::update_affiliate($this->affiliate_id, array('status' => 'active'));
    }
    
    public function deactivate() {
        return AAS_Database::update_affiliate($this->affiliate_id, array('status' => 'inactive'));
    }
    
    public function suspend() {
        return AAS_Database::update_affiliate($this->affiliate_id, array('status' => 'suspended'));
    }
    
    public function update_commission_rate($rate) {
        return AAS_Database::update_affiliate($this->affiliate_id, array('commission_rate' => $rate));
    }
    
    public function get_user() {
        if (!empty($this->data->user_id)) {
            return get_user_by('ID', $this->data->user_id);
        }
        return null;
    }
    
    public static function get_by_user($user_id) {
        $affiliate = AAS_Database::get_affiliate_by_user($user_id);
        if ($affiliate) {
            return new self($affiliate->id);
        }
        return null;
    }
    
    public static function get_by_code($code) {
        $affiliate = AAS_Database::get_affiliate_by_code($code);
        if ($affiliate) {
            return new self($affiliate->id);
        }
        return null;
    }
    
    public static function create($user_id, $data = array()) {
        $defaults = array(
            'user_id' => $user_id,
            'status' => 'pending'
        );
        
        $data = wp_parse_args($data, $defaults);
        $affiliate_id = AAS_Database::create_affiliate($data);
        
        if ($affiliate_id) {
            return new self($affiliate_id);
        }
        
        return null;
    }
}