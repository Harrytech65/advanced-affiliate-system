<?php
// includes/class-aas-reports.php

if (!defined('ABSPATH')) exit;

class AAS_Reports {
    
    /**
     * Get affiliate performance report
     */
    public static function get_affiliate_report($affiliate_id, $days = 30) {
        global $wpdb;
        
        $report = array(
            'clicks' => array(),
            'conversions' => array(),
            'earnings' => array(),
            'labels' => array()
        );
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $report['labels'][] = date('M j', strtotime($date));
            
            // Daily clicks
            $clicks = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_referrals 
                WHERE affiliate_id = %d AND DATE(created_at) = %s",
                $affiliate_id, $date
            ));
            $report['clicks'][] = intval($clicks);
            
            // Daily conversions
            $conversions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_commissions 
                WHERE affiliate_id = %d AND DATE(created_at) = %s",
                $affiliate_id, $date
            ));
            $report['conversions'][] = intval($conversions);
            
            // Daily earnings
            $earnings = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions 
                WHERE affiliate_id = %d AND DATE(created_at) = %s",
                $affiliate_id, $date
            ));
            $report['earnings'][] = floatval($earnings);
        }
        
        return $report;
    }
    
    /**
     * Get top referring URLs
     */
    public static function get_top_referrers($affiliate_id, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT referrer_url, COUNT(*) as count 
            FROM {$wpdb->prefix}aas_referrals 
            WHERE affiliate_id = %d AND referrer_url IS NOT NULL AND referrer_url != '' 
            GROUP BY referrer_url 
            ORDER BY count DESC 
            LIMIT %d",
            $affiliate_id, $limit
        ));
    }
    
    /**
     * Get conversion by time of day
     */
    public static function get_conversion_by_hour($affiliate_id) {
        global $wpdb;
        
        $data = array();
        for ($h = 0; $h < 24; $h++) {
            $conversions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_commissions 
                WHERE affiliate_id = %d AND HOUR(created_at) = %d",
                $affiliate_id, $h
            ));
            $data[] = intval($conversions);
        }
        
        return $data;
    }
    
    /**
     * Get monthly summary
     */
    public static function get_monthly_summary($affiliate_id) {
        global $wpdb;
        
        $current_month_start = date('Y-m-01');
        $last_month_start = date('Y-m-01', strtotime('first day of last month'));
        $last_month_end = date('Y-m-t', strtotime('last month'));
        
        // Current month stats
        $current = array(
            'clicks' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_referrals 
                WHERE affiliate_id = %d AND DATE(created_at) >= %s",
                $affiliate_id, $current_month_start
            )),
            'conversions' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_commissions 
                WHERE affiliate_id = %d AND DATE(created_at) >= %s",
                $affiliate_id, $current_month_start
            )),
            'earnings' => $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions 
                WHERE affiliate_id = %d AND DATE(created_at) >= %s",
                $affiliate_id, $current_month_start
            ))
        );
        
        // Last month stats
        $last = array(
            'clicks' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_referrals 
                WHERE affiliate_id = %d AND DATE(created_at) BETWEEN %s AND %s",
                $affiliate_id, $last_month_start, $last_month_end
            )),
            'conversions' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aas_commissions 
                WHERE affiliate_id = %d AND DATE(created_at) BETWEEN %s AND %s",
                $affiliate_id, $last_month_start, $last_month_end
            )),
            'earnings' => $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions 
                WHERE affiliate_id = %d AND DATE(created_at) BETWEEN %s AND %s",
                $affiliate_id, $last_month_start, $last_month_end
            ))
        );
        
        // Calculate changes
        return array(
            'current' => $current,
            'last' => $last,
            'change' => array(
                'clicks' => $last['clicks'] > 0 ? round((($current['clicks'] - $last['clicks']) / $last['clicks']) * 100, 2) : 0,
                'conversions' => $last['conversions'] > 0 ? round((($current['conversions'] - $last['conversions']) / $last['conversions']) * 100, 2) : 0,
                'earnings' => $last['earnings'] > 0 ? round((($current['earnings'] - $last['earnings']) / $last['earnings']) * 100, 2) : 0
            )
        );
    }
    
    /**
     * Export data to CSV
     */
    public static function export_to_csv($type, $affiliate_id = null) {
        global $wpdb;
        
        $filename = 'affiliate_' . $type . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch($type) {
            case 'commissions':
                fputcsv($output, array('Date', 'Affiliate', 'Order ID', 'Amount', 'Status'));
                
                $where = $affiliate_id ? $wpdb->prepare("WHERE c.affiliate_id = %d", $affiliate_id) : "";
                
                $commissions = $wpdb->get_results(
                    "SELECT c.*, a.affiliate_code 
                    FROM {$wpdb->prefix}aas_commissions c 
                    LEFT JOIN {$wpdb->prefix}aas_affiliates a ON c.affiliate_id = a.id 
                    {$where} 
                    ORDER BY c.created_at DESC"
                );
                
                foreach ($commissions as $commission) {
                    fputcsv($output, array(
                        $commission->created_at,
                        $commission->affiliate_code,
                        $commission->order_id,
                        $commission->amount,
                        $commission->status
                    ));
                }
                break;
                
            case 'clicks':
                fputcsv($output, array('Date', 'Affiliate', 'Referrer URL', 'Landing URL', 'IP Address'));
                
                $where = $affiliate_id ? $wpdb->prepare("WHERE r.affiliate_id = %d", $affiliate_id) : "";
                
                $referrals = $wpdb->get_results(
                    "SELECT r.*, a.affiliate_code 
                    FROM {$wpdb->prefix}aas_referrals r 
                    LEFT JOIN {$wpdb->prefix}aas_affiliates a ON r.affiliate_id = a.id 
                    {$where} 
                    ORDER BY r.created_at DESC 
                    LIMIT 10000"
                );
                
                foreach ($referrals as $referral) {
                    fputcsv($output, array(
                        $referral->created_at,
                        $referral->affiliate_code,
                        $referral->referrer_url,
                        $referral->landing_url,
                        $referral->ip_address
                    ));
                }
                break;
        }
        
        fclose($output);
        exit;
    }
}