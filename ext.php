<?php
/**
*
* @package phpBB Extension - Enhanced Notification Emails
* @copyright (c) 2019 v12mike
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace v12mike\enhancednotificationemails;

class ext extends \phpbb\extension\base
{
	/**
	* Enable extension if phpBB version requirement is met
	*
	* @return bool
	*/
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return version_compare($config['version'], '3.2.0', '>=') && version_compare($config['version'], '3.3.0', '<');
	}
}



