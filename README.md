# Custom Comments for MotoPress Hotel Booking

## Description

Custom Comments for MotoPress Hotel Booking is a WordPress plugin that adds a custom comment and rating system to the MotoPress Hotel Booking plugin. It allows users to submit reviews with various rating criteria, displays the reviews with pagination, and includes an overall rating widget.

## Features

- Custom comment and rating system
- Multiple rating criteria: cleanliness, accuracy, check-in, communication, location, value
- Display reviews with pagination
- Display an overall rating widget
- Shortcode memo for easy reference
- Admin interface to manage comments

## Installation

1. Upload the `custom-comments` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the provided shortcodes to display the review form, reviews, and rating widget on your site.

## Shortcodes

Use the following shortcodes to display different elements:

- `[cc_review_form]`: Displays the review submission form.
- `[cc_reviews count="5"]`: Displays the reviews with pagination. Change the `count` attribute to the number of reviews per page.
- `[cc_rating_widget]`: Displays the overall rating widget.
- `[cc_average_rating field="cleanliness"]`: Displays the average rating for a specific field. Replace "cleanliness" with the desired field (cleanliness, accuracy, checkin, communication, location, value).

