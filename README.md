<p style="text-align:center"><img src="https://github.com/user-attachments/assets/f42e5053-64b7-4ab9-a01f-8f39602d36ca" style="width:250px;" /></p>

## About WooCommerce Northbeam
This plugin connects WooCommerce to Northbeam's Orders API so that the API ingests order data for reporting and analysis. It does this by hooking into the `woocommerce_payment_complete` action, which runs whenever a payment is made for any order. The plugin can also include the Northbeam tracking pixel and the `firePurchaseEvent` script, which runs on the WooCommerce thank you/receipt page. It is compatible with WooCommerce Subscriptions.

### Installation
Download the ZIP of the latest release:

[![Download ZIP](https://img.shields.io/badge/Download-ZIP-green?style=for-the-badge&logo=github)](https://github.com/builtmighty/woocommerce-northbeam/archive/refs/heads/main.zip)

Or use the following WP CLI command:

```
wp plugin install https://github.com/builtmighty/woocommerce-northbeam/archive/refs/heads/main.zip --force --activate
```

### Settings
Settings for the plugin are included within WooCommerce -> Settings and then the "Northbeam" tab. From the settings panel, you're able to set the following:

1. Enable the entire plugin.
2. Enable the tracking pixel.
3. Enable the firePurchaseEvent script.
4. Set the client ID.
5. Set the authorization key.
6. Enable logging.

Once everything is set, the plugin will handle it from there.

### Changelog

#### 🚀 v1.0.0 - Initial Release (01/16/2025)
