<?php

// Add review fields to booking form
function cc_add_review_fields() {
    $custom_fields = get_option('cc_custom_fields', '');
    $custom_fields = json_decode($custom_fields, true);
    if (!is_array($custom_fields)) {
        $custom_fields = array();
    }

    foreach ($custom_fields as $field) {
        ?>
        <p class="form-row form-row-wide <?php echo esc_attr($field['class']); ?>" id="<?php echo esc_attr($field['id']); ?>">
            <label for="booking_<?php echo esc_attr($field['name']); ?>"><?php echo esc_html($field['label']); ?></label>
            <?php
            switch ($field['type']) {
                case 'text':
                    ?>
                    <input type="text" name="booking_<?php echo esc_attr($field['name']); ?>" id="booking_<?php echo esc_attr($field['name']); ?>" />
                    <?php
                    break;
                case 'textarea':
                    ?>
                    <textarea name="booking_<?php echo esc_attr($field['name']); ?>" id="booking_<?php echo esc_attr($field['name']); ?>" rows="4"></textarea>
                    <?php
                    break;
                case 'select':
                    if (!empty($field['options']) && is_array($field['options'])) {
                        ?>
                        <select name="booking_<?php echo esc_attr($field['name']); ?>" id="booking_<?php echo esc_attr($field['name']); ?>">
                            <?php foreach ($field['options'] as $option_value) { ?>
                                <option value="<?php echo esc_attr($option_value); ?>"><?php echo esc_html($option_value); ?></option>
                            <?php } ?>
                        </select>
                        <?php
                    }
                    break;
            }
            ?>
        </p>
        <?php
    }
}
add_action('mphb_sc_booking_form_after_contact_details', 'cc_add_review_fields');

// Save review fields
function cc_save_review_fields($booking_id) {
    $custom_fields = get_option('cc_custom_fields', '');
    $custom_fields = json_decode($custom_fields, true);
    if (!is_array($custom_fields)) {
        $custom_fields = array();
    }

    foreach ($custom_fields as $field) {
        if (isset($_POST['booking_' . $field['name']])) {
            update_post_meta($booking_id, '_booking_' . $field['name'], sanitize_text_field($_POST['booking_' . $field['name']]));
        }
    }
}
add_action('mphb_booking_confirmed', 'cc_save_review_fields');

// Function to calculate rating data
function cc_get_rating_data() {
    global $wpdb;
    $rating_data = array('total' => 0, 'counts' => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0));

    $fields = array('cleanliness', 'accuracy', 'checkin', 'communication', 'location', 'value');
    foreach ($fields as $field) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_value, COUNT(meta_value) as count FROM $wpdb->postmeta WHERE meta_key = %s GROUP BY meta_value",
            '_booking_' . $field
        ));

        foreach ($results as $result) {
            $rating = (int)$result->meta_value;
            $count = (int)$result->count;
            if (isset($rating_data['counts'][$rating])) {
                $rating_data['counts'][$rating] += $count;
                $rating_data['total'] += $rating * $count;
            }
        }
    }

    return $rating_data;
}

// Shortcode to display rating widget
function cc_display_rating_widget() {
    $rating_data = cc_get_rating_data();
    $total_votes = array_sum($rating_data['counts']);
    $average_rating = $total_votes ? round($rating_data['total'] / $total_votes, 1) : 0;

    ob_start();
    ?>
    <div class="cc-rating-widget">
        <h3><?php _e('Évaluation globale', 'custom-comments'); ?></h3>
        <div class="cc-rating-rows">
            <?php for ($i = 5; $i >= 1; $i--) : ?>
                <div class="cc-rating-row">
                    <span class="cc-rating-label"><?php echo $i; ?></span>
                    <span class="cc-rating-bar-container">
                        <span class="cc-rating-bar" style="width: <?php echo ($total_votes ? ($rating_data['counts'][$i] / $total_votes * 100) : 0); ?>%;"></span>
                    </span>
                </div>
            <?php endfor; ?>
        </div>
        <div class="cc-rating-average">
            <span><?php _e('Note moyenne', 'custom-comments'); ?>: <?php echo $average_rating; ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cc_rating_widget', 'cc_display_rating_widget');

// Shortcode to display reviews
function cc_display_reviews($atts) {
    $atts = shortcode_atts(array(
        'count' => 5, // Number of reviews per page
    ), $atts, 'cc_reviews');

    wp_enqueue_script('cc_reviews_js');
    wp_localize_script('cc_reviews_js', 'cc_reviews_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'count' => $atts['count'],
    ));

    ob_start();
    ?>
    <div id="cc-reviews-container">
        <?php cc_load_reviews(1, $atts['count']); ?>
    </div>
    <button id="cc-load-more-reviews" data-page="1"><?php _e('Load More Reviews', 'custom-comments'); ?></button>
    <?php
    return ob_get_clean();
}
add_shortcode('cc_reviews', 'cc_display_reviews');

function cc_load_reviews($page = 1, $count = 5) {
    $args = array(
        'post_type' => 'cc_review',
        'posts_per_page' => $count,
        'paged' => $page,
    );

    $reviews = new WP_Query($args);

    if ($reviews->have_posts()) {
        while ($reviews->have_posts()) {
            $reviews->the_post();
            $author = get_the_author();
            $author_id = get_the_author_meta('ID');
            $author_avatar = get_avatar_url($author_id);
            $date = get_the_date();
            $comment = get_the_content();
            $fields = array('cleanliness', 'accuracy', 'checkin', 'communication', 'location', 'value');
            $total_rating = 0;
            $num_ratings = 0;

            foreach ($fields as $field) {
                $rating = get_post_meta(get_the_ID(), '_booking_' . $field, true);
                if (is_numeric($rating)) {
                    $total_rating += (int)$rating;
                    $num_ratings++;
                }
            }

            $average_rating = $num_ratings ? round($total_rating / $num_ratings, 1) : 0;
            ?>
            <div class="review">
                <div class="review-header">
                    <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author); ?>" class="review-avatar">
                    <div class="review-author"><?php echo esc_html($author); ?></div>
                    <div class="review-rating"><?php echo str_repeat('★', (int)$average_rating); ?></div>
                </div>
                <div class="review-date"><?php echo esc_html($date); ?></div>
                <p class="review-comment"><?php echo esc_html($comment); ?></p>
            </div>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p>' . __('No reviews found.', 'custom-comments') . '</p>';
    }
}

function cc_load_reviews_ajax() {
    if (isset($_POST['page']) && isset($_POST['count'])) {
        $page = intval($_POST['page']) + 1;
        $count = intval($_POST['count']);
        cc_load_reviews($page, $count);
        wp_die();
    }
}
add_action('wp_ajax_cc_load_reviews', 'cc_load_reviews_ajax');
add_action('wp_ajax_nopriv_cc_load_reviews', 'cc_load_reviews_ajax');

// Shortcode to display review submission form
function cc_review_submission_form() {
    if (isset($_POST['cc_submit_review'])) {
        $review_data = array(
            'post_title' => sanitize_text_field($_POST['cc_review_title']),
            'post_content' => sanitize_textarea_field($_POST['cc_review_content']),
            'post_type' => 'cc_review',
            'post_status' => 'publish',
            'meta_input' => array(
                '_booking_cleanliness' => (int)sanitize_text_field($_POST['cc_review_cleanliness']),
                '_booking_accuracy' => (int)sanitize_text_field($_POST['cc_review_accuracy']),
                '_booking_checkin' => (int)sanitize_text_field($_POST['cc_review_checkin']),
                '_booking_communication' => (int)sanitize_text_field($_POST['cc_review_communication']),
                '_booking_location' => (int)sanitize_text_field($_POST['cc_review_location']),
                '_booking_value' => (int)sanitize_text_field($_POST['cc_review_value']),
            ),
        );
        wp_insert_post($review_data);
    }

    ob_start();
    ?>
    <form action="" method="post" class="cc-review-form">
        <p>
            <label for="cc_review_title"><?php _e('Title', 'custom-comments'); ?></label>
            <input type="text" name="cc_review_title" id="cc_review_title" class="cc-input" required>
        </p>
        <p>
            <label for="cc_review_content"><?php _e('Review', 'custom-comments'); ?></label>
            <textarea name="cc_review_content" id="cc_review_content" rows="4" class="cc-input" required></textarea>
        </p>
        <?php
        $fields = array(
            'cleanliness' => 'Propreté',
            'accuracy' => 'Précision',
            'checkin' => 'Arrivée',
            'communication' => 'Communication',
            'location' => 'Emplacement',
            'value' => 'Qualité-prix'
        );
        foreach ($fields as $field => $label) {
            ?>
            <p>
                <label><?php _e($label, 'custom-comments'); ?></label>
                <div class="star-rating" data-field="<?php echo $field; ?>">
                    <?php for ($i = 5; $i >= 1; $i--) : ?>
                        <input type="radio" name="cc_review_<?php echo $field; ?>" id="cc_review_<?php echo $field; ?>_<?php echo $i; ?>" value="<?php echo $i; ?>" />
                        <label for="cc_review_<?php echo $field; ?>_<?php echo $i; ?>">★</label>
                    <?php endfor; ?>
                </div>
            </p>
            <?php
        }
        ?>
        <p>
            <input type="submit" name="cc_submit_review" value="<?php _e('Submit Review', 'custom-comments'); ?>" class="cc-submit">
        </p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('cc_review_form', 'cc_review_submission_form');

// Shortcodes to display average ratings
function cc_average_rating($field) {
    global $wpdb;
    $average = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta WHERE meta_key = %s",
        '_booking_' . $field
    ));
    return round($average, 1);
}

function cc_display_average_rating($atts) {
    $atts = shortcode_atts(array(
        'field' => 'cleanliness',
    ), $atts, 'cc_average_rating');

    $fields = array('cleanliness', 'accuracy', 'checkin', 'communication', 'location', 'value');
    if (in_array($atts['field'], $fields)) {
        return cc_average_rating($atts['field']);
    }
    return '';
}
add_shortcode('cc_average_rating', 'cc_display_average_rating');

?>
