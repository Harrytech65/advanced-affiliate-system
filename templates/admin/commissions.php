<?php
// templates/admin/commissions.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Commissions', 'advanced-affiliate'); ?></h1>
    
    <!-- Bulk Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <a href="?page=aas-commissions&action=bulk_approve" class="button button-primary">
                <?php _e('Bulk Approve Pending', 'advanced-affiliate'); ?>
            </a>
            <a href="?page=aas-commissions&action=export" class="button">
                <?php _e('Export CSV', 'advanced-affiliate'); ?>
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <?php
    $total_commissions = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions");
    $pending_total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE status = 'pending'");
    $approved_total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE status = 'approved'");
    $paid_total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}aas_commissions WHERE status = 'paid'");
    $currency = get_option('aas_currency', 'USD');
    ?>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
        <div class="aas-stat-card">
            <h3><?php _e('Total Commissions', 'advanced-affiliate'); ?></h3>
            <div class="stat-value"><?php echo $currency; ?> <?php echo number_format($total_commissions, 2); ?></div>
        </div>
        <div class="aas-stat-card" style="border-left-color: #ffb900;">
            <h3><?php _e('Pending', 'advanced-affiliate'); ?></h3>
            <div class="stat-value" style="color: #ffb900;"><?php echo $currency; ?> <?php echo number_format($pending_total, 2); ?></div>
        </div>
        <div class="aas-stat-card" style="border-left-color: #46b450;">
            <h3><?php _e('Approved', 'advanced-affiliate'); ?></h3>
            <div class="stat-value" style="color: #46b450;"><?php echo $currency; ?> <?php echo number_format($approved_total, 2); ?></div>
        </div>
        <div class="aas-stat-card" style="border-left-color: #0073aa;">
            <h3><?php _e('Paid Out', 'advanced-affiliate'); ?></h3>
            <div class="stat-value" style="color: #0073aa;"><?php echo $currency; ?> <?php echo number_format($paid_total, 2); ?></div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="aas-admin-filters">
        <ul class="subsubsub">
            <li><a href="?page=aas-commissions&status=all" <?php echo $status_filter === 'all' ? 'class="current"' : ''; ?>><?php _e('All', 'advanced-affiliate'); ?></a> |</li>
            <li><a href="?page=aas-commissions&status=pending" <?php echo $status_filter === 'pending' ? 'class="current"' : ''; ?>><?php _e('Pending', 'advanced-affiliate'); ?></a> |</li>
            <li><a href="?page=aas-commissions&status=approved" <?php echo $status_filter === 'approved' ? 'class="current"' : ''; ?>><?php _e('Approved', 'advanced-affiliate'); ?></a> |</li>
            <li><a href="?page=aas-commissions&status=paid" <?php echo $status_filter === 'paid' ? 'class="current"' : ''; ?>><?php _e('Paid', 'advanced-affiliate'); ?></a> |</li>
            <li><a href="?page=aas-commissions&status=rejected" <?php echo $status_filter === 'rejected' ? 'class="current"' : ''; ?>><?php _e('Rejected', 'advanced-affiliate'); ?></a></li>
        </ul>
    </div>

    <!-- Commissions Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'advanced-affiliate'); ?></th>
                <th><?php _e('Date', 'advanced-affiliate'); ?></th>
                <th><?php _e('Affiliate', 'advanced-affiliate'); ?></th>
                <th><?php _e('Order ID', 'advanced-affiliate'); ?></th>
                <th><?php _e('Amount', 'advanced-affiliate'); ?></th>
                <th><?php _e('Type', 'advanced-affiliate'); ?></th>
                <th><?php _e('Status', 'advanced-affiliate'); ?></th>
                <th><?php _e('Actions', 'advanced-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($commissions)): ?>
                <?php foreach ($commissions as $commission): ?>
                <tr>
                    <td><?php echo esc_html($commission->id); ?></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($commission->created_at)); ?></td>
                    <td>
                        <strong><?php echo esc_html($commission->display_name); ?></strong><br>
                        <small><?php echo esc_html($commission->affiliate_code); ?></small>
                    </td>
                    <td>
                        <?php if ($commission->order_id): ?>
                            <a href="<?php echo admin_url('post.php?post=' . $commission->order_id . '&action=edit'); ?>">
                                #<?php echo esc_html($commission->order_id); ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($commission->amount, 2); ?></strong></td>
                    <td><?php echo ucfirst($commission->type); ?></td>
                    <td>
                        <span class="aas-status-badge aas-status-<?php echo esc_attr($commission->status); ?>">
                            <?php echo ucfirst($commission->status); ?>
                        </span>
                        <?php if (strpos($commission->description, 'REFUNDED') !== false): ?>
                            <br><small style="color: #dc3232;">⚠ Refunded</small>
                        <?php elseif (strpos($commission->description, 'REJECTED') !== false): ?>
                            <br><small style="color: #dc3232;">⚠ Order Issue</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($commission->status === 'pending'): ?>
                            <button class="button button-primary aas-approve-commission" data-id="<?php echo esc_attr($commission->id); ?>">
                                <?php _e('Approve', 'advanced-affiliate'); ?>
                            </button>
                            <button class="button aas-reject-commission" data-id="<?php echo esc_attr($commission->id); ?>" style="color: #a00;">
                                <?php _e('Reject', 'advanced-affiliate'); ?>
                            </button>
                        <?php elseif ($commission->status === 'approved'): ?>
                            <button class="button button-primary aas-mark-paid-commission" data-id="<?php echo esc_attr($commission->id); ?>">
                                <?php _e('Mark as Paid', 'advanced-affiliate'); ?>
                            </button>
                        <?php elseif ($commission->status === 'paid'): ?>
                            <span style="color: #46b450;">✓ <?php _e('Paid', 'advanced-affiliate'); ?></span>
                        <?php endif; ?>
                        
                        <button class="button aas-view-commission-details" data-id="<?php echo esc_attr($commission->id); ?>">
                            <?php _e('Details', 'advanced-affiliate'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8"><?php _e('No commissions found.', 'advanced-affiliate'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Commission Details Modal -->
<div id="aas-commission-modal" style="display:none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h2><?php _e('Commission Details', 'advanced-affiliate'); ?></h2>
        <div id="aas-commission-details-content"></div>
        <button class="button" id="aas-close-modal"><?php _e('Close', 'advanced-affiliate'); ?></button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.aas-approve-commission').on('click', function() {
        var commissionId = $(this).data('id');
        if (!confirm('<?php _e('Approve this commission?', 'advanced-affiliate'); ?>')) return;
        $.post(ajaxurl, {
            action: 'aas_approve_commission',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            commission_id: commissionId
        }, function(response) {
            if (response.success) { location.reload(); } else { alert(response.data); }
        });
    });
    
    $('.aas-reject-commission').on('click', function() {
        var commissionId = $(this).data('id');
        if (!confirm('<?php _e('Reject this commission? This cannot be undone.', 'advanced-affiliate'); ?>')) return;
        $.post(ajaxurl, {
            action: 'aas_reject_commission',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            commission_id: commissionId
        }, function(response) {
            if (response.success) { location.reload(); } else { alert(response.data); }
        });
    });
    
    $('.aas-mark-paid-commission').on('click', function() {
        var commissionId = $(this).data('id');
        if (!confirm('<?php _e('Mark this commission as paid?', 'advanced-affiliate'); ?>')) return;
        $.post(ajaxurl, {
            action: 'aas_mark_paid_commission',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            commission_id: commissionId
        }, function(response) {
            if (response.success) { location.reload(); } else { alert(response.data); }
        });
    });
    
    $('.aas-view-commission-details').on('click', function() {
        var commissionId = $(this).data('id');
        $.post(ajaxurl, {
            action: 'aas_get_commission_details',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            commission_id: commissionId
        }, function(response) {
            if (response.success) {
                $('#aas-commission-details-content').html(response.data.html);
                $('#aas-commission-modal').css('display', 'flex');
            } else {
                alert(response.data);
            }
        });
    });
    
    $('#aas-close-modal').on('click', function() {
        $('#aas-commission-modal').hide();
    });
});
</script>