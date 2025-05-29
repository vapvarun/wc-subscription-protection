/**
 * Gutenberg Block for WooCommerce Subscription Content Protection
 * Author: wbcom designs
 */

(function(blocks, element, editor, components, i18n) {
    'use strict';

    var el = element.createElement;
    var RichText = editor.RichText || wp.blockEditor.RichText;
    var InspectorControls = editor.InspectorControls || wp.blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var CheckboxControl = components.CheckboxControl;
    var TextareaControl = components.TextareaControl;
    var Notice = components.Notice;
    var __ = i18n.__;

    // Register the block
    blocks.registerBlockType('wbcom/subscription-protection', {
        title: __('Subscription Protection', 'wbcom-subscription-protection'),
        description: __('Protect content based on active WooCommerce subscriptions', 'wbcom-subscription-protection'),
        icon: {
            src: 'lock',
            background: '#007cba',
            foreground: '#ffffff'
        },
        category: 'widgets',
        keywords: [
            __('subscription', 'wbcom-subscription-protection'),
            __('protection', 'wbcom-subscription-protection'),
            __('woocommerce', 'wbcom-subscription-protection'),
            __('wbcom', 'wbcom-subscription-protection')
        ],
        supports: {
            html: false,
            align: ['wide', 'full']
        },
        attributes: {
            required_products: {
                type: 'array',
                default: []
            },
            custom_message: {
                type: 'string',
                default: ''
            },
            content: {
                type: 'string',
                default: ''
            },
            blockId: {
                type: 'string',
                default: ''
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var className = props.className;
            var subscription_products = (window.wbcom_block_data && window.wbcom_block_data.subscription_products) || [];

            // Generate unique block ID if not exists
            if (!attributes.blockId) {
                setAttributes({ blockId: 'wbcom-block-' + Math.random().toString(36).substr(2, 9) });
            }

            /**
             * Handle content change
             */
            function onChangeContent(newContent) {
                setAttributes({ content: newContent });
            }

            /**
             * Handle custom message change
             */
            function onChangeCustomMessage(newMessage) {
                setAttributes({ custom_message: newMessage });
            }

            /**
             * Handle product selection toggle
             */
            function onToggleProduct(productId, isChecked) {
                var currentProducts = attributes.required_products || [];
                var newProducts;
                
                if (isChecked) {
                    newProducts = [...currentProducts, productId];
                } else {
                    newProducts = currentProducts.filter(function(id) {
                        return id !== productId;
                    });
                }
                
                setAttributes({ required_products: newProducts });
            }

            /**
             * Get selected product names for display
             */
            function getSelectedProductNames() {
                var selectedProducts = attributes.required_products || [];
                var productNames = [];
                
                selectedProducts.forEach(function(productId) {
                    var product = subscription_products.find(function(p) {
                        return p.id == productId;
                    });
                    if (product) {
                        productNames.push(product.name);
                    }
                });
                
                return productNames;
            }

            // Create product checkbox controls
            var productCheckboxes = subscription_products.map(function(product) {
                return el(CheckboxControl, {
                    key: 'product-' + product.id,
                    label: product.name,
                    checked: (attributes.required_products || []).includes(product.id),
                    onChange: function(isChecked) {
                        onToggleProduct(product.id, isChecked);
                    },
                    className: 'wbcom-product-checkbox'
                });
            });

            // Validation: Check if products are selected
            var hasSelectedProducts = (attributes.required_products || []).length > 0;
            var validationNotice = null;

            if (!hasSelectedProducts) {
                validationNotice = el(Notice, {
                    status: 'warning',
                    isDismissible: false,
                    className: 'wbcom-validation-notice'
                }, __('Please select at least one subscription product to protect this content.', 'wbcom-subscription-protection'));
            }

            // Block preview content
            var selectedProductNames = getSelectedProductNames();
            var productDisplayText = selectedProductNames.length > 0 
                ? __('Requires: ', 'wbcom-subscription-protection') + selectedProductNames.join(', ')
                : __('No products selected', 'wbcom-subscription-protection');

            return [
                // Inspector Controls (Sidebar)
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, {
                        title: __('Protection Settings', 'wbcom-subscription-protection'),
                        initialOpen: true,
                        className: 'wbcom-protection-panel'
                    },
                        el('h4', { 
                            style: { marginBottom: '10px', color: '#1e1e1e' } 
                        }, __('Required Subscription Products', 'wbcom-subscription-protection')),
                        
                        subscription_products.length > 0 ? 
                            el('div', { className: 'wbcom-products-list' }, productCheckboxes) :
                            el('p', { 
                                style: { fontStyle: 'italic', color: '#757575' } 
                            }, __('No subscription products found. Please create subscription products first.', 'wbcom-subscription-protection')),
                        
                        el('hr', { style: { margin: '20px 0' } }),
                        
                        el(TextareaControl, {
                            label: __('Custom Message (optional)', 'wbcom-subscription-protection'),
                            value: attributes.custom_message || '',
                            onChange: onChangeCustomMessage,
                            placeholder: __('Enter a custom message to show when content is protected...', 'wbcom-subscription-protection'),
                            help: __('Leave empty to use the default protection message.', 'wbcom-subscription-protection'),
                            rows: 4
                        })
                    )
                ),

                // Block Editor Content
                el('div', {
                    key: 'content',
                    className: className + ' wbcom-subscription-protection-block',
                    id: attributes.blockId
                },
                    // Block Header
                    el('div', {
                        className: 'wbcom-protection-header',
                        style: {
                            background: 'linear-gradient(135deg, #007cba 0%, #005a87 100%)',
                            color: 'white',
                            padding: '15px 20px',
                            borderRadius: '8px 8px 0 0',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                        }
                    },
                        el('div', {
                            style: { display: 'flex', alignItems: 'center' }
                        },
                            el('span', {
                                style: { 
                                    fontSize: '24px', 
                                    marginRight: '12px',
                                    filter: 'drop-shadow(0 1px 2px rgba(0,0,0,0.3))'
                                }
                            }, '🔒'),
                            el('div', {},
                                el('strong', { 
                                    style: { 
                                        fontSize: '16px',
                                        textShadow: '0 1px 2px rgba(0,0,0,0.3)'
                                    } 
                                }, __('Protected Content Block', 'wbcom-subscription-protection')),
                                el('div', {
                                    style: {
                                        fontSize: '12px',
                                        opacity: '0.9',
                                        marginTop: '2px'
                                    }
                                }, productDisplayText)
                            )
                        ),
                        el('div', {
                            style: {
                                fontSize: '12px',
                                opacity: '0.8',
                                textAlign: 'right'
                            }
                        }, 
                            el('div', {}, __('by wbcom designs', 'wbcom-subscription-protection'))
                        )
                    ),

                    // Validation Notice
                    validationNotice,

                    // Content Editor
                    el('div', {
                        style: {
                            border: '2px solid #007cba',
                            borderTop: 'none',
                            borderRadius: '0 0 8px 8px',
                            backgroundColor: '#ffffff'
                        }
                    },
                        el(RichText, {
                            tagName: 'div',
                            placeholder: __('Enter the content that should be protected by subscription...', 'wbcom-subscription-protection'),
                            value: attributes.content || '',
                            onChange: onChangeContent,
                            style: {
                                minHeight: '120px',
                                padding: '20px',
                                lineHeight: '1.6',
                                fontSize: '14px'
                            },
                            className: 'wbcom-protected-content-editor'
                        }),

                        // Footer info
                        el('div', {
                            style: {
                                padding: '10px 20px',
                                backgroundColor: '#f8f9fa',
                                borderTop: '1px solid #e9ecef',
                                fontSize: '12px',
                                color: '#6c757d',
                                borderRadius: '0 0 6px 6px'
                            }
                        },
                            hasSelectedProducts ?
                                __('✓ Content will be protected for users without active subscriptions', 'wbcom-subscription-protection') :
                                __('⚠ Select subscription products in the sidebar to activate protection', 'wbcom-subscription-protection')
                        )
                    )
                )
            ];
        },

        save: function(props) {
            // Return null because we handle rendering server-side
            // This allows for dynamic content that depends on user subscription status
            return null;
        }
    });

    /**
     * Add custom block styles
     */
    wp.domReady(function() {
        // Add custom CSS for the block editor
        var blockEditorStyles = `
            .wbcom-subscription-protection-block .wbcom-protection-header {
                user-select: none;
            }
            
            .wbcom-protection-panel .wbcom-products-list {
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #ddd;
                padding: 10px;
                border-radius: 4px;
                background: #f9f9f9;
            }
            
            .wbcom-product-checkbox {
                margin-bottom: 8px !important;
            }
            
            .wbcom-protected-content-editor:focus {
                outline: none;
                box-shadow: inset 0 0 0 1px #007cba;
            }
            
            .wbcom-validation-notice {
                margin: 10px 0 !important;
            }
            
            .wbcom-subscription-protection-block {
                margin: 20px 0;
            }
            
            @media (max-width: 768px) {
                .wbcom-protection-header {
                    flex-direction: column;
                    text-align: center;
                    gap: 10px;
                }
            }
        `;

        // Inject styles
        var styleSheet = document.createElement('style');
        styleSheet.type = 'text/css';
        styleSheet.innerText = blockEditorStyles;
        document.head.appendChild(styleSheet);
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components,
    window.wp.i18n
);