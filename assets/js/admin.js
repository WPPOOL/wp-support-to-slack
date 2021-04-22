(function($) {
    'use strict';
  
    $(document).ready(function() {
      //Initiate Color Picker
      //add chooser
      //$(".chosen-select").chosen();
      $('.selecttwo-select').select2({
        placeholder: support_to_slack_admin_setting.please_select,
        allowClear: false,
      });
  
  
      //for dayexception field
      // exceptional field added
      $('.dayexception_wrapper').on('click', '.add_exception', function(e) {
        e.preventDefault();
        var $this    = $(this);
        var $name    = $this.data('name');
        var $section = $this.data('section');
  
        var $ex_wrapper = $this.closest('.dayexception_wrapper');
        var $ex_items   = $ex_wrapper.find('.dayexception_items');
  
        var $unique_last_count     = $ex_wrapper.find('.dayexception_last_count');
        var $unique_last_count_val = parseInt($unique_last_count.val());
  
        $unique_last_count_val++;
  
        $unique_last_count.val($unique_last_count_val);
  
        var field = '<div class="dayexception_item" style="height: 360px; padding: 20px;">' +
            '<div class="feed_item"><div class="feed_item_label"><label for="slack_webhook'+'_'+$unique_last_count_val+'">Slack Webhook</label></div><div class="feed_item_field"><input type="text" name="' + $section + '[' + $name + '][feed][' + $unique_last_count_val + '][webhook]" placeholder="' + support_to_slack_admin_setting.date + '" class="datepicker" id="slack_webhook'+'_'+$unique_last_count_val+'" autocomplete="off" required /></div></div>&nbsp;' +
            '<div class="feed_item"><div class="feed_item_label"><label for="plugin_slug'+'_'+$unique_last_count_val+'">Plugin / Theme Slug</label></div><div class="feed_item_field"><input type="text" name="' + $section + '[' + $name + '][feed][' + $unique_last_count_val + '][org_link]" placeholder="' + support_to_slack_admin_setting.start + '" class="timepicker timepicker-start" id="plugin_slug'+'_'+$unique_last_count_val+'" autocomplete="off" /></div></div>&nbsp;' +
            '<div class="feed_item"><div class="feed_item_label"><label for="custom_slack_message'+'_'+$unique_last_count_val+'">Custom Slack Message</label></div><div class="feed_item_field"><textarea name="' + $section + '[' + $name + '][feed][' + $unique_last_count_val + '][message]" placeholder="' + support_to_slack_admin_setting.end + '" class="timepicker timepicker-end" id="custom_slack_message'+'_'+$unique_last_count_val+'" autocomplete="off" rows="4" cols="50" ></textarea></div></div>&nbsp;' +
            '<a href="#" class="remove_exception button">' + '<span class="dashicons dashicons-trash" style="margin-top: 3px;color: red;"></span>' + support_to_slack_admin_setting.remove + '</a>' +
            '</div>';
  
        $ex_items.append(field);
  
    
  
      }); // end exceptional field
  
      // Remove single exception row
      $('.dayexception_wrapper').on('click', '.remove_exception', function(e) {
        e.preventDefault();
  
        var $this = $(this);
        $this.closest('.dayexception_item').remove();
      });
  
      // Remove all exception rows
      $('.dayexception_wrapper').on('click', '.removeall_exception', function(e) {
        e.preventDefault();
  
        var $this           = $(this);
        var $parent_wrapper = $this.closest('.dayexception_wrapper');
        $parent_wrapper.find('.dayexception_items').empty();
      });
  
      // Switches option sections
      $('.support_to_slack_group').hide();
      var activetab = '';
      if (typeof (localStorage) != 'undefined') {
        //get
        activetab = localStorage.getItem('cbxbusinesshoursactivetab');
      }
      if (activetab != '' && $(activetab).length) {
        $(activetab).fadeIn();
      } else {
        $('.support_to_slack_group:first').fadeIn();
      }
  
      $('.support_to_slack_group .collapsed').each(function() {
        $(this).find('input:checked').parent().parent().parent().nextAll().each(
            function() {
              if ($(this).hasClass('last')) {
                $(this).removeClass('hidden');
                return false;
              }
              $(this).filter('.hidden').removeClass('hidden');
            });
      });
  
      if (activetab != '' && $(activetab + '-tab').length) {
        $(activetab + '-tab').addClass('nav-tab-active');
      } else {
        $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
      }
  
      $('.nav-tab-wrapper a').on('click', function(evt) {
        evt.preventDefault();
  
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active').blur();
        var clicked_group = $(this).attr('href');
        if (typeof (localStorage) != 'undefined') {
          //set
          localStorage.setItem('cbxbusinesshoursactivetab', $(this).attr('href'));
        }
        $('.support_to_slack_group').hide();
        $(clicked_group).fadeIn();
  
      });
  
      //make the subheading single row
      $('.setting_subheading').each(function(index, element) {
        var $element        = $(element);
        var $element_parent = $element.parent('td');
        $element_parent.attr('colspan', 2);
        $element_parent.prev('th').remove();
      });
  
      //make the subheading single row
      $('.setting_heading').each(function(index, element) {
        var $element        = $(element);
        var $element_parent = $element.parent('td');
        $element_parent.attr('colspan', 2);
        $element_parent.prev('th').remove();
      });
  
      $('.support_to_slack_group').each(function(index, element) {
        var $element    = $(element);
        var $form_table = $element.find('.form-table');
        $form_table.prev('h2').remove();
      });
      /* console.log(support_to_slack_admin_setting.plugin_directory+'/templates/admin/sidebar.php');
	  $(".view_php").click(function(){
		  $(".sidebar_plugin").load(''+support_to_slack_admin_setting.plugin_directory+'/templates/admin/sidebar.php');
	  }); */
	$.ajax({
		type : 'POST',
		dataType : 'json',
		url : support_to_slack_admin_setting.ajax_url, //we can use this value because in our php file we used wp_localize_script
		data : {action: 'fetch_wordpress_plugin', },
		success: function(response) {
			//load the fetched php file into the div
			console.log(response);
			$('.sidebar_plugin').html(response.content);
		}
	});

      
    });
    $(".accordion_tab").click(function(){
        $(".accordion_tab").each(function(){
          $(this).parent().removeClass("active");
          $(this).removeClass("active");
        });
        $(this).parent().addClass("active");
        $(this).addClass("active");
    });
  
  })(jQuery);