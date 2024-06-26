jQuery(document).ready(function ($) {
  function loadReviews(page, sort, filter, append = false) {
    var count = cc_reviews_ajax.count;

    $.ajax({
      url: cc_reviews_ajax.ajax_url,
      type: "post",
      data: {
        action: "cc_load_reviews",
        page: page,
        count: count,
        sort: sort,
        filter: filter,
      },
      success: function (response) {
        if (response.trim() !== "<p>No reviews found.</p>") {
          if (append) {
            $("#cc-popup-reviews").append(response); // Append more reviews
          } else {
            $("#cc-popup-reviews").html(response); // Replace content
          }
          $("#cc-load-more-reviews").data("page", page);
          $("#cc-popup").addClass("show");
        }
      },
    });
  }

  $("#cc-load-more-reviews").on("click", function () {
    var button = $(this);
    var page = button.data("page") + 1;
    var sort = $("#cc-sort-reviews").val();
    var filter = $("#cc-filter-reviews").val();
    loadReviews(page, sort, filter, true);
    $("#cc-popup").addClass("show");
  });

  // Sort reviews
  $("#cc-sort-reviews").on("change", function () {
    var sort = $(this).val();
    var filter = $("#cc-filter-reviews").val();
    loadReviews(1, sort, filter);
  });

  // Filter reviews by clicking on most frequent words
  $(document).on("click", ".cc-frequent-word", function () {
    var word = $(this).data("word");
    $("#cc-filter-reviews").val(word);
    var sort = $("#cc-sort-reviews").val();
    loadReviews(1, sort, word);
  });

  // Close popup when clicking outside of it or on the close button
  $(document).on("click", function (event) {
    if ($(event.target).is("#cc-popup")) {
      // Ensure click is outside the popup content
      $("#cc-popup-content").addClass("fadeOut");
      setTimeout(function () {
        $("#cc-popup").removeClass("show");
        $("#cc-popup-content").removeClass("fadeOut");
      }, 300); // Match the duration of the fadeOut animation
    }
  });

  $("#cc-popup-close").on("click", function () {
    $("#cc-popup-content").addClass("fadeOut");
    setTimeout(function () {
      $("#cc-popup").removeClass("show");
      $("#cc-popup-content").removeClass("fadeOut");
    }, 300); // Match the duration of the fadeOut animation
  });
});
