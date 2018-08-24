<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
    die;
}

class ajax extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function login_process_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (get_setting('admin_login_seccode') == 'Y' AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if (get_setting('ucenter_enabled') == 'Y')
        {
            if (! $user_info = $this->model('ucenter')->login($this->user_info['email'], $_POST['password']))
            {
                $user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
            }
        }
        else
        {
            $user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
        }

        if ($user_info['uid'])
        {
            $this->model('admin')->set_admin_login($user_info['uid']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => $_POST['url'] ? base64_decode($_POST['url']) : get_js_url('/admin/')
            ), 1, null));
        }
        else
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('帐号或密码错误')));
        }
    }

    public function save_settings_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['upload_dir'])
        {
            $_POST['upload_dir'] = rtrim(trim($_POST['upload_dir']), '\/');
        }

        if ($_POST['upload_url'])
        {
            $_POST['upload_url'] = rtrim(trim($_POST['upload_url']), '\/');
        }

        if ($_POST['img_url'])
        {
            $_POST['img_url'] = rtrim(trim($_POST['img_url']), '\/');
        }

        if ($_POST['request_route_custom'])
        {
            $_POST['request_route_custom'] = trim($_POST['request_route_custom']);

            if ($request_routes = explode("\n", $_POST['request_route_custom']))
            {
                foreach ($request_routes as $key => $val)
                {
                    if (! strstr($val, '==='))
                    {
                        continue;
                    }

                    list($m, $n) = explode('===', $val);

                    if (substr($n, 0, 1) != '/' OR substr($m, 0, 1) != '/')
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则 URL 必须以 / 开头')));
                    }

                    if (strstr($m, '/admin') OR strstr($n, '/admin'))
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则不允许设置 /admin 路由')));
                    }
                }
            }
        }

        if ($_POST['censoruser'])
        {
            $_POST['censoruser'] = trim($_POST['censoruser']);
        }

        if ($_POST['report_reason'])
        {
            $_POST['report_reason'] = trim($_POST['report_reason']);
        }

        if ($_POST['sensitive_words'])
        {
            $_POST['sensitive_words'] = trim($_POST['sensitive_words']);
        }

        $curl_require_setting = array('qq_login_enabled', 'sina_weibo_enabled');

        if (array_intersect(array_keys($_POST), $curl_require_setting))
        {
            foreach ($curl_require_setting AS $key)
            {
                if ($_POST[$key] == 'Y' AND !function_exists('curl_init'))
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微博登录、QQ 登录等功能须服务器支持 CURL')));
                }
            }
        }

        if ($_POST['weixin_mp_token'])
        {
            $_POST['weixin_mp_token'] = trim($_POST['weixin_mp_token']);
        }

        if ($_POST['weixin_encoding_aes_key'])
        {
            $_POST['weixin_encoding_aes_key'] = trim($_POST['weixin_encoding_aes_key']);

            if (strlen($_POST['weixin_encoding_aes_key']) != 43)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信公众平台接口 EncodingAESKey 应为 43 位')));
            }
        }

        if ($_POST['set_notification_settings'])
        {
            if ($notify_actions = $this->model('notify')->notify_action_details)
            {
                $notification_setting = array();

                foreach ($notify_actions as $key => $val)
                {
                    if (! isset($_POST['new_user_notification_setting'][$key]) AND $val['user_setting'])
                    {
                        $notification_setting[] = intval($key);
                    }
                }
            }

            $_POST['new_user_notification_setting'] = $notification_setting;
        }

        if ($_POST['set_email_settings'])
        {
            $email_settings = array(
                'FOLLOW_ME' => 'N',
                'QUESTION_INVITE' => 'N',
                'NEW_ANSWER' => 'N',
                'NEW_MESSAGE' => 'N',
                'QUESTION_MOD' => 'N',
            );

            if ($_POST['new_user_email_setting'])
            {
                foreach ($_POST['new_user_email_setting'] AS $key => $val)
                {
                    unset($email_settings[$val]);
                }
            }

            $_POST['new_user_email_setting'] = $email_settings;
        }

        if ($_POST['slave_mail_config']['server'])
        {
            $_POST['slave_mail_config']['charset'] = $_POST['mail_config']['charset'];
        }

        if ($_POST['ucenter_path'])
        {
            $_POST['ucenter_path'] = rtrim(trim($_POST['ucenter_path']), '\/');
        }

        if ($_POST['invitation_code_remark'])
        {
            $_POST['invitation_code_remark'] = htmlspecialchars($_POST['invitation_code_remark']);
        }
        $this->model('setting')->set_vars($_POST);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
    }

    public function approval_manage_action()
    {
        if (!in_array($_POST['batch_type'], array('approval', 'decline')))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        if ($_POST['approval_id'])
        {
            $_POST['approval_ids'] = array($_POST['approval_id']);
        }

        if (!$_POST['approval_ids'] OR !is_array($_POST['approval_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['type'])
        {
            case 'weibo_msg':
                if (get_setting('weibo_msg_enabled') != 'question')
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导入微博消息至问题未启用')));
                }

                switch ($_POST['batch_type'])
                {
                    case 'approval':
                        $published_user = get_setting('weibo_msg_published_user');

                        if (!$published_user['uid'])
                        {
                            H::ajax_json_output(AWS_APP::RSM(AWS_APP::lang()->_t('微博发布用户不存在')));
                        }

                        foreach ($_POST['approval_ids'] AS $approval_id)
                        {
                            $this->model('openid_weibo_weibo')->save_msg_info_to_question($approval_id, $published_user['uid']);
                        }

                        break;

                    case 'decline':
                        foreach ($_POST['approval_ids'] AS $approval_id)
                        {
                            $this->model('openid_weibo_weibo')->del_msg_by_id($approval_id);
                        }

                        break;
                }

                break;

            case 'received_email':
                $receiving_email_global_config = get_setting('receiving_email_global_config');

                if ($receiving_email_global_config['enabled'] != 'question')
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导入邮件至问题未启用')));
                }

                switch ($_POST['batch_type'])
                {
                    case 'approval':
                        $receiving_email_global_config = get_setting('receiving_email_global_config');

                        if (!$receiving_email_global_config['publish_user']['uid'])
                        {
                            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邮件发布用户不存在')));
                        }

                        foreach ($_POST['approval_ids'] AS $approval_id)
                        {
                            $this->model('edm')->save_received_email_to_question($approval_id, $receiving_email_global_config['publish_user']['uid']);
                        }

                        break;

                    case 'decline':
                        foreach ($_POST['approval_ids'] AS $approval_id)
                        {
                            $this->model('edm')->remove_received_email($approval_id);
                        }

                        break;
                }

                break;

            default:
                $func = $_POST['batch_type'] . '_publish';

                foreach ($_POST['approval_ids'] AS $approval_id)
                {
                    $this->model('publish')->$func($approval_id);
                }

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function article_manage_action()
    {
        if (!$_POST['article_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章进行操作')));
        }

        switch ($_POST['action'])
        {
            case 'del':
                foreach ($_POST['article_ids'] AS $article_id)
                {
                    $this->model('article')->remove_article($article_id);
                }

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));
            break;
        }
    }

    public function save_category_sort_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (is_array($_POST['category']))
        {
            foreach ($_POST['category'] as $key => $val)
            {
                $this->model('category')->set_category_sort($key, $val['sort']);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类排序已自动保存')));
    }

    public function save_category_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['category_id'] AND $_POST['parent_id'] AND $category_list = $this->model('system')->fetch_category('question', $_POST['category_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统允许最多二级分类, 当前分类下有子分类, 不能移动到其它分类')));
        }

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入分类名称')));
        }

        if ($_POST['url_token'])
        {
            if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类别名只允许输入英文或数字')));
            }

            if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($_POST['category_id'] != $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类别名不可以全为数字')));
            }

            if ($this->model('category')->check_url_token($_POST['url_token'], $_POST['category_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类别名已经被占用请更换一个')));
            }
        }

        if ($_POST['category_id'])
        {
            $category_id = intval($_POST['category_id']);
        }
        else
        {
            $category_id = $this->model('category')->add_category('question', $_POST['title'], $_POST['parent_id']);
        }

        $category = $this->model('system')->get_category_info($category_id);

        if ($category['id'] == $_POST['parent_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能设置当前分类为父级分类')));
        }

        $this->model('category')->update_category_info($category_id, $_POST['title'], $_POST['parent_id'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/category/list/')
        ), 1, null));
    }

    public function remove_category_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (intval($_POST['category_id']) == 1)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('默认分类不可删除')));
        }

        if ($this->model('category')->contents_exists($_POST['category_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类下存在内容, 请先批量移动问题到其它分类, 再删除当前分类')));
        }

        $this->model('category')->delete_category('question', $_POST['category_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function move_category_contents_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$_POST['from_id'] OR !$_POST['target_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先选择指定分类和目标分类')));
        }

        if ($_POST['target_id'] == $_POST['from_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定分类不能与目标分类相同')));
        }

        $this->model('category')->move_contents($_POST['from_id'], $_POST['target_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function edm_add_group_action()
    {
        @set_time_limit(0);

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写用户群名称')));
        }

        $usergroup_id = $this->model('edm')->add_group($_POST['title']);

        switch ($_POST['import_type'])
        {
            case 'text':
                if ($email_list = explode("\n", str_replace(array("\r", "\t"), "\n", $_POST['email'])))
                {
                    foreach ($email_list AS $key => $email)
                    {
                        $this->model('edm')->add_user_data($usergroup_id, $email);
                    }
                }
            break;

            case 'system_group':
                if ($_POST['user_groups'])
                {
                    foreach ($_POST['user_groups'] AS $key => $val)
                    {
                        $this->model('edm')->import_system_email_by_user_group($usergroup_id, $val);
                    }
                }
            break;

            case 'reputation_group':
                if ($_POST['user_groups'])
                {
                    foreach ($_POST['user_groups'] AS $key => $val)
                    {
                        $this->model('edm')->import_system_email_by_reputation_group($usergroup_id, $val);
                    }
                }
            break;

            case 'last_active':
                if ($_POST['last_active'])
                {
                    $this->model('edm')->import_system_email_by_last_active($usergroup_id, $_POST['last_active']);
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function edm_add_task_action()
    {
        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写任务名称')));
        }

        if (trim($_POST['subject']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写邮件标题')));
        }

        if (intval($_POST['usergroup_id']) == 0)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户群组')));
        }

        if (trim($_POST['from_name']) == '')
        {
            $_POST['from_name'] = get_setting('site_name');
        }

        $task_id = $this->model('edm')->add_task($_POST['title'], $_POST['subject'], $_POST['message'], $_POST['from_name']);

        $this->model('edm')->import_group_data_to_task($task_id, $_POST['usergroup_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('任务建立完成')));

    }

    public function save_feature_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题标题不能为空')));
        }

        if ($_GET['feature_id'])
        {
            $feature = $this->model('feature')->get_feature_by_id($_GET['feature_id']);

            $feature_id = $feature['id'];
        }

        if ($_POST['url_token'])
        {
            if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题别名只允许输入英文或数字')));
            }

            if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($_GET['feature_id'] != $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题别名不可以全为数字')));
            }

            if ($this->model('feature')->check_url_token($_POST['url_token'], $_GET['feature_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题别名已经被占用请更换一个')));
            }
        }

        if (!$_GET['feature_id'])
        {
            $feature_id = $this->model('feature')->add_feature($_POST['title']);
        }

        if ($_POST['topics'])
        {
            if ($topics = explode("\n", $_POST['topics']))
            {
                $this->model('feature')->empty_topics($feature_id);
            }

            foreach ($topics AS $key => $topic_title)
            {
                if ($topic_info = $this->model('topic')->get_topic_by_title(trim($topic_title)))
                {
                    $this->model('feature')->add_topic($feature_id, $topic_info['topic_id']);
                }
            }
        }

        $update_data = array(
            'title' => $_POST['title'],
            'description' => htmlspecialchars($_POST['description']),
            'css' => htmlspecialchars($_POST['css']),
            'url_token' => $_POST['url_token'],
            'seo_title' => htmlspecialchars($_POST['seo_title'])
        );

        if ($_FILES['icon']['name'])
        {
            AWS_APP::upload()->initialize(array(
                'allowed_types' => 'jpg,jpeg,png,gif',
                'upload_path' => get_setting('upload_dir') . '/feature',
                'is_image' => TRUE
            ))->do_upload('icon');


            if (AWS_APP::upload()->get_error())
            {
                switch (AWS_APP::upload()->get_error())
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                    break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));
                    break;
                }
            }

            if (! $upload_data = AWS_APP::upload()->data())
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
            }

            foreach (AWS_APP::config()->get('image')->feature_thumbnail as $key => $val)
            {
                $thumb_file[$key] = $upload_data['file_path'] . $feature_id . "_" . $val['w'] . "_" . $val['h'] . '.jpg';

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $upload_data['full_path'],
                    'new_image' => $thumb_file[$key],
                    'width' => $val['w'],
                    'height' => $val['h']
                ))->resize();
            }

            unlink($upload_data['full_path']);

            $update_data['icon'] = basename($thumb_file['min']);
        }

        $this->model('feature')->update_feature($feature_id, $update_data);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/feature/list/')
		), 1, null));
    }

    public function remove_feature_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('feature')->delete_feature($_POST['feature_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_feature_status_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['feature_ids'])
        {
            foreach ($_POST['feature_ids'] AS $feature_id => $val)
            {
                $this->model('feature')->update_feature_enabled($feature_id, $_POST['enabled_status'][$feature_id]);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
    }

    public function save_nav_menu_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['nav_sort'])
        {
            if ($menu_ids = explode(',', $_POST['nav_sort']))
            {
                foreach($menu_ids as $key => $val)
                {
                    $this->model('menu')->update_nav_menu($val, array(
                        'sort' => $key
                    ));
                }
            }
        }

        if ($_POST['nav_menu'])
        {
            foreach($_POST['nav_menu'] as $key => $val)
            {
                $this->model('menu')->update_nav_menu($key, $val);
            }
        }

        $settings_var['category_display_mode'] = $_POST['category_display_mode'];
        $settings_var['nav_menu_show_child'] = isset($_POST['nav_menu_show_child']) ? 'Y' : 'N';

        $this->model('setting')->set_vars($settings_var);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导航菜单保存成功')));
    }

    public function add_nav_menu_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        switch ($_POST['type'])
        {
            case 'category' :
                $type_id = intval($_POST['type_id']);
                $category = $this->model('system')->get_category_info($type_id);
                $title = $category['title'];
            break;

            case 'custom' :
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $link = trim($_POST['link']);
                $type_id = 0;
            break;
        }

        if (!$title)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入导航标题')));
        }

        $this->model('menu')->add_nav_menu($title, $description, $_POST['type'], $type_id, $link);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_nav_menu_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('menu')->remove_nav_menu($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function nav_menu_upload_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        AWS_APP::upload()->initialize(array(
            'allowed_types' => 'jpg,jpeg,png,gif',
            'upload_path' => get_setting('upload_dir') . '/nav_menu',
            'is_image' => TRUE,
            'file_name' => intval($_GET['id']) . '.jpg',
            'encrypt_name' => FALSE
        ))->do_upload('aws_upload_file');

        if (AWS_APP::upload()->get_error())
        {
            switch (AWS_APP::upload()->get_error())
            {
                default:
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                break;

                case 'upload_invalid_filetype':
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));
                break;
            }
        }

        if (! $upload_data = AWS_APP::upload()->data())
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
        }

        if ($upload_data['is_image'] == 1)
        {
            AWS_APP::image()->initialize(array(
                'quality' => 90,
                'source_image' => $upload_data['full_path'],
                'new_image' => $upload_data['full_path'],
                'width' => 50,
                'height' => 50
            ))->resize();
        }

        $this->model('menu')->update_nav_menu($_GET['id'], array('icon' => basename($upload_data['full_path'])));

        echo htmlspecialchars(json_encode(array(
            'success' => true,
            'thumb' => get_setting('upload_url') . '/nav_menu/' . basename($upload_data['full_path'])
        )), ENT_NOQUOTES);
    }

    public function add_page_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$_POST['url_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入页面 URL')));
        }

        if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
        }

        if ($this->model('page')->get_page_by_url_token($_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的页面 URL')));
        }

        $this->model('page')->add_page($_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/page/')
        ), 1, null));
    }

    public function remove_page_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('page')->remove_page($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function edit_page_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$page_info = $this->model('page')->get_page_by_url_id($_POST['page_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面不存在')));
        }

        if (!$_POST['url_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入页面 URL')));
        }

        if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
        }

        if ($_page_info = $this->model('page')->get_page_by_url_token($_POST['url_token']))
        {
            if ($_page_info['id'] != $_page_info['id'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的页面 URL')));
            }
        }

        $this->model('page')->update_page($_POST['page_id'], $_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/page/')
        ), 1, null));
    }

    public function save_page_status_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['page_ids'])
        {
            foreach ($_POST['page_ids'] AS $page_id => $val)
            {
                $this->model('page')->update_page_enabled($page_id, $_POST['enabled_status'][$page_id]);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('启用状态已自动保存')));
    }

    public function question_manage_action()
    {
        if (!$_POST['question_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择问题进行操作')));
        }

        switch ($_POST['action'])
        {
            case 'remove':
                foreach ($_POST['question_ids'] AS $question_id)
                {
                    $this->model('question')->remove_question($question_id);
                }

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));
            break;
        }
    }

    public function report_manage_action()
    {
        if (! $_POST['report_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择内容进行操作')));
        }

        if (! $_POST['action_type'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择操作类型')));
        }

        if ($_POST['action_type'] == 'delete')
        {
            foreach ($_POST['report_ids'] as $val)
            {
                $this->model('question')->delete_report($val);
            }
        }
        else if ($_POST['action_type'] == 'handle')
        {
            foreach ($_POST['report_ids'] as $val)
            {
                $this->model('question')->update_report($val, array(
                    'status' => 1
                ));
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function lock_topic_action()
    {
        $this->model('topic')->lock_topic_by_ids($_POST['topic_id'], $_POST['status']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_topic_action()
    {
        if ($_POST['topic_id'])
        {
            if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('话题不存在')));
            }

            if ($topic_info['topic_title'] != $_POST['topic_title'] AND $this->model('topic')->get_topic_by_title($_POST['topic_title']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('同名话题已经存在')));
            }

            $this->model('topic')->update_topic($this->user_id, $topic_info['topic_id'], $_POST['topic_title'], $_POST['topic_description']);

            $this->model('topic')->lock_topic_by_ids($topic_info['topic_id'], $_POST['topic_lock']);

            $topic_id = $topic_info['topic_id'];
        }
        else
        {
            if ($this->model('topic')->get_topic_by_title($_POST['topic_title']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('同名话题已经存在')));
            }

            $topic_id = $this->model('topic')->save_topic($_POST['topic_title'], $this->user_id, true, $_POST['topic_description']);
        }

        $this->model('topic')->set_is_parent($topic_id, $_POST['is_parent']);

        if ($_POST['is_parent'] == 0)
        {
            $this->model('topic')->set_parent_id($topic_id, $_POST['parent_id']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/topic/list/')
        ), 1, null));
    }

    public function topic_manage_action()
    {
        if (!$_POST['topic_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择话题进行操作')));
        }

        switch($_POST['action'])
        {
            case 'remove' :
                $this->model('topic')->remove_topic_by_ids($_POST['topic_ids']);

                break;

            case 'lock' :
                $this->model('topic')->lock_topic_by_ids($_POST['topic_ids'], 1);

                break;

            case 'set_parent_id':
                $topic_list = $this->model('topic')->get_topics_by_ids($_POST['topic_ids']);

                foreach ($topic_list AS $topic_info)
                {
                    if ($topic_info['is_parent'] == 0)
                    {
                        $to_update_topic_ids[] = $topic_info['topic_id'];
                    }
                }

                if ($to_update_topic_ids)
                {
                    $this->model('topic')->set_parent_id($to_update_topic_ids, $_POST['parent_id']);
                }

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }




    public function save_user_group_action()
    {   


        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($group_data = $_POST['group'])
        {   



            foreach ($group_data as $key => $val)
            {
                if (!$val['group_name'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户组名称')));
                }



                if ($val['reputation_factor'])
                {
                    if (!is_digits($val['reputation_factor']) || floatval($val['reputation_factor']) < 0)
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('威望系数必须为大于或等于 0')));
                    }

                    if (!is_digits($val['reputation_lower']) || floatval($val['reputation_lower']) < 0 || !is_digits($val['reputation_higer']) || floatval($val['reputation_higer']) < 0)
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('威望介于值必须为大于或等于 0')));
                    }

                    $val['reputation_factor'] = floatval($val['reputation_factor']);
                }

                if(!is_digits($val['reputation_praise'])|| $val['reputation_praise'] < 0 || $val['reputation_praise'] > 100)
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('赞踩能量系数只能录入0-100')));
                }

                $this->model('account')->update_user_group_data($key, $val);
            }
        }

        if ($group_new = $_POST['group_new'])
        {
            foreach ($group_new['group_name'] as $key => $val)
            {
                if (trim($group_new['group_name'][$key]))
                {
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 1, $group_new['reputation_lower'][$key], $group_new['reputation_higer'][$key], $group_new['reputation_factor'][$key]);
                }
            }
        }

        if ($group_ids = $_POST['group_ids'])
        {
            foreach ($group_ids as $key => $id)
            {
                $group_info = $this->model('account')->get_user_group_by_id($id);

                if ($group_info['custom'] == 1 OR $group_info['type'] == 1)
                {
                    $this->model('account')->delete_user_group_by_id($id);
                }
                else
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统用户组不可删除')));
                }
            }
        }

        AWS_APP::cache()->cleanGroup('users_group');

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_custom_user_group_action()
    {   


        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($group_data = $_POST['group'])
        {
            foreach ($group_data as $key => $val)
            {
                if (!$val['group_name'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户组名称')));
                }

                $this->model('account')->update_user_group_data($key, $val);
            }
        }

        if ($group_new = $_POST['group_new'])
        {
            foreach ($group_new['group_name'] as $key => $val)
            {
                if (trim($group_new['group_name'][$key]))
                {
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 0);
                }
            }
        }

        if ($group_ids = $_POST['group_ids'])
        {
            foreach ($group_ids as $key => $id)
            {
                $group_info = $this->model('account')->get_user_group_by_id($id);

                if ($group_info['custom'] == 1)
                {
                    $this->model('account')->delete_user_group_by_id($id);
                }
                else
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统用户组不可删除')));
                }
            }
        }

        AWS_APP::cache()->cleanGroup('users_group');

        if ($group_new OR $group_ids)
        {
            $rsm = array(
                'url' => get_js_url('/admin/user/group_list/r-' . rand(1, 999) . '#custom')
            );
        }

        H::ajax_json_output(AWS_APP::RSM($rsm, 1, null));
    }

    public function edit_user_group_permission_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $permission_array = array(
            'is_administortar',
            'is_moderator',
            'publish_question',
            'publish_approval',
            'publish_approval_time',
            'edit_question',
            'edit_topic',
            'manage_topic',
            'create_topic',
            'redirect_question',
            'upload_attach',
            'publish_url',
            'human_valid',
            'question_valid_hour',
            'answer_valid_hour',
            'visit_site',
            'visit_explore',
            'search_avail',
            'visit_question',
            'visit_topic',
            'visit_feature',
            'visit_people',
            'visit_chapter',
            'answer_show',
            'function_interval',
            'publish_article',
            'edit_article',
            'edit_question_topic',
            'publish_comment'
        );

        if (check_extension_package('ticket'))
        {
            $permission_array[] = 'is_service';

            $permission_array[] = 'publish_ticket';
        }

        if (check_extension_package('project'))
        {
            $permission_array[] = 'publish_project';
        }

        $group_setting = array();

        foreach ($permission_array as $permission)
        {
            if ($_POST[$permission])
            {
                $group_setting[$permission] = $_POST[$permission];
            }
        }

        $this->model('account')->update_user_group_data($_POST['group_id'], array(
            'permission' => serialize($group_setting)
        ));

        AWS_APP::cache()->cleanGroup('users_group');

        H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('用户组权限已更新')));
    }

    public function save_user_action()
    {
        if ($_POST['uid'])
        {
            if (!$user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户不存在')));
            }

            if ($user_info['group_id'] == 1 AND !$this->user_info['permission']['is_administortar'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限编辑管理员账号')));
            }

            if ($_POST['user_name'] != $user_info['user_name'] AND $this->model('account')->get_user_info_by_username($_POST['user_name']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已存在')));
            }

            if ($_POST['email'] != $user_info['email'] AND $this->model('account')->get_user_info_by_username($_POST['email']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('E-mail 已存在')));
            }
            if(!is_numeric($_POST['reputation_extend'])){
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('威望请保存数字')));
            }
            if(!is_numeric($_POST['praise_weight'])){
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('能量请保存数字')));
            }

            if ($_FILES['user_avatar']['name'])
            {
                AWS_APP::upload()->initialize(array(
                    'allowed_types' => 'jpg,jpeg,png,gif',
                    'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($user_info['uid'], '', 1),
                    'is_image' => TRUE,
                    'max_size' => get_setting('upload_avatar_size_limit'),
                    'file_name' => $this->model('account')->get_avatar($user_info['uid'], '', 2),
                    'encrypt_name' => FALSE
                ))->do_upload('user_avatar');

                if (AWS_APP::upload()->get_error())
                {
                    switch (AWS_APP::upload()->get_error())
                    {
                        default:
                            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                        break;

                        case 'upload_invalid_filetype':
                            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));
                        break;

                        case 'upload_invalid_filesize':
                            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));
                        break;
                    }
                }

                if (! $upload_data = AWS_APP::upload()->data())
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
                }

                if ($upload_data['is_image'] == 1)
                {
                    foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
                    {
                        $thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($user_info['uid'], $key, 2);

                        AWS_APP::image()->initialize(array(
                            'quality' => 90,
                            'source_image' => $upload_data['full_path'],
                            'new_image' => $thumb_file[$key],
                            'width' => $val['w'],
                            'height' => $val['h']
                        ))->resize();
                    }
                }

                $update_data['avatar_file'] = $this->model('account')->get_avatar($user_info['uid'], null, 1) . basename($thumb_file['min']);
            }

            if ($_POST['email'])
            {
                $update_data['email'] = htmlspecialchars($_POST['email']);
            }

            $update_data['invitation_available'] = intval($_POST['invitation_available']);

            $verify_apply = $this->model('verify')->fetch_apply($user_info['uid']);

            if ($verify_apply)
            {
                $update_data['verified'] = $_POST['verified'];

                if (!$update_data['verified'])
                {
                    $this->model('verify')->decline_verify($user_info['uid']);
                }
                else if ($update_data['verified'] != $verify_apply['type'])
                {
                    $this->model('verify')->update_apply($user_info['uid'], null, null, null, null, $update_data['verified']);
                }
            }
            else if ($_POST['verified'])
            {
                $verified_id = $this->model('verify')->add_apply($user_info['uid'], null, null, $_POST['verified']);

                $this->model('verify')->approval_verify($verified_id);
            }

            $update_data['valid_email'] = intval($_POST['valid_email']);
            $update_data['forbidden'] = intval($_POST['forbidden']);

            $update_data['group_id'] = intval($_POST['group_id']);

            if ($update_data['group_id'] == 1 AND !$this->user_info['permission']['is_administortar'])
            {
                unset($update_data['group_id']);
            }

            $update_data['province'] = htmlspecialchars($_POST['province']);
            $update_data['city'] = htmlspecialchars($_POST['city']);

            $update_data['job_id'] = intval($_POST['job_id']);
            $update_data['mobile'] = htmlspecialchars($_POST['mobile']);

            $update_data['sex'] = intval($_POST['sex']);
            //威望拓展字段保存修改
            $_POST['reputation_extend'] = ($_POST['reputation_extend'] == '') ? 0 : $_POST['reputation_extend'];
            $user_reputation = $this->model('account')->fetch_one('users','reputation','uid ='.$_POST['uid']);
            $user_reputation_extend = $this->model('account')->fetch_one('users','reputation_extend','uid ='.$_POST['uid']);
            $update_data['reputation_extend'] = intval($_POST['reputation_extend']-$user_reputation + $user_reputation_extend);
            $update_data['reputation'] = intval($_POST['reputation_extend']);
            //能量拓展字段保存更新
            $_POST['praise_weight'] = ($_POST['praise_weight'] == '') ? 0 : $_POST['praise_weight'];
            $user_praise_no_weight = $this->model('account')->fetch_one('users','praise_no_weight','uid ='.$_POST['uid']);
            $user_weight_balance = $this->model('account')->fetch_one('users','weight_balance','uid ='.$_POST['uid']);
            $user_last_praise_weight = $this->model('account')->fetch_one('users','praise_weight','uid ='.$_POST['uid']);
            $update_data['praise_weight'] = ($_POST['praise_weight']-$user_praise_no_weight+$user_last_praise_weight);
            $update_data['praise_no_weight'] = $_POST['praise_weight'];
            $update_data['weight_balance'] = ($_POST['praise_weight'] - $user_praise_no_weight) + $user_weight_balance;
            $update_data['whether_decrease_weight'] = intval($_POST['whether_decrease_weight']);
            $this->model('account')->update_users_fields($update_data, $user_info['uid']);

            if ($_POST['delete_avatar'])
            {
                $this->model('account')->delete_avatar($user_info['uid']);
            }

            if ($_POST['password'])
            {
                $this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $user_info['uid'], fetch_salt(4));
            }

            $this->model('account')->update_users_attrib_fields(array(
                'signature' => htmlspecialchars($_POST['signature']),
                'qq' => htmlspecialchars($_POST['qq']),
                'homepage' => htmlspecialchars($_POST['homepage'])
            ), $user_info['uid']);

            if ($_POST['user_name'] != $user_info['user_name'])
            {
                $this->model('account')->update_user_name($_POST['user_name'], $user_info['uid']);
            }

            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户资料更新成功')));
        }
        else
        {
            $_POST['user_name'] = trim($_POST['user_name']);

            $_POST['email'] = trim($_POST['email']);

            $_POST['password'] = trim($_POST['password']);

            $_POST['group_id'] = intval($_POST['group_id']);

            if (!$_POST['user_name'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
            }

            if ($this->model('account')->check_username($_POST['user_name']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
            }

            if ($this->model('account')->check_email($_POST['email']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
            }

            if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
            }

            $uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);

            $this->model('active')->set_user_email_valid_by_uid($uid);

            $this->model('active')->active_user_by_uid($uid);

            if ($_POST['group_id'] == 1 AND !$this->user_info['permission']['is_administortar'])
            {
                $_POST['group_id'] = 4;
            }

            if ($_POST['group_id'] != 4)
            {
                $this->model('account')->update('users', array(
                    'group_id' => $_POST['group_id'],
                ), 'uid = ' . $uid);
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/list/')
            ), 1, null));
        }
    }

    public function forbidden_user_action()
    {
        $this->model('account')->forbidden_user_by_uid($_POST['uid'], $_POST['status'], $this->user_id);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
    public function clean_user_reputation_action()
    {
        $this->model('account')->clean_user_reputation_by_uid($_POST['uid']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function send_invites_action()
    {
        if ($_POST['email_list'])
        {
            if ($emails = explode("\n", str_replace("\r", "\n", $_POST['email_list'])))
            {
                foreach($emails as $key => $email)
                {
                    if (!H::valid_email($email))
                    {
                        continue;
                    }

                    $email_list[] = strtolower($email);
                }
            }
        }
        else
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入邮箱地址')));
        }

        $this->model('invitation')->send_batch_invitations(array_unique($email_list), $this->user_id, $this->user_info['user_name']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邀请已发送')));
    }

    public function remove_job_action()
    {
        $this->model('work')->remove_job($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function add_job_action()
    {
        if (!$_POST['jobs'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入职位名称')));
        }

        $job_list = array();

        if ($job_list_tmp = explode("\n", $_POST['jobs']))
        {
            foreach($job_list_tmp as $key => $job)
            {
                $job_name = trim(strtolower($job));

                if ($job_name)
                {
                    $job_list[] = $job_name;
                }
            }
        }
        else
        {
            $job_list[] = $_POST['jobs'];
        }

        foreach($job_list as $key => $val)
        {
            $this->model('work')->add_job($val);
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_job_action()
    {
        if ($_POST['job_list'])
        {
            foreach($_POST['job_list'] as $key => $val)
            {
                $this->model('work')->update_job($key, array(
                    'job_name' => $val,
                ));
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('职位列表更新成功')));
    }

    public function integral_process_action()
    {
        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户进行操作')));
        }

        if (!$_POST['note'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写理由')));
        }

        $this->model('integral')->process($_POST['uid'], 'AWARD', $_POST['integral'], $_POST['note']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/integral_log/uid-' . $_POST['uid'])
        ), 1, null));
    }

    public function register_approval_manage_action()
    {
        if (!is_array($_POST['approval_uids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'approval':
                foreach ($_POST['approval_uids'] AS $approval_uid)
                {
                    $this->model('active')->active_user_by_uid($approval_uid);;
                }
            break;

            case 'decline':
                foreach ($_POST['approval_uids'] AS $approval_uid)
                {
                    if ($user_info = $this->model('account')->get_user_info_by_uid($approval_uid))
                    {
                        if ($user_info['email'])
                        {
                            $this->model('email')->action_email('REGISTER_DECLINE', $user_info['email'], null, array(
                                'message' => htmlspecialchars($_POST['reason'])
                            ));
                        }

                        $this->model('system')->remove_user_by_uid($approval_uid, true);
                    }
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_verify_approval_action()
    {
        if ($_POST['uid'])
        {
            $this->model('verify')->update_apply($_POST['uid'], $_POST['name'], $_POST['reason'], array(
                'id_code' => htmlspecialchars($_POST['id_code']),
                'contact' => htmlspecialchars($_POST['contact'])
            ));
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/verify_approval_list/')
        ), 1, null));
    }

    public function verify_approval_manage_action()
    {
        if (!is_array($_POST['approval_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'approval':
            case 'decline':
                $func = $_POST['batch_type'] . '_verify';

                foreach ($_POST['approval_ids'] AS $approval_id)
                {
                    $this->model('verify')->$func($approval_id);
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_user_action()
    {
        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        @set_time_limit(0);

        $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

        if (!$user_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('所选用户不存在')));
        }
        else
        {
            if ($user_info['group_id'] == 1)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不允许删除管理员用户组用户')));
            }

            $this->model('system')->remove_user_by_uid($_POST['uid'], $_POST['remove_user_data']);
            $this->model('column')->remove_user_by_uid($_POST['uid']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/list/')
        ), 1, null));
    }

    public function remove_users_action()
    {
        if (!is_array($_POST['uids']) OR !$_POST['uids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的用户')));
        }

        @set_time_limit(0);

        foreach ($_POST['uids'] AS $uid)
        {
            $user_info = $this->model('account')->get_user_info_by_uid($uid);

            if ($user_info)
            {
                if ($user_info['group_id'] == 1)
                {
                    continue;
                }

                $this->model('system')->remove_user_by_uid($uid, true);
            }
            else
            {
                continue;
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function topic_statistic_action()
    {
    	$topic_statistic = array();

    	if ($topic_list = $this->model('topic')->get_hot_topics(null, $_GET['limit'], $_GET['tag']))
    	{
	    	foreach ($topic_list AS $key => $val)
	    	{
		    	$topic_statistic[] = array(
		    		'title' => $val['topic_title'],
		    		'week' => $val['discuss_count_last_week'],
		    		'month' => $val['discuss_count_last_month'],
		    		'all' => $val['discuss_count']
		    	);
	    	}
    	}

	    echo json_encode($topic_statistic);
    }

    public function statistic_action()
    {
        if (!$start_time = strtotime($_GET['start_date'] . ' 00:00:00'))
        {
            $start_time = strtotime('-12 months');
        }

        if (!$end_time = strtotime($_GET['end_date'] . ' 23:59:59'))
        {
            $end_time = time();
        }

        if ($_GET['tag'])
        {
            $statistic_tag = explode(',', $_GET['tag']);
        }

        if (!$month_list = get_month_list($start_time, $end_time, 'y'))
        {
            die;
        }

        foreach ($month_list AS $key => $val)
        {
            $labels[] = $val['year'] . '-' . $val['month'];
            $data_template[] = 0;
        }

        if (!$statistic_tag)
        {
            die;
        }

        foreach ($statistic_tag AS $key => $val)
        {
            switch ($val)
            {
                case 'new_answer':  // 新增答案
                case 'new_question':    // 新增问题
                case 'new_user':    // 新注册用户
                case 'user_valid':  // 新激活用户
                case 'new_topic':   // 新增话题
                case 'new_answer_vote': // 新增答案投票
                case 'new_answer_thanks': // 新增答案感谢
                case 'new_favorite_item': // 新增收藏条目
                case 'new_question_thanks': // 新增问题感谢
                case 'new_question_redirect': // 新增问题重定向
                    $statistic[] = $this->model('system')->statistic($val, $start_time, $end_time);
                break;
            }
        }

        foreach($statistic AS $key => $val)
        {
            $statistic_data = $data_template;

            foreach ($val AS $k => $v)
            {
                $data_key = array_search($v['date'], $labels);

                $statistic_data[$data_key] = $v['count'];
            }

            $data[] = $statistic_data;

        }

        echo json_encode(array(
            'labels' => $labels,
            'data' => $data
        ));
    }

    public function weibo_batch_action()
    {
        if (!$_POST['action'] OR !isset($_POST['uid']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        if (in_array($_POST['action'], array('add_service_user', 'del_service_user')))
        {
            $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

            if (!$user_info)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('所选用户不存在')));
            }

            $service_info = $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($user_info['uid']);

            $tmp_service_account = AWS_APP::cache()->get('tmp_service_account');
        }

        switch ($_POST['action'])
        {
            case 'add_service_user':
                if ($service_info)
                {
                    if (isset($service_info['last_msg_id']))
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已是回答用户')));
                    }

                    $this->model('openid_weibo_weibo')->update_service_account($user_info['uid'], 'add');

                    $rsm = array('staus' => 'bound');
                }
                else
                {
                    if ($tmp_service_account[$user_info['uid']])
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已是回答用户')));
                    }

                    $tmp_service_account[$user_info['uid']] = array(
                                                                    'uid' => $user_info['uid'],
                                                                    'user_name' => $user_info['user_name'],
                                                                    'url_token' => $user_info['url_token']
                                                                );

                    natsort($tmp_service_account);

                    AWS_APP::cache()->set('tmp_service_account', $tmp_service_account, 86400);

                    $rsm = array('staus' => 'unbound');
                }

                break;

            case 'del_service_user':
                if ($service_info)
                {
                    if (!isset($service_info['last_msg_id']))
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户不是回答用户')));
                    }

                    $this->model('openid_weibo_weibo')->update_service_account($user_info['uid'], 'del');
                }
                else
                {
                    if (!$tmp_service_account[$user_info['uid']])
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户不是回答用户')));
                    }

                    unset($tmp_service_account[$user_info['uid']]);

                    AWS_APP::cache()->set('tmp_service_account', $tmp_service_account, 86400);
                }

                break;

            case 'add_published_user':
                $weibo_msg_published_user = get_setting('weibo_msg_published_user');

                if ($_POST['uid'] != $weibo_msg_published_user['uid'])
                {
                    $published_user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

                    if (!$published_user_info)
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('所选用户不存在')));
                    }

                    $this->model('setting')->set_vars(array(
                        'weibo_msg_published_user' => array(
                            'uid' => $published_user_info['uid'],
                            'user_name' => $published_user_info['user_name'],
                            'url_token' => $published_user_info['url_token']
                    )));
                }

                break;

            case 'weibo_msg_enabled':
                if (in_array($_POST['uid'], array('question', 'ticket', 'N')))
                {
                    $this->model('setting')->set_vars(array(
                        'weibo_msg_enabled' => $_POST['uid']
                    ));
                }

                break;
        }

        H::ajax_json_output(AWS_APP::RSM($rsm, 1, null));
    }

    public function save_approval_item_action()
    {
        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择待审项')));
        }

        if (!$_POST['type'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('类型不能为空')));
        }

        switch ($_POST['type'])
        {
            case 'weibo_msg':
                $approval_item = $this->model('openid_weibo_weibo')->get_msg_info_by_id($_POST['id']);

                if ($approval_item['question_id'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该消息已通过审核')));
                }

                $approval_item['type'] = 'weibo_msg';

                break;

            case 'received_email':
                $approval_item = $this->model('edm')->get_received_email_by_id($_POST['id']);

                if ($approval_item['question_id'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该邮件已通过审核')));
                }

                $approval_item['type'] = 'received_email';

                break;

            default:
                $approval_item = $this->model('publish')->get_approval_item($_POST['id']);

                break;
        }

        if (!$approval_item)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('待审项不存在')));
        }

        if ($_POST['type'] != $approval_item['type'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('类型不正确')));
        }

        if (!$_POST['title'] AND in_array($_POST['type'], array('question', 'article', 'received_email')))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入标题')));
        }

        if (!$_POST['content'] AND in_array($_POST['type'], array('answer', 'article_comment')))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入内容')));
        }

        switch ($approval_item['type'])
        {
            case 'question':
                $approval_item['data']['question_content'] = htmlspecialchars_decode($_POST['title']);

                $approval_item['data']['question_detail'] = htmlspecialchars_decode($_POST['content']);

                $approval_item['data']['topics'] = explode(',', htmlspecialchars_decode($_POST['topics']));

                break;

            case 'answer':
                $approval_item['data']['answer_content'] = htmlspecialchars_decode($_POST['content']);

                break;

            case 'article':
                $approval_item['data']['title'] = htmlspecialchars_decode($_POST['title']);

                $approval_item['data']['message'] = htmlspecialchars_decode($_POST['content']);

                break;

            case 'article_comment':
                $approval_item['data']['message'] = htmlspecialchars_decode($_POST['content']);

                break;

            case 'weibo_msg':
                $approval_item['text'] = htmlspecialchars_decode($_POST['content']);

                $approval_item['data']['attach_access_key'] = $approval_item['access_key'];

                break;

            case 'received_email':
                $approval_item['subject'] = htmlspecialchars_decode($_POST['title']);

                $approval_item['content'] = htmlspecialchars_decode($_POST['content']);

                break;
        }

        if ($approval_item['type'] != 'article_comment' AND $_POST['remove_attachs'])
        {
            foreach ($_POST['remove_attachs'] AS $attach_id)
            {
                $this->model('publish')->remove_attach($attach_id, $approval_item['data']['attach_access_key']);
            }
        }

        switch ($approval_item['type'])
        {
            case 'weibo_msg':
                $this->model('openid_weibo_weibo')->update('weibo_msg', array(
                    'text' => $approval_item['text']
                ), 'id = ' . $approval_item['id']);

                break;

            case 'received_email':
                $this->model('edm')->update('received_email', array(
                    'subject' => $approval_item['subject'],
                    'content' => $approval_item['content']
                ), 'id = ' . $approval_item['id']);

                break;

            default:
                $this->model('publish')->update('approval', array(
                    'data' => serialize($approval_item['data'])
                ), 'id = ' . $approval_item['id']);

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/approval/list/')
        ), 1, null));
    }

    public function save_today_topics_action()
    {
        $today_topics = trim($_POST['today_topics']);

        $this->model('setting')->set_vars(array('today_topics' => $today_topics));

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('设置已保存')));
    }

    public function save_receiving_email_config_action()
    {
        if ($_POST['id'])
        {
            $receiving_email_config = $this->model('edm')->get_receiving_email_config_by_id($_POST['id']);

            if (!$receiving_email_config)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该账号不存在')));
            }
        }

        $_POST['server'] = trim($_POST['server']);

        if (!$_POST['server'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入服务器地址')));
        }

        if (!$_POST['protocol'] OR !in_array($_POST['protocol'], array('pop3', 'imap')))
        {
             H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择协议')));
        }

        if ($_POST['port'] AND (!is_digits($_POST['port']) OR $_POST['port'] < 0 OR $_POST['port'] > 65535))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入有效的端口号（0 ~ 65535）')));
        }

        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择此账号对应的用户')));
        }

        $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

        if (!$user_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('所选用户不存在')));
        }

        $receiving_email_config = array(
                                        'server' => $_POST['server'],
                                        'protocol' => $_POST['protocol'],
                                        'ssl' => ($_POST['ssl'] == '1') ? '1' : '0',
                                        'username' => trim($_POST['username']),
                                        'password' => trim($_POST['password']),
                                        'uid' => $user_info['uid']
                                    );

        if ($_POST['port'])
        {
            $receiving_email_config['port'] = $_POST['port'];
        }

        if ($_POST['id'])
        {
            $this->model('edm')->update_receiving_email_config($_POST['id'], 'update', $receiving_email_config);

            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
        }
        else
        {
            $config_id = $this->model('edm')->update_receiving_email_config(null, 'add', $receiving_email_config);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/edm/receiving/id-' . $config_id)
            ), 1, null));
        }
    }

    public function save_receiving_email_global_config_action()
    {
        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请设置邮件内容对应提问用户')));
        }

        $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

        if (!$user_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('所选用户不存在')));
        }

        $this->model('setting')->set_vars(array(
            'receiving_email_global_config' => array(
                'enabled' => (in_array($_POST['enabled'], array('question', 'ticket'))) ? $_POST['enabled'] : 'N',
                'publish_user' => array(
                    'uid' => $user_info['uid'],
                    'user_name' => $user_info['user_name'],
                    'url_token' => $user_info['url_token']
            )
        )));

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
    }

    public function remove_receiving_account_action()
    {
        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的账号')));
        }

        $this->model('edm')->delete('receiving_email_config', 'id = ' . intval($_POST['id']));

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    /**
     * 保存分配yoyow币任务
     */
    public function save_assign_task_action()
    {
        if(!$_POST["act_start_time"]){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入获得积分开始时间')));
        }else if(!strtotime($_POST["act_start_time"])){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('积分开始时间格式不正确')));
        }

        if(!$_POST["act_end_time"]){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入获得积分结束时间')));
        }else if(!strtotime($_POST["act_start_time"])){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('积分结束时间格式不正确')));
        }

        if(empty($_POST["coin"])){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入本次分配币数')));
        }else if(!is_numeric($_POST["coin"])){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本次分配币数必须为数字')));
        }else if($_POST["coin"]<0){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本次分配币数要大于0')));
        }else if(!preg_match("/^\d+(?:\.\d{1,4})?$/",$_POST["coin"])){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本次分配币数最多只能有四位小数')));
        }/*else if(floor($_POST["coin"])!=$_POST["coin"]){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本次分配币数必须为整数')));
        }*/

        if(!$_POST["exec_time"]){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入任务执行时间')));
        }else if(!strtotime($_POST["act_start_time"])){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('任务执行时间格式不正确')));
        }

        if(!$_POST["id"]) {
            //添加
            $data=array(
                "act_start_time"=>strtotime($_POST["act_start_time"]),
                "act_end_time"=>strtotime($_POST["act_end_time"]),
                "coin"=>$_POST["coin"],
                "exec_time"=>strtotime($_POST["exec_time"]),
                "status"=>"0",
                "used_time"=>"0",
                "remain"=>$_POST["coin"]
                );
            $this->model('assigntask')->insert("yoyow_assign_task",$data);
        }else {
            //编辑
            $data=array(
                "id"=>$_POST["id"],
                "act_start_time"=>strtotime($_POST["act_start_time"]),
                "act_end_time"=>strtotime($_POST["act_end_time"]),
                "coin"=>$_POST["coin"],
                "exec_time"=>strtotime($_POST["exec_time"]),
                "status"=>"0",
                "used_time"=>"0",
                "remain"=>$_POST["coin"]
            );
            $where="id='".$_POST["id"]."'";
            $this->model('assigntask')->update("yoyow_assign_task",$data,$where);
        }
        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/assign_task_list/')
        ), -1, AWS_APP::lang()->_t('保存成功')));
    }

    /**
     * 删除一个手动分配任务列表
     */
    public function assign_task_del_action(){
        if(!empty($_POST["id"])){
            $where="id=".$_POST["id"];
            $this->model("assigntask")->delete("yoyow_assign_task",$where);
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/assign_task_list/')
            ), 1, null));
        }
    }

    /**
     * 删除一个手动分配任务列表
     */
    public function assign_task_delm_action(){
        if(!empty($_POST["id"])){
            $where="id in (".implode(',',$_POST["id"]).")";
            $this->model("assigntask")->delete("yoyow_assign_task",$where);
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/assign_task_list/')
            ), 1, null));
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的任务')));
        }

    }

    /**
     * 保存yoyow分币设置
     */
    public function save_yoyow_coin_settings_action(){
//        if(empty($_POST['yoyow_trigger_task_scale'])){
//            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入自动分yoyow币比例')));
//        }
//        if(!is_numeric($_POST['yoyow_trigger_task_time_hour'])){
//            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入自动分币时间中的小时')));
//        }
//        if(!is_numeric($_POST['yoyow_trigger_task_time_minute'])){
//            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入自动分币时间中的分钟')));
//        }
//        $yoyow_trigger_task_time=$_POST['yoyow_trigger_task_time_hour'].':'.$_POST['yoyow_trigger_task_time_minute'];
        $yoyow_assign_task_switch=$_POST['yoyow_assign_task_switch'];
        $default_distribute_yoyow_coin=$_POST['default_distribute_yoyow_coin'];
//        $yoyow_trigger_task_switch=$_POST['yoyow_trigger_task_switch'];
//        $yoyow_trigger_task_scale=$_POST['yoyow_trigger_task_scale'];

        $save_data=array(/*'yoyow_trigger_task_time'=>$yoyow_trigger_task_time,*/
            'yoyow_assign_task_switch'=>$yoyow_assign_task_switch,
            'default_distribute_yoyow_coin'=>$default_distribute_yoyow_coin
            /*,
            'yoyow_trigger_task_switch'=>$yoyow_trigger_task_switch,
            'yoyow_trigger_task_scale'=>$yoyow_trigger_task_scale*/
            );

        $this->model('setting')->set_vars($save_data);
        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
    }

    public function save_site_announce_action(){
        if(empty($_POST['site_announce'])) {
            H::ajax_json_output(AWS_APP::RSM(NULL, -1, AWS_APP::lang()->_t('请输入网站公告内容')));
        }
            $site_announce=htmlspecialchars($_POST['site_announce']);
            $add_time=strtotime('now');
            $status=0;
            $save_data=array(
              'content'=>$site_announce,
              'time'=>$add_time,
              'title'=>$_POST['title'],
              'status'=>$status
            );
        if(!$_POST["id"]) {
            $this->model('setting')->insert('site_announce',$save_data);
        }else{
            $where="id='".$_POST["id"]."'";
            $this->model('setting')->update('site_announce',$save_data,$where);
        }
        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/site_announce_list/')
        ), 1, AWS_APP::lang()->_t('保存成功')));
    }

    public function check_yoyow_assgin_action(){
        $task_end_time=$this->model('account')->get_task_yoyow_end_time(strtotime($_POST['start_time']),$_POST['id']);
        if($task_end_time){
            H::ajax_json_output(AWS_APP::RSM(NULL, 1, AWS_APP::lang()->_t('')));
        }else{
            H::ajax_json_output(AWS_APP::RSM(NULL, -1, AWS_APP::lang()->_t('')));
        }
    }

    public function save_site_link_action(){
        if(empty($_POST['title']) || empty($_POST['linkurl'])){
            H::ajax_json_output(AWS_APP::RSM(NULL, -1, AWS_APP::lang()->_t('请输入标题或者链接地址')));
        }
        $add_time=strtotime('now');
        $save_data=array(
            'title'=>$_POST['title'],
            'linkurl'=>$_POST['linkurl'],
            'time'=>$add_time,
            'sort'=>$_POST['sort']
        );
        if(!$_POST["id"]) {
            $this->model('setting')->insert('site_link',$save_data);
        }else{
            $where="id='".$_POST["id"]."'";
            $this->model('setting')->update('site_link',$save_data,$where);
        }
        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/site_link_list/')
        ), 1, AWS_APP::lang()->_t('保存成功')));
    }

    public function save_index_activity_action(){
        /* if(empty($_POST['title'])){
              H::ajax_json_output(AWS_APP::RSM(NULL, -1, AWS_APP::lang()->_t('请输入标题')));
          }*/
        //图片
        /*if (!$_FILES['linkurl'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请上传图片')));
        }*/



        //图上传
        if ($_FILES['linkurl']){

            $max_size = 5 * 1024 * 1024; //5M
            if($_FILES['linkurl']['size'] > $max_size){
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片不能超过5M，请重新上传')));
            }

            $allow_arr = array('image/jpg','image/jpeg','image/png','image/gif');
            if(!in_array($_FILES['linkurl']['type'],$allow_arr)){
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片上传类型不符合要求')));
            }
            $pic_url = $this->index_activity_upload_action('linkurl',$max_size);
        }


        $add_time=strtotime('now');
        if ($_FILES['linkurl']){
            $save_data=array(
                'title'=>$_POST['title'],
                'linkurl'=>$pic_url,
                'time'=>$add_time,
            );
        }else if(!empty($_POST['title'])){
            $save_data = array(
                'title' => $_POST['title'],
                'time' => $add_time,
            );
        }
        if(isset($save_data)){
            if(!$_POST["id"]) {
                $this->model('setting')->insert('index_activity',$save_data);
            }else{
                $where="id='".$_POST["id"]."'";
                $this->model('setting')->update('index_activity',$save_data,$where);
            }
        }


        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/index_activity_list/')
        ), 1, AWS_APP::lang()->_t('保存成功')));
    }


    /**
     * 图片上传
     *
     */
    public function index_activity_upload_action($column_name,$max_size)
    {
        $time = time();
        AWS_APP::upload()->initialize(array(
            'allowed_types' => 'jpg,jpeg,png,gif',
            'upload_path' => get_setting('upload_dir') . '/common/',
            'is_image' => TRUE,
            'max_size' =>$max_size,
            'file_name' => $time.".jpg",
            'encrypt_name' => FALSE
        ))->do_upload($column_name);


        if (AWS_APP::upload()->get_error()) {
            switch (AWS_APP::upload()->get_error()) {
                default:
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码：'.AWS_APP::upload()->get_error() )));
                    break;

                case 'upload_invalid_filetype':
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片类型无效')));
                    break;

                case 'upload_invalid_filesize':
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片尺寸过大, 最大允许尺寸为'.get_setting('upload_avatar_size_limit').'KB')));
                    break;
            }
        }

        if (!$upload_data = AWS_APP::upload()->data()) {
            die("{'error':'上传失败, 请与管理员联系'}");
        }

      return '/uploads/common/' . $time.".jpg";
    }

    /**
     * 奖励分配任务  进行分币
     */
    public function reward_task_id_action(){

        if(!empty($_POST["id"])){
            $sql = "select SUM(coin) coin,coin_uid from ".get_table('invitation_yoyow')." where id in (".implode(',',$_POST["id"]).") GROUP BY coin_uid";
            $invitation_list = $this->model('yoyowcoin')->query_all($sql);
            foreach ($invitation_list AS $key => $val){
                $yoyow_id = $this->model('assigntask')->fetch_one('users_yoyow','yoyow','uid='.$val['coin_uid']);
                $id_sql = "select id from ".get_table('invitation_yoyow')." where id in (".implode(',',$_POST["id"]).") AND coin_uid = ".$val['coin_uid'];
                $id_list = $this->model('yoyowcoin')->query_all($id_sql);
                $id_lists = array();
                foreach ($id_list AS $k => $v){
                    $id_lists[] = $v['id'];
                }
                $user_info = $this->model('yoyowcoin')->fetch_row('users','uid = '.$val['coin_uid']);
                if(!$yoyow_id && $user_info){
                    $update_sql1="update ".get_table('invitation_yoyow')." set has_ditribute = 3 where id in (".implode(',',$id_lists).")";
                    $this->model('yoyowcoin')->query_all($update_sql1);

                }else if($yoyow_id && $user_info){
                    $return = $this->model('assigntask')->transfer_to_by_yoyow($yoyow_id,$val['coin']);
                    //转账处理 更新invitation_yoyow表has_ditribute字段
                    if(!empty($return) && $return['code']==0 ){
                        $update_sql="update ".get_table('invitation_yoyow')." set has_ditribute = 1 where id in (".implode(',',$id_lists).")";
                        $this->model('yoyowcoin')->query_all($update_sql);
                    }else{
                        $update_sql="update ".get_table('invitation_yoyow')." set has_ditribute = 2 where id in (".implode(',',$id_lists).")";
                        $this->model('yoyowcoin')->query_all($update_sql);
                    }
                }else if(!$user_info){
                    $update_sql1="update ".get_table('invitation_yoyow')." set has_ditribute = 4 where id in (".implode(',',$id_lists).")";
                    $this->model('yoyowcoin')->query_all($update_sql1);
                }
                //分币成功后，将分币结果推送给用户
                $this->model('notify')->send(
                    null,
                    $val['coin_uid'],
                    notify_class::TYPE_COIN_DISTRIBUTE,
                    notify_class::YOYOW_COIN,
                    $id_lists,
                    array('coin' => $val['coin'])
                );

            }
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/reward_task_list/')
            ), 1, null));
            return false;
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要分币的数据')));
        }

    }


    /**
     * 奖励分配任务  进行全部分币
     */
    public function all_invite_reward_task_action()
    {
        $sql = "select SUM(coin) coin,coin_uid from " . get_table('invitation_yoyow') . " where has_ditribute = 0 and effective = 1 GROUP BY coin_uid";
        $invitation_list = $this->model('yoyowcoin')->query_all($sql);
        if(sizeof($invitation_list)==0){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经全部发放完啦，请不要重复点击')));
        }
        foreach ($invitation_list AS $key => $val) {
            $yoyow_id = $this->model('assigntask')->fetch_one('users_yoyow', 'yoyow', 'uid=' . $val['coin_uid']);
            $user_info = $this->model('yoyowcoin')->fetch_row('users','uid = '.$val['coin_uid']);
            if (!$yoyow_id && $user_info) {
                $update_sql1 = "update " . get_table('invitation_yoyow') . " set has_ditribute = 3 where has_ditribute= 0 AND coin_uid = ".$val['coin_uid'];
                $this->model('yoyowcoin')->query_all($update_sql1);

            } else if($yoyow_id && $user_info){
                $return = $this->model('assigntask')->transfer_to_by_yoyow($yoyow_id, $val['coin']);
                //转账处理 更新invitation_yoyow表has_ditribute字段
                if (!empty($return) && $return['code'] == 0) {
                    $update_sql = "update " . get_table('invitation_yoyow') . " set has_ditribute = 1 where has_ditribute= 0 AND coin_uid = ".$val['coin_uid'];
                    $this->model('yoyowcoin')->query_all($update_sql);
                } else {
                    $update_sql = "update " . get_table('invitation_yoyow') . " set has_ditribute = 2 where has_ditribute= 0 AND coin_uid = ".$val['coin_uid'];
                    $this->model('yoyowcoin')->query_all($update_sql);
                }
            }else if(!$user_info){
                $update_sql1="update ".get_table('invitation_yoyow')." set has_ditribute = 4 where has_ditribute= 0 AND coin_uid = ".$val['coin_uid'];
                $this->model('yoyowcoin')->query_all($update_sql1);
            }
            //分币成功后，将分币结果推送给用户
            $this->model('notify')->send(
                null,
                $val['coin_uid'],
                notify_class::TYPE_COIN_DISTRIBUTE,
                notify_class::YOYOW_COIN,
                $val['coin_uid'],
                array('coin' => $val['coin'])
            );

        }
        //点击全部发币将无效的数据改为作废状态
        $update_sql2="update ".get_table('invitation_yoyow')." set has_ditribute = 4 where effective = 0 and has_ditribute= 0";
        $this->model('yoyowcoin')->query_all($update_sql2);
        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/reward_task_list/')
        ), 1, null));
        return false;
    }

    /**
     * 保存分享配置
     */
    public function save_share_settings_action(){
        if(empty($_POST['share_content'])) {
            H::ajax_json_output(AWS_APP::RSM(NULL, -1, AWS_APP::lang()->_t('请输入分享内容')));
        }
        $share_content=$_POST['share_content'];
        $share_title=$_POST['share_title'];
        $invitation_code_remark=$_POST['invitation_code_remark'];
        $invite_start_time=$_POST['invite_start_time'];

        if ($_FILES['poster_bg_img']['name'])
        {
            $file_name = 'poster_bg.jpg';
            $max_size = 5 * 1024; //5M
            AWS_APP::upload()->initialize(array(
                'allowed_types' => 'jpg,jpeg,png',
                'upload_path' => get_setting('upload_dir') . '/poster/bg/',
                'is_image' => TRUE,
                'max_size' => $max_size,
                'width' => 1080,
                'height' => 1920,
                'file_name' => $file_name,
                'encrypt_name' => FALSE
            ))->do_upload('poster_bg_img');

            if (AWS_APP::upload()->get_error())
            {
                switch (AWS_APP::upload()->get_error())
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                        break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));
                        break;

                    case 'upload_invalid_filesize':
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 5M')));
                        break;
                }
            }

            if (! $upload_data = AWS_APP::upload()->data())
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
            }

            $save_data=array(
                'poster_bg_img'=>$file_name
            );
            $this->model('setting')->set_vars($save_data);
        }


        $save_data=array(
            'share_content'=>$share_content,
            'share_title'=>$share_title,
            'invitation_code_remark'=>$invitation_code_remark,
            'invite_start_time'=>$invite_start_time
        );

        $this->model('setting')->set_vars($save_data);
        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
    }



    /**
     * 注册奖励分币
     */
    public function register_reward_task_action(){

        if(!empty($_POST["id"])){
            $sql = "select SUM(coin) coin,uid from ".get_table('register_yoyow_record')." where id in (".implode(',',$_POST["id"]).") GROUP BY uid";
            $register_reward_list = $this->model('yoyowcoin')->query_all($sql);
            foreach ($register_reward_list AS $key => $val){
                //用户在线时长大于0
                if($this->model('project')->fetch_one('users','online_time','uid = '.$val['uid'])>0){
                    $yoyow_id = $this->model('assigntask')->fetch_one('users_yoyow','yoyow','uid='.$val['uid']);
                    //未绑定yoyow 更改状态未提现
                    if(!$yoyow_id){
                        $update_sql = "update " . get_table('register_yoyow_record') . " set status = 1 where id in (" . implode(',', $_POST["id"]) . ") AND uid = ".$val['uid'];
                        $sel_sql = "select * from ".get_table('register_yoyow_record')." where id in (".implode(',',$_POST["id"]).") AND uid =".$val['uid'];
                        $sel_list = $this->model('yoyowcoin')->query_all($sel_sql);
                        foreach ($sel_list AS $k => $v){
                            if($v['operate']==1){
                                $record_id1 = $this->model('account')->update_register_reward_record($v['uid'],"用户注册奖励",$v['coin'],1,2);
                                //分币成功后，将分币结果推送给用户
                                $this->model('notify')->send(
                                    null,
                                    $v['uid'],
                                    notify_class::TYPE_COIN_DISTRIBUTE,
                                    notify_class::YOYOW_COIN,
                                    $record_id1,
                                    array('coin' => $v['coin'])
                                );
                            }else if($v['operate']==2){
                                $record_id2 = $this->model('account')->update_register_reward_record($v['uid'],"用户绑定yoyow奖励",$v['coin'],2,2);
                                //分币成功后，将分币结果推送给用户
                                $this->model('notify')->send(
                                    null,
                                    $v['uid'],
                                    notify_class::TYPE_COIN_DISTRIBUTE,
                                    notify_class::YOYOW_COIN,
                                    $record_id2,
                                    array('coin' => $v['coin'])
                                );
                            }
                        }
                        $this->model('yoyowcoin')->query_all($update_sql);
                    }else {//绑定 则转账处理
                        $return = $this->model('assigntask')->transfer_to_by_yoyow($yoyow_id, $val['coin']);

                        //转账处理
                        if (!empty($return) && $return['code'] == 0) {//转账成功
                            $update_sql = "update " . get_table('register_yoyow_record') . " set status = 1 where id in (" . implode(',', $_POST["id"]) . ") AND uid = ".$val['uid'];
                            $sel_sql = "select * from ".get_table('register_yoyow_record')." where id in (".implode(',',$_POST["id"]).") AND uid =".$val['uid'];
                            $sel_list = $this->model('yoyowcoin')->query_all($sel_sql);
                            foreach ($sel_list AS $k => $v){
                                if($v['operate']==1){
                                    $record_id3 = $this->model('account')->update_register_reward_record($v['uid'],"用户注册奖励",$v['coin'],1,0);
                                    //分币成功后，将分币结果推送给用户
                                    $this->model('notify')->send(
                                        null,
                                        $v['uid'],
                                        notify_class::TYPE_COIN_DISTRIBUTE,
                                        notify_class::YOYOW_COIN,
                                        $record_id3,
                                        array('coin' => $v['coin'])
                                    );
                                }else if($v['operate']==2){
                                    $record_id4 = $this->model('account')->update_register_reward_record($v['uid'],"用户绑定yoyow奖励",$v['coin'],2,0);
                                    //分币成功后，将分币结果推送给用户
                                    $this->model('notify')->send(
                                        null,
                                        $v['uid'],
                                        notify_class::TYPE_COIN_DISTRIBUTE,
                                        notify_class::YOYOW_COIN,
                                        $record_id4,
                                        array('coin' => $v['coin'])
                                    );
                                }
                            }
                            $this->model('yoyowcoin')->query_all($update_sql);
                        } else {//转账失败
                            $update_sql = "update " . get_table('register_yoyow_record') . " set status = 1 where id in (" . implode(',', $_POST["id"]) . ") AND uid = ".$val['uid'];
                            $sel_sql = "select * from ".get_table('register_yoyow_record')." where id in (".implode(',',$_POST["id"]).") AND uid =".$val['uid'];
                            $sel_list = $this->model('yoyowcoin')->query_all($sel_sql);
                            foreach ($sel_list AS $k => $v){
                                if($v['operate']==1){
                                    $record_id5 = $this->model('account')->update_register_reward_record($v['uid'],"用户注册奖励",$v['coin'],1,1);
                                    //分币成功后，将分币结果推送给用户
                                    $this->model('notify')->send(
                                        null,
                                        $v['uid'],
                                        notify_class::TYPE_COIN_DISTRIBUTE,
                                        notify_class::YOYOW_COIN,
                                        $record_id5,
                                        array('coin' => $v['coin'])
                                    );
                                }else if($v['operate']==2){
                                    $record_id6 = $this->model('account')->update_register_reward_record($v['uid'],"用户绑定yoyow奖励",$v['coin'],2,1);
                                    //分币成功后，将分币结果推送给用户
                                    $this->model('notify')->send(
                                        null,
                                        $v['uid'],
                                        notify_class::TYPE_COIN_DISTRIBUTE,
                                        notify_class::YOYOW_COIN,
                                        $record_id6,
                                        array('coin' => $v['coin'])
                                    );
                                }
                            }
                            $this->model('yoyowcoin')->query_all($update_sql);
                        }
                    }
                }else{
                    $update_sql = "update " . get_table('register_yoyow_record') . " set status = 2 where id in (" . implode(',', $_POST["id"]) . ") AND uid = ".$val['uid'];
                    $this->model('yoyowcoin')->query_all($update_sql);
                }

            }
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/project/register_bind_list/')
            ), 1, null));
            return false;
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要分币的数据')));
        }

    }

    /**
     * 威望奖励分币
     */
    public function reputation_reward_task_action(){
        //分配用户威望大于5的奖励
        $sel_reputation = "SELECT uid,id FROM ".get_table('reputation_users')." WHERE reputation >=5 and uid not in(select uid from ".get_table('register_reward_record')." WHERE type=0)";
        $user_reputation_list = $this->model('yoyowcoin')->query_all($sel_reputation);
        if(sizeof($user_reputation_list)==0){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('威望奖励已经全部发放完啦，请不要重复点击')));
        }
        foreach ($user_reputation_list AS $keys => $value){
            $yoyow_id = $this->model('assigntask')->fetch_one('users_yoyow','yoyow','uid='.$value['uid']);
            $update_sql = "update " . get_table('reputation_users') . " set status = 1 where uid = ".$value['uid'];
            //未绑定yoyow 更改状态未提现
            if(!$yoyow_id){
                $this->model('account')->update_register_reward_record($value['uid'],"用户威望奖励",18,0,2);
            }else {//绑定 则转账处理
                $return = $this->model('assigntask')->transfer_to_by_yoyow($yoyow_id,18);

                //转账处理
                if (!empty($return) && $return['code'] == 0) {//转账成功
                    $this->model('account')->update_register_reward_record($value['uid'],"用户威望奖励",18,0,0);
                } else {//转账失败
                    $this->model('account')->update_register_reward_record($value['uid'],"用户威望奖励",18,0,1);
                }
            }
            $this->model('yoyowcoin')->query_all($update_sql);
            //分币成功后，将分币结果推送给用户
            $this->model('notify')->send(
                null,
                $value['uid'],
                notify_class::TYPE_COIN_DISTRIBUTE,
                notify_class::YOYOW_COIN,
                $value['id'],
                array('coin' => '18')
            );
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/project/reputation_reward_list/')
        ), 1, null));
        return false;
    }


    /**
     * 全部发放奖励
     */
    public function all_register_reward_task_action(){
        $sql = "select SUM(a.coin) coin,b.uid from ".get_table('register_yoyow_record')." a inner JOIN ".get_table('users')." b on a.uid = b.uid where (a.status =0 or a.status is null) and b.uid is not null GROUP BY a.uid";
        $register_reward_list = $this->model('yoyowcoin')->query_all($sql);
        if(sizeof($register_reward_list)==0){
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经全部发放完啦，请不要重复点击')));
        }
        foreach ($register_reward_list AS $key => $val){
            //用户在线时长大于0
            if($this->model('project')->fetch_one('users','online_time','uid = '.$val['uid'])>0){
                $yoyow_id = $this->model('assigntask')->fetch_one('users_yoyow','yoyow','uid='.$val['uid']);
                //未绑定yoyow 更改状态未提现
                if(!$yoyow_id){
                    $update_sql = "update " . get_table('register_yoyow_record') . " set status = 1 where (status =0 or status is null) and uid = ".$val['uid'];
                    $sel_sql = "select * from ".get_table('register_yoyow_record')." where (status =0 or status is null) AND uid =".$val['uid'];
                    $sel_list = $this->model('yoyowcoin')->query_all($sel_sql);
                    foreach ($sel_list AS $k => $v){
                        if($v['operate']==1){
                           $record_id1 = $this->model('account')->update_register_reward_record($v['uid'],"用户注册奖励",$v['coin'],1,2);
                            //分币成功后，将分币结果推送给用户
                            $this->model('notify')->send(
                                null,
                                $v['uid'],
                                notify_class::TYPE_COIN_DISTRIBUTE,
                                notify_class::YOYOW_COIN,
                                $record_id1,
                                array('coin' => $v['coin'])
                            );
                        }else if($v['operate']==2){
                            $record_id2 =  $this->model('account')->update_register_reward_record($v['uid'],"用户绑定yoyow奖励",$v['coin'],2,2); //分币成功后，将分币结果推送给用户
                            //分币成功后，将分币结果推送给用户
                            $this->model('notify')->send(
                                null,
                                $v['uid'],
                                notify_class::TYPE_COIN_DISTRIBUTE,
                                notify_class::YOYOW_COIN,
                                $record_id2,
                                array('coin' => $v['coin'])
                            );
                        }
                    }
                    $this->model('yoyowcoin')->query_all($update_sql);
                }else {//绑定 则转账处理
                    $return = $this->model('assigntask')->transfer_to_by_yoyow($yoyow_id, $val['coin']);

                    //转账处理
                    if (!empty($return) && $return['code'] == 0) {//转账成功
                        $update_sql = "update " . get_table('register_yoyow_record') . " set status = 1 where (status =0 or status is null) and uid = ".$val['uid'];
                        $sel_sql = "select * from ".get_table('register_yoyow_record')." where (status =0 or status is null) AND uid =".$val['uid'];
                        $sel_list = $this->model('yoyowcoin')->query_all($sel_sql);
                        foreach ($sel_list AS $k => $v){
                            if($v['operate']==1){
                                $record_id3 = $this->model('account')->update_register_reward_record($v['uid'],"用户注册奖励",$v['coin'],1,0);
                                //分币成功后，将分币结果推送给用户
                                $this->model('notify')->send(
                                    null,
                                    $v['uid'],
                                    notify_class::TYPE_COIN_DISTRIBUTE,
                                    notify_class::YOYOW_COIN,
                                    $record_id3,
                                    array('coin' => $v['coin'])
                                );
                            }else if($v['operate']==2){
                                $record_id4 = $this->model('account')->update_register_reward_record($v['uid'],"用户绑定yoyow奖励",$v['coin'],2,0);
                                //分币成功后，将分币结果推送给用户
                                $this->model('notify')->send(
                                    null,
                                    $v['uid'],
                                    notify_class::TYPE_COIN_DISTRIBUTE,
                                    notify_class::YOYOW_COIN,
                                    $record_id4,
                                    array('coin' => $v['coin'])
                                );
                            }
                        }
                        $this->model('yoyowcoin')->query_all($update_sql);
                    } else {//转账失败
                        $update_sql = "update " . get_table('register_yoyow_record') . " set status = 1 where (status =0 or status is null) AND uid = ".$val['uid'];
                        $sel_sql = "select * from ".get_table('register_yoyow_record')." where (status =0 or status is null) AND uid =".$val['uid'];
                        $sel_list = $this->model('yoyowcoin')->query_all($sel_sql);
                        foreach ($sel_list AS $k => $v){
                            if($v['operate']==1){
                                $record_id5 = $this->model('account')->update_register_reward_record($v['uid'],"用户注册奖励",$v['coin'],1,1);
                                //分币成功后，将分币结果推送给用户
                                $this->model('notify')->send(
                                    null,
                                    $v['uid'],
                                    notify_class::TYPE_COIN_DISTRIBUTE,
                                    notify_class::YOYOW_COIN,
                                    $record_id5,
                                    array('coin' => $v['coin'])
                                );
                            }else if($v['operate']==2){
                                $record_id6 = $this->model('account')->update_register_reward_record($v['uid'],"用户绑定yoyow奖励",$v['coin'],2,1);
                                //分币成功后，将分币结果推送给用户
                                $this->model('notify')->send(
                                    null,
                                    $v['uid'],
                                    notify_class::TYPE_COIN_DISTRIBUTE,
                                    notify_class::YOYOW_COIN,
                                    $record_id6,
                                    array('coin' => $v['coin'])
                                );
                            }
                        }
                        $this->model('yoyowcoin')->query_all($update_sql);
                    }
                }

            }else{
                $update_sql = "update " . get_table('register_yoyow_record') . " set status = 2 where (status =0 or status is null) and uid = ".$val['uid'];
                $this->model('yoyowcoin')->query_all($update_sql);
            }

        }
        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/project/register_bind_list/')
        ), 1, null));
        return false;

    }

    /**
     * 删除一个回复赞踩记录
     */
    public function vote_del_action(){
        if(!empty($_POST["id"])){
            $this->model("update")->update_answer_agree_disagree($_POST["id"]);
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/vote_list/')
            ), 1, null));
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的数据')));
        }

    }
    /**
     * 删除一个文章赞踩记录
     */
    public function article_vote_del_action(){
        if(!empty($_POST["id"])){
            $this->model("update")->update_article_agree_disagree($_POST["id"]);
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/article_vote_list/')
            ), 1, null));
        }else{
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的数据')));
        }

    }


    /**
     * 删除惡意踩记录
     */
    public function vote_lately_del_action(){
        $this->model('update')->delete_answer_vote_oppose_lately_by_uid();
        $this->model('update')->delete_article_vote_oppose_lately_by_uid();
        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/vote_list/')
        ), 1, null));
    }


    public function save_problem_action()
    { 
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }
 
        if (count(array_filter($_POST['content'])) != count(array_unique(array_filter($_POST['content'])))) {   
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('答案重复')));
        }


        if(!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('标题不能为空')));
        }

        if(!$_POST['right_key'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择正确答案')));
        }

        $rs = $this->model('problem')->save_problem($_POST);

        if($rs['status'] == -1)
        {
            H::ajax_json_output(AWS_APP::RSM(null,$rs['status'], AWS_APP::lang()->_t($rs['msg'])));
            return false;
        }else
        {
            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/problem/list/')
            ), 1, null));
            return false;
        }


    }


    public function problem_manage_action()
    {
        if (!$_POST['problem_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择问题进行操作')));
        }

        switch($_POST['action'])
        {
            case 'remove' :
                $this->model('problem')->remove_problem_by_ids($_POST['problem_ids']);

                break;

            case 'lock' :
                $this->model('problem')->lock_problem_by_ids($_POST['problem_ids'], 0);

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }


    public function lock_problem_action()
    {
        $this->model('problem')->lock_problem_by_ids($_POST['id'], $_POST['status']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
