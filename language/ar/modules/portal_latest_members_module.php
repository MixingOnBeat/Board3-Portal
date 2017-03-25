<?php
/**
*
* @package Board3 Portal v2.1 - Latest Members
* @copyright (c) 2013 Board3 Group ( www.board3.de )
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
* Translated By : Bassel Taha Alhitary - www.alhitary.net
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
$lang = array_merge($lang, array(
	'LATEST_MEMBERS'	=> 'آخر الأعضاء المُسجلين',

	// ACP
	'ACP_PORTAL_MEMBERS_SETTINGS'			=> 'إعدادات آخر الأعضاء المُسجلين',
	'ACP_PORTAL_MEMBERS_SETTINGS_EXP'	=> 'من هنا تستطيع تخصيص موديل آخر الأعضاء المُسجلين.',
	'PORTAL_MAX_LAST_MEMBER'				=> 'عدد الأعضاء ',
	'PORTAL_MAX_LAST_MEMBER_EXP'		=> 'الحد الأقصى لعدد آخر الأعضاء المُسجلين الذين سيتم عرضهم. الصفر يعني عدد غير محدود',
));