<?php
/**
*
* @package phpBB Extension - Enhanced Notification Emails
* @copyright (c) 2019 v12mike
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace v12mike\enhancednotificationemails;

class release_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['notification_email_expiry_days']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\alpha2');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('notification_email_expiry_days', 30)),

		/*	array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_DEMO_TITLE'
			)),*/
			/*array('module.add', array(
				'acp',
				'ACP_DEMO_TITLE',
				array(
					'module_basename'	=> '\acme\demo\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),*/
		);
	}
}
