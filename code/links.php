<?php
/**
*
* This file is part of the .
*
* @copyright (c)  <https://>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace v12mike\enhancednotificationemails\code;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class links
{
	/* @var \phpbb\controller\helper */
//	protected $helper;

	/* @var \phpbb\template\template */
//	protected $template;

	/* @var \phpbb\user */
//	protected $user;

	/* @var */
//	protected $phpbb_root_path;

//	public $u_action;


	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper  $helper
	 * @param \phpbb\template\template  $template
	 * @param \phpbb\user				$user
    * @param \phpbb\notification\manager $notification_manager Controller helper object 
	 * @param 							$phpbb_root_path
	 */
/*	public function __construct(\phpbb\controller\helper $helper, ContainerInterface $phpbb_container, \phpbb\template\template $template, \phpbb\request\request_interface $request, \phpbb\user $user, \phpbb\language\language $lang, \phpbb\notification\manager $notification_manager, $phpbb_root_path)
	{
		$this->helper   = $helper;
        $this->phpbb_container = $phpbb_container; 
		$this->template = $template;
		$this->request 	= $request;
		$this->user 	= $user;
		$this->language = $lang;
        $this->notification_manager = $notification_manager;
		$this->phpbb_root_path = $phpbb_root_path;
	}*/


    protected function create_token(int $target_user_id, int $notification_type, int $identifier, int $time_stamp)
    {
        $salt = 1234567;
        $token = sha1($target_user_id . $notification_type . $identifier . $time_stamp . $salt);
        $token = substr($token, -10);
        return $token;
    }

    protected function create_timestamp()
    {
        $timestamp = intdiv(time(), (3600 * 24)) - 18000;
        return $timestamp;
    }
    
     public function validate_timestamp(int $timestamp)
     {
         $validity_period = 7; //days
         if (($this->create_timestamp() - $timestamp) > $validity_period)
         {
             return false;
         }
         return true;
     }


    public function validate_unsubscribe_token(int $target_user_id, int $notification_type, int $identifier, int $time_stamp, $token)
    {
        if ($token == $this->create_token($target_user_id, $notification_type, $identifier, $time_stamp))
        {
            return true;
        }
        return false;
    }

    public function create_unsubscribe_string(int $notified_user_id, int $notification_type_id, int $notification_identifier)
    {
        $timestamp = $this->create_timestamp();
        $token = $this->create_token($notified_user_id, $notification_type_id, $notification_identifier, $timestamp);

        $unsubscribe_string = "{$notified_user_id}/{$notification_type_id}/{$notification_identifier}/{$timestamp}/{$token}";
        $this->unsubscribe_string = $unsubscribe_string;

        return $unsubscribe_string;
    }

}
