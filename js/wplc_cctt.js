jQuery(document).ready(function () {

	jQuery("body").on("click", ".wplc_admin_convert_chat_to_ticket", function() {
        jQuery("#wplc_admin_convert_chat_to_ticket").hide();
        html = "<span class='wplc_cctt_loading'><em>"+wplc_cctt_string_loading+"</em></span>";
                    
        jQuery("#wplc_admin_convert_chat_to_ticket").after(html);
        var cur_id = jQuery(this).attr("cid");
        var data = {
            action: 'wplc_cctt_admin_convert_chat',
            security: wplc_cctt_nonce,
            cid: cur_id
        };
        jQuery.post(ajaxurl, data, function(response) {
            returned_data = JSON.parse(response);
            console.log(returned_data.constructor);
            if (returned_data.constructor === Object) {
                if (returned_data.errorstring) {
                    jQuery("#wplc_admin_convert_chat_to_ticket").after("<p><strong>"+wplc_cctt_string_error1+"</strong></p>");
                } else {
                    jQuery(".wplc_cctt_loading").hide();

                    html = "<span class=''>"+wplc_cctt_string_ticket_created+"</span> <a href='post.php?post="+returned_data.success+"&action=edit' target='_BLANK' class='button button-primary'>"+wplc_cctt_string_ticket_link_text+"</a>";
                    jQuery("#wplc_admin_convert_chat_to_ticket").after(html);
                    jQuery("#wplc_admin_convert_chat_to_ticket").hide();
                }
            }

        });


    });

});