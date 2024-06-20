<?php

// Add a submenu for settings
function cc_add_settings_menu() {
    add_submenu_page(
        'cc-comments-management', // Parent slug
        __('Settings', 'custom-comments'), // Page title
        __('Settings', 'custom-comments'), // Menu title
        'manage_options', // Capability
        'cc-settings', // Menu slug
        'cc_settings_page' // Function to display the page
    );
}
add_action('admin_menu', 'cc_add_settings_menu');

// Register settings
function cc_register_settings() {
    register_setting('cc-settings-group', 'cc_custom_fields');
}
add_action('admin_init', 'cc_register_settings');

// Display the settings page
function cc_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Custom Comments Settings', 'custom-comments'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('cc-settings-group'); ?>
            <?php do_settings_sections('cc-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Custom Fields', 'custom-comments'); ?></th>
                    <td>
                        <textarea name="cc_custom_fields" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('cc_custom_fields', '')); ?></textarea>
                        <p class="description"><?php _e('Enter custom fields in JSON format. Example: [{"label": "New Field", "name": "new_field", "type": "text", "class": "my-class", "id": "my-id"}]', 'custom-comments'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
?>
