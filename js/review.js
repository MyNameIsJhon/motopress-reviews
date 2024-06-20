jQuery(document).ready(function($) {
    $('#cc-load-more-reviews').on('click', function() {
        var button = $(this);
        var page = button.data('page');
        var count = cc_reviews_ajax.count;

        $.ajax({
            url: cc_reviews_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'cc_load_reviews',
                page: page,
                count: count
            },
            success: function(response) {
                $('#cc-reviews-container').append(response);
                button.data('page', page + 1);
            }
        });
    });

    $('.star-rating input').change(function() {
        var $radio = $(this);
        var $ratingContainer = $radio.closest('.star-rating');
        $ratingContainer.find('label').css('color', '#ddd');
        $radio.prevAll('label').addBack().css('color', '#ffb400');
    });

    $('.star-rating label').hover(
        function() {
            var $label = $(this);
            $label.css('color', '#ffb400');
            $label.prevAll('label').css('color', '#ffb400');
        }, function() {
            var $ratingContainer = $(this).closest('.star-rating');
            $ratingContainer.find('input:checked').prevAll('label').addBack().css('color', '#ffb400');
            $ratingContainer.find('input:not(:checked)').nextAll('label').css('color', '#ddd');
        }
    );
});
