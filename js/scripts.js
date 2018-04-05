jQuery(document).ready(function($) {
    jQuery("#wype_ajax_load_posts").on("click", function() {
        var request = jQuery.ajax({
            url: "admin-ajax.php",
            method: "POST",
            data: { action: 'wype_load_posts', post_type: jQuery("#wype_post_type_id").val(), total: jQuery("#wype_total_results").val() }
        });

        request.done(function(content) {
            jQuery("#wype_posts_content").html(String(content));

            jQuery("#wype_type").val("post");
        });
    });

    jQuery("#wype_ajax_load_taxonomies").on("click", function() {
        var request = jQuery.ajax({
            url: "admin-ajax.php",
            method: "POST",
            data: { action: 'wype_load_terms', taxonomy_id: jQuery("#wype_post_terms_id").val(), total: jQuery("#wype_total_results").val() }
        });

        request.done(function(content) {
            jQuery("#wype_posts_content").html(String(content));

            jQuery("#wype_type").val("taxonomy");
        });
    });

    jQuery("#search").on("keyup", function() {
        if (jQuery(this).val() != "") {
            var value = jQuery(this).val().toLowerCase();

            jQuery("#wype_posts_content .row").filter(function() {
                jQuery(this).toggle(jQuery(this).text().toLowerCase().indexOf(value) > -1)
            });

        } else
            jQuery("#wype_posts_content .row").css("display", "block");
    });

    jQuery("#show_ytitles").change(function() {
        if (jQuery(this).is(':checked'))
            jQuery("#wype_posts_content .ytitles").css("display", "block");
        else
            jQuery("#wype_posts_content .ytitles").css("display", "none");
    });

    jQuery("#show_ykeywords").change(function() {
        if (jQuery(this).is(':checked'))
            jQuery("#wype_posts_content .ykeywords").css("display", "block");
        else
            jQuery("#wype_posts_content .ykeywords").css("display", "none");
    });

    jQuery("#show_ydescriptions").change(function() {
        if (jQuery(this).is(':checked'))
            jQuery("#wype_posts_content .ydescriptions").css("display", "block");
        else
            jQuery("#wype_posts_content .ydescriptions").css("display", "none");
    });

    jQuery("#show_slugs").change(function() {
        if (jQuery(this).is(':checked'))
            jQuery("#wype_posts_content .slug").css("display", "block");
        else
            jQuery("#wype_posts_content .slug").css("display", "none");
    });
});