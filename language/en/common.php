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
	'ENE_NOTIFICATION_OPTIONS'			=> 'Unsubscribe from notification emails',
	'ENE_NOTIFICATION_OPTIONS_EXPLAIN'	=> 'You may unsubscribe from categories of notifications by checking the options in the upper panel on this page. For the specific categories of "subscribed forums" and "subscribed topics" you may unsubscribe from each forum and topic by checking the boxes in the lower panel of this page',

	'ACP_DEMO_TITLE'			=> 'Demo Module',
));
