//"use strict";
jQuery(document).on('ready', function () {
    var loader_html = '<div class="provider-site-wrap"><div class="provider-loader"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>';
    var delete_article_title = fw_ext_articles_scripts_vars.delete_article_title;
    var delete_article_msg = fw_ext_articles_scripts_vars.delete_article_msg;
	
    /***********************************************
     * Add/Edit new Article
     **********************************************/
    jQuery(document).on('click', '.process-article', function (e) {
        e.preventDefault();
        if( typeof tinyMCE === 'object' ) {
		  tinyMCE.triggerSave();
		}
		
        var _this = jQuery(this);
        var _type = _this.data('type');
        var serialize_data = jQuery('.tg-addarticle').serialize();
        var dataString = 'type=' + _type + '&' + serialize_data + '&action=fw_ext_listingo_process_articles';
        jQuery('body').append(loader_html);
        jQuery.ajax({
            type: "POST",
            url: fw_ext_articles_scripts_vars.ajaxurl,
            data: dataString,
            dataType: "json",
            success: function (response) {
                jQuery('body').find('.provider-site-wrap').remove();
                if (response.type == 'error') {
                    jQuery.sticky(response.message, {classList: 'important', speed: 200, autoclose: 5000});
                } else {                 
                    jQuery.sticky(response.message, {classList: 'success', speed: 200, autoclose: 5000, position: 'top-right', });
					if (response.return_url) {                      
						window.location.replace(response.return_url);                  
					}
                }
            }
        });
        return false;
    });
	
    /***********************************************
     * Delete Article
     **********************************************/
    jQuery(document).on('click', '.btn-article-del', function (event) {
        event.preventDefault();
        var _this = jQuery(this);
        var _id = _this.data('key');
        jQuery.confirm({
            'title': delete_article_title,
            'message': delete_article_msg,
            'buttons': {
                'Yes': {
                    'class': 'blue',
                    'action': function () {
                        jQuery('body').append(loader_html);
                        jQuery.ajax({
                            type: "POST",
                            url: fw_ext_articles_scripts_vars.ajaxurl,
                            data: 'id=' + _id + '&action=fw_ext_listingo_delete_articles',
                            dataType: "json",
                            success: function (response) {
                                jQuery('body').find('.provider-site-wrap').remove();
                                if (response.type == 'success') {
                                    jQuery.sticky(response.message, {classList: 'success', speed: 200, autoclose: 5000, position: 'top-right', });
                                    _this.parents('tr').remove();
                                } else {
                                    jQuery.sticky(response.message, {classList: 'important', speed: 200, autoclose: 5000});
                                }
                            }
                        });
                    }
                },
                'No': {
                    'class': 'gray',
                    'action': function () {
                        return false;
                    }	// Nothing to do in this case. You can as well omit the action property.
                }
            }
        });
    });

    var featured_frame;

    jQuery(document).on('click', '#upload-featured-image', function (event) {

        var $el = jQuery(this);
        event.preventDefault();
        if (featured_frame) {
            featured_frame.open();
            return;
        }

        // Create the media frame.
        featured_frame = wp.media.frames.avatar = wp.media({
            title: $el.data('choose'),
            button: {
                text: $el.data('update'),
            },
            states: [
                new wp.media.controller.Library({
                    title: $el.data('choose'),
                    filterable: 'image',
                    multiple: true,
                })
            ]
        });

        featured_frame.on('select', function () {
            var selection = featured_frame.state().get('selection');
            selection.map(function (attachment) {
                attachment = attachment.toJSON();
                if (attachment.id) {
                    var data = {'id': attachment.id, 'thumbnail': attachment.url};

                    var load_featured = wp.template('load-featured-thumb');
                    var _thumb = load_featured(data);
                    jQuery(".tg-gallery").html(_thumb);
                }
            });

        });
        // Finally, open the modal.
        featured_frame.open();
    });

    /************************************************
     * Sort Articles
     **********************************************/
    jQuery(document).on('change', '.sort_by, .order_by', function (event) {
        jQuery(".form-sort-articles").submit();
    });

    /*****************************************
     * Add Article Tags
     ***************************************/
    jQuery(document).on('click', '.add-article-tags', function (e) {
        e.preventDefault();
        var _this = jQuery(this);
        var _input = jQuery('.input-feature');
		var _inputval = jQuery('.input-feature').val();
		
		if( _inputval ){
			var load_tags = wp.template('load-article-tags');
			var load_tags = load_tags(_inputval);
			_this.parents('.tg-addallowances').find('.sp-feature-wrap').append(load_tags);
			_input.val('');
		}
        
    });

    /************************************************
     * Delete Article Tags
     **********************************************/
    jQuery(document).on('click', '.delete_article_tags', function (e) {
        e.preventDefault();
        var _this = jQuery(this);
        _this.parents('li').remove();
    });

});