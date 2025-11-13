<?php
// templates/admin/affiliates.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Affiliates', 'advanced-affiliate'); ?></h1>

    <div class="aas-admin-filters">
        <ul class="subsubsub">
            <li><a href="?page=aas-affiliates&status=all" <?php echo $status_filter === 'all' ? 'class="current"' : ''; ?>><?php _e('All', 'advanced-affiliate'); ?></a> |</li>
            <li><a href="?page=aas-affiliates&status=active" <?php echo $status_filter === 'active' ? 'class="current"' : ''; ?>><?php _e('Active', 'advanced-affiliate'); ?></a> |</li>
            <li><a href="?page=aas-affiliates&status=pending" <?php echo $status_filter === 'pending' ? 'class="current"' : ''; ?>><?php _e('Pending', 'advanced-affiliate'); ?></a> |</li>
            <li><a href="?page=aas-affiliates&status=rejected" <?php echo $status_filter === 'rejected' ? 'class="current"' : ''; ?>><?php _e('Rejected', 'advanced-affiliate'); ?></a></li>
        </ul>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'advanced-affiliate'); ?></th>
                <th><?php _e('Name', 'advanced-affiliate'); ?></th>
                <th><?php _e('Email', 'advanced-affiliate'); ?></th>
                <th><?php _e('Affiliate Code', 'advanced-affiliate'); ?></th>
                <th><?php _e('Commission Rate', 'advanced-affiliate'); ?></th>
                <th><?php _e('Total Earnings', 'advanced-affiliate'); ?></th>
                <th><?php _e('Status', 'advanced-affiliate'); ?></th>
                <th><?php _e('Actions', 'advanced-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($affiliates)): ?>
                <?php foreach ($affiliates as $affiliate): ?>
                <tr data-affiliate-id="<?php echo esc_attr($affiliate->id); ?>">
                    <td><?php echo esc_html($affiliate->id); ?></td>
                    <td><strong><?php echo esc_html($affiliate->display_name); ?></strong></td>
                    <td><?php echo esc_html($affiliate->user_email); ?></td>
                    <td><code><?php echo esc_html($affiliate->affiliate_code); ?></code></td>
                    <td><?php echo esc_html($affiliate->commission_rate); ?>%</td>
                    <td><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($affiliate->total_earnings, 2); ?></td>
                    <td>
                        <span class="aas-status-badge aas-status-<?php echo esc_attr($affiliate->status); ?>">
                            <?php echo ucfirst($affiliate->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($affiliate->status === 'pending'): ?>
                            <button class="button button-primary aas-approve-affiliate" data-id="<?php echo esc_attr($affiliate->id); ?>">
                                <?php _e('Approve', 'advanced-affiliate'); ?>
                            </button>
                            <button class="button aas-reject-affiliate" data-id="<?php echo esc_attr($affiliate->id); ?>">
                                <?php _e('Reject', 'advanced-affiliate'); ?>
                            </button>
                        <?php elseif ($affiliate->status === 'active'): ?>
                            <button class="button aas-deactivate-affiliate" data-id="<?php echo esc_attr($affiliate->id); ?>">
                                <?php _e('Deactivate', 'advanced-affiliate'); ?>
                            </button>
                        <?php elseif ($affiliate->status === 'inactive'): ?>
                            <button class="button button-primary aas-activate-affiliate" data-id="<?php echo esc_attr($affiliate->id); ?>">
                                <?php _e('Activate', 'advanced-affiliate'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <a href="?page=aas-affiliates&action=edit&id=<?php echo esc_attr($affiliate->id); ?>" class="button">
                            <?php _e('Edit', 'advanced-affiliate'); ?>
                        </a>
                        
                        <button class="button aas-delete-affiliate" data-id="<?php echo esc_attr($affiliate->id); ?>" style="color: #a00;">
                            <?php _e('Delete', 'advanced-affiliate'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8"><?php _e('No affiliates found.', 'advanced-affiliate'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.aas-status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.aas-status-active { background: #d4edda; color: #155724; }
.aas-status-pending { background: #fff3cd; color: #856404; }
.aas-status-rejected { background: #f8d7da; color: #721c24; }
.aas-status-inactive { background: #e2e3e5; color: #383d41; }
</style>

<script>
jQuery(document).ready(function($) {
    // Approve Affiliate
    $('.aas-approve-affiliate').on('click', function() {
        var affiliateId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        if (!confirm('<?php _e('Approve this affiliate?', 'advanced-affiliate'); ?>')) return;
        
        $.post(ajaxurl, {
            action: 'aas_approve_affiliate',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            affiliate_id: affiliateId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
            }
        });
    });
    
    // Reject Affiliate
    $('.aas-reject-affiliate').on('click', function() {
        var affiliateId = $(this).data('id');
        
        if (!confirm('<?php _e('Reject this affiliate?', 'advanced-affiliate'); ?>')) return;
        
        $.post(ajaxurl, {
            action: 'aas_reject_affiliate',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            affiliate_id: affiliateId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
            }
        });
    });
    
    // Activate Affiliate
    $('.aas-activate-affiliate').on('click', function() {
        var affiliateId = $(this).data('id');
        
        if (!confirm('<?php _e('Activate this affiliate?', 'advanced-affiliate'); ?>')) return;
        
        $.post(ajaxurl, {
            action: 'aas_activate_affiliate',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            affiliate_id: affiliateId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
            }
        });
    });
    
    // Deactivate Affiliate
    $('.aas-deactivate-affiliate').on('click', function() {
        var affiliateId = $(this).data('id');
        
        if (!confirm('<?php _e('Deactivate this affiliate?', 'advanced-affiliate'); ?>')) return;
        
        $.post(ajaxurl, {
            action: 'aas_deactivate_affiliate',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            affiliate_id: affiliateId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
            }
        });
    });
    
    // Delete Affiliate
    $('.aas-delete-affiliate').on('click', function() {
        var affiliateId = $(this).data('id');
        
        if (!confirm('<?php _e('Delete this affiliate? This will also delete all their commissions and referrals. This cannot be undone!', 'advanced-affiliate'); ?>')) return;
        
        $.post(ajaxurl, {
            action: 'aas_delete_affiliate',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            affiliate_id: affiliateId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
            }
        });
    });
});
</script>