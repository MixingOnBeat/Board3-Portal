<?php
/**
*
* @package Board3 Portal v2.1
* @copyright (c) 2013 Board3 Group ( www.board3.de )
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace board3\portal\modules;

/**
* @package Recent
*/
class recent extends module_base
{
	/**
	* Allowed columns: Just sum up your options (Exp: left + right = 10)
	* top		1
	* left		2
	* center	4
	* right		8
	* bottom	16
	*/
	public $columns = 21;

	/**
	* Default modulename
	*/
	public $name = 'PORTAL_RECENT';

	/**
	* Default module-image:
	* file must be in "{T_THEME_PATH}/images/portal/"
	*/
	public $image_src = '';

	/**
	* module-language file
	* file must be in "language/{$user->lang}/mods/portal/"
	*/
	public $language = 'portal_recent_module';

	/**
	* custom acp template
	* file must be in "adm/style/portal/"
	*/
	public $custom_acp_tpl = '';

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template */
	protected $template;

	/** @var php file extension */
	protected $php_ext;

	/** @var phpbb root path */
	protected $phpbb_root_path;

	/**
	* Construct a recent object
	*
	* @param \phpbb\auth\auth $auth phpBB auth
	* @param \phpbb\config\config $config phpBB config
	* @param \phpbb\db\driver $db phpBB db driver
	* @param \phpbb\request\request $request phpBB request
	* @param \phpbb\template $template phpBB template
	* @param string $phpbb_root_path phpBB root path
	* @param string $phpEx php file extension
	*/
	public function __construct($auth, $config, $db, $request, $template, $phpbb_root_path, $phpEx)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;
	}

	/**
	* @inheritdoc
	*/
	public function get_template_center($module_id)
	{
		//
		// Exclude forums
		//
		$sql_where = '';
		if ($this->config['board3_recent_forum_' . $module_id] > 0)
		{
			$exclude_forums = explode(',', $this->config['board3_recent_forum_' . $module_id]);

			$sql_where = ' AND ' . $this->db->sql_in_set('forum_id', array_map('intval', $exclude_forums), ($this->config['board3_recent_exclude_forums_' . $module_id]) ? true : false);
		}

		// Get a list of forums the user cannot read
		$forum_ary = array_unique(array_keys($this->auth->acl_getf('!f_read', true)));

		// Determine first forum the user is able to read (must not be a category)
		$sql = 'SELECT forum_id
			FROM ' . FORUMS_TABLE . '
			WHERE forum_type = ' . FORUM_POST;

		$forum_sql = '';
		if (sizeof($forum_ary))
		{
			$sql .= ' AND ' . $this->db->sql_in_set('forum_id', $forum_ary, true);
			$forum_sql = ' AND ' . $this->db->sql_in_set('t.forum_id', $forum_ary, true);
		}

		$result = $this->db->sql_query_limit($sql, 1);
		$g_forum_id = (int) $this->db->sql_fetchfield('forum_id');
		$this->db->sql_freeresult($result);

		//
		// Recent announcements
		//
		$sql = 'SELECT topic_title, forum_id, topic_id
			FROM ' . TOPICS_TABLE . ' t
			WHERE topic_status <> ' . FORUM_LINK . '
				AND topic_visibility = ' . ITEM_APPROVED . '
				AND (topic_type = ' . POST_ANNOUNCE . ' OR topic_type = ' . POST_GLOBAL . ')
				AND topic_moved_id = 0
				' . $sql_where . '' .  $forum_sql . '
			ORDER BY topic_time DESC';
		$result = $this->db->sql_query_limit($sql, $this->config['board3_max_topics_' . $module_id]);

		while(($row = $this->db->sql_fetchrow($result)) && ($row['topic_title']))
		{
			// auto auth
			if (($this->auth->acl_get('f_read', $row['forum_id'])) || ($row['forum_id'] == '0'))
			{
				$this->template->assign_block_vars('latest_announcements', array(
					'TITLE'			=> character_limit($row['topic_title'], $this->config['board3_recent_title_limit_' . $module_id]),
					'FULL_TITLE'	=> censor_text($row['topic_title']),
					'U_VIEW_TOPIC'	=> append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 'f=' . (($row['forum_id'] == 0) ? $g_forum_id : $row['forum_id']) . '&amp;t=' . $row['topic_id'])
				));
			}
		}
		$this->db->sql_freeresult($result);

		//
		// Recent hot topics
		//
		$sql = 'SELECT topic_title, forum_id, topic_id
			FROM ' . TOPICS_TABLE . ' t
			WHERE topic_visibility = ' . ITEM_APPROVED . '
				AND topic_posts_approved >' . $this->config['hot_threshold'] . '
				AND topic_moved_id = 0
				' . $sql_where . '' .  $forum_sql . '
			ORDER BY topic_time DESC';
		$result = $this->db->sql_query_limit($sql, $this->config['board3_max_topics_' . $module_id]);

		while(($row = $this->db->sql_fetchrow($result)) && ($row['topic_title']))
		{
			// auto auth
			if (($this->auth->acl_get('f_read', $row['forum_id'])) || ($row['forum_id'] == '0'))
			{
				$this->template->assign_block_vars('latest_hot_topics', array(
					'TITLE'			=> character_limit($row['topic_title'], $this->config['board3_recent_title_limit_' . $module_id]),
					'FULL_TITLE'	=> censor_text($row['topic_title']),
					'U_VIEW_TOPIC'	=> append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 'f=' . (($row['forum_id'] == 0) ? $g_forum_id : $row['forum_id']) . '&amp;t=' . $row['topic_id'])
				));
			}
		}
		$this->db->sql_freeresult($result);

		//
		// Recent topic (only show normal topic)
		//
		$sql = 'SELECT topic_title, forum_id, topic_id
			FROM ' . TOPICS_TABLE . ' t
			WHERE topic_status <> ' . ITEM_MOVED . '
				AND topic_visibility = ' . ITEM_APPROVED . '
				AND topic_type = ' . POST_NORMAL . '
				AND topic_moved_id = 0
				' . $sql_where . '' .  $forum_sql . '
			ORDER BY topic_time DESC';
		$result = $this->db->sql_query_limit($sql, $this->config['board3_max_topics_' . $module_id]);

		while(($row = $this->db->sql_fetchrow($result)) && ($row['topic_title']))
		{
			// auto auth
			if (($this->auth->acl_get('f_read', $row['forum_id'])) || ($row['forum_id'] == '0'))
			{
				$this->template->assign_block_vars('latest_topics', array(
					'TITLE'			=> character_limit($row['topic_title'], $this->config['board3_recent_title_limit_' . $module_id]),
					'FULL_TITLE'	=> censor_text($row['topic_title']),
					'U_VIEW_TOPIC'	=> append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 'f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id'])
				));
			}
		}
		$this->db->sql_freeresult($result);

		return 'recent_center.html';
	}

	/**
	* @inheritdoc
	*/
	public function get_template_acp($module_id)
	{
		return array(
			'title'	=> 'ACP_PORTAL_RECENT_SETTINGS',
			'vars'	=> array(
				'legend1'							=> 'ACP_PORTAL_RECENT_SETTINGS',
				'board3_max_topics_' . $module_id				=> array('lang' => 'PORTAL_MAX_TOPIC',			'validate' => 'int',		'type' => 'text:3:3',		'explain' => true),
				'board3_recent_title_limit_' . $module_id		=> array('lang' => 'PORTAL_RECENT_TITLE_LIMIT',	'validate' => 'int',		'type' => 'text:3:3',		'explain' => true),
				'board3_recent_forum_' . $module_id				=> array('lang' => 'PORTAL_RECENT_FORUM',		'validate' => 'string',		'type' => 'custom',			'explain' => true, 'method' => 'select_forums', 'submit' => 'store_selected_forums'),
				'board3_recent_exclude_forums_' . $module_id	=> array('lang' => 'PORTAL_EXCLUDE_FORUM',		'validate' => 'bool',		'type' => 'radio:yes_no',	'explain' => true),
			)
		);
	}

	/**
	* @inheritdoc
	*/
	public function install($module_id)
	{
		set_config('board3_max_topics_' . $module_id, 10);
		set_config('board3_recent_title_limit_' . $module_id, 100);
		set_config('board3_recent_forum_' . $module_id, '');
		set_config('board3_recent_exclude_forums_' . $module_id, 1);
		return true;
	}

	/**
	* @inheritdoc
	*/
	public function uninstall($module_id, $db)
	{
		$del_config = array(
			'board3_max_topics_' . $module_id,
			'board3_recent_title_limit_' . $module_id,
			'board3_recent_forum_' . $module_id,
			'board3_recent_exclude_forums_' . $module_id,
		);
		$sql = 'DELETE FROM ' . CONFIG_TABLE . '
			WHERE ' . $db->sql_in_set('config_name', $del_config);
		return $db->sql_query($sql);
	}

	/**
	* Create forum select box
	*
	* @param mixed $value Value of input
	* @param string $key Key name
	* @param int $module_id Module ID
	*
	* @return null
	*/
	public function select_forums($value, $key, $module_id)
	{
		$forum_list = make_forum_select(false, false, true, true, true, false, true);

		$selected = array();
		if(isset($this->config[$key]) && strlen($this->config[$key]) > 0)
		{
			$selected = explode(',', $this->config[$key]);
		}
		// Build forum options
		$s_forum_options = '<select id="' . $key . '" name="' . $key . '[]" multiple="multiple">';
		foreach ($forum_list as $f_id => $f_row)
		{
			$s_forum_options .= '<option value="' . $f_id . '"' . ((in_array($f_id, $selected)) ? ' selected="selected"' : '') . (($f_row['disabled']) ? ' disabled="disabled" class="disabled-option"' : '') . '>' . $f_row['padding'] . $f_row['forum_name'] . '</option>';
		}
		$s_forum_options .= '</select>';

		return $s_forum_options;

	}

	/**
	* Store selected forums
	*
	* @param string $key Key name
	* @param int $module_id Module ID
	*
	* @return null
	*/
	public function store_selected_forums($key, $module_id)
	{
		// Get selected extensions
		$values = $this->request->variable($key, array(0 => ''));

		$news = implode(',', $values);

		set_config($key, $news);
	}
}