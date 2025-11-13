/**
 * Admin JavaScript for Advanced Affiliate System
 */

(function($) {
    'use strict';

    var AAS_Admin = {
        
        init: function() {
            this.approveAffiliate();
            this.rejectAffiliate();
            this.approveCommission();
            this.rejectCommission();
            this.processPayout();
            this.bulkActions();
            this.exportData();
            this.charts();
            this.filters();
        },

        /**
         * Approve Affiliate
         */
        approveAffiliate: function() {
            $(document).on('click', '.aas-approve-affiliate', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var affiliateId = $btn.data('id');
                var $row = $btn.closest('tr');
                
                if (!confirm('Are you sure you want to approve this affiliate?')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aas_approve_affiliate',
                        nonce: aas_ajax.nonce,
                        affiliate_id: affiliateId
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.find('.aas-status-badge')
                                .removeClass('aas-status-pending')
                                .addClass('aas-status-active')
                                .text('Active');
                            $btn.remove();
                            AAS_Admin.showNotice('Affiliate approved successfully!', 'success');
                        } else {
                            alert(response.data);
                            $btn.prop('disabled', false).text('Approve');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $btn.prop('disabled', false).text('Approve');
                    }
                });
            });
        },

        /**
         * Reject Affiliate
         */
        rejectAffiliate: function() {
            $(document).on('click', '.aas-reject-affiliate', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var affiliateId = $btn.data('id');
                var $row = $btn.closest('tr');
                
                if (!confirm('Are you sure you want to reject this affiliate?')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aas_reject_affiliate',
                        nonce: aas_ajax.nonce,
                        affiliate_id: affiliateId
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.fadeOut(400, function() {
                                $(this).remove();
                            });
                            AAS_Admin.showNotice('Affiliate rejected.', 'success');
                        } else {
                            alert(response.data);
                            $btn.prop('disabled', false).text('Reject');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $btn.prop('disabled', false).text('Reject');
                    }
                });
            });
        },

        /**
         * Approve Commission
         */
        approveCommission: function() {
            $(document).on('click', '.aas-approve-commission', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var commissionId = $btn.data('id');
                var $row = $btn.closest('tr');
                
                if (!confirm('Approve this commission?')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aas_approve_commission',
                        nonce: aas_ajax.nonce,
                        commission_id: commissionId
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.find('.aas-status-badge')
                                .removeClass('aas-status-pending')
                                .addClass('aas-status-approved')
                                .text('Approved');
                            $btn.remove();
                            AAS_Admin.showNotice('Commission approved!', 'success');
                        } else {
                            alert(response.data);
                            $btn.prop('disabled', false).text('Approve');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $btn.prop('disabled', false).text('Approve');
                    }
                });
            });
        },

        /**
         * Reject Commission
         */
        rejectCommission: function() {
            $(document).on('click', '.aas-reject-commission', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var commissionId = $btn.data('id');
                
                if (!confirm('Reject this commission? This cannot be undone.')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aas_reject_commission',
                        nonce: aas_ajax.nonce,
                        commission_id: commissionId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                            $btn.prop('disabled', false).text('Reject');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $btn.prop('disabled', false).text('Reject');
                    }
                });
            });
        },

        /**
         * Process Payout
         */
        processPayout: function() {
            var currentPayoutId = null;
            
            $(document).on('click', '.aas-process-payout', function(e) {
                e.preventDefault();
                currentPayoutId = $(this).data('id');
                $('#aas-payout-modal').fadeIn();
            });
            
            $(document).on('click', '#aas-cancel-payout', function() {
                $('#aas-payout-modal').fadeOut();
                $('#aas-transaction-id').val('');
                currentPayoutId = null;
            });
            
            $(document).on('click', '#aas-confirm-payout', function() {
                var transactionId = $('#aas-transaction-id').val();
                
                if (!transactionId) {
                    alert('Please enter a transaction ID');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aas_process_payout',
                        nonce: aas_ajax.nonce,
                        payout_id: currentPayoutId,
                        transaction_id: transactionId
                    },
                    success: function(response) {
                        if (response.success) {
                            AAS_Admin.showNotice('Payout processed successfully!', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            alert(response.data);
                            $btn.prop('disabled', false).text('Confirm Payout');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $btn.prop('disabled', false).text('Confirm Payout');
                    }
                });
            });
        },

        /**
         * Bulk Actions
         */
        bulkActions: function() {
            $('.aas-bulk-action-btn').on('click', function(e) {
                e.preventDefault();
                
                var action = $('.aas-bulk-action-select').val();
                var selected = [];
                
                $('.aas-checkbox:checked').each(function() {
                    selected.push($(this).val());
                });
                
                if (selected.length === 0) {
                    alert('Please select items first.');
                    return;
                }
                
                if (!confirm('Apply ' + action + ' to ' + selected.length + ' items?')) {
                    return;
                }
                
                // Process bulk action via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aas_bulk_action',
                        nonce: aas_ajax.nonce,
                        bulk_action: action,
                        items: selected
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });
            
            // Select all checkbox
            $('#aas-select-all').on('change', function() {
                $('.aas-checkbox').prop('checked', $(this).prop('checked'));
            });
        },

        /**
         * Export Data
         */
        exportData: function() {
            $('.aas-export-btn').on('click', function(e) {
                e.preventDefault();
                
                var exportType = $(this).data('export');
                var $btn = $(this);
                
                $btn.prop('disabled', true).text('Exporting...');
                
                window.location.href = ajaxurl + '?action=aas_export_data&type=' + exportType + '&nonce=' + aas_ajax.nonce;
                
                setTimeout(function() {
                    $btn.prop('disabled', false).text('Export');
                }, 2000);
            });
        },

        /**
         * Charts
         */
        charts: function() {
            // Initialize charts if Chart.js is loaded
            if (typeof Chart !== 'undefined' && $('#aas-earnings-chart').length) {
                // Example chart initialization
                var ctx = document.getElementById('aas-earnings-chart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Earnings',
                            data: [],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        /**
         * Filters
         */
        filters: function() {
            // Date range filter
            $('#aas-date-from, #aas-date-to').on('change', function() {
                var from = $('#aas-date-from').val();
                var to = $('#aas-date-to').val();
                
                if (from && to) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('date_from', from);
                    url.searchParams.set('date_to', to);
                    window.location.href = url.toString();
                }
            });
            
            // Status filter
            $('.aas-status-filter').on('change', function() {
                var status = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('status', status);
                window.location.href = url.toString();
            });
        },

        /**
         * Show Notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after(notice);
            
            setTimeout(function() {
                notice.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Copy to Clipboard
         */
        copyToClipboard: function(text) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AAS_Admin.init();
    });

})(jQuery);