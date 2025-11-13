<?php
// templates/admin/edit-affiliate.php
if (!defined('ABSPATH')) exit;

$affiliate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$affiliate_id) {
    wp_die(__('Invalid affiliate ID', 'advanced-affiliate'));
}

global $wpdb;

$affiliate = $wpdb->get_row($wpdb->prepare(
    "SELECT a.*, u.user_email, u.display_name 
    FROM {$wpdb->prefix}aas_affiliates a 
    LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
    WHERE a.id = %d",
    $affiliate_id
));

if (!$affiliate) {
    wp_die(__('Affiliate not found', 'advanced-affiliate'));
}

// Handle form submission
if (isset($_POST['aas_update_affiliate']) && check_admin_referer('aas_update_affiliate')) {
    $update_data = array(
        'status' => sanitize_text_field($_POST['status']),
        'commission_rate' => floatval($_POST['commission_rate']),
        'payment_email' => sanitize_email($_POST['payment_email']),
        'payment_method' => sanitize_text_field($_POST['payment_method'])
    );
    
    AAS_Database::update_affiliate($affiliate_id, $update_data);
    
    echo '<div class="notice notice-success"><p>' . __('Affiliate updated successfully!', 'advanced-affiliate') . '</p></div>';
    
    // Refresh data
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, u.user_email, u.display_name 
        FROM {$wpdb->prefix}aas_affiliates a 
        LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
        WHERE a.id = %d",
        $affiliate_id
    ));
}

$stats = AAS_Database::get_affiliate_stats($affiliate_id);
?>

<div class="wrap">
    <h1><?php _e('Edit Affiliate', 'advanced-affiliate'); ?>: <?php echo esc_html($affiliate->display_name); ?></h1>
    
    <a href="?page=aas-affiliates" class="button">&larr; <?php _e('Back to Affiliates', 'advanced-affiliate'); ?></a>
    
    <hr>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        
        <!-- Edit Form -->
        <div class="aas-dashboard-widget">
            <h2><?php _e('Affiliate Details', 'advanced-affiliate'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('aas_update_affiliate'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('User', 'advanced-affiliate'); ?></label>
                        </th>
                        <td>
                            <strong><?php echo esc_html($affiliate->display_name); ?></strong><br>
                            <small><?php echo esc_html($affiliate->user_email); ?></small>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Affiliate Code', 'advanced-affiliate'); ?></label>
                        </th>
                        <td>
                            <code style="font-size: 16px; padding: 5px 10px; background: #f0f0f0; border-radius: 4px;">
                                <?php echo esc_html($affiliate->affiliate_code); ?>
                            </code>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Status', 'advanced-affiliate'); ?></label>
                        </th>
                        <td>
                            <select name="status" id="status" required>
                                <option value="active" <?php selected($affiliate->status, 'active'); ?>><?php _e('Active', 'advanced-affiliate'); ?></option>
                                <option value="inactive" <?php selected($affiliate->status, 'inactive'); ?>><?php _e('Inactive', 'advanced-affiliate'); ?></option>
                                <option value="pending" <?php selected($affiliate->status, 'pending'); ?>><?php _e('Pending', 'advanced-affiliate'); ?></option>
                                <option value="suspended" <?php selected($affiliate->status, 'suspended'); ?>><?php _e('Suspended', 'advanced-affiliate'); ?></option>
                                <option value="rejected" <?php selected($affiliate->status, 'rejected'); ?>><?php _e('Rejected', 'advanced-affiliate'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="commission_rate"><?php _e('Commission Rate (%)', 'advanced-affiliate'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="commission_rate" id="commission_rate" 
                                   value="<?php echo esc_attr($affiliate->commission_rate); ?>" 
                                   step="0.01" min="0" max="100" required class="regular-text">
                            <p class="description"><?php _e('Custom commission rate for this affiliate', 'advanced-affiliate'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="payment_email"><?php _e('Payment Email', 'advanced-affiliate'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="payment_email" id="payment_email" 
                                   value="<?php echo esc_attr($affiliate->payment_email); ?>" 
                                   required class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="payment_method"><?php _e('Payment Method', 'advanced-affiliate'); ?></label>
                        </th>
                        <td>
                            <select name="payment_method" id="payment_method" required>
                                <option value="paypal" <?php selected($affiliate->payment_method, 'paypal'); ?>>PayPal</option>
                                <option value="bank" <?php selected($affiliate->payment_method, 'bank'); ?>><?php _e('Bank Transfer', 'advanced-affiliate'); ?></option>
                                <option value="stripe" <?php selected($affiliate->payment_method, 'stripe'); ?>>Stripe</option>
                                <option value="other" <?php selected($affiliate->payment_method, 'other'); ?>><?php _e('Other', 'advanced-affiliate'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Registration Date', 'advanced-affiliate'); ?></label>
                        </th>
                        <td>
                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($affiliate->created_at)); ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Update Affiliate', 'advanced-affiliate'), 'primary', 'aas_update_affiliate'); ?>
            </form>
        </div>
        
        <!-- Stats Sidebar -->
        <div>
            <div class="aas-dashboard-widget">
                <h3><?php _e('Performance Stats', 'advanced-affiliate'); ?></h3>
                
                <div style="margin: 20px 0;">
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Total Clicks:', 'advanced-affiliate'); ?></strong><br>
                        <span style="font-size: 24px; color: #667eea;"><?php echo number_format($stats['clicks']); ?></span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Conversions:', 'advanced-affiliate'); ?></strong><br>
                        <span style="font-size: 24px; color: #46b450;"><?php echo number_format($stats['conversions']); ?></span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Conversion Rate:', 'advanced-affiliate'); ?></strong><br>
                        <span style="font-size: 24px; color: #ff9800;"><?php echo $stats['conversion_rate']; ?>%</span>
                    </div>
                    
                    <hr>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Total Earnings:', 'advanced-affiliate'); ?></strong><br>
                        <span style="font-size: 24px; color: #667eea;">
                            <?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($stats['total_commissions'], 2); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Pending:', 'advanced-affiliate'); ?></strong><br>
                        <span style="font-size: 18px; color: #ffb900;">
                            <?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($stats['pending_commissions'], 2); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Paid Out:', 'advanced-affiliate'); ?></strong><br>
                        <span style="font-size: 18px; color: #46b450;">
                            <?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($stats['paid_commissions'], 2); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="aas-dashboard-widget" style="margin-top: 20px;">
                <h3><?php _e('Quick Actions', 'advanced-affiliate'); ?></h3>
                
                <p>
                    <a href="?page=aas-commissions&affiliate_id=<?php echo $affiliate_id; ?>" class="button button-primary" style="width: 100%; text-align: center;">
                        <?php _e('View Commissions', 'advanced-affiliate'); ?>
                    </a>
                </p>
                
                <p>
                    <a href="?page=aas-payouts&affiliate_id=<?php echo $affiliate_id; ?>" class="button" style="width: 100%; text-align: center;">
                        <?php _e('View Payouts', 'advanced-affiliate'); ?>
                    </a>
                </p>
                
                <p>
                    <a href="?page=aas-affiliates&action=view_clicks&id=<?php echo $affiliate_id; ?>" class="button" style="width: 100%; text-align: center;">
                        <?php _e('View Click History', 'advanced-affiliate'); ?>
                    </a>
                </p>
            </div>
            
            <div class="aas-dashboard-widget" style="margin-top: 20px; background: #fff3cd; border-left: 4px solid #ffb900;">
                <h3><?php _e('Danger Zone', 'advanced-affiliate'); ?></h3>
                <p><?php _e('Deleting an affiliate will remove all their data permanently.', 'advanced-affiliate'); ?></p>
                <button class="button" onclick="deleteAffiliate(<?php echo $affiliate_id; ?>)" style="color: #dc3232;">
                    <?php _e('Delete Affiliate', 'advanced-affiliate'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteAffiliate(id) {
    if (!confirm('<?php _e('Are you sure? This will delete all commissions, referrals, and payouts. This cannot be undone!', 'advanced-affiliate'); ?>')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'aas_delete_affiliate',
        nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
        affiliate_id: id
    }, function(response) {
        if (response.success) {
            window.location.href = '?page=aas-affiliates';
        } else {
            alert(response.data);
        }
    });
}
</script>