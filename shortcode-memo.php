<?php

// Add a submenu for shortcode memo
function cc_add_shortcode_memo_menu() {
    add_submenu_page(
        'cc-comments-management', // Parent slug
        __('Shortcode Memo', 'custom-comments'), // Page title
        __('Shortcode Memo', 'custom-comments'), // Menu title
        'manage_options', // Capability
        'cc-shortcode-memo', // Menu slug
        'cc_shortcode_memo_page' // Function to display the page
    );
}
add_action('admin_menu', 'cc_add_shortcode_memo_menu');

// Display the shortcode memo page
function cc_shortcode_memo_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Shortcode Memo', 'custom-comments'); ?></h1>
        <p><?php _e('Use the following shortcodes to display different elements:', 'custom-comments'); ?></p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Shortcode', 'custom-comments'); ?></th>
                    <th><?php _e('Description', 'custom-comments'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[cc_review_form]</code></td>
                    <td><?php _e('Displays the review submission form.', 'custom-comments'); ?></td>
                </tr>
                <tr>
                    <td><code>[cc_reviews count="5"]</code></td>
                    <td><?php _e('Displays the reviews with pagination. Change the count attribute to the number of reviews per page.', 'custom-comments'); ?></td>
                </tr>
                <tr>
                    <td><code>[cc_rating_widget]</code></td>
                    <td><?php _e('Displays the overall rating widget.', 'custom-comments'); ?></td>
                </tr>
                <tr>
                    <td><code>[cc_average_rating field="cleanliness"]</code></td>
                    <td><?php _e('Displays the average rating for a specific field. Replace "cleanliness" with the desired field (cleanliness, accuracy, checkin, communication, location, value).', 'custom-comments'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}
?>
