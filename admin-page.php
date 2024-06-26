<?php

// Add admin menu
function cc_add_admin_menu() {
    add_menu_page(
        __('Comments Management', 'custom-comments'),
        __('Comments Management', 'custom-comments'),
        'manage_options',
        'cc-comments-management',
        'cc_comments_management_page',
        'dashicons-admin-comments',
        20
    );
}
add_action('admin_menu', 'cc_add_admin_menu');

// Display the comments management page
function cc_comments_management_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Comments Management', 'custom-comments'); ?></h1>
        <?php
        $args = array(
            'post_type' => 'cc_review',
            'posts_per_page' => -1,
        );

        $reviews = new WP_Query($args);

        if ($reviews->have_posts()) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th><?php _e('Title', 'custom-comments'); ?></th>
                    <th><?php _e('Comment', 'custom-comments'); ?></th>
                    <th><?php _e('Ratings', 'custom-comments'); ?></th>
                    <th><?php _e('Actions', 'custom-comments'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                while ($reviews->have_posts()) {
                    $reviews->the_post();
                    $fields = array(
                        'cleanliness' => 'Propreté',
                        'accuracy' => 'Précision',
                        'checkin' => 'Arrivée',
                        'communication' => 'Communication',
                        'location' => 'Emplacement',
                        'value' => 'Qualité-prix'
                    );
                    ?>
                    <tr>
                        <td><?php the_title(); ?></td>
                        <td><?php the_content(); ?></td>
                        <td>
                            <?php
                            foreach ($fields as $field => $label) {
                                $rating = get_post_meta(get_the_ID(), '_booking_' . $field, true);
                                if (is_numeric($rating) && $rating > 0) {
                                    echo '<div>' . $label . ': ' . str_repeat('★', (int)$rating) . '</div>';
                                } else {
                                    echo '<div>' . $label . ': ' . __('No rating', 'custom-comments') . '</div>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link(get_the_ID()); ?>"><?php _e('Edit', 'custom-comments'); ?></a> |
                            <a href="<?php echo get_delete_post_link(get_the_ID()); ?>"><?php _e('Delete', 'custom-comments'); ?></a>
                        </td>
                    </tr>
                    <?php
                }
                wp_reset_postdata();
                ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>' . __('No reviews found.', 'custom-comments') . '</p>';
        }
        ?>
    </div>
    <?php
}
function cc_clean_plugin_database() {
    global $wpdb;

    // Delete all reviews and their meta
    $reviews = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'cc_review'");
    foreach ($reviews as $review) {
        wp_delete_post($review->ID, true);
    }

    // Delete all related post meta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_booking_%'");

    // Display admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Plugin database cleaned successfully.', 'custom-comments') . '</p></div>';
    });
}

// Add clean database button to the admin page
function cc_add_clean_db_button() {
    ?>
    <div class="wrap">
        <h1><?php _e('Custom Comments Settings', 'custom-comments'); ?></h1>
        <form method="post" action="">
            <?php
            if (isset($_POST['cc_clean_db'])) {
                cc_clean_plugin_database();
            }
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Clean Database', 'custom-comments'); ?></th>
                    <td>
                        <button type="submit" name="cc_clean_db" class="button button-secondary"><?php _e('Clean Database', 'custom-comments'); ?></button>
                        <p class="description"><?php _e('This will delete all reviews and their associated metadata.', 'custom-comments'); ?></p>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <?php
}
add_action('admin_menu', function() {
    add_submenu_page(
        'cc-comments-management',
        __('Settings', 'custom-comments'),
        __('Settings', 'custom-comments'),
        'manage_options',
        'cc-settings',
        'cc_add_clean_db_button'
    );
});

?>
