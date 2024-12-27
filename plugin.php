<?php
/**
 * Plugin Name: Subscription Role Manager
 * Plugin URI: NA
 * Description: A plugin to manage user roles based on subscription activation, suspension, or cancellation using YITH WooCommerce Subscriptions.
 * Version: 1.0.0
 * Author: Himanshu Borah
 * Author URI: https://borahdev.in/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: borahdev-subscription-role-manager
 * Domain Path: /languages
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// For debugging purposes
// No longer used in code
// In order to debug, use this
function borahdev_log_to_file($message) {
    // Get the child theme directory
    $log_file = get_stylesheet_directory() . '/log.txt';
    
    // Check if the message is an array or object, and pretty-print it
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    // Append the message with a timestamp to the log file
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


// Add custom field to variations
add_action( 'woocommerce_product_after_variable_attributes', 'borahdev_add_custom_field_to_variations', 10, 3 );
function borahdev_add_custom_field_to_variations( $loop, $variation_data, $variation ) {
    // Check if the variation is a YITH subscription
    $is_subscription = get_post_meta( $variation->ID, '_ywsbs_subscription', true ); 
    
    if ( $is_subscription ) {
        ?>
        <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <p class="form-row form-row-full" style="margin-bottom: 10px;">
                <label for="select_role_<?php echo $loop; ?>" style="font-size: 14px; font-weight: 600; color: #333;">Assign Role on Subscription Activation</label>
                <select name="select_role[<?php echo $loop; ?>]" id="select_role_<?php echo $loop; ?>" 
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background-color: #fff; color: #333;">
                    <option value=""> -- No Change in Role -- </option>
                    <?php
                    // Get all roles except default WordPress and Yoast SEO roles
                    $filtered_roles = borahdev_get_non_default_and_non_yoast_roles();
                    foreach ( $filtered_roles as $role_key => $role_name ) {
                        $selected = selected( get_post_meta( $variation->ID, '_select_role', true ), $role_key, false );
                        echo "<option value='{$role_key}' {$selected}>{$role_name}</option>";
                    }
                    ?>
                </select>
            </p>
            <p class="description" style="font-size: 12px; color: #666; margin-top: 5px;">
                Select a user role to assign when the subscription for this variation is activated. The role should match the needs of this subscription plan.
            </p>
        </div>
        <?php
    }
}

// Save the custom field value for variations
add_action( 'woocommerce_save_product_variation', 'borahdev_save_custom_field_to_variations', 10, 2 );
function borahdev_save_custom_field_to_variations( $variation_id, $i ) {
    if ( isset( $_POST['select_role'][$i] ) ) {
        $role = sanitize_text_field( $_POST['select_role'][$i] );
        update_post_meta( $variation_id, '_select_role', $role );
    }
}

// Include custom field value in variation data
add_filter( 'woocommerce_available_variation', 'borahdev_add_custom_field_to_variation_data' );
function borahdev_add_custom_field_to_variation_data( $variation ) {
    $variation['select_role'] = get_post_meta( $variation['variation_id'], '_select_role', true );
    return $variation;
}


// Function to get all roles except default WordPress and Yoast SEO roles
function borahdev_get_non_default_and_non_yoast_roles() {
    global $wp_roles;

    if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles();
    }

    // Roles to exclude
    $excluded_roles = [
        // Default WordPress roles
        'subscriber', 'contributor', 'author', 'editor', 'administrator',
        // Yoast SEO roles
        'wpseo_manager', 'wpseo_editor'
    ];

    $filtered_roles = [];

    foreach ( $wp_roles->roles as $role_key => $role_details ) {
        // Exclude specified roles
        if ( ! in_array( $role_key, $excluded_roles ) ) {
            $filtered_roles[ $role_key ] = $role_details['name'];
        }
    }

    return $filtered_roles;
}



// Add a fallback role option to the General settings
add_action( 'admin_init', 'borahdev_register_subscription_fallback_role_setting' );
function borahdev_register_subscription_fallback_role_setting() {
    // Register the setting in General Settings
    register_setting( 'general', 'subscription_fallback_role', [
        'type' => 'string',
        'description' => 'Global fallback role for subscriptions',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ] );

    // Add the field to General Settings
    add_settings_field(
        'subscription_fallback_role', // ID
        'Global Fallback Role for Subscriptions', // Title
        'borahdev_display_subscription_fallback_role_field', // Callback function to render the field
        'general', // Page (General Settings)
        'default' // Section
    );
}

// Render the dropdown field for the fallback role
function borahdev_display_subscription_fallback_role_field() {
    // Get all roles except default WordPress and Yoast SEO roles
    $roles = borahdev_get_non_default_and_non_yoast_roles();
    $selected_role = get_option( 'subscription_fallback_role', '' );

    echo '<select name="subscription_fallback_role" id="subscription_fallback_role" style="width: 300px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px;">';
    echo '<option value="">-- No Fallback --</option>';
    foreach ( $roles as $role_key => $role_name ) {
        $selected = selected( $selected_role, $role_key, false );
        echo "<option value='{$role_key}' {$selected}>{$role_name}</option>";
    }
    echo '</select>';
    echo '<p class="description" style="font-size: 12px; color: #666; margin-top: 5px;">';
    echo 'The fallback role is assigned when subscription ends or is canceled. This ensures the user is downgraded to an appropriate role, maintaining control over their access and permissions.';
    echo '</p>';
}


function borahdev_change_user_role_to( $user_id, $new_role ) {
    // Get the user object
    $user = new WP_User( $user_id );

    // Check if the user exists
    if ( $user && $user->exists() ) {
        // Set the new role
        $user->set_role( $new_role );
        return true; // Role successfully changed
    }

    return false; // User doesn't exist or role change failed
}


function borahdev_subscription_change_listener($data) {
   
    // Retrieve the user ID of the subscription author
    $user_id = $data->post->post_author;

    // Subscription ID
    $subscription_id = $data->id;

    // Status of the current subscription
    $status = get_post_meta($subscription_id, 'status', true);

    // Fallback role
    $fallback_role = get_option('subscription_fallback_role');

    // Retrieve product ID and variation ID
    $product_id = get_post_meta($subscription_id, 'product_id', true);
    $variation_id = get_post_meta($subscription_id, 'variation_id', true);
    if (empty($variation_id) || $variation_id < 1) {
        return;
    }

    // Upgrade role
    $upgrade_role_to = get_post_meta($variation_id, '_select_role', true);
    if (empty($upgrade_role_to) || strlen($upgrade_role_to) < 1) {
        return;
    }

    // Check for existing subscription ID in the user meta
    $active_subscription_id = get_user_meta($user_id, 'active_subscription_id', true);

    if ($status === 'suspended' || $status === 'cancelled') {
        // If the subscription is cancelled or suspended, only downgrade if it matches the active subscription
        if ($active_subscription_id == $subscription_id) {
            if (empty($fallback_role) || strlen($fallback_role) < 1) {
                borahdev_change_user_role_to($user_id, "subscriber");
            } else {
                borahdev_change_user_role_to($user_id, $fallback_role);
            }
            delete_user_meta($user_id, 'active_subscription_id'); // Clear active subscription
        } else {
            // Cancellation ignored for non-active subscription ID
            // Nothing to do here
        }
    } else {
        // If activating a new subscription
        borahdev_change_user_role_to($user_id, $upgrade_role_to);
        update_user_meta($user_id, 'active_subscription_id', $subscription_id); // Set this as the active subscription
    }
}

// Hook into the subscription actions
add_action('ywsbs_customer_subscription_actived_mail', 'borahdev_subscription_change_listener', 10, 1);
add_action('ywsbs_customer_subscription_cancelled_mail', 'borahdev_subscription_change_listener', 10, 1);
add_action('ywsbs_customer_subscription_suspended_mail', 'borahdev_subscription_change_listener', 10, 1);

