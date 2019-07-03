<?php
/**
*
* This file is part of the .
*
* @copyright (c)  <https://>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace v12mike\enhancednotificationemails\controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use v12mike\enhancednotificationemails\code;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class unsubscribe
{
	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var */
	protected $phpbb_root_path;

    protected $notification_type_ids;

	public $u_action;


	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper  $helper
	 * @param \phpbb\template\template  $template
	 * @param \phpbb\user				$user
    * @param \phpbb\notification\manager $notification_manager Controller helper object 
	 * @param 							$phpbb_root_path
	 */
	public function __construct(\phpbb\controller\helper $helper, 
                                ContainerInterface $phpbb_container, 
                                \phpbb\config\config $config, 
                                \phpbb\db\driver\driver_interface $db, 
                                \phpbb\cache\service $cache, 
                                \phpbb\template\template $template, 
                                \phpbb\request\request_interface $request, 
                                \phpbb\user $user, 
                                \phpbb\user_loader $user_loader, 
                                \phpbb\auth\auth $auth, 
                                $unsubscribe_links, 
                                \phpbb\language\language $lang, 
                                \phpbb\notification\manager $notification_manager,
                                $notification_types,
                                $phpbb_root_path, 
                                $phpbb_php_ext, 
                                $notifications_types_table,
                                $user_notifications_table)
	{
		$this->helper   = $helper;
        $this->phpbb_container = $phpbb_container; 
		$this->config   = $config;
		$this->db       = $db;
		$this->cache    = $cache;
		$this->template = $template;
		$this->request 	= $request;
		$this->user 	= $user;
        $this->user_loader = $user_loader;
        $this->auth     = $auth;
        $this->unsubscribe_links = $unsubscribe_links;
		$this->lang = $lang;
        $this->notification_manager = $notification_manager;
        $this->notification_types = $notification_types;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpbb_php_ext = $phpbb_php_ext;
		$this->notification_types_table = $notifications_types_table;
		$this->user_notifications_table = $user_notifications_table;

        $this->notification_type_ids = array();
	}

	public function handle(int $target_user_id, int $notification_type, int $identifier, int $time_stamp, string $token)
	{

        /* if this is a one-click unsubscribe, we don't generate a full html page, but just send a response code */
        if ($this->request->is_set_post('List-Unsubscribe') && ($this->request->variable('List-Unsubscribe', '') == 'One-Click'))
        {
            if ($this->unsubscribe_links->validate_unsubscribe_token($target_user_id, $notification_type, $identifier, $time_stamp, $token))
            {
                if ($this->unsubscribe_links->validate_timestamp)
                {
                    /* this is a valid 1-click unsubscribe */
                    $this->unsubscribe_notifications($target_user_id, $notification_type, $identifier);
                    return;
                }
                /* expired timestamp */
                return;
            }
            else
            {
                trigger_error('FORM_INVALID');
                return;
            }
        }
        else
        {
            /* not a 1-click unsubscribe, we will handle this further down */
        }

        $this->lang->add_lang('ucp');

        /* the user may or may not have an active session with the current browser */
        $session_user_id = $this->user->data['user_id'];
        if ($session_user_id == ANONYMOUS)
        {
            /* we need to get relavent data (more or less) directly from the database */
        }
        else if ($target_user_id == $session_user_id)
        {
            /* we are in a session for the target user */
            $test = 1;
            /* we continue... */
        }
        else
        {
            /* we are in a session, but not for the target user */
            $test = 2;
            /* to avoid confusion, take to warning page with logout or cancel option */

        }

        $this->user->add_lang_ext('v12mike/enhancednotificationemails', 'common');
		add_form_key('enhancednotificationemails_unsubscribe');

		$start = $this->request->variable('start', 0);
		$form_time = $this->request->variable('form_time', 0);
		$form_time = ($form_time <= 0 || $form_time > time()) ? time() : $form_time;

        /* from here on we work with the language (etc) of the target user */
        $target_user = $this->user_loader->get_user($target_user_id, true);
        $target_lang = $target_user['lang'];
        $this->lang->set_user_language($target_lang);

	//	$pagination = $this->phpbb_container->get('pagination');
        $subscriptions = $this->get_user_email_notification_subscriptions($target_user_id);

        if ($this->request->is_set_post('submit'))
        {
            // remove subscriptions
            if (check_form_key('enhancednotificationemails_unsubscribe'))
            {
                /* unsubscribe any requested subscriptions */
                foreach ($this->get_subscription_types() as $group => $subscription_types)
                {
                    foreach ($subscription_types as $type => $data)
                    {
                        if ($this->request->is_set_post(str_replace('.', '_', 'm_' . $type . '_notification.method.email')) && isset($subscriptions[$type]))
                        {
                            $this->notification_manager->delete_subscription($type, 0, 'notification.method.email', $target_user_id);
                            unset($subscriptions[$type]);
                        }
                    }
                }


            /*    $url = ('./unsubscribe/' . $target_user_id . '/' . $notification_type . '/' . $identifier . '/' . $time_stamp . '/' . $token);
                meta_refresh(3, $url);
                $message = $this->lang->lang('PREFERENCES_UPDATED') . '<br /><br />' . sprintf($this->lang->lang('RETURN_UCP'), '<a href="' . $this->u_action . '">', '</a>');
                trigger_error($message);*/
            }
            else
            {
                $msg = $this->lang->lang('FORM_INVALID');
            }
        }

        /* handle subscribed forums and topics */
        if (isset($_POST['unwatch']))
        {
            if (check_form_key('enhancednotificationemails_unsubscribe'))
            {
                $forums = array_keys($this->request->variable('f', array(0 => 0)));
                $topics = array_keys($this->request->variable('t', array(0 => 0)));

                if (count($forums) || count($topics))
                {
                    $l_unwatch = '';
                    if (count($forums))
                    {
                        $sql = 'DELETE FROM ' . FORUMS_WATCH_TABLE . '
                            WHERE ' . $this->db->sql_in_set('forum_id', $forums) . '
                                AND user_id = ' . $target_user['user_id'];
                        $this->db->sql_query($sql);

                        $l_unwatch .= '_FORUMS';
                    }

                    if (count($topics))
                    {
                        $sql = 'DELETE FROM ' . TOPICS_WATCH_TABLE . '
                            WHERE ' . $this->db->sql_in_set('topic_id', $topics) . '
                                AND user_id = ' . $target_user['user_id'];
                        $this->db->sql_query($sql);

                        $l_unwatch .= '_TOPICS';
                    }
                    $msg = $this->lang->lang('UNWATCHED' . $l_unwatch);
                }
                else
                {
                    $msg = $this->lang->lang('NO_WATCHED_SELECTED');
                }
              /*  $message = $msg . '<br /><br />' . sprintf($this->lang->lang['RETURN_UCP'], '<a href="' . append_sid("{$phpbb_root_path}ucp.$phpEx", "i=$id&amp;mode=subscribed") . '">', '</a>');
                meta_refresh(3, append_sid("{$phpbb_root_path}ucp.$phpEx", "i=$id&amp;mode=subscribed"));
                trigger_error($message);*/
            }
            else
            {
                $msg = $this->lang->lang('FORM_INVALID');
            }
        }

        /* build the notification methods table */
        $this->template->assign_block_vars('notification_methods', array('METHOD'	=> 'notification.method.email', 'NAME' => 'email'));

        $this->output_notification_types($subscriptions, ($identifier == 0) ? $notification_type : 0);

        $this->tpl_name = 'ucp_notifications';
        $this->page_title = 'ENE_NOTIFICATION_OPTIONS';

		$this->template->assign_vars(array(
		/*	'TITLE'				=> $this->lang->lang($this->page_title),
			'TITLE_EXPLAIN'		=> $this->lang->lang($this->page_title . '_EXPLAIN'),*/

			'MODE'				=> 'notification_options',

			'FORM_TIME'			=> time(),
		));

        /* build the subscribed forums table */

        if ($this->config['allow_forum_notify'])
        {
            $this->assign_forumlist($target_user, $notification_type, $identifier);
        }

        /* build the subscribed topics table */
        if ($this->config['allow_topic_notify'])
        {
            $this->assign_topiclist($target_user, $notification_type, $identifier);
        }

        $this->template->assign_vars(array(
            'S_TOPIC_NOTIFY'		=> $config['allow_topic_notify'],
            'S_FORUM_NOTIFY'		=> $config['allow_forum_notify'],
        ));

		return $this->helper->render('unsubscribe.html', 'Unsubscribe');
	}

	/**
	* Output all the notification types to the template
	*
	* @param array $subscriptions Array containing global subscriptions
	* @param \phpbb\notification\manager $phpbb_notifications
	* @param \phpbb\template\template $template
	* @param \phpbb\user $user
	* @param string $block
	*/
	public function output_notification_types($subscriptions, $selected_type_id = 0)
	{
        $any_subscribed = false;
        $notification_types = $this->get_subscription_types();
        $notification_type_id_names = $this->get_notification_type_id_names();
     //   $notification_type_ids = $this->notification_manager->get_notification_type_ids($notification_types);

		foreach ($notification_types as $group => $subscription_types)
		{
            $group_subscribed = false;

			foreach ($subscription_types as $type => $type_data)
			{
            //    $temp = $type_data['type'];
             //   $temp2 = $this->notification_manager->get_notification_type_id($type_data['id']);
            //    $this->notification_type_ids[$type_data['id']] = $this->notification_manager->get_notification_type_id($type_data['id']);
                $type_subscribed = false;
                $this_type_selected = ($selected_type_id && ($notification_type_id_names[$selected_type_id] == $type));

                $subscribed = isset($subscriptions[$type]);
                if ($subscribed)
                {
                    if (!$group_subscribed)
                    {
                        $this->template->assign_block_vars('notification_types', array(
                            'GROUP_NAME'	=> $this->lang->lang($group),
                        ));
                    }
                    if (!$type_subscribed)
                    {
                        $this->template->assign_block_vars('notification_types', array(
                            'TYPE'				=> $type,
                            'NAME'				=> $this->lang->lang($type_data['lang']),
                            'EXPLAIN'			=> (isset($this->lang->lang[$type_data['lang'] . '_EXPLAIN'])) ? $this->lang->lang($type_data['lang'] . '_EXPLAIN') : '',
                        ));
                    }
                    $this->template->assign_block_vars('notification_types' . '.notification_methods', array(
                        'METHOD'			=> 'notification.method.email',
                        'NAME'				=> $this->lang->lang($method_data['lang']),
                        'AVAILABLE'			=> true,
                        'SUBSCRIBED'		=> !$this_type_selected,
                    ));
                    $any_subscribed = true;
                    $group_subscribed = true;
                    $type_subscribed = true;
                }
			}
		}
        /* if no subscriptions found, output a message*/
        if (!$any_subscribed)
        {
        }

		$this->template->assign_vars(array(
			strtoupper($block) . '_COLS' => 2,
		));
	}

	/**
	* Build and assign forumlist for subscribed forums
	*/
	protected function assign_forumlist($user, $selected_type_id, $identifier)
    {
        include_once($this->phpbb_root_path . 'includes/functions_display.' . $this->phpbb_php_ext);

        $notification_type_id_names = $this->get_notification_type_id_names();

        $sql_array = array(
            'SELECT'	=> 'f.*',
            'FROM'		=> array(
                FORUMS_WATCH_TABLE	=> 'fw',
                FORUMS_TABLE		=> 'f'
            ),
            'WHERE'		=> 'fw.user_id = ' . $user['user_id'] . '
                AND f.forum_id = fw.forum_id',
            'ORDER_BY'	=> 'left_id'
        );
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);

        while ($row = $this->db->sql_fetchrow($result))
        {
            $forum_id = $row['forum_id'];

            // Which folder should we display?
            if ($row['forum_status'] == ITEM_LOCKED)
            {
                $folder_image = ($unread_forum) ? 'forum_unread_locked' : 'forum_read_locked';
                $folder_alt = 'FORUM_LOCKED';
            }
            else
            {
                $folder_image = ($unread_forum) ? 'forum_unread' : 'forum_read';
                $folder_alt = ($unread_forum) ? 'UNREAD_POSTS' : 'NO_UNREAD_POSTS';
            }

            $template_vars = array(
                'FORUM_ID'				=> $forum_id,
           /*     'FORUM_IMG_STYLE'		=> $folder_image,
                'FORUM_FOLDER_IMG'		=> $user->img($folder_image, $folder_alt),
                'FORUM_IMAGE'			=> ($row['forum_image']) ? '<img src="' . $phpbb_root_path . $row['forum_image'] . '" alt="' . $this->lang->lang[$folder_alt] . '" />' : '',
                'FORUM_IMAGE_SRC'		=> ($row['forum_image']) ? $phpbb_root_path . $row['forum_image'] : '',*/
                'FORUM_NAME'			=> $row['forum_name'],
                'FORUM_DESC'			=> generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
                'FORUM_SELECTED'        => $selected_type_id && ($notification_type_id_names[$selected_type_id] == 'notification.type.topic') && ($identifier == $forum_id),

                'U_VIEWFORUM'			=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $row['forum_id'])
            );

            $this->template->assign_block_vars('forumrow', $template_vars);
        }
        $this->db->sql_freeresult($result);
    }

	/**
	* Build and assign topiclist for subscribed topics
	*/
	function assign_topiclist($user, $selected_type_id, $identifier)
	{
        include_once($this->phpbb_root_path . 'includes/functions_display.' . $this->phpbb_php_ext);

        $notification_type_id_names = $this->get_notification_type_id_names();

		/* @var $pagination \phpbb\pagination */
	//	$pagination = $this->phpbb_container->get('pagination');
		$start = $this->request->variable('start', 0);

		// Grab icons
		$icons = $this->cache->obtain_icons();

		$sql_array = array(
			'SELECT'	=> 'COUNT(t.topic_id) as topics_count',
			'FROM'		=> array(
				TOPICS_WATCH_TABLE	=> 'i',
				TOPICS_TABLE	=> 't'
			),
			'WHERE'		=>	'i.topic_id = t.topic_id
				AND i.user_id = ' . $user['user_id'],
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$topics_count = (int) $this->db->sql_fetchfield('topics_count');
		$this->db->sql_freeresult($result);

		if ($topics_count)
		{
            $pagination = $this->phpbb_container->get('pagination');
			$start = $pagination->validate_start($start, $this->config['topics_per_page'], $topics_count);
			$pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $topics_count, $this->config['topics_per_page'], $start);

			$this->template->assign_vars(array(
				'TOTAL_TOPICS'	=> $this->lang->lang('VIEW_FORUM_TOPICS', (int) $topics_count),
			));
		}

        $sql_array = array(
            'SELECT'	=> 't.*, f.forum_name',

            'FROM'		=> array(
                TOPICS_WATCH_TABLE	=> 'tw',
                TOPICS_TABLE		=> 't'
            ),

            'WHERE'		=> 'tw.user_id = ' . $user['user_id'] . '
                AND t.topic_id = tw.topic_id
                AND ' . $this->db->sql_in_set('t.forum_id', $forbidden_forum_ary, true, true),

            'ORDER_BY'	=> 't.topic_last_post_time DESC, t.topic_last_post_id DESC'
        );

        $sql_array['LEFT_JOIN'] = array();
		$sql_array['LEFT_JOIN'][] = array('FROM' => array(FORUMS_TABLE => 'f'), 'ON' => 't.forum_id = f.forum_id');

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $config['topics_per_page'], $start);

		$topic_list = $topic_forum_list = $global_announce_list = $rowset = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$topic_id = (isset($row['b_topic_id'])) ? $row['b_topic_id'] : $row['topic_id'];

			$topic_list[] = $topic_id;
			$rowset[$topic_id] = $row;

			$topic_forum_list[$row['forum_id']]['topics'][] = $topic_id;

			if ($row['topic_type'] == POST_GLOBAL)
			{
				$global_announce_list[] = $topic_id;
			}
		}
		$this->db->sql_freeresult($result);

		foreach ($topic_list as $topic_id)
		{
			$row = &$rowset[$topic_id];

			$forum_id = $row['forum_id'];
			$topic_id = (isset($row['b_topic_id'])) ? $row['b_topic_id'] : $row['topic_id'];

			if ($row['topic_status'] == ITEM_MOVED && !empty($row['topic_moved_id']))
			{
				$topic_id = $row['topic_moved_id'];
			}

			// Get folder img, topic status/type related information
		/*	$folder_img = $folder_alt = $topic_type = '';*/
		/*	topic_status($row, $replies, $unread_topic, $folder_img, $folder_alt, $topic_type);*/

			$view_topic_url_params = "f=$forum_id&amp;t=$topic_id";
			$view_topic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", $view_topic_url_params);

			// Send vars to template
			$template_vars = array(
				'FORUM_ID'					=> $forum_id,
				'TOPIC_ID'					=> $topic_id,
			/*	'FIRST_POST_TIME'			=> $user->format_date($row['topic_time']),
				'LAST_POST_SUBJECT'			=> $row['topic_last_post_subject'],
				'LAST_POST_TIME'			=> $user->format_date($row['topic_last_post_time']),
				'LAST_VIEW_TIME'			=> $user->format_date($row['topic_last_view_time']),

				'TOPIC_AUTHOR'				=> get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
				'TOPIC_AUTHOR_COLOUR'		=> get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
				'TOPIC_AUTHOR_FULL'			=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
				'U_TOPIC_AUTHOR'			=> get_username_string('profile', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),

				'LAST_POST_AUTHOR'			=> get_username_string('username', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
				'LAST_POST_AUTHOR_COLOUR'	=> get_username_string('colour', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
				'LAST_POST_AUTHOR_FULL'		=> get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
				'U_LAST_POST_AUTHOR'		=> get_username_string('profile', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
*/
				'S_DELETED_TOPIC'	=> (!$row['topic_id']) ? true : false,

				'TOPIC_TITLE'		=> censor_text($row['topic_title']),
			/*	'TOPIC_TYPE'		=> $topic_type,*/
				'FORUM_NAME'		=> $row['forum_name'],

			/*	'TOPIC_IMG_STYLE'		=> $folder_img,*/
			/*	'TOPIC_FOLDER_IMG'		=> $user->img($folder_img, $folder_alt),*/
			/*	'TOPIC_FOLDER_IMG_ALT'	=> $this->lang->lang[$folder_alt],*/
			/*	'TOPIC_ICON_IMG'		=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['img'] : '',
				'TOPIC_ICON_IMG_WIDTH'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['width'] : '',
				'TOPIC_ICON_IMG_HEIGHT'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['height'] : '',

				'S_TOPIC_TYPE'			=> $row['topic_type'],*/

                'TOPIC_SELECTED'        => $selected_type_id && ($notification_type_id_names[$selected_type_id] == 'notification.type.post') && ($identifier == $topic_id),
				'U_VIEW_TOPIC'			=> $view_topic_url,
				'U_VIEW_FORUM'			=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id),
			);

			$this->template->assign_block_vars('topicrow', $template_vars);

			$pagination->generate_template_pagination(append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id'] . "&amp;t=$topic_id"), 'topicrow.pagination', 'start', count($topic_list), $this->config['posts_per_page'], 1, true, true);
		}
	}

	/**
	* Get user's notification data
	*
	* @param int $user_id The user_id of the user to get the notifications for
	*
	* @return array User's notification
	*/
	protected function get_user_email_notification_subscriptions($user_id)
	{
		$sql = 'SELECT notify, item_type
				FROM ' . $this->user_notifications_table . '
				WHERE user_id = ' . (int) $user_id . '
                    AND method = "notification.method.email"
					AND item_id = 0
                    AND notify = 1';

		$result = $this->db->sql_query($sql);
		$user_notifications = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_notifications[$row['item_type']][] = $row;
		}
		$this->db->sql_freeresult($result);

		return $user_notifications;
	}

	/**
	* Get all of the subscription types
	*
	* @return array Array of item types
	*/
	protected function get_subscription_types()
	{
		if ($this->subscription_types === null)
		{
			$this->subscription_types = array();

			foreach ($this->notification_types as $type_name => $data)
			{
				/** @var type\base $type */
				$type = $this->notification_manager->get_item_type_class($type_name);

				if ($type instanceof \phpbb\notification\type\type_interface)
				{
					$options = array_merge(array(
						'type'	=> $type,
						'id'	=> $type->get_type(),
						'lang'	=> 'NOTIFICATION_TYPE_' . strtoupper($type->get_type()),
						'group'	=> 'NOTIFICATION_GROUP_MISCELLANEOUS',
					), (($type::$notification_option !== false) ? $type::$notification_option : array()));

					$this->subscription_types[$options['group']][$options['id']] = $options;
				}
			}

			// Move Miscellaneous to the very last section
			if (isset($this->subscription_types['NOTIFICATION_GROUP_MISCELLANEOUS']))
			{
				$miscellaneous = $this->subscription_types['NOTIFICATION_GROUP_MISCELLANEOUS'];
				unset($this->subscription_types['NOTIFICATION_GROUP_MISCELLANEOUS']);
				$this->subscription_types['NOTIFICATION_GROUP_MISCELLANEOUS'] = $miscellaneous;
			}
		}

		return $this->subscription_types;
	}

    protected function get_notification_type_id_names()
    {
        $notification_type_id_names = array();

		$sql = 'SELECT notification_type_id, notification_type_name
			FROM ' . $this->notification_types_table;
		$result = $this->db->sql_query($sql, 604800); // cache for one week
		while ($row = $this->db->sql_fetchrow($result))
		{
			$notification_type_id_names[(int) $row['notification_type_id']] = $row['notification_type_name'];
		}
		$this->db->sql_freeresult($result);

        return $notification_type_id_names;
    }

}
