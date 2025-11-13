<?php
// templates/admin/payouts.php
if (!defined('ABSPATH')) exit;

$payout_handler = new AAS_Payout();
$pending_payouts = $payout_handler->get_pending_payouts();
?>

<div class="wrap">
    <h1><?php _e('Payouts', 'advanced-affiliate'); ?></h1>

    <h2><?php _e('Pending Payout Requests', 'advanced-affiliate'); ?></h2>

    <?php if (!empty($pending_payouts)): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Request Date', 'advanced-affiliate'); ?></th>
                <th><?php _e('Affiliate', 'advanced-affiliate'); ?></th>
                <th><?php _e('Amount', 'advanced-affiliate'); ?></th>
                <th><?php _e('Payment Method', 'advanced-affiliate'); ?></th>
                <th><?php _e('Payment Email', 'advanced-affiliate'); ?></th>
                <th><?php _e('Actions', 'advanced-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pending_payouts as $payout): ?>
            <tr>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($payout->created_at)); ?></td>
                <td>
                    <strong><?php echo esc_html($payout->display_name); ?></strong><br>
                    <small><?php echo esc_html($payout->affiliate_code); ?></small>
                </td>
                <td><strong><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($payout->amount, 2); ?></strong></td>
                <td><?php echo ucfirst($payout->method); ?></td>
                <td><?php echo esc_html($payout->user_email); ?></td>
                <td>
                    <button class="button button-primary aas-process-payout" data-id="<?php echo esc_attr($payout->id); ?>">
                        <?php _e('Mark as Paid', 'advanced-affiliate'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p><?php _e('No pending payout requests.', 'advanced-affiliate'); ?></p>
    <?php endif; ?>

    <hr>

    <h2><?php _e('Payout History', 'advanced-affiliate'); ?></h2>
    
    <?php
    global $wpdb;
    $completed_payouts = $wpdb->get_results(
        "SELECT p.*, a.affiliate_code, u.display_name 
        FROM {$wpdb->prefix}aas_payouts p 
        LEFT JOIN {$wpdb->prefix}aas_affiliates a ON p.affiliate_id = a.id 
        LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
        WHERE p.status = 'completed' 
        ORDER BY p.paid_at DESC 
        LIMIT 50"
    );
    ?>

    <?php if (!empty($completed_payouts)): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Paid Date', 'advanced-affiliate'); ?></th>
                <th><?php _e('Affiliate', 'advanced-affiliate'); ?></th>
                <th><?php _e('Amount', 'advanced-affiliate'); ?></th>
                <th><?php _e('Method', 'advanced-affiliate'); ?></th>
                <th><?php _e('Transaction ID', 'advanced-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($completed_payouts as $payout): ?>
            <tr>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($payout->paid_at)); ?></td>
                <td>
                    <strong><?php echo esc_html($payout->display_name); ?></strong><br>
                    <small><?php echo esc_html($payout->affiliate_code); ?></small>
                </td>
                <td><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($payout->amount, 2); ?></td>
                <td><?php echo ucfirst($payout->method); ?></td>
                <td><?php echo esc_html($payout->transaction_id); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p><?php _e('No payout history yet.', 'advanced-affiliate'); ?></p>
    <?php endif; ?>
</div>

<div id="aas-payout-modal" style="display:none;">
    <div class="aas-modal-content">
        <h3><?php _e('Process Payout', 'advanced-affiliate'); ?></h3>
        <p><?php _e('Enter transaction ID or reference number:', 'advanced-affiliate'); ?></p>
        <input type="text" id="aas-transaction-id" placeholder="<?php _e('Transaction ID', 'advanced-affiliate'); ?>">
        <div class="aas-modal-actions">
            <button class="button button-primary" id="aas-confirm-payout"><?php _e('Confirm Payout', 'advanced-affiliate'); ?></button>
            <button class="button" id="aas-cancel-payout"><?php _e('Cancel', 'advanced-affiliate'); ?></button>
        </div>
    </div>
</div>

<style>
#aas-payout-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.aas-modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
}
.aas-modal-content input {
    width: 100%;
    padding: 10px;
    margin: 15px 0;
}
.aas-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentPayoutId = null;
    
    $('.aas-process-payout').on('click', function() {
        currentPayoutId = $(this).data('id');
        $('#aas-payout-modal').fadeIn();
    });
    
    $('#aas-cancel-payout').on('click', function() {
        $('#aas-payout-modal').fadeOut();
        currentPayoutId = null;
    });
    
    $('#aas-confirm-payout').on('click', function() {
        var transactionId = $('#aas-transaction-id').val();
        
        if (!transactionId) {
            alert('<?php _e('Please enter a transaction ID', 'advanced-affiliate'); ?>');
            return;
        }
        
        $.post(ajaxurl, {
            action: 'aas_process_payout',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            payout_id: currentPayoutId,
            transaction_id: transactionId
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