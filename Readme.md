# Subscription Role Manager Documentation

## 1. Ensure the Plugin is Active

Before proceeding, confirm that the "Subscription Role Manager" plugin is active in your WordPress setup.

![Subscription Role Manager Active](https://github.com/user-attachments/assets/179a3be1-0057-4bca-9e51-7b8fb7adc0b9)

## 2. Configure General Settings

Once the plugin is active, navigate to **Settings >> General Settings**. You will find a new option at the bottom of the page.

![General Settings](https://github.com/user-attachments/assets/a6ffdd1e-f456-4f0d-9be6-87178ff75323)

This setting allows you to specify a global role to downgrade users to when their subscription ends, is canceled, or is suspended.

## 3. Compatibility with Variable WooCommerce Products

The plugin has been tested and is fully compatible with **Variable WooCommerce Products**.

![Variable WooCommerce Product](https://github.com/user-attachments/assets/a7b10994-529a-4f8a-b4ee-6274b830263d)

## 4. Assigning Roles to Product Variations

In the **Variations** window of your WooCommerce product, you can assign a role to each variation. When a customer purchases a subscription, their user role will automatically change to the role mapped to the variation they select.

- Ensure the **Subscription** checkbox is checked for the product variation.

![Assign Role to Variations](https://github.com/user-attachments/assets/fcb1302b-41fa-4c65-9287-a0b0e9b440b2)

## Important Notes

- **Role Restrictions:** The roles displayed in the plugin settings are restricted to prevent misconfigurations and security issues. Default WordPress roles and roles associated with Yoast SEO are excluded from the plugin’s settings.

---

By following this guide, you can effectively configure and use the Subscription Role Manager plugin to manage user roles based on subscription statuses and variations.
