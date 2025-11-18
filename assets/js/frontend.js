/**
 * Frontend JavaScript for Advanced Affiliate System
 */

(function($) {
    'use strict';

    var AAS_Frontend = {
        
        init: function() {
            this.copyAffiliateLink();
            this.registerAffiliate();
            this.requestPayout();
            this.tabs();
            this.tooltips();
        },

        /**
         * Copy Affiliate Link
         */
        copyAffiliateLink: function() {
            $(document).on('click', '.aas-copy-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $input = $('#aas-affiliate-link');
                var link = $input.val();
                
                // Modern clipboard API
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(link).then(function() {
                        AAS_Frontend.showCopySuccess($btn);
                    }).catch(function() {
                        AAS_Frontend.fallbackCopy($input, $btn);
                    });
                } else {
                    AAS_Frontend.fallbackCopy($input, $btn);
                }
            });
        },

        /**
         * Fallback copy method
         */
        fallbackCopy: function($input, $btn) {
            $input.select();
            document.execCommand('copy');
            AAS_Frontend.showCopySuccess($btn);
        },

        /**
         * Show copy success
         */
        showCopySuccess: function($btn) {
            var originalText = $btn.text();
            $btn.text('Copied!').addClass('copied');
            
            setTimeout(function() {
                $btn.text(originalText).removeClass('copied');
            }, 2000);
        },

        /**
         * Register Affiliate
         */
        registerAffiliate: function() {
            $('#aas-registration-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $message = $('#aas-reg-message');
                var $btn = $form.find('button[type="submit"]');
                
                // Validate form
                if (!AAS_Frontend.validateForm($form)) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Submitting...');
                $message.removeClass('success error').hide();
                
                $.ajax({
                    url: aas_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aas_register_affiliate',
                        nonce: aas_ajax.nonce,
                        payment_email: $('#payment_email').val(),
                        payment_method: $('#payment_method').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.addClass('success')
                                   .text(response.data.message)
                                   .fadeIn();
                            
                            // Redirect after 2 seconds
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        } else {
                            $message.addClass('error')
                                   .text(response.data)
                                   .fadeIn();
                            $btn.prop('disabled', false).text('Submit Application');
                        }
                    },
                    error: function() {
                        $message.addClass('error')
                               .text('An error occurred. Please try again.')
                               .fadeIn();
                        $btn.prop('disabled', false).text('Submit Application');
                    }
                });
            });
        },

        /**
         * Validate Form
         */
        validateForm: function($form) {
            var isValid = true;
            
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (!value || value.trim() === '') {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
                
                // Email validation
                if ($field.attr('type') === 'email' && value) {
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        $field.addClass('error');
                        isValid = false;
                    }
                }
            });
            
            // Checkbox validation
            var termsCheckbox = $form.find('input[name="terms"]');
            if (termsCheckbox.length && !termsCheckbox.is(':checked')) {
                alert('Please accept the terms and conditions.');
                isValid = false;
            }
            
            return isValid;
        },

        /**
         * Request Payout
         */
        requestPayout: function() {
            $(document).on('click', '#aas-request-payout', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var originalText = $btn.text();
                
                if (!confirm('Request payout? Your available balance will be processed for payment.')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: aas_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aas_request_payout',
                        nonce: aas_ajax.nonce
                    },
                    success: function(response) {
                        console.log('Payout Response:', response);
                        if (response.success) {
                            alert(response.data);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            alert('Error: ' + response.data);
                            $btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Payout Error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText
                        });
                        alert('Request failed. Please try again.');
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        },

        /**
         * Tabs
         */
        tabs: function() {
            $('.aas-tabs .aas-tab-link').on('click', function(e) {
                e.preventDefault();
                
                var $tab = $(this);
                var targetId = $tab.attr('href');
                
                // Update active tab
                $('.aas-tab-link').removeClass('active');
                $tab.addClass('active');
                
                // Show target content
                $('.aas-tab-content').removeClass('active');
                $(targetId).addClass('active');
            });
        },

        /**
         * Tooltips
         */
        tooltips: function() {
            $('[data-tooltip]').each(function() {
                var $el = $(this);
                var tooltipText = $el.data('tooltip');
                
                $el.hover(
                    function() {
                        var $tooltip = $('<div class="aas-tooltip">' + tooltipText + '</div>');
                        $('body').append($tooltip);
                        
                        var pos = $el.offset();
                        $tooltip.css({
                            top: pos.top - $tooltip.outerHeight() - 10,
                            left: pos.left + ($el.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                        }).fadeIn(200);
                    },
                    function() {
                        $('.aas-tooltip').remove();
                    }
                );
            });
        },

        /**
         * Show Notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var noticeClass = 'aas-notice';
            if (type === 'success') noticeClass += ' aas-success';
            if (type === 'error') noticeClass += ' aas-error';
            
            var $notice = $('<div class="' + noticeClass + '">' + message + '</div>');
            
            // Insert at top of dashboard
            $('.aas-dashboard').prepend($notice);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AAS_Frontend.init();
    });

    // Add error styling for form validation
    var style = $('<style>.error { border-color: #dc3545 !important; }</style>');
    $('head').append(style);

})(jQuery);