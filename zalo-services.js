jQuery(function( $ ){
  fontsize = function () {
    var fontSize = $(".zalo-services .service .service-info h4").outerHeight();
    var maxfontSize = $(".zalo-services .service .service-image").outerWidth();
    if ( fontSize > maxfontSize) {
      maxfontSize = (maxfontSize - 5);
      $(".zalo-services .service .service-image").css('font-size', maxfontSize);
    } else {
      $(".zalo-services .service .service-image").css('font-size', fontSize);
    }
  };
  $(window).resize(fontsize);
  $(document).ready(fontsize);
});
