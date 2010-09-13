<?php
/**
* @package Portal - Donation
* @version $Id$
* @copyright (c) 2009, 2010 Board3 Portal Team
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package Donation
*/
class portal_donation_module
{
	/**
	* Allowed columns: Just sum up your options (Exp: left + right = 10)
	* top		1
	* left		2
	* center	4
	* right		8
	* bottom	16
	*/
	var $columns = 31;

	/**
	* Default modulename
	*/
	var $name = 'DONATION';

	/**
	* Default module-image:
	* file must be in "{T_THEME_PATH}/images/portal/"
	*/
	var $image_src = 'portal_donation.png';

	/**
	* module-language file
	* file must be in "language/{$user->lang}/mods/portal/"
	*/
	var $language = 'portal_donation_module';

	function get_template_center($module_id)
	{
		global $config, $template;
		
		$template->assign_var('PAY_ACC', $config['board3_pay_acc']);

		return 'donation_center.html';
	}

	function get_template_side($module_id)
	{
		global $config, $template;

		$template->assign_var('PAY_ACC', $config['board3_pay_acc']);

		return 'donation_side.html';
	}

	function get_template_acp($module_id)
	{
		return array(
			'title'	=> 'ACP_PORTAL_PAYPAL_SETTINGS',
			'vars'	=> array(
				'legend1'							=> 'ACP_PORTAL_PAYPAL_SETTINGS',
				'board3_pay_acc'					=> array('lang' => 'PORTAL_PAY_ACC'						,	'validate' => 'string',		'type' => 'text:25:100',	 'explain' => true),
			)
		);
	}

	/**
	* API functions
	*/
	function install($module_id)
	{
		set_config('board3_pay_acc', 'your@paypal.com');
		return true;
	}

	function uninstall($module_id)
	{
		global $db;

		$del_config = array(
			'board3_pay_acc',
		);
		$sql = 'DELETE FROM ' . CONFIG_TABLE . '
			WHERE ' . $db->sql_in_set('config_name', $del_config);
		return $db->sql_query($sql);
	}
}

?>