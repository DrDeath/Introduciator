<?php
/**
 *
 * @package phpBB Extension - Introduciator Extension
 * @author Feneck91 (Stéphane Château) feneck91@free.fr
 * @copyright (c) 2013 @copyright (c) 2014 Feneck91
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace feneck91\introduciator\acp;

/**
 * Module to manage ACP extension configuration.
 */
class introduciator_module
{
	/**
	 * URL of web site where download the latest version file info
	 */
	protected $url_version_check		= 'feneck91.free.fr';
	
	/**
	 * Folder in web site where download the latest version file info
	 */
	protected $folder_version_check		= '/phpbb';
	
	/**
	 * File name to download the latest version file info
	 */
	protected $file_version_check		= 'introduciator_extension_version.txt';
	
	/**
	 *  Action
	 */
	public $u_action;

	/**
	 * Template name
	 */
	public $tpl_name;
	
	/**
	 * @var \Symfony\Component\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 * @var \phpbb\language\language
	 */
	protected $language;

	/**
	 * @var \phpbb\request\request
	 */
	protected $request;
	
	/**
	 * @var \phpbb\template\template
	 */
	protected $template;

	public function main($id, $mode)
	{
		global $phpbb_container;
		$this->container = $phpbb_container;
		$this->language = $phpbb_container->get('language');
		$this->request = $phpbb_container->get('request');
		$this->template = $this->container->get('template');
		$user = $this->container->get('user');
		$config = $this->container->get('config');
		$phpbb_log = $this->container->get('log');

		// Add a secret token to the form
		// This functions adds a secret token to any form, a token which should be checked after
		// submission with the check_form_key function to ensure that the received data is the same as the submitted.
		add_form_key('feneck91/introduciator');

		switch ($mode)
		{
			case 'general':
				global $phpbb_admin_path, $phpEx;

				// Set the template for this module
				$this->tpl_name = 'acp_introduciator_general'; // Template file : adm/style/introduciator/acp_introduciator_general.htm
				$this->page_title = 'INTRODUCIATOR_GENERAL';
				
				// Check if a new version of this extension is available
				$latest_version_info = $this->obtain_latest_version_info($this->request->variable('introduciator_versioncheck_force', false));

				if ($latest_version_info === false || !function_exists('phpbb_version_compare'))
				{
					$this->template->assign_var('S_INTRODUCIATOR_VERSIONCHECK_FAIL', true);
				}
				else
				{
					$latest_version_info = explode("\n", $latest_version_info);
					$version_check = $this->get_update_information('url-', $latest_version_info);
					$infos = $this->get_update_information('info-', $latest_version_info);

					$this->template->assign_vars(array(
						'S_INTRODUCIATOR_VERSION_UP_TO_DATE'	=> phpbb_version_compare(trim($latest_version_info[0]), $config['introduciator_extension_version'], '<='),
						'S_INTRODUCIATOR_VERSIONCHECK_URL_FOUND'=> $version_check[1],
						'U_INTRODUCIATOR_VERSIONCHECK'			=> $version_check[0],
						'L_INTRODUCIATOR_UPDATE_VERSION'		=> trim($latest_version_info[0]),
						'L_INTRODUCIATOR_UPDATE_FILENAME'		=> trim(sizeof($latest_version_info) < 3 ? '' : $latest_version_info[2]),
						'U_INTRODUCIATOR_UPDATE_URL'			=> trim(sizeof($latest_version_info) < 4 ? '' : $latest_version_info[3]),
						'L_INTRODUCIATOR_UPDATE_INFORMATION'	=> $infos[0],
					));
				}

				$this->template->assign_vars(array(
					// Display general page content into ACP Extensions tab
					'S_INTRODUCIATOR_GENERAL_PAGES'			=> true,

					// Current version of this extension
					'INTRODUCIATOR_VERSION'					=> $config['introduciator_extension_version'],
					// Install date of this extension
					'INTRODUCIATOR_INSTALL_DATE'			=> $user->format_date($config['introduciator_install_date']),

					// Check force URL
					// i is the ID of this extension's module (-feneck91-introduciator-acp-introduciator_module) / mode is the sub item
					'U_INTRODUCIATOR_VERSIONCHECK_FORCE'	=> append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-feneck91-introduciator-acp-introduciator_module&amp;mode=' . $mode . '&amp;introduciator_versioncheck_force=1'),
					'U_ACTION'								=> $this->u_action,
				));
			break;

			case 'configuration':
				global $db, $phpbb_root_path; // Database, Root path

				// Get Action
				$action = $this->request->variable('action', '');

				// Set the template for this module
				$this->tpl_name = 'acp_introduciator_configuration'; // Template file : adm/style/introduciator/acp_introduciator_configuration.htm
				$this->page_title = 'INTRODUCIATOR_CONFIGURATION';

				// Display configuration page content into ACP Extensions tab
				$this->template->assign_var('S_CONFIGURATION_PAGES', true);

				// Get Introduciator class helper
				$introduciator_helper = $this->container->get('feneck91.introduciator.helper');

				// If no action, display configuration
				if (empty($action))
				{	// no action or update current
					$dp_data = array();
					$params = $introduciator_helper->introduciator_getparams(true);
					$this->template->assign_vars(array(
						'INTRODUCIATOR_EXTENSION_ACTIVATED'										=> $params['introduciator_allow'],
						'INTRODUCIATOR_INTRODUCTION_MANDATORY'									=> $params['is_introduction_mandatory'],
						'INTRODUCIATOR_CHECK_DELETE_FIRST_POST_ACTIVATED'						=> $params['is_check_delete_first_post'],
						'INTRODUCIATOR_POSTING_APPROVAL_LEVEL_NO_APPROVAL_ENABLED'				=> $params['posting_approval_level'] == $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_NO_APPROVAL,
						'INTRODUCIATOR_POSTING_APPROVAL_LEVEL_APPROVAL_ENABLED'					=> $params['posting_approval_level'] == $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_APPROVAL,
						'INTRODUCIATOR_POSTING_APPROVAL_LEVEL_NO_APPROVAL_WITH_EDIT_ENABLED'	=> $params['posting_approval_level'] == $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_APPROVAL_WITH_EDIT,
						'INTRODUCIATOR_DISPLAY_EXPLANATION_ENABLED'								=> $params['is_explanation_enabled'],
						'INTRODUCIATOR_USE_PERMISSIONS'											=> $params['is_use_permissions'],
						'INTRODUCIATOR_INCLUDE_GROUPS_SELECTED'									=> $params['is_include_groups'],
						'INTRODUCIATOR_ITEM_IGNORED_USERS'										=> $params['ignored_users'],
						'INTRODUCIATOR_EXPLANATION_MESSAGE_TITLE'								=> $params['explanation_message_title'],
						'INTRODUCIATOR_EXPLANATION_MESSAGE_TEXT'								=> $params['explanation_message_text'],
						'INTRODUCIATOR_EXPLANATION_IS_DISPLAY_RULES_ENABLED'					=> $params['is_explanation_display_rules'],
						'INTRODUCIATOR_EXPLANATION_MESSAGE_RULES_TITLE'							=> $params['explanation_message_rules_title'],
						'INTRODUCIATOR_EXPLANATION_MESSAGE_RULES_TEXT'							=> $params['explanation_message_rules_text'],
						'U_ACTION'																=> $this->u_action,
					));

					// Add all forums
					$this->add_all_forums($params['fk_forum_id'], 0, 0);

					// Add all groups
					$this->add_all_groups($introduciator_helper);

					$s_hidden_fields = build_hidden_fields(array(
							'action'				=> 'update',					// Action
						));

					$this->template->assign_var('S_HIDDEN_FIELDS', $s_hidden_fields);
				}
				else
				{	// Action !
					if (!check_form_key('feneck91/introduciator'))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}
					switch ($action)
					{
						case 'update' :
							// User has request an update : write it into database
							// Update Database
							$is_enabled									= $this->request->variable('extension_activated', false);
							$is_check_introduction_mandatory_activated  = $this->request->variable('check_introduction_mandatory_activated', true);
							$is_check_delete_first_post_activated		= $this->request->variable('check_delete_first_post_activated', false);
							$fk_forum_id								= $this->request->variable('forum_choice', 0);
							$posting_approval_level						= $this->request->variable('posting_approval_level', $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_NO_APPROVAL);
							$is_explanation_enabled						= $this->request->variable('display_explanation', false);
							$is_use_permissions							= $this->request->variable('is_use_permissions', true);
							$is_include_groups							= $this->request->variable('include_groups', true);
							$groups										= $this->request->variable('groups_choices', array('' => 0)); // Array of IDs of selected groups
							$ignored_users								= substr(utf8_normalize_nfc($this->request->variable('ignored_users', '')), 0, 255);
							$explanation_message_title					= utf8_normalize_nfc($this->request->variable('explanation_message_title', '', true));
							$explanation_message_text					= utf8_normalize_nfc($this->request->variable('explanation_message_text', '', true));
							$explanation_display_rules_enabled			= $this->request->variable('explanation_display_rules_enabled', false);
							$explanation_message_rules_title			= utf8_normalize_nfc($this->request->variable('explanation_message_rules_title', '', true));
							$explanation_message_rules_text				= utf8_normalize_nfc($this->request->variable('explanation_message_rules_text', '', true));

							if ($is_enabled && $fk_forum_id === 0)
							{
								trigger_error($this->language->lang('INTRODUCIATOR_ERROR_MUST_SELECT_FORUM') . adm_back_link($this->u_action), E_USER_WARNING);
							}

							// Verify message rules texts and convert with BBCode

							// Replace all url by real fake urls
							$introduciator_helper->replace_all_by(
								array(
									&$explanation_message_title,
									&$explanation_message_text,
									&$explanation_message_rules_title,
									&$explanation_message_rules_text,
								),
								array(
									'%forum_url%'	=> 'http://aghxkfps.com', // Make link work if placed into [url]
									'%forum_post%'	=> 'http://dqsdfzef.com', // Make link work if placed into [url]
								)
							);

							$explanation_message_array = array(
								'introduciator_explanation_message_title'			=> $explanation_message_title,
								'introduciator_explanation_message_text'			=> $explanation_message_text,
								'introduciator_explanation_message_rules_title'		=> $explanation_message_rules_title,
								'introduciator_explanation_message_rules_text'		=> $explanation_message_rules_text,
							);

							// Verify all user inputs
							$explanation_message_array_result = array();
							foreach ($explanation_message_array as $key => $value)
							{
								$new_uid = $bitfield = $bbcode_options = '';
								$texts_errors = generate_text_for_storage($value, $new_uid, $bitfield, $bbcode_options, true, true, true);
								if (sizeof($texts_errors))
								{	// Errors occured, show them to the user (split br else MPV found an error because /> is not written
									trigger_error(implode('<b' . 'r>', $texts_errors) . adm_back_link($this->u_action), E_USER_WARNING);
								}
								// Merge results into array
								$explanation_message_array_result = array_merge($explanation_message_array_result, array(
									$key						=> $value,
									$key . '_uid'				=> $new_uid,
									$key . '_bitfield'			=> $bitfield,
									$key . '_bbcode_options'	=> $bbcode_options,
								));
								
								if (strlen($value) > 255)
								{	// Errors occured, show them to the user.
									trigger_error($this->language->lang('INTRODUCIATOR_ERROR_TOO_LONG_TEXT') . adm_back_link($this->u_action), E_USER_WARNING);
								}
							}

							if ($posting_approval_level != $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_NO_APPROVAL && $posting_approval_level != $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_APPROVAL && $posting_approval_level != $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_APPROVAL_WITH_EDIT)
							{	// Verify the level approval values => No correct value ? Set to INTRODUCIATOR_POSTING_APPROVAL_LEVEL_NO_APPROVAL
								$posting_approval_level = $introduciator_helper::INTRODUCIATOR_POSTING_APPROVAL_LEVEL_NO_APPROVAL;
							}
									
							$config->set('introduciator_allow', $is_enabled ? '1' : '0'); // Set the activation extension config
							$config->set('introduciator_is_introduction_mandatory', $is_check_introduction_mandatory_activated ? '1' : '0');
							$config->set('introduciator_is_check_delete_first_post', $is_check_delete_first_post_activated ? '1' : '0');
							$config->set('introduciator_fk_forum_id', $fk_forum_id);
							$config->set('introduciator_posting_approval_level', $posting_approval_level);
							$config->set('introduciator_is_explanation_enabled', $is_explanation_enabled ? '1' : '0');
							$config->set('introduciator_is_use_permissions', $is_use_permissions ? '1' : '0');
							$config->set('introduciator_is_include_groups', $is_include_groups ? '1' : '0');
							$config->set('introduciator_ignored_users', $ignored_users);
							$config->set('introduciator_is_explanation_display_rules', $explanation_display_rules_enabled ? '1' : '0');

							// Set results into config
							foreach ($explanation_message_array_result as $key => $value)
							{
								$config->set($key, $value);
							}

							// Update INTRODUCIATOR_GROUPS_TABLE
							// 1> Remove all entries
							$sql = 'DELETE FROM ' . $introduciator_helper->Get_INTRODUCIATOR_GROUPS_TABLE();
							$db->sql_query($sql);

							// 2> Add all entries
							$values = array();
							foreach ($groups as &$group)
							{	// Create elements to add by row
								$values[] = array('fk_group' => (int) $group);
							}
							// Create and execute SQL request
							$db->sql_multi_insert($introduciator_helper->Get_INTRODUCIATOR_GROUPS_TABLE(), $values);

							$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_INTRODUCIATOR_UPDATED');
							trigger_error($this->language->lang('INTRODUCIATOR_CP_UPDATED') . adm_back_link($this->u_action));
							break;

						default:
							trigger_error($this->language->lang('NO_MODE') . adm_back_link($this->u_action));
							break;
				} // End of switch Action
			}
		}
	}

	function add_all_forums($fk_selected_forum_id, $id_parent, $level)
	{
		global $db;

		if ($id_parent === 0)
		{	// Add deactivation item
			$this->template->assign_block_vars('forums', array(
				'FORUM_NAME'	=> $this->language->lang('INTRODUCIATOR_NO_FORUM_CHOICE'),
				'FORUM_ID'		=> (int) 0,
				'SELECTED'		=> ($fk_selected_forum_id === 0),
				'CAN_SELECT'	=> true,
				'TOOLTIP'		=> $this->language->lang('INTRODUCIATOR_NO_FORUM_CHOICE_TOOLTIP'),
			));
		}

		// Add all forums
		$sql = 'SELECT forum_name, forum_id, forum_desc, forum_type
				FROM ' . FORUMS_TABLE . '
				WHERE parent_id = ' . (int) $id_parent;

		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('forums', array(
				'FORUM_NAME'	=> str_repeat("&nbsp;", 4 * $level) . $row['forum_name'],
				'FORUM_ID'		=> (int) $row['forum_id'],
				'SELECTED'		=> ($fk_selected_forum_id == $row['forum_id']),
				'CAN_SELECT'	=> ((int) $row['forum_type']) == FORUM_POST,
				'TOOLTIP'		=> $row['forum_desc'],
			));
			$this->add_all_forums($fk_selected_forum_id, $row['forum_id'], $level + 1);
		}
		$db->sql_freeresult($result);
	}

	/**
	 * Find all groups to propose it to the user.
	 *
	 * Add all elements into the template.
	 */
	function add_all_groups($introduciator_helper)
	{
		global $db;

		$sql = 'SELECT group_id, group_desc
			FROM ' . GROUPS_TABLE;

		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('group', array(
				'NAME'		=> get_group_name($row['group_id']),
				'ID'		=> (int) $row['group_id'],
				'SELECTED'	=> $introduciator_helper->is_group_selected($row['group_id']),
				'TOOLTIP'	=> $row['group_desc'],
			));
		}
		$db->sql_freeresult($result);
	}

	/**
	 * Obtains the latest version information.
	 *
	 * @param bool $force_update Ignores cached data. Defaults to false.
	 * @param int $ttl Cache version information for $ttl seconds. Defaults to 86400 (24 hours).
	 *
	 * @return string | false Version info on success, false on failure.
	 */
	function obtain_latest_version_info($force_update = false, $ttl = 86400)
	{
		global $cache;

		$info = $cache->get('introduciator_version_check');

		if ($info === false || $force_update)
		{
			$errstr = '';
			$errno = 0;

			$info = get_remote_file($this->url_version_check, $this->folder_version_check, $this->file_version_check, $errstr, $errno);

			if ($info === false)
			{
				$cache->destroy('introduciator_version_check');
				return false;
			}

			$cache->put('introduciator_version_check', $info, $ttl);
		}

		return $info;
	}

	/**
	 * Get the update information string from text loaded from update web site.
	 *
	 * The language is written at the beginning of each lines, like [en] ou [fr].
	 *
	 * @param string $tag the tag to found. Searching [$tag{language name}] at the beginning of the line.
	 * @param array $latest_version_info Array of string, the informations begins at line 2.
	 * @return An array with:
	 *   [0] The string into the correct language. English if the current language is not found. Error message if default language was not found
	 *   [1] Indicate if the string (default or not) was found or not (true / false).
	 */
	function get_update_information($tag, $latest_version_info)
	{
		global $tag_and_lang, $tag_and_lang_en, $tag_len;

		$information = $this->language->lang('INTRODUCIATOR_NO_UPDATE_INFO_FOUND');
		$found = false;

		$tag_and_lang = '[' . $tag . $this->language->lang('USER_LANG') . ']';
		$tag_and_lang_en =  '[' . $tag . 'en]';
		$tag_len = strlen($tag_and_lang_en);

		for ($index = 4;$index < sizeof($latest_version_info);++$index)
		{
			if (strlen($latest_version_info[$index]) > $tag_len)
			{
				$line_lang = substr($latest_version_info[$index], 0, $tag_len);
				if ($line_lang === $tag_and_lang)
				{
					$information = substr($latest_version_info[$index], $tag_len, strlen($latest_version_info[$index]) - $tag_len);
					$found = true;
					break; // Found, quit the for
				}
				else if ($line_lang === $tag_and_lang_en)
				{	// English by default if found
					$information = substr($latest_version_info[$index], $tag_len, strlen($latest_version_info[$index]) - $tag_len);
					$found = true;
				}
			}
		}

		return array(
			str_replace('\\n', '<br/>', $information),
			$found,
		);
	}
}