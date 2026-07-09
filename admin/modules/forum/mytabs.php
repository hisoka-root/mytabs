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

	/* Prevent users from accessing this file directly. */
	if(!defined("IN_MYBB"))
	{
		die( 'You aren\'t allowed to view this file directly.<br /><br />Please make sure IN_MYBB is defined.' );
	}

	global $admin_options, $mybb, $db, $page, $lang;

	/* Load selected language pack. */
	$lang->load('mytabs');

	$page->add_breadcrumb_item("MyTabs", "index.php?module=forum-mytabs");

	$sub_tabs['mytabs'] = array(
		'title' => $lang->current_tabs,
		'link' => "index.php?module=forum-mytabs",
		'description' => $lang->current_tabs_desc
	);

	$sub_tabs['mytabsadd'] = array(
		'title' => $lang->add_new_tab,
		'link' => "index.php?module=forum-mytabs&amp;do=add",
		'description' => $lang->add_new_tab_desc
	);

	$sub_tabs['mytabssettings'] = array(
		'title' => $lang->settings_title,
		'link' => "index.php?module=forum-mytabs&amp;do=settings",
		'description' => $lang->settings_desc
	);

	/* Get settings array. */
	$setting = array();
	$query = $db->simple_select("mytabs_settings", "*");
	while($result = $db->fetch_array($query))
	{
		$setting[$result['name']] = $result['value'];
	}

	$errors = array();

	if(isset($mybb->input['do']) && $mybb->input['do'] == 'add')
	{
		if(!empty($admin_options['codepress']))
		{
			$page->extra_header .= '
		<link type="text/css" href="./jscripts/codepress/languages/codepress-mybb.css" rel="stylesheet" id="cp-lang-style" />
		<script type="text/javascript" src="./jscripts/codepress/codepress.js"></script>
		<script type="text/javascript">
			CodePress.language = \'mybb\';
		</script>';
		}

		$page->output_header($lang->add_new_tab);

		$page->output_nav_tabs($sub_tabs, 'mytabsadd');

		$page->add_breadcrumb_item("mytabsadd", "index.php?module=forum-mytabs&amp;do=add");

		/* Generate the adding form for tabs. */

		$form = new Form("index.php?module=forum-mytabs&amp;do=add", "post", "save");
		echo $form->generate_hidden_field("id", isset($mybb->input['id']) ? $mybb->input['id'] : '');
		if($mybb->request_method == "post")
		{
			if(!isset($mybb->input['my_post_key']) || $mybb->input['my_post_key'] != $mybb->post_code)
			{
				flash_message($lang->invalid_post_verify_code2, 'error');
				admin_redirect("index.php?module=forum-mytabs");
			}
			/* Add new tab. */
			if(!empty($mybb->input['name']))
			{
				$tab = array(
					'name' => $db->escape_string($mybb->input['name']),
					'forums' => isset($mybb->input['forums']) && is_array($mybb->input['forums']) ? implode(',', $mybb->input['forums']) : '',
					'tab_code' => $db->escape_string(isset($mybb->input['tab_code']) ? $mybb->input['tab_code'] : ''),
					'selected_tab_code' => $db->escape_string(isset($mybb->input['selected_tab_code']) ? $mybb->input['selected_tab_code'] : ''),
					'visible' => isset($mybb->input['visible']) ? (int)$mybb->input['visible'] : 0,
					'order' => isset($mybb->input['disporder']) ? (int)$mybb->input['disporder'] : 1
				);
				if($db->insert_query('mytabs', $tab))
				{
					flash_message($lang->success_add, 'success');
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
			else
			{
				flash_message($lang->error_no_name, 'error');
				admin_redirect("index.php?module=forum-mytabs&amp;do=add");
			}
		}
		if($errors)
		{
			$page->output_inline_error($errors);
		}

		$form_container = new FormContainer($lang->add_new_tab);
		$name_value = isset($mybb->input['name']) ? $mybb->input['name'] : '';
		$forums_value = isset($mybb->input['forums']) ? $mybb->input['forums'] : array();
		$tab_code_value = isset($mybb->input['tab_code']) ? $mybb->input['tab_code'] : '';
		$selected_tab_code_value = isset($mybb->input['selected_tab_code']) ? $mybb->input['selected_tab_code'] : '';
		$visible_value = isset($mybb->input['visible']) ? $mybb->input['visible'] : 1;
		$disporder_value = isset($mybb->input['disporder']) ? $mybb->input['disporder'] : 1;

		$form_container->output_row($lang->tab_options_name." <em>*</em>", $lang->tab_options_name_desc, $form->generate_text_box('name', $name_value, array('id' => 'name')));
		$form_container->output_row($lang->tab_options_forums, $lang->tab_options_forums_desc, $form->generate_forum_select("forums[]", $forums_value, array('multiple' => 1, 'size' => 8)));
		$form_container->output_row($lang->tab_options_style, $lang->tab_options_style_desc, $form->generate_text_area('tab_code', $tab_code_value, array('id' => 'tab_code', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'tab_code');
		$form_container->output_row($lang->tab_options_selected_style, $lang->tab_options_selected_style_desc, $form->generate_text_area('selected_tab_code', $selected_tab_code_value, array('id' => 'selected_tab_code', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'selected_tab_code');
		$form_container->output_row($lang->tab_options_visible, $lang->tab_options_visible_desc, $form->generate_yes_no_radio('visible', $visible_value));
		$form_container->output_row($lang->tab_options_order, $lang->tab_options_order_desc, $form->generate_text_box('disporder', $disporder_value, array('id' => 'disporder')));

		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->submit_add);

		$form->output_submit_wrapper($buttons);

		$form->end();

		if(!empty($admin_options['codepress']))
		{
			echo "<script type=\"text/javascript\">
		Event.observe('save', 'submit', function()
		{
			if($('tab_code_cp')) {
				var area = $('tab_code_cp');
				area.id = 'tab_code';
				area.value = tab_code.getCode();
				area.disabled = false;
			}

			if($('selected_tab_code_cp')) {
				var area = $('selected_tab_code_cp');
				area.id = 'selected_tab_code';
				area.value = selected_tab_code.getCode();
				area.disabled = false;
			}
		});
	</script>";
		}

		$page->output_footer();
	}
	else if(isset($mybb->input['do']) && $mybb->input['do'] == 'settings')
	{
		if(!empty($admin_options['codepress']))
		{
			$page->extra_header .= '
		<link type="text/css" href="./jscripts/codepress/languages/codepress-mybb.css" rel="stylesheet" id="cp-lang-style" />
		<script type="text/javascript" src="./jscripts/codepress/codepress.js"></script>
		<script type="text/javascript">
			CodePress.language = \'mybb\';
		</script>';
		}

		$page->output_header($lang->edit_settings);

		$page->add_breadcrumb_item("mytabssettings", "index.php?module=forum-mytabs&amp;do=settings");

		$page->output_nav_tabs($sub_tabs, 'mytabssettings');

		$form = new Form("index.php?module=forum-mytabs&amp;do=settings", "post", "save");

		if($mybb->request_method == "post")
		{
			if(!isset($mybb->input['my_post_key']) || $mybb->input['my_post_key'] != $mybb->post_code)
			{
				flash_message($lang->invalid_post_verify_code2, 'error');
				admin_redirect("index.php?module=forum-mytabs");
			}
			/* Update settings. */
			if(isset($mybb->input['enable_tabs']))
			{
				$updated = 0;

				/* Check for updates. */

				/* AJAX Setting Update */
				$setting_ajax = $db->simple_select('mytabs_settings', '*', "`name` ='ajax'");
				if($db->num_rows($setting_ajax) < 1)
				{
					/* Create ajax setting. */
					$db->insert_query('mytabs_settings', array('name' => 'ajax', 'value' => '1'));
					$updated = 1;
				}

				/* User default tab setting update */
				if ( !$db->field_exists( 'default_tab', 'users' ) )
				{
					$db->add_column('users', 'default_tab', 'VARCHAR(20) NOT NULL DEFAULT \'\'');
					$updated = 1;
				}

				$db->update_query('mytabs_settings', array('value' => (int)$mybb->input['enable_tabs']), "`name` ='enabled'");
				$db->update_query('mytabs_settings', array('value' => isset($mybb->input['default_tab']) ? (int)$mybb->input['default_tab'] : 0), "`name` ='default_tab'");
				$db->update_query('mytabs_settings', array('value' => $db->escape_string(isset($mybb->input['default_tab_code']) ? $mybb->input['default_tab_code'] : '')), "`name` ='default_tab_code'");
				$db->update_query('mytabs_settings', array('value' => $db->escape_string(isset($mybb->input['default_selected_tab_code']) ? $mybb->input['default_selected_tab_code'] : '')), "`name` ='default_selected_tab_code'");
				$db->update_query('mytabs_settings', array('value' => $db->escape_string(isset($mybb->input['tab_list_code']) ? $mybb->input['tab_list_code'] : '')), "`name` ='tab_list_code'");
				$db->update_query('mytabs_settings', array('value' => isset($mybb->input['use_ajax']) ? (int)$mybb->input['use_ajax'] : 0), "`name` ='ajax'");

				if($updated)
				{
					flash_message($lang->success_updated, 'success');
				}
				else
				{
					flash_message($lang->success_settings, 'success');
				}

				if(!empty($mybb->input['continue'])) {
					admin_redirect("index.php?module=forum-mytabs&amp;do=settings");
				} else {
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
		}

		$form_container = new FormContainer($lang->mytabs_settings);

		$form_container->output_row($lang->tab_setting_enabled, $lang->tab_setting_enabled_desc, $form->generate_yes_no_radio('enable_tabs', isset($setting['enabled']) ? $setting['enabled'] : 1));
		$form_container->output_row($lang->tab_setting_ajax, $lang->tab_setting_ajax_desc, $form->generate_yes_no_radio('use_ajax', isset($setting['ajax']) ? $setting['ajax'] : 1));

		$query = $db->simple_select("mytabs");
		if($db->num_rows($query) > 0)
		{
			while($tab = $db->fetch_array($query))
			{
				$select_options[$tab['id']] = $tab['name'];
			}
		}
		else
		{
			$select_options = array(0 => "None");
		}

		$form_container->output_row($lang->tab_setting_default_tab, $lang->tab_setting_default_tab_desc, $form->generate_select_box('default_tab', $select_options, isset($setting['default_tab']) ? $setting['default_tab'] : 0));
		$form_container->output_row($lang->tab_setting_default_style, $lang->tab_setting_default_style_desc, $form->generate_text_area('default_tab_code', isset($setting['default_tab_code']) ? $setting['default_tab_code'] : '', array('id' => 'default_tab_code', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'default_tab_code');
		$form_container->output_row($lang->tab_setting_default_selected_style, $lang->tab_setting_default_selected_style_desc, $form->generate_text_area('default_selected_tab_code', isset($setting['default_selected_tab_code']) ? $setting['default_selected_tab_code'] : '', array('id' => 'default_selected_tab_code', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'default_selected_tab_code');
		$form_container->output_row($lang->tab_setting_tab_list_style, $lang->tab_setting_tab_list_style_desc, $form->generate_text_area('tab_list_code', isset($setting['tab_list_code']) ? $setting['tab_list_code'] : '', array('id' => 'tab_list_code', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'tab_list_code');

		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->submit_save_continue, array('name' => 'continue'));
		$buttons[] = $form->generate_submit_button($lang->submit_save_exit, array('name' => 'exit'));

		$form->output_submit_wrapper($buttons);
		$form->end();

		if(!empty($admin_options['codepress']))
		{
			echo "<script type=\"text/javascript\">
		Event.observe('save', 'submit', function()
		{
			if($('default_tab_code_cp')) {
				var area = $('default_tab_code_cp');
				area.id = 'default_tab_code';
				area.value = default_tab_code.getCode();
				area.disabled = false;
			}

			if($('default_selected_tab_code_cp')) {
				var area = $('default_selected_tab_code_cp');
				area.id = 'default_selected_tab_code';
				area.value = default_selected_tab_code.getCode();
				area.disabled = false;
			}

			if($('tab_list_code_cp')) {
				var area = $('tab_list_code_cp');
				area.id = 'tab_list_code';
				area.value = tab_list_code.getCode();
				area.disabled = false;
			}
		});
	</script>";
		}

		$page->output_footer();
	}
	else if(isset($mybb->input['do']) && $mybb->input['do'] == 'edit')
	{
		if(!empty($admin_options['codepress']))
		{
			$page->extra_header .= '
		<link type="text/css" href="./jscripts/codepress/languages/codepress-mybb.css" rel="stylesheet" id="cp-lang-style" />
		<script type="text/javascript" src="./jscripts/codepress/codepress.js"></script>
		<script type="text/javascript">
			CodePress.language = \'mybb\';
		</script>';
		}

		$page->output_header($lang->edit_tab);
		$page->output_nav_tabs($sub_tabs, 'mytabs');
		$page->add_breadcrumb_item("mytabs", "index.php?module=forum-mytabs");

		/* Show tab editing form. */
		if($mybb->request_method == "post")
		{
			if(!isset($mybb->input['my_post_key']) || $mybb->input['my_post_key'] != $mybb->post_code)
			{
				flash_message($lang->invalid_post_verify_code2, 'error');
				admin_redirect("index.php?module=forum-mytabs");
			}
			/* Edit selected tab. */
			if(!empty($mybb->input['name']))
			{
				if(isset($mybb->input['id']))
				{
					$tab = array(
						'name' => $db->escape_string($mybb->input['name']),
						'forums' => isset($mybb->input['forums']) && is_array($mybb->input['forums']) ? implode(',', $mybb->input['forums']) : '',
						'tab_code' => $db->escape_string(isset($mybb->input['tab_code']) ? $mybb->input['tab_code'] : ''),
						'selected_tab_code' => $db->escape_string(isset($mybb->input['selected_tab_code']) ? $mybb->input['selected_tab_code'] : ''),
						'visible' => isset($mybb->input['visible']) ? (int)$mybb->input['visible'] : 0,
						'order' => isset($mybb->input['disporder']) ? (int)$mybb->input['disporder'] : 1
					);
					if($db->update_query('mytabs', $tab, "id='".(int)$mybb->input['id']."'"))
					{
						flash_message($lang->success_edit, 'success');
						if(!empty($mybb->input['continue'])) {
							admin_redirect("index.php?module=forum-mytabs&amp;do=edit&amp;id=" . (int)$mybb->input['id']);
						} else {
							admin_redirect("index.php?module=forum-mytabs");
						}
					}
				}
				else
				{
					flash_message($lang->error_invalid_id, 'error');
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
			else
			{
				flash_message($lang->error_no_name, 'error');
				admin_redirect("index.php?module=forum-mytabs&amp;do=edit&amp;id=".(isset($mybb->input['id']) ? (int)$mybb->input['id'] : ''));
			}
		}
		$tab_id = isset($mybb->input['id']) ? (int)$mybb->input['id'] : -1;
		$query = $db->simple_select('mytabs', '*', "id='{$tab_id}'");
		if($db->num_rows($query) > 0)
		{
			$tab = $db->fetch_array($query);
			$form = new Form("index.php?module=forum-mytabs&amp;do=edit", "post", "save");
			echo $form->generate_hidden_field("id", $tab_id);
			if($errors)
			{
				$page->output_inline_error($errors);
			}

			$form_container = new FormContainer($lang->edit_tab);

			$form_container->output_row($lang->tab_options_name.". <em>*</em>", $lang->tab_options_name_desc, $form->generate_text_box('name', $tab['name'], array('id' => 'name')));
			$forums_edit_value = !empty($tab['forums']) ? explode(',', $tab['forums']) : array();
			$form_container->output_row($lang->tab_options_forums, $lang->tab_options_forums_desc, $form->generate_forum_select("forums[]", $forums_edit_value, array('multiple' => 1, 'size' => 8)));
			$form_container->output_row($lang->tab_options_style, $lang->tab_options_style_desc, $form->generate_text_area('tab_code', $tab['tab_code'], array('id' => 'tab_code', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'tab_code');
			$form_container->output_row($lang->tab_options_selected_style, $lang->tab_options_selected_style_desc, $form->generate_text_area('selected_tab_code', $tab['selected_tab_code'], array('id' => 'selected_tab_code', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'selected_tab_code');
			$form_container->output_row($lang->tab_options_visible, $lang->tab_options_visible_desc, $form->generate_yes_no_radio('visible', $tab['visible']));
			$form_container->output_row($lang->tab_options_order, $lang->tab_options_order_desc, $form->generate_text_box('disporder', $tab['order'], array('id' => 'disporder')));

			$form_container->end();

			$buttons[] = $form->generate_submit_button($lang->submit_save_continue, array('name' => 'continue'));
			$buttons[] = $form->generate_submit_button($lang->submit_save_exit, array('name' => 'exit'));

			$form->output_submit_wrapper($buttons);

			$form->end();
		}
		else
		{
			flash_message($lang->error_invalid_id, 'error');
			admin_redirect("index.php?module=forum-mytabs");
		}

		if(!empty($admin_options['codepress']))
		{
			echo "<script type=\"text/javascript\">
		Event.observe('save', 'submit', function()
		{
			if($('tab_code_cp')) {
				var area = $('tab_code_cp');
				area.id = 'tab_code';
				area.value = tab_code.getCode();
				area.disabled = false;
			}

			if($('selected_tab_code_cp')) {
				var area = $('selected_tab_code_cp');
				area.id = 'selected_tab_code';
				area.value = selected_tab_code.getCode();
				area.disabled = false;
			}
		});
	</script>";
		}

		$page->output_footer();
	}
	else
	{
		/* Show the current tabs. */

		$page->output_header($lang->current_tabs);

		if(isset($mybb->input['do']) && $mybb->input['do'] == 'delete')
		{
			/* Delete selected tab. */
			if(isset($mybb->input['id']) && $mybb->input['my_post_key'] == $mybb->post_code)
			{
				if($db->delete_query('mytabs', "`id`=".(int)$mybb->input['id']))
				{
					flash_message($lang->success_delete, 'success');
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
		}
		else if(isset($mybb->input['do']) && $mybb->input['do'] == 'updateorders')
		{
			if(!isset($mybb->input['my_post_key']) || $mybb->input['my_post_key'] != $mybb->post_code)
			{
				flash_message($lang->invalid_post_verify_code2, 'error');
				admin_redirect("index.php?module=forum-mytabs");
			}
			/* Update orders. */
			if(!empty($mybb->input['disporder']) && is_array($mybb->input['disporder']))
			{
				foreach($mybb->input['disporder'] as $id => $val)
				{
					$db->update_query('mytabs', array('order' => (int)$val), "id='".(int)$id."'");
				}
			}
			flash_message($lang->success_order, 'success');
			admin_redirect("index.php?module=forum-mytabs");
		}

		$page->output_nav_tabs($sub_tabs, 'mytabs');

		$page->add_breadcrumb_item("mytabs", "index.php?module=forum-mytabs");

		if($errors)
		{
			$page->output_inline_error($errors);
		}

		$form = new Form("index.php?module=forum-mytabs&amp;do=updateorders", "post");

		$form_container = new FormContainer($lang->current_tabs);

		$form_container->output_row_header($lang->tab);
		$form_container->output_row_header($lang->order, array("class" => "align_center", 'width' => '5%'));
		$form_container->output_row_header($lang->controls, array("class" => "align_center", 'style' => 'width: 200px'));

		/* Generate the list of tabs. */
		$query = $db->simple_select("mytabs", "*", "", array('order_by' => '`order`', 'order_dir' => 'asc'));
		if($db->num_rows($query) > 0)
		{
			while($tab = $db->fetch_array($query))
			{
				$form_container->output_cell("<strong>" . htmlspecialchars_uni($tab['name']) . "</strong>");

				$form_container->output_cell("<input type=\"text\" name=\"disporder[".$tab['id']."]\" value=\"".$tab['order']."\" class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" />", array("class" => "align_center"));

				$popup = new PopupMenu("tab_{$tab['id']}", $lang->options);
				$popup->add_item($lang->edit_tab, "index.php?module=forum-mytabs&amp;do=edit&amp;id={$tab['id']}");
				$popup->add_item($lang->delete_tab, "index.php?module=forum-mytabs&amp;do=delete&amp;id={$tab['id']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_delete}')");

				$form_container->output_cell($popup->fetch(), array("class" => "align_center"));

				$form_container->construct_row();
			}
		}

		$submit_options = array();

		if($form_container->num_rows() == 0)
		{
			$form_container->output_cell($lang->no_tabs, array('colspan' => 3));
			$form_container->construct_row();
			$submit_options = array('disabled' => true);
		}

		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->submit_update, $submit_options);

		$form->output_submit_wrapper($buttons);

		$form->end();

		$page->output_footer();
	}
