(function($){

$(".add_to_cron").on("click", function (e) {

    e.preventDefault();
    console.log("heloo");
    
    var $last_count = $('.last_count');
    var $last_count_val = parseInt($last_count.val());
    
    $last_count_val++;
    
    $last_count.val($last_count_val);
    
    
    var fields = '<div class="cron_plugin_info"><div class="slack_webhook_sec"><label for="hook_url">Slack Webhook</label><input type="text" value="" name="plugin_feed_info[' + $last_count_val + '][hook_url]" class="hook_url" id="hook_url" /><button type="button" name="remove_cron" class="remove_cron" id="remove_cron" value="Remove">Remove</button></div><div class="plugin_feed_sec"><label for="plugin_feed">Plugin Feed URL</label><input type="text" value="" name="plugin_feed_info[' + $last_count_val + '][plugin_feed]" class="plugin_feed" id="plugin_feed" /><br><span">e.g. <code>https://wordpress.org/support/plugin/your_plugin_slug/feed</code></span><div class="interval_time_sec"><label for="time_interval">Time interval</label><input type="text" value="" name="plugin_feed_info[' + $last_count_val+ '][time_interval]" class="time_interval" id="time_interval" /></div></div><hr>';
    
    $('.cron_plugin_list').append(fields);
    
    
    });
    
    //================
    
    // Remove extra service section specific row
    
    //================
    $(".cron_plugin_list").on("click", ".remove_cron", function (e) {
    
    e.preventDefault();
    
    $(this).closest('.cron_plugin_info').remove();
    });
 
})(jQuery);