/**
 * load images on showing an message entry
 */
(function($){
$.fn.lazyLoadImages = function() {
    $(this).find('img[ref]').each(function(i, self) {
        $(self).attr('src', $(self).attr('ref'));
    });
}
})(jQuery);