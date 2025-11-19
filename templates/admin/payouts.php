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
                <td><strong style="font-size: 16px; color: #46b450;"><?php echo get_option('aas_currency', 'USD'); ?> <?php echo number_format($payout->amount, 2); ?></strong></td>
                <td>
                    <?php 
                    if ($payout->method === 'bank') {
                        echo '🏦 Bank Transfer';
                    } elseif ($payout->method === 'paypal') {
                        echo '💳 PayPal';
                    } elseif ($payout->method === 'upi') {
                        echo '📱 UPI';
                    } else {
                        echo ucfirst($payout->method);
                    }
                    ?>
                </td>
                <td>
                    <button class="button button-primary aas-view-payout-details" data-id="<?php echo esc_attr($payout->id); ?>">
                        👁️ <?php _e('View Details', 'advanced-affiliate'); ?>
                    </button>
                    <button class="button button-primary aas-process-payout" data-id="<?php echo esc_attr($payout->id); ?>" style="background: #46b450; border-color: #46b450;">
                        ✓ <?php _e('Mark as Paid', 'advanced-affiliate'); ?>
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
                <td><code><?php echo esc_html($payout->transaction_id); ?></code></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p><?php _e('No payout history yet.', 'advanced-affiliate'); ?></p>
    <?php endif; ?>
</div>

<!-- Payment Details Modal -->
<div id="aas-payment-details-modal" style="display:none;">
    <div class="aas-modal-overlay"></div>
    <div class="aas-modal-content-large">
        <button class="aas-modal-close" id="aas-close-details">&times;</button>
        <div id="aas-payment-details-content">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<!-- Process Payout Modal -->
<div id="aas-payout-modal" style="display:none;">
    <div class="aas-modal-overlay"></div>
    <div class="aas-modal-content">
        <h3><?php _e('Process Payout', 'advanced-affiliate'); ?></h3>
        <p><?php _e('Enter transaction ID or reference number:', 'advanced-affiliate'); ?></p>
        <input type="text" id="aas-transaction-id" placeholder="<?php _e('Transaction ID / Reference Number', 'advanced-affiliate'); ?>">
        <div class="aas-modal-actions">
            <button class="button button-primary" id="aas-confirm-payout"><?php _e('Confirm Payout', 'advanced-affiliate'); ?></button>
            <button class="button" id="aas-cancel-payout"><?php _e('Cancel', 'advanced-affiliate'); ?></button>
        </div>
    </div>
</div>

<style>
.aas-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 99999;
}
#aas-payout-modal,
#aas-payment-details-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
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
    position: relative;
    z-index: 100001;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
.aas-modal-content-large {
    background: white;
    padding: 30px;
    border-radius: 12px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    z-index: 100001;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
.aas-modal-content input {
    width: 100%;
    padding: 12px;
    margin: 15px 0;
    border: 2px solid #ddd;
    border-radius: 4px;
    font-size: 15px;
}
.aas-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}
.aas-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #dc3232;
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
    z-index: 100002;
}
.aas-modal-close:hover {
    background: #a00;
}
.aas-payment-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}
.aas-payment-info h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
}
.aas-payment-row {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid #e0e0e0;
}
.aas-payment-row:last-child {
    border-bottom: none;
}
.aas-payment-label {
    font-weight: 600;
    width: 180px;
    color: #555;
}
.aas-payment-value {
    flex: 1;
    color: #333;
}
.aas-payment-value code {
    background: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    border: 2px solid #ddd;
    display: inline-block;
}
.aas-copy-btn {
    margin-left: 10px;
    padding: 5px 12px;
    font-size: 12px;
}
.aas-amount-highlight {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0;
}
.aas-amount-highlight .amount {
    font-size: 36px;
    font-weight: bold;
    margin: 10px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentPayoutId = null;
    
    // View payment details
    $('.aas-view-payout-details').on('click', function() {
        var payoutId = $(this).data('id');
        
        $.post(ajaxurl, {
            action: 'aas_get_payout_details',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            payout_id: payoutId
        }, function(response) {
            if (response.success) {
                $('#aas-payment-details-content').html(response.data.html);
                $('#aas-payment-details-modal').fadeIn();
            } else {
                alert('Error loading payment details');
            }
        });
    });
    
    // Close details modal
    $('#aas-close-details, #aas-payment-details-modal .aas-modal-overlay').on('click', function() {
        $('#aas-payment-details-modal').fadeOut();
    });
    
    // Process payout
    $('.aas-process-payout').on('click', function() {
        currentPayoutId = $(this).data('id');
        $('#aas-payout-modal').fadeIn();
        $('#aas-transaction-id').focus();
    });
    
    $('#aas-cancel-payout, #aas-payout-modal .aas-modal-overlay').on('click', function() {
        $('#aas-payout-modal').fadeOut();
        $('#aas-transaction-id').val('');
        currentPayoutId = null;
    });
    
    $('#aas-confirm-payout').on('click', function() {
        var transactionId = $('#aas-transaction-id').val();
        
        if (!transactionId) {
            alert('<?php _e('Please enter a transaction ID', 'advanced-affiliate'); ?>');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');
        
        $.post(ajaxurl, {
            action: 'aas_process_payout',
            nonce: '<?php echo wp_create_nonce('aas_admin_nonce'); ?>',
            payout_id: currentPayoutId,
            transaction_id: transactionId
        }, function(response) {
            if (response.success) {
                alert('✓ Payout processed successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
                $btn.prop('disabled', false).text('<?php _e('Confirm Payout', 'advanced-affiliate'); ?>');
            }
        });
    });
    
    // Copy to clipboard function
    $(document).on('click', '.aas-copy-btn', function() {
        var text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(function() {
            var $btn = $('.aas-copy-btn');
            var originalText = $btn.text();
            $btn.text('✓ Copied!');
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        });
    });
});
</script>