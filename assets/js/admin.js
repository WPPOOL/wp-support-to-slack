(function($){
    $('#theme_or_plugin').change(function() {
        var val = $(this).val();
        if(val == 'theme'){
           $("#plugin_feed_label_id").text("Theme Slug (wordpress.org)");
        }else if(val == 'plugin'){
            $("#plugin_feed_label_id").text("Plugin Slug (wordpress.org)");
        }
      }) 
})(jQuery);