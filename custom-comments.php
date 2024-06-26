<?php
/*
Plugin Name: Custom Comments for MotoPress Hotel Booking
Description: Adds a custom comment and rating system for MotoPress Hotel Booking.
Version: 1.2
Author: Evocative Studio 
*/

require_once plugin_dir_path(__FILE__) . 'review-functions.php';
require_once plugin_dir_path(__FILE__) . 'admin-page.php';
require_once plugin_dir_path(__FILE__) . 'shortcode-memo.php';
require_once plugin_dir_path(__FILE__) . 'settings-page.php';
function cc_enqueue_styles() {
    wp_enqueue_style('cc-reviews-css', plugin_dir_url(__FILE__) . 'styles/reviews.css');
    wp_enqueue_script('cc_reviews_js', plugin_dir_url(__FILE__) . 'js/reviews.js', array('jquery'), null, true);
    wp_enqueue_script('cc_popup_js', plugin_dir_url(__FILE__) . 'js/popup.js', array('jquery'), null, true);
    wp_localize_script('cc_reviews_js', 'cc_reviews_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'count' => 5,
    ));
}
add_action('wp_enqueue_scripts', 'cc_enqueue_styles');

// Register custom post type for reviews
function cc_create_review_post_type() {
    register_post_type('cc_review',
        array(
            'labels' => array(
                'name' => __('Reviews'),
                'singular_name' => __('Review')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'author', 'custom-fields'),
        )
    );
}
add_action('init', 'cc_create_review_post_type');

?>

