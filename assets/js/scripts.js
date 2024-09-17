// console.log('Hello AJAX');
jQuery( function($) {
  $('.js-filter select').on('change', function () {
    var cat = $('#cat').val()
    rating = $('#popularity').val();
    // alert(cat);
    var data = {
      action: 'filter_posts',
      cat: cat,
      rating: rating,
    }
    $.ajax({
      url: variables.ajax_url,
      type: 'POST',
      data: data,
      success: function (response) {
        $('.films-list').html(response);
      }
    })
  });
});