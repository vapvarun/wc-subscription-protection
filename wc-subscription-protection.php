<?php
/**
 * Plugin Name: WooCommerce Subscription Content Protection
 * Description: Protect pages/posts based on active WooCommerce subscriptions
 * Version: 1.0.0
 * Author: wbcom designs
 * Author URI: https://wbcomdesigns.com
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Wbcom_WC_Subscription_Content_Protection {
    
    public function __construct() {
        add_action('init', array($this, 'wbcom_init'));
        add_action('wp_enqueue_scripts', array($this, 'wbcom_enqueue_scripts'));
        add_action('widgets_init', array($this, 'wbcom_register_widget'));
        add_action('add_meta_boxes', array($this, 'wbcom_add_protection_meta_box'));
        add_action('save_post', array($this, 'wbcom_save_protection_meta'));
        add_filter('the_content', array($this, 'wbcom_protect_content'));
        
        // Admin styles
        add_action('admin_enqueue_scripts', array($this, 'wbcom_admin_enqueue_scripts'));
        
        // Gutenberg block support
        add_action('init', array($this, 'wbcom_register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'wbcom_enqueue_block_editor_assets'));
        
        // Classic editor support (TinyMCE)
        add_action('media_buttons', array($this, 'wbcom_add_media_button'));
        add_action('wp_ajax_wbcom_insert_protection_shortcode', array($this, 'wbcom_insert_protection_shortcode'));
        
        // Shortcode support
        add_shortcode('wbcom_subscription_protection', array($this, 'wbcom_protection_shortcode'));
    }
    
    public function wbcom_init() {
        // Check if WooCommerce and WooCommerce Subscriptions are active
        if (!class_exists('WooCommerce') || !class_exists('WC_Subscriptions')) {
            add_action('admin_notices', array($this, 'wbcom_missing_dependencies_notice'));
            return;
        }
    }
    
    public function wbcom_missing_dependencies_notice() {
        echo '<div class="error"><p><strong>WooCommerce Subscription Content Protection:</strong> This plugin requires WooCommerce and WooCommerce Subscriptions to be installed and activated.</p></div>';
    }
    
    public function wbcom_enqueue_scripts() {
        wp_enqueue_style('wbcom-wc-subscription-protection', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }
    
    public function wbcom_admin_enqueue_scripts($hook) {
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_style('wbcom-wc-subscription-protection-admin', plugin_dir_url(__FILE__) . 'assets/admin-style.css', array(), '1.0.0');
            wp_enqueue_script('wbcom-subscription-protection-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('wbcom-subscription-protection-admin', 'wbcom_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wbcom_protection_nonce')
            ));
        }
    }
    
    /**
     * Enqueue Gutenberg block editor assets
     */
    public function wbcom_enqueue_block_editor_assets() {
        wp_enqueue_script(
            'wbcom-subscription-protection-block',
            plugin_dir_url(__FILE__) . 'assets/block-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
            '1.0.0',
            true
        );
        
        wp_localize_script('wbcom-subscription-protection-block', 'wbcom_block_data', array(
            'subscription_products' => $this->wbcom_get_subscription_products_for_js()
        ));
    }
    
    /**
     * Register Gutenberg block
     */
    public function wbcom_register_block() {
        if (function_exists('register_block_type')) {
            register_block_type('wbcom/subscription-protection', array(
                'editor_script' => 'wbcom-subscription-protection-block',
                'render_callback' => array($this, 'wbcom_render_protection_block'),
                'attributes' => array(
                    'required_products' => array(
                        'type' => 'array',
                        'default' => array()
                    ),
                    'custom_message' => array(
                        'type' => 'string',
                        'default' => ''
                    ),
                    'content' => array(
                        'type' => 'string',
                        'default' => ''
                    )
                )
            ));
        }
    }
    
    /**
     * Render Gutenberg block
     */
    public function wbcom_render_protection_block($attributes, $content) {
        $required_products = isset($attributes['required_products']) ? $attributes['required_products'] : array();
        $custom_message = isset($attributes['custom_message']) ? $attributes['custom_message'] : '';
        $protected_content = isset($attributes['content']) ? $attributes['content'] : $content;
        
        // Check if user has required subscription
        if ($this->wbcom_user_has_subscription_for_products($required_products)) {
            return $protected_content;
        }
        
        return $this->wbcom_get_protection_message_for_products($required_products, $custom_message);
    }
    
    /**
     * Add media button for classic editor
     */
    public function wbcom_add_media_button() {
        global $post;
        if (!$post || !in_array($post->post_type, array('post', 'page'))) {
            return;
        }
        
        echo '<button type="button" id="wbcom-add-protection-button" class="button">
                🔒 Add Subscription Protection
              </button>';
        
        // Add popup HTML
        add_action('admin_footer', array($this, 'wbcom_add_protection_popup'));
    }
    
    /**
     * Add protection popup for classic editor
     */
    public function wbcom_add_protection_popup() {
        $subscription_products = $this->wbcom_get_subscription_products();
        ?>
        <div id="wbcom-protection-popup" style="display: none;">
            <div class="wbcom-protection-popup-content">
                <h3>Subscription Content Protection</h3>
                <div class="wbcom-protection-field">
                    <label>Required Subscription Products:</label>
                    <div class="wbcom-protection-products">
                        <?php if (!empty($subscription_products)): ?>
                            <?php foreach ($subscription_products as $product): ?>
                                <label>
                                    <input type="checkbox" name="wbcom_popup_required_products[]" value="<?php echo esc_attr($product->get_id()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </label><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No subscription products found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="wbcom-protection-field">
                    <label>Custom Message (optional):</label>
                    <textarea id="wbcom-popup-custom-message" placeholder="Custom message to show when content is protected..."></textarea>
                </div>
                <div class="wbcom-protection-field">
                    <label>Protected Content:</label>
                    <textarea id="wbcom-popup-content" placeholder="Enter the content that should be protected..."></textarea>
                </div>
                <div class="wbcom-protection-buttons">
                    <button type="button" id="wbcom-insert-protection" class="button button-primary">Insert Protection</button>
                    <button type="button" id="wbcom-cancel-protection" class="button">Cancel</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#wbcom-add-protection-button').click(function() {
                $('#wbcom-protection-popup').show();
            });
            
            $('#wbcom-cancel-protection').click(function() {
                $('#wbcom-protection-popup').hide();
            });
            
            $('#wbcom-insert-protection').click(function() {
                var required_products = [];
                $('input[name="wbcom_popup_required_products[]"]:checked').each(function() {
                    required_products.push($(this).val());
                });
                
                var custom_message = $('#wbcom-popup-custom-message').val();
                var content = $('#wbcom-popup-content').val();
                
                if (required_products.length === 0) {
                    alert('Please select at least one subscription product.');
                    return;
                }
                
                if (!content.trim()) {
                    alert('Please enter the content to protect.');
                    return;
                }
                
                var shortcode = '[wbcom_subscription_protection';
                shortcode += ' required_products="' + required_products.join(',') + '"';
                if (custom_message) {
                    shortcode += ' custom_message="' + custom_message.replace(/"/g, '&quot;') + '"';
                }
                shortcode += ']' + content + '[/wbcom_subscription_protection]';
                
                // Insert into editor
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    tinymce.activeEditor.insertContent(shortcode);
                } else {
                    // Fallback for HTML editor
                    var textarea = document.getElementById('content');
                    if (textarea) {
                        textarea.value += shortcode;
                    }
                }
                
                $('#wbcom-protection-popup').hide();
                
                // Clear form
                $('input[name="wbcom_popup_required_products[]"]').prop('checked', false);
                $('#wbcom-popup-custom-message').val('');
                $('#wbcom-popup-content').val('');
            });
        });
        </script>
        
        <style>
        #wbcom-protection-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        .wbcom-protection-popup-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 5px;
            min-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .wbcom-protection-field {
            margin-bottom: 15px;
        }
        
        .wbcom-protection-field label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .wbcom-protection-field textarea {
            width: 100%;
            height: 80px;
        }
        
        .wbcom-protection-products {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
        }
        
        .wbcom-protection-buttons {
            text-align: right;
            margin-top: 20px;
        }
        
        .wbcom-protection-buttons .button {
            margin-left: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Shortcode handler
     */
    public function wbcom_protection_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'required_products' => '',
            'custom_message' => ''
        ), $atts);
        
        $required_products = array_filter(array_map('trim', explode(',', $atts['required_products'])));
        
        if (empty($required_products)) {
            return $content;
        }
        
        // Check if user has required subscription
        if ($this->wbcom_user_has_subscription_for_products($required_products)) {
            return do_shortcode($content);
        }
        
        return $this->wbcom_get_protection_message_for_products($required_products, $atts['custom_message']);
    }
    
    /**
     * Check if user has subscription for specific products
     */
    private function wbcom_user_has_subscription_for_products($product_ids) {
        if (!is_user_logged_in() || empty($product_ids)) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        foreach ($product_ids as $product_id) {
            if (wcs_user_has_subscription($user_id, $product_id, 'active')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get protection message for specific products
     */
    private function wbcom_get_protection_message_for_products($product_ids, $custom_message = '') {
        if (!empty($custom_message)) {
            $message = $custom_message;
        } else {
            $message = 'This content is protected and requires an active subscription to view.';
        }
        
        $html = '<div class="wbcom-subscription-protection-message">';
        $html .= '<div class="protection-icon">🔒</div>';
        $html .= '<h3>Subscription Required</h3>';
        $html .= '<p>' . esc_html($message) . '</p>';
        
        if (!empty($product_ids)) {
            $html .= '<div class="required-subscriptions">';
            $html .= '<p><strong>Required subscriptions:</strong></p>';
            $html .= '<ul>';
            
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $html .= '<li><a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a></li>';
                }
            }
            
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        if (!is_user_logged_in()) {
            $html .= '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="button">Login to Continue</a></p>';
        } else {
            $html .= '<p><a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="button">Browse Subscriptions</a></p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get subscription products for JavaScript
     */
    private function wbcom_get_subscription_products_for_js() {
        $products = $this->wbcom_get_subscription_products();
        $products_data = array();
        
        foreach ($products as $product) {
            $products_data[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name()
            );
        }
        
        return $products_data;
    }
    
    /**
     * Add meta box for content protection settings
     */
    public function wbcom_add_protection_meta_box() {
        $post_types = array('post', 'page');
        foreach ($post_types as $post_type) {
            add_meta_box(
                'wbcom_subscription_protection',
                'Subscription Content Protection',
                array($this, 'wbcom_protection_meta_box_callback'),
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Meta box callback
     */
    public function wbcom_protection_meta_box_callback($post) {
        wp_nonce_field('wbcom_subscription_protection_nonce', 'wbcom_subscription_protection_nonce');
        
        $is_protected = get_post_meta($post->ID, '_wbcom_subscription_protected', true);
        $required_products = get_post_meta($post->ID, '_wbcom_subscription_required_products', true);
        $custom_message = get_post_meta($post->ID, '_wbcom_subscription_custom_message', true);
        
        // Get available subscription products
        $subscription_products = $this->wbcom_get_subscription_products();
        
        ?>
        <div class="wbcom-protection-field">
            <label>
                <input type="checkbox" name="wbcom_subscription_protected" value="1" <?php checked($is_protected, '1'); ?>>
                Enable subscription protection for this content
            </label>
        </div>
        
        <div class="wbcom-protection-field">
            <label>Required Subscription Products:</label>
            <div class="wbcom-protection-products">
                <?php if (!empty($subscription_products)): ?>
                    <?php foreach ($subscription_products as $product): ?>
                        <label style="font-weight: normal; display: block; margin-bottom: 5px;">
                            <input type="checkbox" 
                                   name="wbcom_subscription_required_products[]" 
                                   value="<?php echo esc_attr($product->get_id()); ?>"
                                   <?php checked(in_array($product->get_id(), (array)$required_products)); ?>>
                            <?php echo esc_html($product->get_name()); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No subscription products found. <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>">Create subscription products</a></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="wbcom-protection-field">
            <label>Custom Message (optional):</label>
            <textarea name="wbcom_subscription_custom_message" placeholder="Custom message to show when content is protected..."><?php echo esc_textarea($custom_message); ?></textarea>
            <small>Leave empty to use default message.</small>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function wbcom_save_protection_meta($post_id) {
        if (!isset($_POST['wbcom_subscription_protection_nonce']) || 
            !wp_verify_nonce($_POST['wbcom_subscription_protection_nonce'], 'wbcom_subscription_protection_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save protection status
        $is_protected = isset($_POST['wbcom_subscription_protected']) ? '1' : '0';
        update_post_meta($post_id, '_wbcom_subscription_protected', $is_protected);
        
        // Save required products
        $required_products = isset($_POST['wbcom_subscription_required_products']) ? array_map('intval', $_POST['wbcom_subscription_required_products']) : array();
        update_post_meta($post_id, '_wbcom_subscription_required_products', $required_products);
        
        // Save custom message
        $custom_message = isset($_POST['wbcom_subscription_custom_message']) ? sanitize_textarea_field($_POST['wbcom_subscription_custom_message']) : '';
        update_post_meta($post_id, '_wbcom_subscription_custom_message', $custom_message);
    }
    
    /**
     * Protect content based on subscription status
     */
    public function wbcom_protect_content($content) {
        if (!is_singular() || is_admin()) {
            return $content;
        }
        
        global $post;
        
        $is_protected = get_post_meta($post->ID, '_wbcom_subscription_protected', true);
        
        if (!$is_protected) {
            return $content;
        }
        
        // Check if user has required subscription
        if ($this->wbcom_user_has_required_subscription($post->ID)) {
            return $content;
        }
        
        // User doesn't have required subscription, show protection message
        return $this->wbcom_get_protection_message($post->ID);
    }
    
    /**
     * Check if current user has required subscription
     */
    private function wbcom_user_has_required_subscription($post_id) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $required_products = get_post_meta($post_id, '_wbcom_subscription_required_products', true);
        
        if (empty($required_products)) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user has active subscription for any of the required products
        foreach ($required_products as $product_id) {
            if (wcs_user_has_subscription($user_id, $product_id, 'active')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get protection message
     */
    private function wbcom_get_protection_message($post_id) {
        $custom_message = get_post_meta($post_id, '_wbcom_subscription_custom_message', true);
        $required_products = get_post_meta($post_id, '_wbcom_subscription_required_products', true);
        
        if (!empty($custom_message)) {
            $message = $custom_message;
        } else {
            $message = 'This content is protected and requires an active subscription to view.';
        }
        
        $html = '<div class="wbcom-subscription-protection-message">';
        $html .= '<div class="protection-icon">🔒</div>';
        $html .= '<h3>Subscription Required</h3>';
        $html .= '<p>' . esc_html($message) . '</p>';
        
        if (!empty($required_products)) {
            $html .= '<div class="required-subscriptions">';
            $html .= '<p><strong>Required subscriptions:</strong></p>';
            $html .= '<ul>';
            
            foreach ($required_products as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $html .= '<li><a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a></li>';
                }
            }
            
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        if (!is_user_logged_in()) {
            $html .= '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="button">Login to Continue</a></p>';
        } else {
            $html .= '<p><a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="button">Browse Subscriptions</a></p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get subscription products
     */
    private function wbcom_get_subscription_products() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_subscription_type',
                    'value' => array('subscription', 'variable-subscription'),
                    'compare' => 'IN'
                )
            )
        );
        
        $products = array();
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $products[] = wc_get_product(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        return $products;
    }
    
    /**
     * Register the widget
     */
    public function wbcom_register_widget() {
        register_widget('Wbcom_Subscription_Protection_Widget');
    }
}

/**
 * Widget Class
 */
class Wbcom_Subscription_Protection_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'wbcom_subscription_protection_widget',
            'Subscription Content Protection',
            array('description' => 'Display subscription protection status and controls')
        );
    }
    
    public function widget($args, $instance) {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        
        $title = !empty($instance['title']) ? $instance['title'] : 'Content Protection';
        $show_status = !empty($instance['show_status']) ? $instance['show_status'] : false;
        $show_toggle = !empty($instance['show_toggle']) ? $instance['show_toggle'] : false;
        
        $is_protected = get_post_meta($post->ID, '_wbcom_subscription_protected', true);
        
        echo $args['before_widget'];
        echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        
        echo '<div class="wbcom-subscription-widget">';
        
        if ($show_status) {
            echo '<div class="protection-status">';
            if ($is_protected) {
                echo '<span class="status protected">🔒 Protected Content</span>';
                
                $required_products = get_post_meta($post->ID, '_wbcom_subscription_required_products', true);
                if (!empty($required_products)) {
                    echo '<div class="required-subs">';
                    echo '<small>Requires: ';
                    $product_names = array();
                    foreach ($required_products as $product_id) {
                        $product = wc_get_product($product_id);
                        if ($product) {
                            $product_names[] = $product->get_name();
                        }
                    }
                    echo esc_html(implode(', ', $product_names));
                    echo '</small>';
                    echo '</div>';
                }
            } else {
                echo '<span class="status unprotected">🔓 Public Content</span>';
            }
            echo '</div>';
        }
        
        // Show toggle for administrators
        if ($show_toggle && current_user_can('edit_posts')) {
            echo '<div class="protection-toggle">';
            echo '<form method="post" class="protection-toggle-form">';
            wp_nonce_field('wbcom_toggle_protection', 'wbcom_toggle_protection_nonce');
            echo '<input type="hidden" name="post_id" value="' . esc_attr($post->ID) . '">';
            
            if ($is_protected) {
                echo '<button type="submit" name="action" value="disable" class="button button-small">Disable Protection</button>';
            } else {
                echo '<button type="submit" name="action" value="enable" class="button button-small">Enable Protection</button>';
            }
            
            echo '</form>';
            echo '</div>';
        }
        
        echo '</div>';
        echo $args['after_widget'];
        
        // Handle form submission
        $this->wbcom_handle_toggle_form();
    }
    
    private function wbcom_handle_toggle_form() {
        if (!isset($_POST['wbcom_toggle_protection_nonce']) || 
            !wp_verify_nonce($_POST['wbcom_toggle_protection_nonce'], 'wbcom_toggle_protection') ||
            !current_user_can('edit_posts')) {
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $action = sanitize_text_field($_POST['action']);
        
        if ($action === 'enable') {
            update_post_meta($post_id, '_wbcom_subscription_protected', '1');
        } elseif ($action === 'disable') {
            update_post_meta($post_id, '_wbcom_subscription_protected', '0');
        }
        
        // Redirect to prevent form resubmission
        wp_redirect(get_permalink($post_id));
        exit;
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Content Protection';
        $show_status = !empty($instance['show_status']) ? $instance['show_status'] : false;
        $show_toggle = !empty($instance['show_toggle']) ? $instance['show_toggle'] : false;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_status); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_status')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_status')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_status')); ?>">Show protection status</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_toggle); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_toggle')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_toggle')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_toggle')); ?>">Show toggle button (admins only)</label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['show_status'] = (!empty($new_instance['show_status'])) ? 1 : 0;
        $instance['show_toggle'] = (!empty($new_instance['show_toggle'])) ? 1 : 0;
        return $instance;
    }
}

// Initialize the plugin
new Wbcom_WC_Subscription_Content_Protection();
?>