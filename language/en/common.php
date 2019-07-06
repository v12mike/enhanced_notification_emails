<?php
/**
*
* @package phpBB Extension - Enhanced Notification Emails
* @copyright (c) 2019 v12mike
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ENE_NOTIFICATION_OPTIONS'			=> 'Unsubscribe from notification emails for user: ',
	'ENE_NOTIFICATION_OPTIONS_EXPLAIN'	=> 'Below is the set of notification categories that you are subscribed to.  You may unsubscribe from categories of notifications by checking the options in the panel below and clicking the UNSUBSCRIBE button.',
	'ENE_NO_SUBSCRIBED_NOTIFICATION_TYPES' => 'You are not subscribed to any categories for email notification',
));
