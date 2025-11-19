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
        
        <h3 class="aas-section-title"><?php _e('Payment Information', 'advanced-affiliate'); ?></h3>
        
        <div class="aas-form-group">
            <label for="payment_method"><?php _e('Preferred Payment Method', 'advanced-affiliate'); ?> *</label>
            <select id="payment_method" name="payment_method" required>
                <option value=""><?php _e('Select Payment Method', 'advanced-affiliate'); ?></option>
                <option value="paypal"><?php _e('PayPal', 'advanced-affiliate'); ?></option>
                <option value="bank"><?php _e('Bank Transfer', 'advanced-affiliate'); ?></option>
                <option value="upi"><?php _e('UPI (India)', 'advanced-affiliate'); ?></option>
                <option value="other"><?php _e('Other', 'advanced-affiliate'); ?></option>
            </select>
        </div>

        <!-- PayPal Section -->
        <div id="paypal-section" class="payment-section" style="display:none;">
            <div class="aas-form-group">
                <label for="payment_email"><?php _e('PayPal Email', 'advanced-affiliate'); ?> *</label>
                <input type="email" id="payment_email" name="payment_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                <small><?php _e('Enter your PayPal email address', 'advanced-affiliate'); ?></small>
            </div>
        </div>

        <!-- Bank Transfer Section -->
        <div id="bank-section" class="payment-section" style="display:none;">
            <div class="aas-form-group">
                <label for="country"><?php _e('Country', 'advanced-affiliate'); ?> *</label>
                <select id="country" name="country">
                    <option value=""><?php _e('Select Country', 'advanced-affiliate'); ?></option>
                    <option value="IN">🇮🇳 India</option>
                    <option value="US">🇺🇸 United States</option>
                    <option value="GB">🇬🇧 United Kingdom</option>
                    <option value="PK">🇵🇰 Pakistan</option>
                    <option value="BD">🇧🇩 Bangladesh</option>
                    <option value="CA">🇨🇦 Canada</option>
                    <option value="AU">🇦🇺 Australia</option>
                    <option value="DE">🇩🇪 Germany</option>
                    <option value="FR">🇫🇷 France</option>
                    <option value="AE">🇦🇪 UAE</option>
                    <option value="SA">🇸🇦 Saudi Arabia</option>
                    <option value="SG">🇸🇬 Singapore</option>
                    <option value="OTHER">🌍 Other</option>
                </select>
            </div>

            <div class="aas-form-group">
                <label for="bank_name"><?php _e('Bank Name', 'advanced-affiliate'); ?> *</label>
                <input type="text" id="bank_name" name="bank_name" placeholder="e.g., State Bank of India">
            </div>

            <div class="aas-form-group">
                <label for="account_holder_name"><?php _e('Account Holder Name', 'advanced-affiliate'); ?> *</label>
                <input type="text" id="account_holder_name" name="account_holder_name" placeholder="Full name as per bank account">
            </div>

            <div class="aas-form-group">
                <label for="account_number"><?php _e('Account Number / IBAN', 'advanced-affiliate'); ?> *</label>
                <input type="text" id="account_number" name="account_number" placeholder="Account number or IBAN">
                <small><?php _e('For international transfers, provide IBAN if available', 'advanced-affiliate'); ?></small>
            </div>

            <div class="aas-form-group">
                <label for="routing_code"><?php _e('Routing Code (IFSC/SWIFT/BIC/Sort Code)', 'advanced-affiliate'); ?></label>
                <input type="text" id="routing_code" name="routing_code" placeholder="e.g., SBIN0001234 or SWIFT code">
                <small><?php _e('IFSC for India, SWIFT/BIC for international, Sort Code for UK', 'advanced-affiliate'); ?></small>
            </div>

            <div class="aas-form-group">
                <label for="bank_address"><?php _e('Bank Branch Address (Optional)', 'advanced-affiliate'); ?></label>
                <textarea id="bank_address" name="bank_address" rows="2" placeholder="Bank branch address"></textarea>
            </div>
        </div>

        <!-- UPI Section (India) -->
        <div id="upi-section" class="payment-section" style="display:none;">
            <div class="aas-form-group">
                <label for="upi_id"><?php _e('UPI ID', 'advanced-affiliate'); ?> *</label>
                <input type="text" id="upi_id" name="upi_id" placeholder="yourname@paytm">
                <small><?php _e('Your Google Pay, PhonePe, or Paytm UPI ID', 'advanced-affiliate'); ?></small>
            </div>
        </div>

        <!-- Other Payment Section -->
        <div id="other-section" class="payment-section" style="display:none;">
            <div class="aas-form-group">
                <label for="other_payment_details"><?php _e('Payment Details', 'advanced-affiliate'); ?> *</label>
                <textarea id="other_payment_details" name="other_payment_details" rows="3" placeholder="Provide your payment details"></textarea>
            </div>
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
.aas-registration { max-width: 700px; margin: 0 auto; padding: 20px; }
.aas-reg-header { text-align: center; margin-bottom: 30px; }
.aas-reg-header h2 { font-size: 32px; margin-bottom: 10px; }
.aas-reg-benefits { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; }
.aas-reg-benefits h3 { margin-top: 0; color: white; }
.aas-reg-benefits ul { list-style: none; padding: 0; margin: 0; }
.aas-reg-benefits li { padding: 10px 0; font-size: 16px; border-bottom: 1px solid rgba(255,255,255,0.2); }
.aas-reg-benefits li:last-child { border-bottom: none; }
.aas-form { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.aas-section-title { margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; color: #333; font-size: 18px; }
.aas-form-group { margin-bottom: 20px; }
.aas-form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
.aas-form-group input[type="email"],
.aas-form-group input[type="text"],
.aas-form-group textarea,
.aas-form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 15px; transition: border-color 0.3s; }
.aas-form-group input:focus,
.aas-form-group textarea:focus,
.aas-form-group select:focus { outline: none; border-color: #667eea; }
.aas-form-group small { display: block; margin-top: 6px; color: #666; font-size: 13px; }
.aas-form-group textarea { resize: vertical; font-family: inherit; }
.payment-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea; }
.aas-form-actions { margin-top: 30px; }
.aas-btn { padding: 14px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; width: 100%; transition: all 0.3s; }
.aas-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.aas-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
.aas-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
#aas-reg-message { margin-top: 20px; padding: 15px; border-radius: 6px; display: none; font-weight: 500; }
#aas-reg-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: block; }
#aas-reg-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: block; }
</style>

<script>
jQuery(document).ready(function($) {
    // Show/hide payment sections based on selection
    $('#payment_method').on('change', function() {
        var method = $(this).val();
        
        // Hide all sections
        $('.payment-section').hide();
        
        // Clear all inputs
        $('.payment-section input, .payment-section textarea').prop('required', false);
        
        // Show selected section and make fields required
        if (method === 'paypal') {
            $('#paypal-section').show();
            $('#payment_email').prop('required', true);
        } else if (method === 'bank') {
            $('#bank-section').show();
            $('#country, #bank_name, #account_holder_name, #account_number').prop('required', true);
        } else if (method === 'upi') {
            $('#upi-section').show();
            $('#upi_id').prop('required', true);
        } else if (method === 'other') {
            $('#other-section').show();
            $('#other_payment_details').prop('required', true);
        }
    });

    $('#aas-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#aas-reg-message');
        var $btn = $form.find('button[type="submit"]');
        
        $btn.prop('disabled', true).text('<?php _e('Submitting...', 'advanced-affiliate'); ?>');
        
        // Collect all form data
        var formData = {
            action: 'aas_register_affiliate',
            nonce: aas_ajax.nonce,
            payment_method: $('#payment_method').val(),
            payment_email: $('#payment_email').val(),
            country: $('#country').val(),
            bank_name: $('#bank_name').val(),
            account_holder_name: $('#account_holder_name').val(),
            account_number: $('#account_number').val(),
            routing_code: $('#routing_code').val(),
            bank_address: $('#bank_address').val(),
            upi_id: $('#upi_id').val(),
            other_payment_details: $('#other_payment_details').val()
        };
        
        $.post(aas_ajax.ajax_url, formData, function(response) {
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