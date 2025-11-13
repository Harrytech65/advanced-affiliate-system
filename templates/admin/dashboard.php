<?php
// templates/admin/dashboard.php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get overall stats
$total_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aas_affiliates");
$active_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aas_affiliates WHERE status = 'active'");
$pending_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aas_affiliates WHERE status = 'pending'");

$total_commissions = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions");
$pending_commissions = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE status = 'pending'");
$paid_commissions = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE status = 'paid'");

$total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aas_referrals");
$total_conversions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aas_commissions");

$conversion_rate = $total_clicks > 0 ? round(($total_conversions / $total_clicks) * 100, 2) : 0;

// Recent affiliates
$recent_affiliates = $wpdb->get_results(
    "SELECT a.*, u.display_name, u.user_email 
    FROM {$wpdb->prefix}aas_affiliates a 
    LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
    ORDER BY a.created_at DESC 
    LIMIT 5"
);

// Top affiliates
$top_affiliates = $wpdb->get_results(
    "SELECT a.*, u.display_name, 
    COALESCE(SUM(c.amount), 0) as earnings 
    FROM {$wpdb->prefix}aas_affiliates a 
    LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
    LEFT JOIN {$wpdb->prefix}aas_commissions c ON a.id = c.affiliate_id 
    GROUP BY a.id 
    ORDER BY earnings DESC 
    LIMIT 5"
);

$currency = get_option('aas_currency', 'USD');
?>

<div class="wrap">
    <h1><?php _e('Affiliate Dashboard', 'advanced-affiliate'); ?></h1>

    <!-- Stats Overview -->
    <div class="aas-admin-stats">
        <div class="aas-stat-card">
            <h3><?php _e('Total Affiliates', 'advanced-affiliate'); ?></h3>
            <div class="stat-value"><?php echo number_format($total_affiliates); ?></div>
            <p class="stat-detail">
                <span class="active"><?php echo $active_affiliates; ?> <?php _e('Active', 'advanced-affiliate'); ?></span> | 
                <span class="pending"><?php echo $pending_affiliates; ?> <?php _e('Pending', 'advanced-affiliate'); ?></span>
            </p>
        </div>

        <div class="aas-stat-card">
            <h3><?php _e('Total Commissions', 'advanced-affiliate'); ?></h3>
            <div class="stat-value"><?php echo $currency; ?> <?php echo number_format($total_commissions, 2); ?></div>
            <p class="stat-detail"><?php _e('All time earnings', 'advanced-affiliate'); ?></p>
        </div>

        <div class="aas-stat-card">
            <h3><?php _e('Pending Payouts', 'advanced-affiliate'); ?></h3>
            <div class="stat-value"><?php echo $currency; ?> <?php echo number_format($pending_commissions, 2); ?></div>
            <p class="stat-detail"><?php _e('Awaiting approval', 'advanced-affiliate'); ?></p>
        </div>

        <div class="aas-stat-card">
            <h3><?php _e('Total Clicks', 'advanced-affiliate'); ?></h3>
            <div class="stat-value"><?php echo number_format($total_clicks); ?></div>
            <p class="stat-detail"><?php echo $conversion_rate; ?>% <?php _e('Conversion Rate', 'advanced-affiliate'); ?></p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="aas-quick-actions">
        <a href="?page=aas-affiliates&status=pending" class="button button-primary">
            <?php _e('Review Pending Affiliates', 'advanced-affiliate'); ?> 
            <?php if ($pending_affiliates > 0): ?>
                <span class="badge"><?php echo $pending_affiliates; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=aas-commissions&status=pending" class="button">
            <?php _e('Approve Commissions', 'advanced-affiliate'); ?>
        </a>
        <a href="?page=aas-payouts" class="button">
            <?php _e('Process Payouts', 'advanced-affiliate'); ?>
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
        
        <!-- Recent Affiliates -->
        <div class="aas-dashboard-widget">
            <h3><?php _e('Recent Affiliates', 'advanced-affiliate'); ?></h3>
            
            <?php if (!empty($recent_affiliates)): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'advanced-affiliate'); ?></th>
                        <th><?php _e('Code', 'advanced-affiliate'); ?></th>
                        <th><?php _e('Status', 'advanced-affiliate'); ?></th>
                        <th><?php _e('Date', 'advanced-affiliate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_affiliates as $affiliate): ?>
                    <tr>
                        <td><strong><?php echo esc_html($affiliate->display_name); ?></strong></td>
                        <td><code><?php echo esc_html($affiliate->affiliate_code); ?></code></td>
                        <td>
                            <span class="aas-status-badge aas-status-<?php echo esc_attr($affiliate->status); ?>">
                                <?php echo ucfirst($affiliate->status); ?>
                            </span>
                        </td>
                        <td><?php echo date_i18n('M j, Y', strtotime($affiliate->created_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php _e('No affiliates yet.', 'advanced-affiliate'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Top Performers -->
        <div class="aas-dashboard-widget">
            <h3><?php _e('Top Performing Affiliates', 'advanced-affiliate'); ?></h3>
            
            <?php if (!empty($top_affiliates)): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'advanced-affiliate'); ?></th>
                        <th><?php _e('Code', 'advanced-affiliate'); ?></th>
                        <th><?php _e('Earnings', 'advanced-affiliate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_affiliates as $affiliate): ?>
                    <tr>
                        <td><strong><?php echo esc_html($affiliate->display_name); ?></strong></td>
                        <td><code><?php echo esc_html($affiliate->affiliate_code); ?></code></td>
                        <td><strong><?php echo $currency; ?> <?php echo number_format($affiliate->earnings, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php _e('No data available yet.', 'advanced-affiliate'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity Chart -->
    <div class="aas-dashboard-widget" style="margin-top: 20px;">
        <h3><?php _e('Earnings Over Time (Last 30 Days)', 'advanced-affiliate'); ?></h3>
        <canvas id="aas-earnings-chart" style="max-height: 300px;"></canvas>
    </div>
</div>

<style>
.aas-quick-actions {
    margin: 20px 0;
}
.aas-quick-actions .button {
    margin-right: 10px;
}
.aas-quick-actions .badge {
    background: #dc3232;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 5px;
}
.stat-detail {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}
.stat-detail .active {
    color: #46b450;
    font-weight: 600;
}
.stat-detail .pending {
    color: #ffb900;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Prepare chart data
    <?php
    // Get earnings data for last 30 days
    $days = 30;
    $earnings_data = array();
    $labels = array();
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M j', strtotime($date));
        
        $daily_earnings = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions 
            WHERE DATE(created_at) = %s",
            $date
        ));
        
        $earnings_data[] = floatval($daily_earnings);
    }
    ?>
    
    var ctx = document.getElementById('aas-earnings-chart');
    if (ctx) {
        ctx = ctx.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Earnings (<?php echo $currency; ?>)',
                    data: <?php echo json_encode($earnings_data); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '<?php echo $currency; ?> ' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>