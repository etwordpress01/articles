<?php

if (!defined('FW')) {
    die('Forbidden');
}

/**
 * @hook render articles listing view
 * @type echo
 */
if (!function_exists('_filter_fw_ext_get_render_article_listing')) {

    function _filter_fw_ext_get_render_article_listing() {
        echo fw_ext_get_render_articles_listing();
    }

    add_action('render_article_listing_view', '_filter_fw_ext_get_render_article_listing', 10);
}

/**
 * @hook render articles add view
 * @type echo
 */
if (!function_exists('_filter_fw_ext_get_render_article_add')) {

    function _filter_fw_ext_get_render_article_add() {
        echo fw_ext_get_render_articles_add();
    }

    add_action('render_article_add_view', '_filter_fw_ext_get_render_article_add', 10);
}


/**
 * @hook render articles dashboard view
 * @type echo
 */
if (!function_exists('_filter_fw_ext_get_render_article_dashboard_view')) {

    function _filter_fw_ext_get_render_article_dashboard_view() {
        echo fw_ext_get_render_articles_dashboard_view();
    }

    add_action('render_sp_display_articles', '_filter_fw_ext_get_render_article_dashboard_view', 10);
}


/**
 * @hook render articles dashboard view
 * @type echo
 */
if (!function_exists('_filter_fw_ext_article_view')) {

    function _filter_fw_ext_article_view() {
        echo filter_fw_ext_article_view_v2();
    }

    add_action('render_sp_display_articles_v2', '_filter_fw_ext_article_view', 10);
}

/**
 * @hook render articles edit view
 * @type echo
 */
if (!function_exists('_filter_fw_ext_get_render_article_edit')) {

    function _filter_fw_ext_get_render_article_edit() {
        echo fw_ext_get_render_articles_edit();
    }

    add_action('render_article_edit_view', '_filter_fw_ext_get_render_article_edit', 10);
}

/**
 * @hook process articles
 * @type insert
 */
if (!function_exists('fw_ext_listingo_process_articles')) {

    function fw_ext_listingo_process_articles() {
        global $current_user, $wp_roles, $userdata;
        $return_url = '';
        $type = !empty($_POST['type']) ? esc_attr($_POST['type']) : '';
        $current = !empty($_POST['current']) ? esc_attr($_POST['current']) : '';
        $provider_category = listingo_get_provider_category($current_user->ID);
        remove_all_filters("content_save_pre");

        if (function_exists('listingo_is_demo_site')) {
            listingo_is_demo_site();
        }; //if demo site then prevent

        do_action('listingo_is_action_allow'); //is action allow

        $do_check = check_ajax_referer('listingo_article_nounce', 'listingo_article_nounce', false);
        if ($do_check == false) {
            $json['type'] = 'error';
            $json['message'] = esc_html__('No kiddies please!', 'listingo');
            echo json_encode($json);
            die;
        }

        if (empty($_POST['article_title'])) {
            $json['type'] = 'error';
            $json['message'] = esc_html__('Title field should not be empty.', 'listingo');
            echo json_encode($json);
            die;
        }

        $title = !empty($_POST['article_title']) ? esc_attr($_POST['article_title']) : esc_html__('unnamed', 'listingo');
        $article_detail = force_balance_tags($_POST['article_detail']);

        $attachment_id = !empty($_POST['attachment_id']) ? intval($_POST['attachment_id']) : '';
        $article_tags = !empty($_POST['article_tags']) ? listingo_sanitize_array($_POST['article_tags']) : array();

        $dir_profile_page = '';
        if (function_exists('fw_get_db_settings_option')) {
            $dir_profile_page = fw_get_db_settings_option('dir_profile_page', $default_value = null);
        }

        $profile_page = isset($dir_profile_page[0]) ? $dir_profile_page[0] : '';

        if (function_exists('fw_get_db_settings_option')) {
            $approve_articles = fw_get_db_settings_option('approve_articles', $default_value = null);
        }

        //add/edit article
        if (isset($type) && $type === 'add') {

            if (isset($approve_articles) && $approve_articles === 'need_approval') {
                $status = 'pending';
                $json['message'] = esc_html__('Your article has submitted and will be publish after the review.', 'listingo');
            } else {
                $status = 'publish';
                $json['message'] = esc_html__('Article added successfully.', 'listingo');
            }

            $article_post = array(
                'post_title' => $title,
                'post_status' => $status,
                'post_content' => $article_detail,
                'post_author' => $current_user->ID,
                'post_type' => 'sp_articles',
                'post_date' => current_time('Y-m-d H:i:s')
            );

            $post_id = wp_insert_post($article_post);

            wp_set_post_terms($post_id, $article_tags, 'article_tags');
            if (!empty($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }

            $return_url = Listingo_Profile_Menu::listingo_profile_menu_link($profile_page, 'articles', $current_user->ID, 'true', 'listing');
            $json['return_url'] = htmlspecialchars_decode($return_url);

            update_post_meta($post_id, 'provider_category', $provider_category);

            if (isset($approve_articles) && $approve_articles === 'need_approval') {
                if (class_exists('ListingoProcessEmail')) {
                    $email_helper = new ListingoProcessEmail();
                    $emailData = array();
                    $emailData['article_name'] = $title;
                    $emailData['link'] = get_edit_post_link($post_id);

                    $email_helper->approve_article($emailData);
                }
            }
        } elseif (isset($type) && $type === 'update' && !empty($current)) {
            $post_author = get_post_field('post_author', $current);
            $post_id = $current;
            $status = get_post_status($post_id);

            if (intval($current_user->ID) === intval($post_author)) {
                $article_post = array(
                    'ID' => $current,
                    'post_title' => $title,
                    'post_content' => $article_detail,
                    'post_status' => $status,
                );

                wp_update_post($article_post);

                wp_set_post_terms($post_id, $article_tags, 'article_tags');
                update_post_meta($post_id, 'provider_category', $provider_category);

                //delete prevoius attachment ID
                $pre_attachment_id = get_post_thumbnail_id($post_id);
                if (!empty($pre_attachment_id) && intval($pre_attachment_id) != intval($attachment_id)) {
                    wp_delete_attachment($pre_attachment_id, true);
                }

                if (!empty($attachment_id)) {
                    delete_post_thumbnail($post_id);
                    set_post_thumbnail($post_id, $attachment_id);
                } else if(!empty( $pre_attachment_id )){
					wp_delete_attachment( $pre_attachment_id, true );
				}

                $json['message'] = esc_html__('Article updated successfully.', 'listingo');
            } else {
                $json['type'] = 'error';
                $json['message'] = esc_html__('An error occurred, please try again later', 'listingo');
                echo json_encode($json);
                die;
            }
        } else {
            $json['type'] = 'error';
            $json['message'] = esc_html__('An error occurred, please try again later', 'listingo');
            echo json_encode($json);
            die;
        }


        $json['type'] = 'success';

        echo json_encode($json);
        die;
    }

    add_action('wp_ajax_fw_ext_listingo_process_articles', 'fw_ext_listingo_process_articles');
    add_action('wp_ajax_nopriv_fw_ext_listingo_process_articles', 'fw_ext_listingo_process_articles');
}

/**
 * @hook delete articles
 * @type delete
 */
if (!function_exists('fw_ext_listingo_delete_articles')) {

    function fw_ext_listingo_delete_articles() {
        global $current_user, $wp_roles, $userdata;

        $post_id = intval($_POST['id']);
        $post_author = get_post_field('post_author', $post_id);

        if (function_exists('listingo_is_demo_site')) {
            listingo_is_demo_site();
        }; //if demo site then prevent

        if (!empty($post_id) && intval($current_user->ID) === intval($post_author)) {
            wp_delete_post($post_id);
            $json['type'] = 'success';
            $json['message'] = esc_html__('Article deleted successfully.', 'listingo');
            echo json_encode($json);
            die;
        } else {
            $json['type'] = 'error';
            $json['message'] = esc_html__('An error occurred, please try again later', 'listingo');
            echo json_encode($json);
            die;
        }
    }

    add_action('wp_ajax_fw_ext_listingo_delete_articles', 'fw_ext_listingo_delete_articles');
    add_action('wp_ajax_nopriv_fw_ext_listingo_delete_articles', 'fw_ext_listingo_delete_articles');
}


/**
 * @upload featured image
 * @return {}
 */
if (!function_exists('listingo_featured_image_uploader')) {

    function listingo_featured_image_uploader() {
        global $current_user, $wp_roles, $userdata, $post;
        $user_identity = $current_user->ID;

        if (function_exists('listingo_is_demo_site')) {
            listingo_is_demo_site();
        }; //if demo site then prevent

        $nonce = $_REQUEST['nonce'];
        $type = $_REQUEST['type'];

        if (!wp_verify_nonce($nonce, 'listingo_featured_nounce')) {
            $ajax_response = array(
                'success' => false,
                'reason' => 'Security check failed!',
            );
            echo json_encode($ajax_response);
            die;
        }

        $submitted_file = $_FILES['listingo_uploader'];
        $uploaded_image = wp_handle_upload($submitted_file, array('test_form' => false));

        if (isset($uploaded_image['file'])) {
            $file_name = basename($submitted_file['name']);
            $file_type = wp_check_filetype($uploaded_image['file']);

            // Prepare an array of post data for the attachment.
            $attachment_details = array(
                'guid' => $uploaded_image['url'],
                'post_mime_type' => $file_type['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_name)),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment_details, $uploaded_image['file']);
            $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_image['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            //Image Size
            $image_size = 'thumbnail';
            $thumbnail_url = listingo_get_profile_image_url($attach_data, $image_size,$file_name); //get image url

            $ajax_response = array(
                'success' => true,
                'url' => $thumbnail_url,
                'attachment_id' => $attach_id
            );

            echo json_encode($ajax_response);
            die;
        } else {
            $ajax_response = array('success' => false, 'reason' => 'Image upload failed!');
            echo json_encode($ajax_response);
            die;
        }
    }

    add_action('wp_ajax_listingo_featured_image_uploader', 'listingo_featured_image_uploader');
    add_action('wp_ajax_nopriv_listingo_featured_image_uploader', 'listingo_featured_image_uploader');
}