<?php
// templates/registration.php
if (!defined('ABSPATH')) exit;
?>

<div class="aas-registration">
    <div class="aas-reg-header">
        <h2><?php _e('Become an Affiliate', 'advanced-affiliate'); ?></h2>
        <p><?php _e('Join our affiliate program and start earning commissions!', 'advanced-affiliate'); ?></p>
    </div>

    <div class="aas-reg-benefits">
        <h3><?php _e('Program Benefits', 'advanced-affiliate'); ?></h3>
        <ul>
            <li>✓ <?php printf(__('Earn %s%% commission on every sale', 'advanced-affiliate'), get_option('aas_commission_rate', 10)); ?></li>
            <li>✓ <?php _e('Real-time tracking and reporting', 'advanced-affiliate'); ?></li>
            <li>✓ <?php printf(__('Get paid when you reach %s', 'advanced-affiliate'), get_option('aas_currency', 'USD') . ' ' . get_option('aas_payout_threshold', 50)); ?></li>
            <li>✓ <?php _e('Access to marketing materials', 'advanced-affiliate'); ?></li>
        </ul>
    </div>

    <form id="aas-registration-form" class="aas-form">
        <div class="aas-form-group">
            <label for="payment_email"><?php _e('PayPal Email / Payment Email', 'advanced-affiliate'); ?> *</label>
            <input type="email" id="payment_email" name="payment_email" required value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
            <small><?php _e('Enter the email where you want to receive payments', 'advanced-affiliate'); ?></small>
        </div>

        <div class="aas-form-group">
            <label for="payment_method"><?php _e('Preferred Payment Method', 'advanced-affiliate'); ?> *</label>
            <select id="payment_method" name="payment_method" required>
                <option value="paypal"><?php _e('PayPal', 'advanced-affiliate'); ?></option>
                <option value="bank"><?php _e('Bank Transfer', 'advanced-affiliate'); ?></option>
                <option value="other"><?php _e('Other', 'advanced-affiliate'); ?></option>
            </select>
        </div>

        <div class="aas-form-group">
            <label>
                <input type="checkbox" name="terms" required>
                <?php _e('I agree to the terms and conditions', 'advanced-affiliate'); ?> *
            </label>
        </div>

        <div class="aas-form-actions">
            <button type="submit" class="aas-btn aas-btn-primary"><?php _e('Submit Application', 'advanced-affiliate'); ?></button>
        </div>

        <div id="aas-reg-message"></div>
    </form>
</div>

<style>
.aas-registration { max-width: 600px; margin: 0 auto; padding: 20px; }
.aas-reg-header { text-align: center; margin-bottom: 30px; }
.aas-reg-benefits { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
.aas-reg-benefits ul { list-style: none; padding: 0; }
.aas-reg-benefits li { padding: 8px 0; font-size: 16px; }
.aas-form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.aas-form-group { margin-bottom: 20px; }
.aas-form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
.aas-form-group input[type="email"],
.aas-form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
.aas-form-group small { display: block; margin-top: 5px; color: #666; font-size: 14px; }
.aas-form-actions { margin-top: 30px; }
.aas-btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
.aas-btn-primary { background: #0073aa; color: white; }
.aas-btn-primary:hover { background: #005a87; }
#aas-reg-message { margin-top: 20px; padding: 10px; border-radius: 4px; display: none; }
#aas-reg-message.success { background: #d4edda; color: #155724; display: block; }
#aas-reg-message.error { background: #f8d7da; color: #721c24; display: block; }
</style>

<script>
jQuery(document).ready(function($) {
    $('#aas-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#aas-reg-message');
        var $btn = $form.find('button[type="submit"]');
        
        $btn.prop('disabled', true).text('<?php _e('Submitting...', 'advanced-affiliate'); ?>');
        
        $.post(aas_ajax.ajax_url, {
            action: 'aas_register_affiliate',
            nonce: aas_ajax.nonce,
            payment_email: $('#payment_email').val(),
            payment_method: $('#payment_method').val()
        }, function(response) {
            if (response.success) {
                $message.removeClass('error').addClass('success').text(response.data.message).show();
                setTimeout(function() {
                    window.location.href = response.data.redirect;
                }, 2000);
            } else {
                $message.removeClass('success').addClass('error').text(response.data).show();
                $btn.prop('disabled', false).text('<?php _e('Submit Application', 'advanced-affiliate'); ?>');
            }
        });
    });
});
</script>