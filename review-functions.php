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

/*Get the 5 most frequent used words in commentaries * */
function cc_get_most_frequent_words($limit = 5) {
    global $wpdb;

    $results = $wpdb->get_results("
        SELECT post_content 
        FROM {$wpdb->posts} 
        WHERE post_type = 'cc_review' 
        AND post_status = 'publish'
    ");

    $text = '';
    foreach ($results as $result) {
        $text .= ' ' . $result->post_content;
    }

    $words = str_word_count(strtolower($text), 1);
    $words = array_filter($words, function($word) {
        return strlen($word) > 3; // Filter out words shorter than 4 characters
    });

    $word_counts = array_count_values($words);
    arsort($word_counts);

    return array_slice($word_counts, 0, $limit);
}

function cc_display_most_frequent_words() {
    $frequent_words = cc_get_most_frequent_words();
    ob_start();
    ?>
    <div class="cc-frequent-words">
        <h3><?php _e('Most Frequent Words', 'custom-comments'); ?></h3>
        <ul>
            <?php foreach ($frequent_words as $word => $count) : ?>
                <li class="cc-frequent-word" data-word="<?php echo esc_attr($word); ?>"><?php echo esc_html($word); ?> (<?php echo intval($count); ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}


// Save review fields
function cc_save_review_fields($booking_id) {
    $custom_fields = get_option('cc_custom_fields', '');
    $custom_fields = json_decode($custom_fields, true);
    if (!is_array($custom_fields)) {
        $custom_fields = array();
    }

    $total_rating = 0;
    $num_ratings = 0;

    foreach ($custom_fields as $field) {
        if (isset($_POST['booking_' . $field['name']])) {
            $value = sanitize_text_field($_POST['booking_' . $field['name']]);
            update_post_meta($booking_id, '_booking_' . $field['name'], $value);

            if (is_numeric($value) && $value > 0) {
                $total_rating += (int)$value;
                $num_ratings++;
            }
        }
    }

    $average_rating = $num_ratings ? round($total_rating / $num_ratings, 1) : 0;
    update_post_meta($booking_id, '_booking_rating', $average_rating);
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

    <!-- Popup structure -->
    <div id="cc-popup">
        <div id="cc-popup-content">
            <span id="cc-popup-close">&times;</span>
            <div id="cc-popup-left">
                <?php echo do_shortcode('[cc_rating_widget]'); ?>
                <?php echo do_shortcode('[cc_average_rating_inline field="cleanliness"]');?>
                <?php echo do_shortcode('[cc_average_rating_inline field="accuracy"]');?>
                <?php echo do_shortcode('[cc_average_rating_inline field="checkin"]');?>
                <?php echo do_shortcode('[cc_average_rating_inline field="communication"]');?>
                <?php echo do_shortcode('[cc_average_rating_inline field="location"]');?>
                <?php echo do_shortcode('[cc_average_rating_inline field="value"]');?>


               
            </div>
            <div id="cc-popup-right">
                 <div class="cc-sort-reviews">
                    <label for="cc-sort-reviews"><?php _e('Sort By:', 'custom-comments'); ?></label>
                    <select id="cc-sort-reviews">
                        <option value="date_desc"><?php _e('Date Descending', 'custom-comments'); ?></option>
                        <option value="date_asc"><?php _e('Date Ascending', 'custom-comments'); ?></option>
                        <option value="best_rated"><?php _e('Best Rated', 'custom-comments'); ?></option>
                        <option value="worst_rated"><?php _e('Worst Rated', 'custom-comments'); ?></option>
                    </select>
                </div>
                <?php echo cc_display_most_frequent_words(); ?>
                <input type="hidden" id="cc-filter-reviews" value="">
                <div id="cc-popup-reviews">
                    <?php cc_load_reviews(1, $atts['count']); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cc_reviews', 'cc_display_reviews');





// Function to load reviews for AJAX call
function cc_load_reviews($page = 1, $count = 5, $sort = 'date_desc', $filter = '') {
    $args = array(
        'post_type' => 'cc_review',
        'posts_per_page' => $count,
        'paged' => $page,
    );

    if (!empty($filter)) {
        $args['s'] = $filter;
    }

    // Sorting logic
    switch ($sort) {
        case 'date_asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'date_desc':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'best_rated':
            $args['meta_key'] = '_booking_rating';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'worst_rated':
            $args['meta_key'] = '_booking_rating';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
    }

    $reviews = new WP_Query($args);

    if ($reviews->have_posts()) {
        while ($reviews->have_posts()) {
            $reviews->the_post();
            $author = get_the_author();
            $author_id = get_the_author_meta('ID');
            $author_avatar = get_avatar_url($author_id);
            $date = get_the_date();
            $comment = get_the_content();
            $average_rating = get_post_meta(get_the_ID(), '_booking_rating', true);
            $average_rating = is_numeric($average_rating) ? (int) $average_rating : 0;

            // Display review
            ?>
            <div class="review">
                <div class="review-header">
                    <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author); ?>" class="review-avatar">
                    <div class="review-author"><?php echo esc_html($author); ?></div>
                    <div class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $average_rating ? '<span style="color: #ffb400;">★</span>' : '<span style="color: #ddd;">★</span>';
                        } ?>
                    </div>
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
    if (isset($_POST['page']) && isset($_POST['count']) && isset($_POST['sort'])) {
        $page = intval($_POST['page']);
        $count = intval($_POST['count']);
        $sort = sanitize_text_field($_POST['sort']);
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
        ob_start();
        cc_load_reviews($page, $count, $sort, $filter);
        $output = ob_get_clean();
        echo $output;
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
        <div>
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
            <div class="hell">
                <label><?php _e($label, 'custom-comments'); ?></label>
                <div class="star-rating" data-field="<?php echo $field; ?>">
                    <?php for ($i = 5; $i >= 1; $i--) : ?>
                        <input type="radio" name="cc_review_<?php echo $field; ?>" id="cc_review_<?php echo $field; ?>_<?php echo $i; ?>" value="<?php echo $i; ?>" />
                        <label for="cc_review_<?php echo $field; ?>_<?php echo $i; ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php
        }
        ?>
        </div>
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

    // Tableau associatif pour mapper les champs à leurs valeurs textuelles et icônes Font Awesome
    $field_data = array(
        'cleanliness' => array('label' => 'Cleanliness', 'icon' => 'fa-sharp fa-thin fa-hand-sparkles fa-2xl'),
        'accuracy' => array('label' => 'Accuracy', 'icon' => 'fa-thin fa-circle-check fa-2xl'),
        'checkin' => array('label' => 'Check-in', 'icon' => 'fa-thin fa-key fa-2xl'),
        'communication' => array('label' => 'Communication', 'icon' => 'fa-thin fa-message fa-2xl'),
        'location' => array('label' => 'Location', 'icon' => 'fa-thin fa-map fa-2xl'),
        'value' => array('label' => 'Value', 'icon' => 'fa-thin fa-tag fa-2xl')
    );

    if (array_key_exists($atts['field'], $field_data)) {
        $field_label = $field_data[$atts['field']]['label'];
        $field_icon = $field_data[$atts['field']]['icon'];
        return "<h6 style='font-weight:600;'>". $field_label . "</h6><h5>" . cc_average_rating($atts['field']) . "</5><div style='margin-top:20px;'><i class='" . $field_icon . "'></i></div> " ;
    }
    return '';
}
add_shortcode('cc_average_rating', 'cc_display_average_rating');


function cc_average_rating_inline($field) {
    global $wpdb;
    $average = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta WHERE meta_key = %s",
        '_booking_' . $field
    ));
    return round($average, 1);
}


function cc_display_average_rating_inline($atts) {
    $atts = shortcode_atts(array(
        'field' => 'cleanliness',
    ), $atts, 'cc_average_rating_inline');

    // Tableau associatif pour mapper les champs à leurs valeurs textuelles et icônes Font Awesome
    $field_data = array(
        'cleanliness' => array('label' => 'Cleanliness', 'icon' => 'fa-sharp fa-thin fa-hand-sparkles fa-2xl'),
        'accuracy' => array('label' => 'Accuracy', 'icon' => 'fa-thin fa-circle-check fa-2xl'),
        'checkin' => array('label' => 'Check-in', 'icon' => 'fa-thin fa-key fa-2xl'),
        'communication' => array('label' => 'Communication', 'icon' => 'fa-thin fa-message fa-2xl'),
        'location' => array('label' => 'Location', 'icon' => 'fa-thin fa-map fa-2xl'),
        'value' => array('label' => 'Value', 'icon' => 'fa-thin fa-tag fa-2xl')
    );

    if (array_key_exists($atts['field'], $field_data)) {
        $field_label = $field_data[$atts['field']]['label'];
        $field_icon = $field_data[$atts['field']]['icon'];
        return "<div class='inline-quotation-block'><div class='inline-quotation-text-log'><i class='".$field_icon."'></i><h6 style='font-weight:600; margin-bottom:0;'>". $field_label . "</h6></div><h5 style='margin-bottom:0;'>" . cc_average_rating($atts['field']) . "</5> </div> " ;
    }
    return '';
}
add_shortcode('cc_average_rating_inline', 'cc_display_average_rating_inline');

?>
