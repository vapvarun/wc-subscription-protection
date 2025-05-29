/**
 * Admin JavaScript for WooCommerce Subscription Content Protection Plugin
 * Author: wbcom designs
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize admin functionality
        WbcomSubscriptionProtection.init();
        
    });

    /**
     * Main Admin Object
     */
    var WbcomSubscriptionProtection = {
        
        /**
         * Initialize all admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initMetaBox();
            this.initClassicEditor();
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Meta box checkbox dependencies
            $(document).on('change', 'input[name="wbcom_subscription_protected"]', function() {
                self.toggleProtectionFields($(this).is(':checked'));
            });
            
            // Product selection validation
            $(document).on('change', 'input[name="wbcom_subscription_required_products[]"]', function() {
                self.validateProductSelection();
            });
            
            // Classic editor popup events
            $(document).on('click', '#wbcom-add-protection-button', function(e) {
                e.preventDefault();
                self.openProtectionPopup();
            });
            
            $(document).on('click', '#wbcom-cancel-protection', function(e) {
                e.preventDefault();
                self.closeProtectionPopup();
            });
            
            $(document).on('click', '#wbcom-insert-protection', function(e) {
                e.preventDefault();
                self.insertProtectionShortcode();
            });
            
            // Close popup on outside click
            $(document).on('click', '#wbcom-protection-popup', function(e) {
                if (e.target === this) {
                    self.closeProtectionPopup();
                }
            });
            
            // Escape key to close popup
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#wbcom-protection-popup').is(':visible')) {
                    self.closeProtectionPopup();
                }
            });
        },

        /**
         * Initialize meta box functionality
         */
        initMetaBox: function() {
            // Check initial state
            var isProtected = $('input[name="wbcom_subscription_protected"]').is(':checked');
            this.toggleProtectionFields(isProtected);
            
            // Add helpful tooltips
            this.addTooltips();
            
            // Validate initial state
            this.validateProductSelection();
        },

        /**
         * Toggle protection fields based on main checkbox
         */
        toggleProtectionFields: function(show) {
            var $fields = $('.wbcom-protection-field').not(':first');
            
            if (show) {
                $fields.slideDown(300);
                $('input[name="wbcom_subscription_required_products[]"]').first().focus();
            } else {
                $fields.slideUp(300);
            }
        },

        /**
         * Validate product selection
         */
        validateProductSelection: function() {
            var $protectionCheckbox = $('input[name="wbcom_subscription_protected"]');
            var $productCheckboxes = $('input[name="wbcom_subscription_required_products[]"]');
            var $submitButton = $('#publish, #save-post');
            
            if (!$protectionCheckbox.is(':checked')) {
                return; // No validation needed if protection is disabled
            }
            
            var hasSelectedProducts = $productCheckboxes.is(':checked');
            
            // Remove existing notices
            $('.wbcom-protection-notice').remove();
            
            if (!hasSelectedProducts) {
                var notice = $('<div class="wbcom-protection-notice error">' +
                    'Please select at least one subscription product to protect this content.' +
                    '</div>');
                $('.wbcom-protection-products').after(notice);
                
                // Disable submit button
                $submitButton.prop('disabled', true);
            } else {
                // Enable submit button
                $submitButton.prop('disabled', false);
            }
        },

        /**
         * Add helpful tooltips
         */
        addTooltips: function() {
            // Add tooltip to protection checkbox
            var $protectionLabel = $('input[name="wbcom_subscription_protected"]').parent();
            $protectionLabel.attr('title', 'When enabled, only users with active subscriptions can view this content');
            
            // Add tooltip to custom message field
            var $messageField = $('textarea[name="wbcom_subscription_custom_message"]');
            $messageField.attr('title', 'This message will be shown to users who don\'t have the required subscription');
        },

        /**
         * Initialize classic editor functionality
         */
        initClassicEditor: function() {
            // Ensure the media button is properly styled
            $('#wbcom-add-protection-button').css({
                'background': '#007cba',
                'border-color': '#007cba',
                'color': 'white'
            });
        },

        /**
         * Open protection popup for classic editor
         */
        openProtectionPopup: function() {
            $('#wbcom-protection-popup').fadeIn(300);
            
            // Focus first input for accessibility
            setTimeout(function() {
                $('#wbcom-protection-popup input[type="checkbox"]').first().focus();
            }, 350);
        },

        /**
         * Close protection popup
         */
        closeProtectionPopup: function() {
            $('#wbcom-protection-popup').fadeOut(300);
            
            // Return focus to media button
            setTimeout(function() {
                $('#wbcom-add-protection-button').focus();
            }, 350);
        },

        /**
         * Insert protection shortcode into editor
         */
        insertProtectionShortcode: function() {
            var self = this;
            
            // Get selected products
            var required_products = [];
            $('input[name="wbcom_popup_required_products[]"]:checked').each(function() {
                required_products.push($(this).val());
            });
            
            // Get custom message and content
            var custom_message = $('#wbcom-popup-custom-message').val().trim();
            var content = $('#wbcom-popup-content').val().trim();
            
            // Validation
            if (required_products.length === 0) {
                alert('Please select at least one subscription product.');
                $('input[name="wbcom_popup_required_products[]"]').first().focus();
                return;
            }
            
            if (!content) {
                alert('Please enter the content to protect.');
                $('#wbcom-popup-content').focus();
                return;
            }
            
            // Build shortcode
            var shortcode = '[wbcom_subscription_protection';
            shortcode += ' required_products="' + required_products.join(',') + '"';
            
            if (custom_message) {
                // Escape quotes in custom message
                var escapedMessage = custom_message.replace(/"/g, '&quot;');
                shortcode += ' custom_message="' + escapedMessage + '"';
            }
            
            shortcode += ']' + content + '[/wbcom_subscription_protection]';
            
            // Insert into editor
            this.insertIntoEditor(shortcode);
            
            // Close popup and reset form
            this.closeProtectionPopup();
            this.resetPopupForm();
            
            // Show success message
            this.showNotice('Protection shortcode inserted successfully!', 'success');
        },

        /**
         * Insert content into the active editor
         */
        insertIntoEditor: function(content) {
            // Try TinyMCE first (Visual editor)
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
                tinymce.activeEditor.insertContent(content);
                tinymce.activeEditor.focus();
            }
            // Fallback to HTML editor
            else {
                var $textarea = $('#content');
                if ($textarea.length) {
                    var currentContent = $textarea.val();
                    var cursorPos = $textarea.prop('selectionStart');
                    
                    var newContent = currentContent.substring(0, cursorPos) + 
                                   content + 
                                   currentContent.substring(cursorPos);
                    
                    $textarea.val(newContent);
                    $textarea.focus();
                    
                    // Set cursor after inserted content
                    var newCursorPos = cursorPos + content.length;
                    $textarea.prop('selectionStart', newCursorPos);
                    $textarea.prop('selectionEnd', newCursorPos);
                }
            }
        },

        /**
         * Reset popup form fields
         */
        resetPopupForm: function() {
            $('input[name="wbcom_popup_required_products[]"]').prop('checked', false);
            $('#wbcom-popup-custom-message').val('');
            $('#wbcom-popup-content').val('');
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
            
            // Handle manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Utility function to get URL parameter
         */
        getUrlParameter: function(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        },

        /**
         * Debug logging (only in development)
         */
        log: function(message, data) {
            if (window.console && typeof console.log === 'function') {
                console.log('[WBCom Subscription Protection] ' + message, data || '');
            }
        }
    };

    // Make object globally accessible for debugging
    window.WbcomSubscriptionProtection = WbcomSubscriptionProtection;

})(jQuery);