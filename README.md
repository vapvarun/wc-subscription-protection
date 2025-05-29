# WooCommerce Subscription Content Protection Plugin

**Author:** wbcom designs  
**Website:** https://wbcomdesigns.com  
**Version:** 1.0.0  
**Requires:** WordPress 5.0+, WooCommerce 3.0+, WooCommerce Subscriptions

## 📋 Description

This plugin allows you to protect specific content on your WordPress site based on active WooCommerce subscriptions. Users must have an active subscription to view protected content, making it perfect for membership sites, premium content areas, and subscription-based businesses.

## ✨ Features

- 🔒 **Content Protection** - Protect pages, posts, or specific content blocks
- 🎯 **Subscription-Based Access** - Only users with active subscriptions can view protected content
- 🧩 **Multiple Integration Methods** - Gutenberg blocks, classic editor, meta boxes, widgets, and shortcodes
- 📱 **Responsive Design** - Mobile-friendly protection messages
- 🎨 **Customizable Messages** - Set custom messages for protected content
- 👥 **Multi-Product Support** - Require one or multiple subscription products
- 🔧 **Admin Controls** - Easy-to-use admin interface with toggle controls

## 📦 Installation

### Method 1: Manual Installation

1. **Download & Extract**

   ```
   Download the plugin files and extract to:
   wp-content/plugins/wc-subscription-protection/
   ```

2. **Create Directory Structure**

   ```
   wp-content/plugins/wc-subscription-protection/
   ├── wc-subscription-protection.php
   ├── assets/
   │   ├── style.css
   │   ├── admin-style.css
   │   ├── admin.js
   │   └── block-editor.js
   └── README.md
   ```

3. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "WooCommerce Subscription Content Protection"
   - Click "Activate"

### Method 2: Upload via WordPress Admin

1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Choose the plugin ZIP file
4. Click "Install Now" then "Activate"

## 🔧 Requirements

- **WordPress:** 5.0 or higher
- **WooCommerce:** 3.0 or higher
- **WooCommerce Subscriptions:** Latest version
- **PHP:** 7.4 or higher

## 🚀 Usage Guide

### 1. Page/Post Protection (Meta Box Method)

1. **Edit any post or page**
2. **Find the "Subscription Content Protection" meta box** (usually in the sidebar)
3. **Check "Enable subscription protection"**
4. **Select required subscription products**
5. **Add custom message** (optional)
6. **Save/Update the post**

### 2. Gutenberg Block Method

1. **Add new block** in Gutenberg editor
2. **Search for "Subscription Protection"**
3. **Configure settings in the sidebar:**
   - Select required subscription products
   - Set custom message (optional)
4. **Add your protected content** in the block
5. **Publish/Update**

### 3. Classic Editor Method

1. **Click the "🔒 Add Subscription Protection" button** above the editor
2. **Configure popup settings:**
   - Select subscription products
   - Set custom message
   - Enter protected content
3. **Click "Insert Protection"**
4. **Save/Update the post**

### 4. Shortcode Method

```
[wbcom_subscription_protection required_products="123,456" custom_message="Premium content only!"]
Your protected content goes here
[/wbcom_subscription_protection]
```

**Parameters:**

- `required_products` - Comma-separated list of subscription product IDs
- `custom_message` - Optional custom message (use quotes)

### 5. Widget Method

1. **Go to Appearance → Widgets**
2. **Add "Subscription Content Protection" widget**
3. **Configure widget settings:**
   - Show protection status
   - Show admin toggle (for administrators)
4. **Save widget**

## ⚙️ Configuration Options

### Meta Box Settings

- **Enable Protection:** Toggle content protection on/off
- **Required Products:** Select which subscription products are needed
- **Custom Message:** Override default protection message

### Widget Settings

- **Title:** Widget title
- **Show Status:** Display protection status (protected/public)
- **Show Toggle:** Show admin controls for quick enable/disable

### Gutenberg Block Settings

- **Required Products:** Multi-select subscription products
- **Custom Message:** Textarea for custom protection message
- **Protected Content:** Rich text editor for content

## 🎨 Customization

### Custom CSS Classes

The plugin provides several CSS classes for customization:

```css
/* Protection message container */
.wbcom-subscription-protection-message {
}

/* Protection icon */
.wbcom-subscription-protection-message .protection-icon {
}

/* Required subscriptions list */
.wbcom-subscription-protection-message .required-subscriptions {
}

/* Call-to-action button */
.wbcom-subscription-protection-message .button {
}

/* Widget container */
.wbcom-subscription-widget {
}

/* Status indicators */
.wbcom-subscription-widget .status.protected {
}
.wbcom-subscription-widget .status.unprotected {
}
```

### Custom Messages

You can customize protection messages in several ways:

1. **Per-content custom message** - Set in meta box or block settings
2. **Global message override** - Use WordPress filters
3. **CSS styling** - Style the protection message appearance

## 🔍 Troubleshooting

### Common Issues

**Q: Protection not working?**

- Ensure WooCommerce and WooCommerce Subscriptions are active
- Check that subscription products are properly created
- Verify user has active subscription (not expired/cancelled)

**Q: Gutenberg block not showing?**

- Clear browser cache
- Check browser console for JavaScript errors
- Ensure WordPress 5.0+ for Gutenberg support

**Q: Widget not displaying?**

- Widget only shows on single posts/pages
- Check widget area is properly configured
- Verify theme supports widgets

**Q: Shortcode not working?**

- Check product IDs are correct
- Ensure proper shortcode syntax
- Verify subscription products exist

### Debug Mode

Add this to wp-config.php for debugging:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for error messages.

## 🔒 Security Features

- **Nonce verification** - All forms use WordPress nonces
- **Capability checks** - Admin functions require proper permissions
- **Data sanitization** - All input is properly sanitized
- **SQL injection protection** - Uses WordPress database methods
- **XSS prevention** - Output is properly escaped

## 🛠️ Developer Hooks

### Actions

```php
// Before content protection check
do_action('wbcom_before_content_protection', $post_id);

// After content protection applied
do_action('wbcom_after_content_protection', $post_id, $user_id);
```

### Filters

```php
// Customize protection message
add_filter('wbcom_protection_message', function($message, $post_id) {
    return 'Your custom message here';
}, 10, 2);

// Modify required products
add_filter('wbcom_required_products', function($products, $post_id) {
    // Modify $products array
    return $products;
}, 10, 2);
```

## 📋 Changelog

### Version 1.0.0

- Initial release
- Gutenberg block support
- Classic editor integration
- Meta box protection
- Widget functionality
- Shortcode support
- Responsive design
- wbcom designs branding

## 📞 Support

For support and updates:

- **Website:** https://wbcomdesigns.com
- **Documentation:** Check plugin files
- **Issues:** Contact wbcom designs support

## 📄 License

This plugin is licensed under GPL v2 or later.

## 🙏 Credits

**Developed by:** wbcom designs  
**Framework:** WordPress Plugin API  
**Dependencies:** WooCommerce, WooCommerce Subscriptions

---

**Thank you for using WooCommerce Subscription Content Protection!** 🚀
