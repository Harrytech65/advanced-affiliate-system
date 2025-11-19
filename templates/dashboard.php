<?php
// templates/dashboard.php
if (!defined('ABSPATH')) exit;
?>

<div class="aas-dashboard">
    <div class="aas-header">
        <h2><?php _e('Affiliate Dashboard', 'advanced-affiliate'); ?></h2>
        <p><?php printf(__('Welcome back, %s!', 'advanced-affiliate'), wp_get_current_user()->display_name); ?></p>
    </div>

    <div class="aas-affiliate-link">
        <label><?php _e('Your Affiliate Link:', 'advanced-affiliate'); ?></label>
        <div class="aas-link-box">
            <input type="text" readonly value="<?php echo esc_url(home_url('?ref=' . $affiliate->affiliate_code)); ?>" id="aas-affiliate-link">
            <button class="aas-copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('aas-affiliate-link').value); this.textContent='Copied!'">
                <?php _e('Copy', 'advanced-affiliate'); ?>
            </button>
        </div>
    </div>

    <div class="aas-stats-grid">
        <div class="aas-stat-box">
            <div class="aas-stat-value"><?php echo number_format($stats['clicks']); ?></div>
            <div class="aas-stat-label"><?php _e('Total Clicks', 'advanced-affiliate'); ?></div>
        </div>

        <div class="aas-stat-box">
            <div class="aas-stat-value"><?php echo number_format($stats['conversions']); ?></div>
            <div class="aas-stat-label"><?php _e('Conversions', 'advanced-affiliate'); ?></div>
        </div>

        <div class="aas-stat-box">
            <div class="aas-stat-value"><?php echo $stats['conversion_rate']; ?>%</div>
            <div class="aas-stat-label"><?php _e('Conversion Rate', 'advanced-affiliate'); ?></div>
        </div>

        <div class="aas-stat-box">
            <div class="aas-stat-value"><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($stats['total_commissions'], 2); ?></div>
            <div class="aas-stat-label"><?php _e('Total Earnings', 'advanced-affiliate'); ?></div>
        </div>

        <div class="aas-stat-box">
            <div class="aas-stat-value"><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($stats['pending_commissions'], 2); ?></div>
            <div class="aas-stat-label"><?php _e('Pending', 'advanced-affiliate'); ?></div>
        </div>

        <div class="aas-stat-box">
            <div class="aas-stat-value"><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($stats['paid_commissions'], 2); ?></div>
            <div class="aas-stat-label"><?php _e('Paid Out', 'advanced-affiliate'); ?></div>
        </div>
    </div>

    <div class="aas-section">
        <h3><?php _e('Recent Commissions', 'advanced-affiliate'); ?></h3>
        
        <?php if (!empty($commissions)): ?>
        <table class="aas-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'advanced-affiliate'); ?></th>
                    <th><?php _e('Description', 'advanced-affiliate'); ?></th>
                    <th><?php _e('Amount', 'advanced-affiliate'); ?></th>
                    <th><?php _e('Status', 'advanced-affiliate'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($commissions, 0, 10) as $commission): ?>
                <tr>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($commission->created_at)); ?></td>
                    <td><?php echo esc_html($commission->description); ?></td>
                    <td><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($commission->amount, 2); ?></td>
                    <td><span class="aas-status aas-status-<?php echo esc_attr($commission->status); ?>"><?php echo ucfirst($commission->status); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No commissions yet. Start promoting to earn!', 'advanced-affiliate'); ?></p>
        <?php endif; ?>
    </div>

    <?php
    // Calculate available balance from database
    global $wpdb;
    $available_balance = $wpdb->get_var($wpdb->prepare(
        "SELECT (total_earnings - total_paid) FROM {$wpdb->prefix}aas_affiliates WHERE id = %d",
        $affiliate->id
    ));
    $threshold = get_option('aas_payout_threshold', 50);
    ?>
    
    <div class="aas-section">
        <h3><?php _e('Request Payout', 'advanced-affiliate'); ?></h3>
        <p><?php printf(__('Available balance: %s %s', 'advanced-affiliate'), get_option('aas_currency', 'USD'), number_format($available_balance, 2)); ?></p>
        <p><?php printf(__('Minimum payout: %s %s', 'advanced-affiliate'), get_option('aas_currency', 'USD'), number_format($threshold, 2)); ?></p>
        
        <?php if ($available_balance >= $threshold): ?>
        <button class="aas-btn aas-btn-primary" id="aas-request-payout"><?php _e('Request Payout', 'advanced-affiliate'); ?></button>
        <?php else: ?>
        <p class="aas-notice"><?php _e('You need to reach the minimum payout threshold.', 'advanced-affiliate'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Performance Chart -->
    <div class="aas-section">
        <h3><?php _e('Performance Overview (Last 30 Days)', 'advanced-affiliate'); ?></h3>
        <canvas id="aas-performance-chart" style="max-height: 300px;"></canvas>
    </div>
</div>

<style>
.aas-dashboard { max-width: 1200px; margin: 0 auto; padding: 20px; }
.aas-header { margin-bottom: 30px; }
.aas-affiliate-link { margin-bottom: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.aas-affiliate-link label { display: block; color: #fff; font-weight: 600; margin-bottom: 12px; font-size: 16px; }
.aas-link-box { display: flex; gap: 10px; }
.aas-link-box input { flex: 1; padding: 12px 15px; border: none; border-radius: 6px; font-size: 14px; background: rgba(255,255,255,0.95); color: #333; }
.aas-copy-btn { padding: 12px 25px; background: #fff; color: #667eea; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; white-space: nowrap; }
.aas-copy-btn:hover { background: #f8f9fa; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
.aas-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
.aas-stat-box { background: #fff; padding: 25px 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease; }
.aas-stat-box:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
.aas-stat-value { font-size: 36px; font-weight: bold; color: #667eea; margin-bottom: 8px; }
.aas-stat-label { font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.aas-section { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
.aas-section h3 { margin: 0 0 20px; font-size: 20px; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
.aas-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.aas-table thead { background: #f8f9fa; }
.aas-table th { padding: 14px 16px; text-align: left; font-weight: 600; color: #555; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
.aas-table td { padding: 14px 16px; border-bottom: 1px solid #f0f0f0; color: #666; }
.aas-table tbody tr:hover { background: #f8f9fa; }
.aas-status { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.aas-status-pending { background: #fff3cd; color: #856404; }
.aas-status-approved { background: #d4edda; color: #155724; }
.aas-status-paid { background: #d1ecf1; color: #0c5460; }
.aas-status-rejected { background: #f8d7da; color: #721c24; }
.aas-btn { padding: 12px 28px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s ease; }
.aas-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.aas-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
.aas-notice { padding: 15px 20px; background: #e7f3ff; border-left: 4px solid #2196f3; border-radius: 4px; margin: 15px 0; color: #0c5460; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
jQuery(document).ready(function($) {
    // Debug check
    console.log('AAS Ajax:', typeof aas_ajax !== 'undefined' ? aas_ajax : 'NOT LOADED');
    
    // Copy affiliate link
    $('.aas-copy-btn').on('click', function() {
        var link = $('#aas-affiliate-link').val();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(function() {
                $('.aas-copy-btn').text('Copied!');
                setTimeout(function() {
                    $('.aas-copy-btn').text('<?php _e('Copy', 'advanced-affiliate'); ?>');
                }, 2000);
            });
        }
    });
    
    // Request Payout
    $('#aas-request-payout').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php _e('Request payout? Your available balance will be processed for payment.', 'advanced-affiliate'); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Processing...');
        
        // Check if aas_ajax is defined
        if (typeof aas_ajax === 'undefined') {
            alert('Error: Ajax configuration not loaded. Please refresh the page.');
            $btn.prop('disabled', false).text(originalText);
            return;
        }
        
        $.ajax({
            url: aas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aas_request_payout',
                nonce: aas_ajax.nonce
            },
            success: function(response) {
                console.log('Success:', response);
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                alert('Request failed: ' + xhr.status + ' - Please check console for details');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Performance Chart (if exists)
    <?php 
    if (class_exists('AAS_Reports')) {
        $report = AAS_Reports::get_affiliate_report($affiliate->id, 30);
    ?>
    var ctx = document.getElementById('aas-performance-chart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($report['labels']); ?>,
                datasets: [
                    {
                        label: 'Clicks',
                        data: <?php echo json_encode($report['clicks']); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: 'Conversions',
                        data: <?php echo json_encode($report['conversions']); ?>,
                        borderColor: '#46b450',
                        backgroundColor: 'rgba(70, 180, 80, 0.1)',
                        borderWidth: 2,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    <?php } ?>
});
</script>