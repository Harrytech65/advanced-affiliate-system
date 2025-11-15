<?php
// templates/admin/settings.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Affiliate Settings', 'advanced-affiliate'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('aas_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="aas_commission_type"><?php _e('Commission Type', 'advanced-affiliate'); ?></label>
                </th>
                <td>
                    <select name="aas_commission_type" id="aas_commission_type">
                        <option value="percentage" <?php selected(get_option('aas_commission_type'), 'percentage'); ?>>
                            <?php _e('Percentage', 'advanced-affiliate'); ?>
                        </option>
                        <option value="fixed" <?php selected(get_option('aas_commission_type'), 'fixed'); ?>>
                            <?php _e('Fixed Amount', 'advanced-affiliate'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Choose how commissions are calculated', 'advanced-affiliate'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aas_commission_rate"><?php _e('Default Commission Rate', 'advanced-affiliate'); ?></label>
                </th>
                <td>
                    <input type="number" name="aas_commission_rate" id="aas_commission_rate" 
                           value="<?php echo esc_attr(get_option('aas_commission_rate', 10)); ?>" 
                           step="0.01" min="0" class="regular-text">
                    <p class="description"><?php _e('Default commission percentage or fixed amount', 'advanced-affiliate'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aas_cookie_duration"><?php _e('Cookie Duration', 'advanced-affiliate'); ?></label>
                </th>
                <td>
                    <input type="number" name="aas_cookie_duration" id="aas_cookie_duration" 
                           value="<?php echo esc_attr(get_option('aas_cookie_duration', 30)); ?>" 
                           min="1" class="regular-text">
                    <p class="description"><?php _e('Number of days to track referrals', 'advanced-affiliate'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aas_payout_threshold"><?php _e('Minimum Payout', 'advanced-affiliate'); ?></label>
                </th>
                <td>
                    <input type="number" name="aas_payout_threshold" id="aas_payout_threshold" 
                           value="<?php echo esc_attr(get_option('aas_payout_threshold', 50)); ?>" 
                           step="0.01" min="0" class="regular-text">
                    <p class="description"><?php _e('Minimum amount before affiliates can request payout', 'advanced-affiliate'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aas_currency"><?php _e('Currency', 'advanced-affiliate'); ?></label>
                </th>
                <td>
                    <input type="text" name="aas_currency" id="aas_currency" 
                           value="<?php echo esc_attr(get_option('aas_currency', 'USD')); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('Currency code (e.g., USD, EUR, GBP)', 'advanced-affiliate'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('Auto-Approve Affiliates', 'advanced-affiliate'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="aas_auto_approve" value="yes" 
                               <?php checked(get_option('aas_auto_approve'), 'yes'); ?>>
                        <?php _e('Automatically approve new affiliate registrations', 'advanced-affiliate'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('Require Approval', 'advanced-affiliate'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="aas_require_approval" value="yes" 
                               <?php checked(get_option('aas_require_approval'), 'yes'); ?>>
                        <?php _e('Require admin approval for affiliate registration', 'advanced-affiliate'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php _e('Affiliate Pages', 'advanced-affiliate'); ?></label>
                </th>
                <td>
                    <p>
                        <strong><?php _e('Dashboard:', 'advanced-affiliate'); ?></strong> 
                        <a href="<?php echo get_permalink(get_option('aas_affiliate_dashboard_page_id')); ?>" target="_blank">
                            <?php echo get_the_title(get_option('aas_affiliate_dashboard_page_id')); ?>
                        </a>
                    </p>
                    <p>
                        <strong><?php _e('Registration:', 'advanced-affiliate'); ?></strong> 
                        <a href="<?php echo get_permalink(get_option('aas_affiliate_registration_page_id')); ?>" target="_blank">
                            <?php echo get_the_title(get_option('aas_affiliate_registration_page_id')); ?>
                        </a>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aas_refund_period"><?php _e('Refund Period (Days)', 'advanced-affiliate'); ?></label>
                </th>
                <td>
                    <input type="number" name="aas_refund_period" id="aas_refund_period" 
                           value="<?php echo esc_attr(get_option('aas_refund_period', 30)); ?>" 
                           min="0" max="365" class="regular-text">
                    <p class="description"><?php _e('Commission will be held for this many days before approval. Set 0 to disable.', 'advanced-affiliate'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php _e('Auto-Approve Commissions', 'advanced-affiliate'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="aas_auto_approve_commissions" value="yes" 
                               <?php checked(get_option('aas_auto_approve_commissions'), 'yes'); ?>>
                        <?php _e('Automatically approve commissions after refund period', 'advanced-affiliate'); ?>
                    </label>
                    <p class="description"><?php _e('If enabled, commissions will auto-approve after refund period expires', 'advanced-affiliate'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php _e('Handle Refunds', 'advanced-affiliate'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="aas_handle_refunds" value="yes" 
                               <?php checked(get_option('aas_handle_refunds', 'yes'), 'yes'); ?>>
                        <?php _e('Automatically deduct commission on order refund/cancellation', 'advanced-affiliate'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'advanced-affiliate'), 'primary', 'aas_save_settings'); ?>
    </form>
</div>