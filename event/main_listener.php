<?php
/**
*
* @package phpBB Extension - Enhanced Notification Emails
* @copyright (c) 2019 v12mike
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace v12mike\enhancednotificationemails\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use v12mike\enhancednotificationemails\code;

/**
* Event listener
*/
class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.modify_email_headers'	=> 'update_email_headers',
            'core.notification_post_modify_template_vars' => 'update_template_variables'
		);
	}

	/**
	* Constructor
	*
    * @param \phpbb\notification\manager $notification_manager Controller helper object 
	* @param \phpbb\template\template	$template	Template object
	*/
	public function __construct(\phpbb\notification\manager $notification_manager, $unsubscribe_links)
	{
        $this->notification_manager = $notification_manager;
        $this->unsubscribe_links = $unsubscribe_links;
	}

/*	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'acme/demo',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}*/

	public function update_email_headers($event)
	{
        $headers = $event['headers'];
        $headers_updated = false;
        foreach ($headers as & $header)
        {
            $matches = array();
            preg_match('%List-Unsubscribe: <(https?://.+)/unsubscribe/(\d+)/(\d+)/(\d+)/(\d+)/(\d+)>%', 
                       $header, 
                       $matches);

            if (isset($matches[1]) && isset($this->unsubscribe_links->unsubscribe_string))
            {
               $header = "List-Unsubscribe:
<mailto:unsubscribe@frenchcarforum.co.uk?subject={$this->unsubscribe_links->unsubscribe_string}>,
<{$matches[1]}/unsubscribe/{$this->unsubscribe_links->unsubscribe_string}>";
                $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
                $headers_updated = true;
                break;
            }
        }
        if (!$headers_updated && isset($this->unsubscribe_links->unsubscribe_string))
        {
            $headers[] = "List-Unsubscribe:
<mailto:unsubscribe@frenchcarforum.co.uk?subject={$this->unsubscribe_links->unsubscribe_string}>,
<" . generate_board_url() . "/unsubscribe/{$this->unsubscribe_links->unsubscribe_string}>";
            $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        }
        $event['headers'] = $headers;
	}


	public function update_template_variables($event)
    {
        $template_vars = $event['template_vars'];

        $unsubscribe_string = $this->unsubscribe_links->create_unsubscribe_string($event['notified_user_id'], $event['notification_type_id'], $event['topic_id']);

        if (isset($template_vars['U_NOTIFICATION_SETTINGS']))
        {
            $template_vars['U_NOTIFICATION_SETTINGS'] = generate_board_url() . "/unsubscribe/{$unsubscribe_string}";
        }
        if (isset($template_vars['U_STOP_WATCHING_FORUM']))
        {
            $template_vars['U_STOP_WATCHING_FORUM'] = generate_board_url() . "/unsubscribe/{$unsubscribe_string}";
        }
        if (isset($template_vars['U_STOP_WATCHING_TOPIC']))
        {
            $template_vars['U_STOP_WATCHING_TOPIC'] = generate_board_url() . "/unsubscribe/{$unsubscribe_string}";
        }
        $event['template_vars'] = $template_vars;
    }
}
