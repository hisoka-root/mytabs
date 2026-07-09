<?php

	/*
	 *	MyTabs - Tabbed forum browsing plugin for MyBB
	 *
	 *	Copyright (C) 2026  hisoka (root@hisoka.lol)
	 *	https://github.com/hisoka-root
	 *
	 *	Originally created by Ethan / FatalMessiah at MyBBPlugins
	 *	http://www.mybbplug.in/s/
	 *
	 *	This program is free software: you can redistribute it and/or modify
	 *	it under the terms of the GNU General Public License as published by
	 *	the Free Software Foundation, either version 3 of the License, or
	 *	(at your option) any later version.
	 *
	 *	This program is distributed in the hope that it will be useful,
	 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *	GNU General Public License for more details.
	 *
	 *	You should have received a copy of the GNU General Public License
	 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
	 *
	 */

	$plugins->add_hook("index_start", "mytabs_start");
	$plugins->add_hook("index_end", "mytabs_forums");
	$plugins->add_hook("usercp_options_end", "mytabs_useroptions");
	$plugins->add_hook("usercp_do_options_end", "mytabs_save_useroptions");
	$plugins->add_hook("admin_forum_menu", "mytabs_menu");
	$plugins->add_hook("admin_forum_action_handler", "mytabs_action_handler");
	$plugins->add_hook("xmlhttp", "mytabs_xmlhttp");

	function mytabs_info()
	{
		return array(
			'name'			=> 'MyTabs',
			'description'	=> 'Lets you implement tabbed browsing in your forum.',
			'website'		=> 'https://github.com/hisoka-root',
			'author'		=> 'Ethan / FatalMessiah (maintained by hisoka)',
			'authorsite'	=> 'https://github.com/hisoka-root',
			'version'		=> '1.0.1',
			'guid'			=> 'b7a85f25bf6b4058baadcc7ca66b147f'
		);
	}

	function mytabs_activate()
	{
		global $mybb, $db;

		/* Create table for storing tabs. */
		if ( !$db->table_exists( 'mytabs' ) )
		{
			$mytabs_table = 'CREATE TABLE `'.TABLE_PREFIX.'mytabs` (
											`id` INT( 10 ) NOT NULL AUTO_INCREMENT ,
											`name` TEXT NOT NULL ,
											`forums` TEXT NOT NULL ,
											`visible` TEXT NOT NULL ,
											`order` TEXT NOT NULL ,
											`tab_code` TEXT NOT NULL ,
											`selected_tab_code` TEXT NOT NULL ,
											PRIMARY KEY ( `id` )
											) ENGINE = InnoDB ;
								';
			$db->query( $mytabs_table );
		}

		/* Create settings table. */
		if ( !$db->table_exists( 'mytabs_settings' ) )
		{
			$mytabs_table = 'CREATE TABLE `'.TABLE_PREFIX.'mytabs_settings` (
											`id` INT( 10 ) NOT NULL AUTO_INCREMENT ,
											`name` TEXT NOT NULL ,
											`value` TEXT NOT NULL ,
											PRIMARY KEY ( `id` )
											) ENGINE = InnoDB ;
								';
			$db->query( $mytabs_table );
		}

		/* Create default settings. */

		$default_settings[] = array(
			'id' => 1,
			'name' => 'enabled',
			'value' => '1'
		);

		$default_settings[] = array(
			'id' => 2,
			'name' => 'default_tab_code',
			'value' => '<td class="thead" style="border: 1px solid black; margin-right: 5px; padding: 2px 5px 2px 5px;">'."\r\n".'	<div>'."\r\n".'		<a href="{$link}">{$name}</a>'."\r\n".'	</div>'."\r\n".'</td>'."\r\n"
		);

		$default_settings[] = array(
			'id' => 3,
			'name' => 'default_selected_tab_code',
			'value' => '<td class="thead" style="border: 1px solid black; margin-right: 5px; padding: 2px 5px 2px 5px;">'."\r\n".'	<div>'."\r\n".'		<strong><a href="{$link}">{$name}</a></strong>'."\r\n".'	</div>'."\r\n".'</td>'
		);

		$default_settings[] = array(
			'id' => 4,
			'name' => 'tab_list_code',
			'value' => '<table border="0" cellspacing="1" cellpadding="1" class="tdborder">'."\r\n".'		<tr>'."\r\n".'			{$tablist}'."\r\n".'		</tr>'."\r\n".'</table>'
		);

		$default_settings[] = array(
			'id' => 5,
			'name' => 'default_tab',
			'value' => '1'
		);

		$default_settings[] = array(
			'id' => 6,
			'name' => 'ajax',
			'value' => '1'
		);

		$db->insert_query_multiple('mytabs_settings', $default_settings);

		/* Create user default tab option column. */

		if ( !$db->field_exists( 'default_tab', 'users' ) )
		{
			$db->add_column('users', 'default_tab', 'VARCHAR(20) NOT NULL DEFAULT \'\'');
		}
	}

	function mytabs_deactivate()
	{
		global $mybb, $db;

		/* Drop the tabs table. */
		if ( $db->table_exists( 'mytabs' ) )
		{
			$db->drop_table( 'mytabs' );
		}

		/* Drop the tabs settings table. */
		if ( $db->table_exists( 'mytabs_settings' ) )
		{
			$db->drop_table( 'mytabs_settings' );
		}

		/* Drop the user default tab option column. */
		if ( $db->field_exists( 'default_tab', 'users' ) )
		{
			$db->drop_column('users', 'default_tab');
		}
	}

	function mytabs_start()
	{
		global $db, $header, $headerinclude, $mybb;

		$query = $db->simple_select('mytabs_settings');
		while($result = $db->fetch_array($query))
		{
			$setting[$result['name']] = $result['value'];
		}

		if(!empty($setting['enabled']))
		{
			$headerinclude .= "<script type=\"text/javascript\" src=\"{$mybb->settings['bburl']}/jscripts/mytabs.js\"></script>";
		}
	}

	function mytabs_forums()
	{
		global $db, $forumpermissions, $forums, $mybb;

		$setting = array();
		$query = $db->simple_select('mytabs_settings');
		while($result = $db->fetch_array($query))
		{
			$setting[$result['name']] = $result['value'];
		}

		if (empty($setting['enabled']))
		{
			return;
		}

		$selected_tab = isset($mybb->input['tab']) ? (int)$mybb->input['tab'] : 0;

		$query = $db->simple_select('users', 'default_tab', "`uid`='" . (int)$mybb->user['uid'] . "'");
		if($db->num_rows($query) > 0)
		{
			if($user_tab_info = $db->fetch_array($query))
			{
				$temp = rtrim($user_tab_info['default_tab']);
				if(!empty($temp))
				{
					if(empty($selected_tab))
					{
						$selected_tab = (int)$user_tab_info['default_tab'];
					}
				}
			}
		}

		if($selected_tab > 0)
		{
			$query = $db->simple_select('mytabs', '*', "id='{$selected_tab}'");
			if($temp_tab = $db->fetch_array($query))
			{
				if(!$temp_tab['visible'])
				{
					if($setting['default_tab'] == 0)
					{
						$selected_tab = 1;
					}
					else
					{
						$selected_tab = (int)$setting['default_tab'];
					}
				}
			}
			else
			{
				if($setting['default_tab'] == 0)
				{
					$selected_tab = 1;
				}
				else
				{
					$selected_tab = (int)$setting['default_tab'];
				}
			}
		}
		else
		{
			if($setting['default_tab'] == 0)
			{
				$selected_tab = 1;
			}
			else
			{
				$selected_tab = (int)$setting['default_tab'];
			}
		}

		if (!empty($setting['ajax']))
		{
			$tab_query = $db->simple_select('mytabs', "*", '', array('order_by' => '`order`', 'order_dir' => 'asc'));

			if($db->num_rows($tab_query) < 1)
			{
				return;
			}

			$tablist = '';
			$navbar = '';
			$body_content = '';

			$noshow = array();
			if(is_array($forumpermissions))
			{
				foreach($forumpermissions as $fid => $perms)
				{
					if(!$forumpermissions[$fid]['canview'])
					{
						$noshow[] = $fid;
					}
				}
			}

			while($tab = $db->fetch_array($tab_query))
			{
				if(!$tab['visible'] && $tab['id'] != $selected_tab)
					continue;

				$name = htmlspecialchars_uni($tab['name']);
				$link_attr = "?tab={$tab['id']}\" onclick=\"return switchTab('{$tab['id']}', 'true');";

				$code_template = '';
				if($tab['id'] == $selected_tab)
				{
					$code_template = !empty($tab['selected_tab_code']) ? $tab['selected_tab_code'] : $setting['default_selected_tab_code'];
					$code_template = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $code_template);

					$tablist .= str_replace(array('{$link}', '{$name}'), array($link_attr, $name), $code_template);

					if(is_array($forumpermissions))
					{
						foreach($forumpermissions as $fid => $perms)
						{
							$forumpermissions[$fid]['canview'] = 0;
						}
					}

					$tab_forums = rtrim($tab['forums']);
					if(!empty($tab_forums))
					{
						$forum_ids = array_filter(explode(',', $tab_forums));
						foreach($forum_ids as $fid)
						{
							$fid = (int)$fid;
							if($fid > 0 && !in_array($fid, $noshow) && isset($forumpermissions[$fid]))
							{
								$forumpermissions[$fid]['canview'] = 1;

								$parents = get_parent_list($fid);
								if(!empty($parents))
								{
									$parent_ids = array_filter(explode(',', $parents));
									foreach($parent_ids as $pid)
									{
										$pid = (int)$pid;
										if($pid > 0 && !in_array($pid, $noshow) && isset($forumpermissions[$pid]))
										{
											$forumpermissions[$pid]['canview'] = 1;
										}
									}
								}
							}
						}
						$forum_list = build_forumbits();
						$body_content = $forum_list['forum_list'];
					}
				}
				else
				{
					$code_template = !empty($tab['tab_code']) ? $tab['tab_code'] : $setting['default_tab_code'];
					$code_template = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $code_template);

					$tablist .= str_replace(array('{$link}', '{$name}'), array($link_attr, $name), $code_template);
				}
			}

			if(!empty($tablist))
			{
				$tablist_code = $setting['tab_list_code'];
				$tablist_code = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $tablist_code);
				$navbar = str_replace('{$tablist}', $tablist, $tablist_code);
				$navbar = "<div id=\"tab_nav_{$selected_tab}\" style=\"\">{$navbar}</div>";
			}

			if(!empty($navbar) && !empty($body_content))
			{
				$forums = "<!-- mytabs: start (ajax) -->\n<!-- mytabs_full_start --><div id=\"mytabs_full\">\n";
				$forums .= "<div id=\"tab_navbar\">\n{$navbar}\n</div>\n<div id=\"tab_content\">\n{$body_content}\n</div>";
				$forums .= "\n<!-- mytabs_full_end --></div>\n<!-- mytabs: end -->";
			}
		}
		else
		{
			$tab_query = $db->simple_select('mytabs', "*", '', array('order_by' => '`order`', 'order_dir' => 'asc'));

			if($db->num_rows($tab_query) < 1)
			{
				return;
			}

			$forums = "";

			$forums .= "\n<!-- mytabs: start -->\n<!-- mytabs_full_start --><div id=\"mytabs_full\">";
			$forums .= "\n<div id=\"tab_nav\">";

			$query_nav = $db->simple_select('mytabs', '*', '', array('order_by' => '`order`', 'order_dir' => 'asc'));
			while($tab = $db->fetch_array($query_nav))
			{
				if(!$tab['visible'])
					continue;
				$nav_tablist = '';
				$query_nav2 = $db->simple_select('mytabs', '*', '', array('order_by' => '`order`', 'order_dir' => 'asc'));
				while($tab2 = $db->fetch_array($query_nav2))
				{
					if(!$tab2['visible'])
						continue;
					$name = htmlspecialchars_uni($tab2['name']);
					$link_attr = "?tab={$tab2['id']}\" onclick=\"return switchTab('{$tab2['id']}', false);";

					$code_template = '';
					if($tab['id'] == $tab2['id'])
					{
						$code_template = !empty($tab2['selected_tab_code']) ? $tab2['selected_tab_code'] : $setting['default_selected_tab_code'];
					}
					else
					{
						$code_template = !empty($tab2['tab_code']) ? $tab2['tab_code'] : $setting['default_tab_code'];
					}
					$code_template = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $code_template);
					$nav_tablist .= str_replace(array('{$link}', '{$name}'), array($link_attr, $name), $code_template);
				}

				$tablist_code = $setting['tab_list_code'];
				$tablist_code = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $tablist_code);
				$tab_nav_html = str_replace('{$tablist}', $nav_tablist, $tablist_code);

				$display_style = ($selected_tab == $tab['id']) ? '' : 'display: none;';
				$forums .= "<div id=\"tab_nav_{$tab['id']}\" style=\"{$display_style}\">{$tab_nav_html}</div>";
			}
			$forums .= "\n</div>\n<div id=\"tab_content\" style=\"\">";

			$noshow = array();
			if(is_array($forumpermissions))
			{
				foreach($forumpermissions as $fid => $perms)
				{
					if(!$forumpermissions[$fid]['canview'])
					{
						$noshow[] = $fid;
					}
				}
			}

			$tab_query2 = $db->simple_select('mytabs', '*', '', array('order_by' => '`order`', 'order_dir' => 'asc'));
			while($tab = $db->fetch_array($tab_query2))
			{
				if(!$tab['visible'])
					continue;
				$forums .= "\n<!-- Starting with tab[{$tab['id']}] -->";
				if($tab['id'] == $selected_tab) {
					$forums .= "\n<div id=\"tab_{$tab['id']}\" style=\"\">";
				} else {
					$forums .= "\n<div id=\"tab_{$tab['id']}\" style=\"display: none;\">";
				}
				if(is_array($forumpermissions))
				{
					foreach($forumpermissions as $fid => $perms)
					{
						$forumpermissions[$fid]['canview'] = 0;
					}
				}
				$tab_forums = rtrim($tab['forums']);
				if(!empty($tab_forums))
				{
					$forum_ids = array_filter(explode(',', $tab_forums));
					foreach($forum_ids as $fid)
					{
						$fid = (int)$fid;
						if($fid > 0 && !in_array($fid, $noshow) && isset($forumpermissions[$fid]))
						{
							$forumpermissions[$fid]['canview'] = 1;

							$parents = get_parent_list($fid);
							if(!empty($parents))
							{
								$parent_ids = array_filter(explode(',', $parents));
								foreach($parent_ids as $pid)
								{
									$pid = (int)$pid;
									if($pid > 0 && !in_array($pid, $noshow) && isset($forumpermissions[$pid]))
									{
										$forumpermissions[$pid]['canview'] = 1;
									}
								}
							}
						}
					}
					$forum_list = build_forumbits();
					$forums .= $forum_list['forum_list'];
				}
				$forums .= "</div>";
				$forums .= "\n<!-- Finished with tab[{$tab['id']}] -->\n";
			}
			$forums .= "\n</div>\n<!-- mytabs_full_end --></div>";
			$forums .= "\n<!-- mytabs: end -->";
		}
	}

	function mytabs_useroptions()
	{
		global $db, $lang, $mybb, $templates, $tppselect;

		$setting = array();
		$query = $db->simple_select('mytabs_settings');
		while($result = $db->fetch_array($query))
		{
			$setting[$result['name']] = $result['value'];
		}

		if(!empty($setting['enabled']))
		{
			$lang->load('mytabs');

			$selected_tab = '';
			$query = $db->simple_select('users', 'default_tab', "uid='" . (int)$mybb->user['uid'] . "'");
			if($db->num_rows($query) > 0)
			{
				if($user_tab_info = $db->fetch_array($query))
				{
					$temp = rtrim($user_tab_info['default_tab']);
					if(!empty($temp))
					{
						$selected_tab = $user_tab_info['default_tab'];
					}
				}
			}

			$tppoptions = '';
			$query = $db->simple_select("mytabs");
			if($db->num_rows($query) > 0)
			{
				while($tab = $db->fetch_array($query))
				{
					if($tab['visible'])
					{
						$selected = "";
						if($selected_tab == $tab['id'])
						{
							$selected = "selected=\"selected\"";
						}
						$tppoptions .= "<option value=\"{$tab['id']}\" $selected>" . htmlspecialchars_uni($tab['name']) . "</option>\n";
					}
				}
				$tabselect = '';
				eval("\$tabselect .= \"".$templates->get("usercp_options_tppselect")."\";");
				$tppselect .= str_replace('name="tpp"', 'name="defaulttab"', $tabselect);
			}
		}
	}

	function mytabs_save_useroptions()
	{
		global $db, $mybb;

		$setting = array();
		$query = $db->simple_select('mytabs_settings');
		while($result = $db->fetch_array($query))
		{
			$setting[$result['name']] = $result['value'];
		}

		if(!empty($setting['enabled']) && isset($mybb->input['defaulttab']))
		{
			$db->update_query("users", array('default_tab' => (int)$mybb->input['defaulttab']), "uid='" . (int)$mybb->user['uid'] . "'");
		}
	}

	function mytabs_menu(&$sub_menu)
	{
		global $mybb, $lang;

		if(is_array($sub_menu) && !empty($sub_menu))
		{
			end($sub_menu);
			$key = (key($sub_menu)) + 10;
		}
		else
		{
			$key = 50;
		}

		if(!$key)
		{
			$key = 50;
		}
		$sub_menu[$key] = array('id' => 'mytabs', 'title' => 'MyTabs', 'link' => "index.php?module=forum-mytabs");
	}

	function mytabs_action_handler(&$action)
	{
		$action['mytabs'] = array('active' => 'mytabs', 'file' => 'mytabs.php');
	}

	function mytabs_xmlhttp()
	{
		global $db, $mybb;

		if($mybb->input['action'] != 'mytabs_switch')
			return;

		$tab_id = isset($mybb->input['tab']) ? (int)$mybb->input['tab'] : 0;
		if($tab_id < 1)
			return;

		$setting = array();
		$query = $db->simple_select('mytabs_settings');
		while($result = $db->fetch_array($query))
		{
			$setting[$result['name']] = $result['value'];
		}

		if(empty($setting['enabled']) || empty($setting['ajax']))
			return;

		$query = $db->simple_select('mytabs', '*', "id='{$tab_id}'");
		$tab = $db->fetch_array($query);
		if(!$tab)
			return;

		require_once MYBB_ROOT.'inc/functions_forumlist.php';
		$forumpermissions = forum_permissions();

		$noshow = array();
		if(is_array($forumpermissions))
		{
			foreach($forumpermissions as $fid => $perms)
			{
				if(!$forumpermissions[$fid]['canview'])
					$noshow[] = $fid;
			}
		}

		$tab_query = $db->simple_select('mytabs', '*', '', array('order_by' => '`order`', 'order_dir' => 'asc'));

		$tablist = '';
		$body_content = '';

		while($t = $db->fetch_array($tab_query))
		{
			if(!$t['visible'] && $t['id'] != $tab_id)
				continue;

			$name = htmlspecialchars_uni($t['name']);
			$link_attr = "?tab={$t['id']}\" onclick=\"return switchTab('{$t['id']}', 'true');";

			if($t['id'] == $tab_id)
			{
				$code_template = !empty($t['selected_tab_code']) ? $t['selected_tab_code'] : $setting['default_selected_tab_code'];
				$code_template = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $code_template);
				$tablist .= str_replace(array('{$link}', '{$name}'), array($link_attr, $name), $code_template);

				if(is_array($forumpermissions))
				{
					foreach($forumpermissions as $fid => $perms)
						$forumpermissions[$fid]['canview'] = 0;
				}

				$tab_forums = rtrim($t['forums']);
				if(!empty($tab_forums))
				{
					$forum_ids = array_filter(explode(',', $tab_forums));
					foreach($forum_ids as $fid)
					{
						$fid = (int)$fid;
						if($fid > 0 && !in_array($fid, $noshow) && isset($forumpermissions[$fid]))
						{
							$forumpermissions[$fid]['canview'] = 1;

							$parents = get_parent_list($fid);
							if(!empty($parents))
							{
								$parent_ids = array_filter(explode(',', $parents));
								foreach($parent_ids as $pid)
								{
									$pid = (int)$pid;
									if($pid > 0 && !in_array($pid, $noshow) && isset($forumpermissions[$pid]))
										$forumpermissions[$pid]['canview'] = 1;
								}
							}
						}
					}
					$forum_list = build_forumbits();
					$body_content = $forum_list['forum_list'];
				}
			}
			else
			{
				$code_template = !empty($t['tab_code']) ? $t['tab_code'] : $setting['default_tab_code'];
				$code_template = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $code_template);
				$tablist .= str_replace(array('{$link}', '{$name}'), array($link_attr, $name), $code_template);
			}
		}

		if(empty($tablist) || empty($body_content))
			return;

		$tablist_code = $setting['tab_list_code'];
		$tablist_code = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $tablist_code);
		$navbar = str_replace('{$tablist}', $tablist, $tablist_code);
		$navbar = "<div id=\"tab_nav_{$tab_id}\" style=\"\">{$navbar}</div>";

		$output = "<div id=\"tab_navbar\">\n{$navbar}\n</div>\n<div id=\"tab_content\">\n{$body_content}\n</div>";

		header("Content-type: text/html; charset=UTF-8");
		echo $output;
		exit;
	}
