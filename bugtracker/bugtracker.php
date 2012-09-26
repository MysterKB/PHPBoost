<?php
/*##################################################
 *                              bugtracker.php
 *                            -------------------
 *   begin                : February 05, 2012
 *   copyright            : (C) 2012 Julien BRISWALTER
 *   email                : julien.briswalter@gmail.com
 *
 *  
 ###################################################
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ###################################################*/

require_once('../kernel/begin.php');
include_once('bugtracker_begin.php');
require_once('../kernel/header.php');

$bugtracker_config = BugtrackerConfig::load();

//Configuration des authorisations
$authorizations = $bugtracker_config->get_authorizations();
$items_per_page = $bugtracker_config->get_items_per_page();
$comments_activated = $bugtracker_config->get_comments_activated();
$types = $bugtracker_config->get_types();
$categories = $bugtracker_config->get_categories();
$versions = $bugtracker_config->get_versions();
$severities = $bugtracker_config->get_severities();
$priorities = $bugtracker_config->get_priorities();
$status_list = $bugtracker_config->get_status_list();
$rejected_bug_color = $bugtracker_config->get_rejected_bug_color();
$closed_bug_color = $bugtracker_config->get_closed_bug_color();

$auth_read = $User->check_auth($authorizations, BugtrackerConfig::BUG_READ_AUTH_BIT);
$auth_create = $User->check_auth($authorizations, BugtrackerConfig::BUG_CREATE_AUTH_BIT);
$auth_create_advanced = $User->check_auth($authorizations, BugtrackerConfig::BUG_CREATE_ADVANCED_AUTH_BIT);
$auth_moderate = $User->check_auth($authorizations, BugtrackerConfig::BUG_MODERATE_AUTH_BIT);
$display_types = sizeof($types) > 1 ? true : false;
$display_categories = sizeof($categories) > 1 ? true : false;
$display_versions = sizeof($versions) > 1 ? true : false;
$default_type = isset($types[0]) ? $types[0] : '';
$default_category = isset($categories[0]) ? $categories[0] : '';
$default_version = isset($versions[0]['name']) ? $versions[0]['name'] : '';

$now = new Date(DATE_NOW, TIMEZONE_AUTO);

$id = retrieve(GET, 'id', 0, TINTEGER);
$id_post = retrieve(POST, 'id', 0, TINTEGER);

if (!empty($id) || !empty($id_post))
{
	$id_tmp = !empty($id) ? $id : $id_post;
	
	$bug_exist = $Sql->query_array(PREFIX . 'bugtracker', '*', "WHERE id = '" . $id_tmp . "'", __LINE__, __FILE__);
	if (empty($bug_exist))
		AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php?error=unexist_bug#message_helper');
}

if ( !empty($_POST['valid_add']) )
{
	//checking authorization
	if (!$auth_create)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}
	
	$title = retrieve(POST, 'title', '');
	$contents = retrieve(POST, 'contents', '', TSTRING_PARSE);
	$type = $display_types ? retrieve(POST, 'type', '') : $default_type;
	$category = $display_categories ? retrieve(POST, 'category', '') : $default_category;
	$severity = $auth_create_advanced ? retrieve(POST, 'severity', '') : 'minor';
	$priority = $auth_create_advanced ? retrieve(POST, 'priority', '') : 'normal';
	$detected_in = $display_versions ? retrieve(POST, 'detected_in', '') : $default_version;
	$reproductible = retrieve(POST, 'reproductible', '', TBOOL);
	$reproduction_method = retrieve(POST, 'reproduction_method', '', TSTRING_PARSE);
	
	$category_needed = false;
	if ($display_categories && empty($category))
		$category_needed = true;
	
	if (!empty($title) && !empty($contents) && !$category_needed)
	{
		// Creation du bug
		$Sql->query_inject("INSERT INTO " . PREFIX . "bugtracker (title, contents, author_id, submit_date, status, type, category, severity, priority, detected_in, reproductible, reproduction_method)
		VALUES('" . $title . "', '" . $contents . "', '" . $User->get_id() . "', '" . $now->get_timestamp() . "', 'new', '" . $type . "', '" . $category . "', '" . $severity . "', '" . $priority . "', '" . $detected_in . "', '" . $reproductible . "', '" . $reproduction_method . "')", __LINE__, __FILE__);
		
		$Cache->Generate_module_file('bugtracker');
		
		AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php');
	}
	else
	AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php?error=incomplete#message_helper');
}
else if (isset($_GET['add'])) // ajout d'un bug
{	
	//checking authorization
	if (!$auth_create)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}
	
	$Template->set_filenames(array(
		'bugtracker' => 'bugtracker/bugtracker.tpl'
	));
	
	$contents_editor = AppContext::get_content_formatting_service()->get_default_editor();
	$contents_editor->set_identifier('contents');
	
	$reproduction_method_editor = AppContext::get_content_formatting_service()->get_default_editor();
	$reproduction_method_editor->set_identifier('reproduction_method');
	
	$Template->assign_vars(array(
		'C_DISPLAY_TYPES' 	=> $display_types,
		'C_DISPLAY_CATEGORIES' 	=> $display_categories,
		'C_DISPLAY_VERSIONS' 	=> $display_versions,
		'C_DISPLAY_ADVANCED' 	=> $auth_create_advanced ? true : false,
		'L_ADD_BUG'		=> $LANG['bugs.titles.add_bug'],
		'L_TITLE'		=> $LANG['bugs.labels.fields.title'],
		'L_CONTENT'		=> $LANG['bugs.labels.fields.contents'],
		'L_REQUIRE' 		=> $LANG['require'],
		'L_REQUIRE_TITLE' 	=> $LANG['require_title'],
		'L_REQUIRE_CONTENT'	=> $LANG['require_text'],
		'L_REQUIRE_CATEGORY'	=> $LANG['bugs.notice.require_category'],
		'L_TYPE'		=> $LANG['bugs.labels.fields.type'],
		'L_CATEGORY'		=> $LANG['bugs.labels.fields.category'],
		'L_PRIORITY'		=> $LANG['bugs.labels.fields.priority'],
		'L_SEVERITY'		=> $LANG['bugs.labels.fields.severity'],
		'L_DETECTED_IN'		=> $LANG['bugs.labels.fields.detected_in'],
		'L_REPRODUCTIBLE'	=> $LANG['bugs.labels.fields.reproductible'],
		'L_REPRODUCTION_METHOD'	=> $LANG['bugs.labels.fields.reproduction_method'],
		'L_SUBMIT' 		=> $LANG['submit'],
		'L_PREVIEW' 		=> $LANG['preview'],
		'L_RESET' 		=> $LANG['reset'],
		'L_YES' 		=> $LANG['yes'],
		'L_NO'	 		=> $LANG['no'],
		'CONTENTS'	 	=> FormatingHelper::unparse($bugtracker_config->get_contents_value()),
		'REPRODUCTIBLE_ENABLED' => 'checked="checked"',
		'REPRODUCTIBLE_DISABLED'=> '',
		'REPRODUCTION_METHOD' 	=> '',
		'CONTENTS_KERNEL_EDITOR'=> $contents_editor->display(),
		'METHOD_KERNEL_EDITOR' 	=> $reproduction_method_editor->display(),
		'TOKEN'			=> $Session->get_token()
	));
	
	$Template->assign_block_vars('add', array());
	
	// Gravit�s
	foreach ($severities as $s)
	{
		$Template->assign_block_vars('select_severity', array(
			'SEVERITY' => '<option value="' . $s . '">' . $LANG['bugs.severity.' . $s] . '</option>'
		));
	}
	
	// Priorit�s
	foreach ($priorities as $p)
	{
		$selected = ($p == 'normal') ? 'selected="selected"' : '';
		$Template->assign_block_vars('select_priority', array(
			'PRIORITY' => '<option value="' . $p . '" ' . $selected . '>' . $LANG['bugs.priority.' . $p] . '</option>'
		));
	}
	
	//Types
	foreach ($types as $type)
	{
		$Template->assign_block_vars('select_type', array(
			'TYPE' => '<option value="' . $type . '">' . stripslashes($type) . '</option>'
		));
	}
	
	//Categories
	$Template->assign_block_vars('select_category', array(
		'CATEGORY' => '<option value=""></option>'
	));
	foreach ($categories as $category)
	{
		$Template->assign_block_vars('select_category', array(
			'CATEGORY' => '<option value="' . $category . '">' . stripslashes($category) . '</option>'
		));
	}
	
	//Detected in
	$Template->assign_block_vars('select_detected_in', array(
		'VERSION' => '<option value=""></option>'
	));
	foreach ($versions as $version)
	{
		if ($version['detected_in'] == true)
		{
			$Template->assign_block_vars('select_detected_in', array(
				'VERSION' => '<option value="' . $version['name'] . '">' . stripslashes($version['name']) . '</option>'
			));
		}

	}
	
	// Gestion erreur.
	$get_error = retrieve(GET, 'error', '');
	switch ($get_error)
	{
		case 'incomplete':
		$errstr = $LANG['e_incomplete'];
		break;
		default:
		$errstr = '';
	}
	if (!empty($errstr))
	$Template->put('message_helper', MessageHelper::display($errstr, E_USER_NOTICE));
}
else if (isset($_GET['delete']) && is_numeric($id)) //Suppression du bug.
{
	$Session->csrf_get_protect(); //Protection csrf
	
	//checking authorization
	if (!$User->is_admin())
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}
	
	//On supprime dans la table bugtracker_history.
	$Sql->query_inject("DELETE FROM " . PREFIX . "bugtracker_history WHERE bug_id = '" . $id . "'", __LINE__, __FILE__);

	//On supprime dans la bdd.
	$Sql->query_inject("DELETE FROM " . PREFIX . "bugtracker WHERE id = '" . $id . "'", __LINE__, __FILE__);

	//On supprimes les �ventuels commentaires associ�s.
	CommentsService::delete_comments_topic_module('bugtracker', $id);

	//Mise � jour de la liste des bugs dans le cache de la configuration.
	$Cache->Generate_module_file('bugtracker');

	AppContext::get_response()->redirect(HOST . SCRIPT);
}
else if (!empty($_POST['valid_edit']) && is_numeric($id_post))
{
	//checking authorization
	if (!$auth_create)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}
	
	$title = retrieve(POST, 'title', '');
	$contents = retrieve(POST, 'contents', '', TSTRING_PARSE);
	$category = retrieve(POST, 'category', '');
	$status = retrieve(POST, 'status', '');
	$assigned_to = retrieve(POST, 'assigned_to', '');
	$reproductible = retrieve(POST, 'reproductible', '', TBOOL);
	$reproduction_method = retrieve(POST, 'reproduction_method', '', TSTRING_PARSE);
	
	$category_needed = false;
	if ($display_categories && empty($category))
		$category_needed = true;
	
	//On met � jour
	if (!empty($title) && !empty($contents) && !$category_needed)
	{
		$old_values = $Sql->query_array(PREFIX . 'bugtracker b', '*', "
		JOIN " . DB_TABLE_MEMBER . " a ON (a.user_id = b.author_id)
		WHERE b.id = '" . $id_post . "'", __LINE__, __FILE__);
		
		$type = $display_types ? retrieve(POST, 'type', '') : $old_values['type'];
		$category = $display_categories ? retrieve(POST, 'category', '') : $old_values['category'];
		$detected_in = $display_versions ? retrieve(POST, 'detected_in', '') : $old_values['detected_in'];
		$fixed_in = $display_versions ? retrieve(POST, 'fixed_in', '') : $old_values['fixed_in'];
		
		$severity = $auth_create_advanced ? retrieve(POST, 'severity', '') : $old_values['severity'];
		$priority = $auth_create_advanced ? retrieve(POST, 'priority', '') : $old_values['priority'];
		
		//Champs suppl�mentaires pour l'administrateur
		if ($User->is_admin() || $auth_moderate || (!empty($old_values['assigned_to_id']) && $User->get_attribute('user_id') == $old_values['assigned_to_id']))
		{
			if ($status == 'assigned' && empty($assigned_to)) // Erreur si le statut est "Assign�" et aucun utilisateur n'est s�lectionn�
				AppContext::get_response()->redirect(PATH_TO_ROOT . '/bugtracker/bugtracker.php?edit&id=' . $id_post . '&error=no_user_assigned#message_helper');
			
			// if ($status == 'closed' && empty($fixed_in)) // Erreur si le statut est "Ferm�" et aucune version de correction n'est s�lectionn�e
				// AppContext::get_response()->redirect(PATH_TO_ROOT . '/bugtracker/bugtracker.php?edit&id=' . $id_post . '&error=no_closed_version#message_helper');
			
			$assigned_to_id = (!empty($assigned_to)) ? $Sql->query("SELECT user_id FROM " . DB_TABLE_MEMBER . " WHERE login = '" . $assigned_to . "'", __LINE__, __FILE__) : 0;
			
			if (!empty($assigned_to) && empty($assigned_to_id)) // Erreur si l'utilisateur s�lectionn� n'existe pas
				AppContext::get_response()->redirect(PATH_TO_ROOT . '/bugtracker/bugtracker.php?edit&id=' . $id_post . '&error=unexist_user#message_helper');
			
			$new_values = array(
				'title'			=> $title,
				'contents' 		=> $contents,
				'type' 			=> $type,
				'category' 		=> $category,
				'severity' 		=> $severity,
				'priority' 		=> $priority,		
				'detected_in' 		=> $detected_in,
				'reproductible' 	=> $reproductible,
				'reproduction_method' 	=> $reproduction_method,
				'status' 		=> $status,
				'assigned_to_id'	=> $assigned_to_id,		
				'fixed_in' 		=> $fixed_in
			);
			
			$fields = array('title', 'contents', 'type', 'category', 'severity', 'priority', 'detected_in', 'reproductible', 'reproduction_method', 'status', 'fixed_in', 'assigned_to_id');
			
			$modification = false;
			foreach ($fields as $field)
			{
				if ($old_values[$field] != $new_values[$field])
					$modification = true;
			}
			if ($modification == false)
				AppContext::get_response()->redirect(PATH_TO_ROOT . '/bugtracker/bugtracker.php?edit&id=' . $id_post);
			
			$Sql->query_inject("UPDATE " . PREFIX . "bugtracker SET title = '" . $title . "', contents = '" . $contents . "', status = '" . $status . "', type = '" . $type . "', category = '" . $category . "', severity = '" . $severity . "', priority = '" . $priority . "', assigned_to_id = '" . $assigned_to_id . "', detected_in = '" . $detected_in . "', fixed_in = '" . $fixed_in . "', reproductible = '" . $reproductible . "', reproduction_method = '" . $reproduction_method . "'
			WHERE id = '" . $id_post . "'", __LINE__, __FILE__);
			
			// Envoi d'un MP � l'utilisateur auquel a �t� affect� le bug
			if (!empty($assigned_to_id) && ($old_values['assigned_to_id'] != $assigned_to_id) && ($User->get_attribute('user_id') != $assigned_to_id)) {
				$Privatemsg = new PrivateMsg();
				$Privatemsg->start_conversation(
					$assigned_to_id, 
					sprintf($LANG['bugs.pm.assigned.title'], $LANG['bugs.module_title'], $id_post), 
					sprintf($LANG['bugs.pm.assigned.contents'], '[url]' . HOST . DIR . '/bugtracker/bugtracker.php?view&id=' . $id_post . '[/url]'), 
					'-1', 
					PrivateMsg::SYSTEM_PM
				);
			}
		}
		else
		{
			$new_values = array(
				'title'			=> $title,
				'contents' 		=> $contents,
				'type' 			=> $type,
				'category' 		=> $category,
				'severity' 		=> $severity,
				'priority' 		=> $priority,
				'detected_in' 		=> $detected_in,	
				'reproductible' 	=> $reproductible,			
				'reproduction_method' 	=> $reproduction_method
			);
			
			$fields = array('title', 'contents', 'type', 'category', 'severity', 'priority', 'detected_in', 'reproductible', 'reproduction_method');
			
			$modification = false;
			foreach ($fields as $field)
			{
				if ($old_values[$field] != $new_values[$field])
					$modification = true;
			}
			if ($modification == false)
				AppContext::get_response()->redirect(PATH_TO_ROOT . '/bugtracker/bugtracker.php?edit&id=' . $id_post);
			
			$Sql->query_inject("UPDATE " . PREFIX . "bugtracker SET title = '" . $title . "', contents = '" . $contents . "', type = '" . $type . "', category = '" . $category . "', severity = '" . $severity . "', priority = '" . $priority . "', detected_in = '" . $detected_in . "', reproductible = '" . $reproductible . "'
			WHERE id = '" . $id_post . "'", __LINE__, __FILE__);
		}
		
		foreach ($fields as $field)
		{
			if ($field == 'assigned_to_id')
			{
				$result_assigned = $Sql->query_array(DB_TABLE_MEMBER, "login, level", "WHERE user_id = " . $$field, __LINE__, __FILE__);
				$comment = $LANG['bugs.labels.fields.assigned_to_id'] . ' <a href="' . UserUrlBuilder::profile($field)->absolute() . '" class="' . UserService::get_level_class($result_assigned['level']) . '">' . $result_assigned['login'] . '</a>';
			}
			else if ($field == 'title' || $field == 'severity' || $field == 'priority' || $field == 'type' || $field == 'category' || $field == 'detected_in' || $field == 'fixed_in')
			{
				$old_values[$field] = addslashes($old_values[$field]);
			}
			else if ($field == 'contents')
			{
				$old_values[$field] = '';
				$new_values[$field] = '';
				$comment = $LANG['bugs.notice.contents_update'];
			}
			else if ($field == 'reproduction_method')
			{
				$old_values[$field] = '';
				$new_values[$field] = '';
				$comment = $LANG['bugs.notice.reproduction_method_update'];
			}
			else $comment = '';
			
			if ($old_values[$field] != $new_values[$field])
			{
				$Sql->query_inject("INSERT INTO " . PREFIX . "bugtracker_history (bug_id, updater_id, update_date, updated_field, old_value, new_value, change_comment)
				VALUES('" . $id_post . "', '" . $User->get_id() . "', '" . $now->get_timestamp() . "', '" . $field . "', '" . $old_values[$field] . "', '" . $new_values[$field] . "', '" . $comment . "')", __LINE__, __FILE__);
			}
		}
		
		###### R�g�n�ration du cache #######
		$Cache->Generate_module_file('bugtracker');

		AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php?error=edit_success#message_helper');
	}
	else
	AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php?edit&id= ' . $id_post . '&error=incomplete#message_helper');
}
else if (isset($_GET['edit']) && is_numeric($id)) // edition d'un bug
{
	//checking authorization
	if (!$auth_create)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}
	
	if (empty($id))
		AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php?error=unexist_bug#message_helper');
	
	$Template->set_filenames(array(
		'bugtracker' => 'bugtracker/bugtracker.tpl'
	));
	
	//R�cup�ration de l'id de l'auteur du bug
	$author_id = $Sql->query("SELECT author_id FROM " . PREFIX . "bugtracker WHERE id = '" . $id . "'", __LINE__, __FILE__);
	
	if ($author_id == '-1')
	{
		$result = $Sql->query_array(PREFIX . 'bugtracker', '*', "
		WHERE id = '" . $id . "'", __LINE__, __FILE__);
	}
	else
	{
		$result = $Sql->query_array(PREFIX . 'bugtracker b', '*', "
		JOIN " . DB_TABLE_MEMBER . " a ON (a.user_id = b.author_id)
		WHERE b.id = '" . $id . "'", __LINE__, __FILE__);
	}
	
	//Champs suppl�mentaires pour l'administrateur
	if ($User->is_admin() || $auth_moderate || (!empty($result['assigned_to_id']) && $User->get_attribute('user_id') == $result['assigned_to_id']))
	{
		$assigned_to = !empty($result['assigned_to_id']) ? $Sql->query("SELECT login FROM " . DB_TABLE_MEMBER . " WHERE user_id = '" . $result['assigned_to_id'] . "'", __LINE__, __FILE__) : '';
		$Template->assign_block_vars('edit', array(
			'C_IS_ASSIGNED'			=> true,
			'ID' 				=> $id,
			'TITLE' 			=> $result['title'],
			'CONTENTS' 			=> FormatingHelper::unparse($result['contents']),
			'REPRODUCTIBLE_ENABLED' 	=> ($result['reproductible']) ? 'checked="checked"' : '',
			'REPRODUCTIBLE_DISABLED' 	=> (!$result['reproductible']) ? 'checked="checked"' : '',
			'REPRODUCTION_METHOD' 		=> FormatingHelper::unparse($result['reproduction_method']),
			'AUTHOR' 			=> !empty($result['login']) ? '<a href="' . UserUrlBuilder::profile($result['user_id'])->absolute() . '" class="' . UserService::get_level_class($result['level']) . '">' . $result['login'] . '</a>': $LANG['guest'],
			'ASSIGNED_TO'			=> $assigned_to,
			'DATE' 				=> gmdate_format('date_format', $result['submit_date'])
		));
	}
	else
	{
		$Template->assign_block_vars('edit', array(
			'ID' 				=> $id,
			'TITLE' 			=> $result['title'],
			'CONTENTS' 			=> FormatingHelper::unparse($result['contents']),
			'REPRODUCTIBLE_ENABLED' 	=> ($result['reproductible']) ? 'checked="checked"' : '',
			'REPRODUCTIBLE_DISABLED' 	=> (!$result['reproductible']) ? 'checked="checked"' : ''
		));
	}
	
	$contents_editor = AppContext::get_content_formatting_service()->get_default_editor();
	$contents_editor->set_identifier('contents');
	
	$reproduction_method_editor = AppContext::get_content_formatting_service()->get_default_editor();
	$reproduction_method_editor->set_identifier('reproduction_method');
	
	$Template->assign_vars(array(
		'C_DISPLAY_TYPES' 	=> $display_types,
		'C_DISPLAY_CATEGORIES' 	=> $display_categories,
		'C_DISPLAY_VERSIONS' 	=> $display_versions,
		'C_DISPLAY_ADVANCED' 	=> $auth_create_advanced ? true : false,
		'C_REPRODUCTIBLE' 	=> ($result['reproductible'] == true) ? true : false,
		'L_EDIT_BUG'		=> $LANG['bugs.titles.edit_bug'],
		'L_TITLE'		=> $LANG['bugs.labels.fields.title'],
		'L_CONTENT'		=> $LANG['bugs.labels.fields.contents'],
		'L_REQUIRE' 		=> $LANG['require'],
		'L_REQUIRE_LOGIN' 	=> $LANG['bugs.notice.require_login'],
		'L_REQUIRE_TITLE' 	=> $LANG['require_title'],
		'L_REQUIRE_CONTENT'	=> $LANG['require_text'],
		'L_REQUIRE_CATEGORY'	=> $LANG['bugs.notice.require_category'],
		'L_STATUS'		=> $LANG['bugs.labels.fields.status'],
		'L_TYPE'		=> $LANG['bugs.labels.fields.type'],
		'L_CATEGORY'		=> $LANG['bugs.labels.fields.category'],
		'L_AUTHOR'		=> $LANG['bugs.labels.fields.author_id'],
		'L_ASSIGNED_TO'		=> $LANG['bugs.labels.fields.assigned_to_id'],
		'L_DATE'		=> $LANG['bugs.labels.fields.submit_date'],
		'L_PRIORITY'		=> $LANG['bugs.labels.fields.priority'],
		'L_SEVERITY'		=> $LANG['bugs.labels.fields.severity'],
		'L_DETECTED_IN'		=> $LANG['bugs.labels.fields.detected_in'],
		'L_FIXED_IN'		=> $LANG['bugs.labels.fields.fixed_in'],
		'L_REPRODUCTIBLE'	=> $LANG['bugs.labels.fields.reproductible'],
		'L_REPRODUCTION_METHOD'	=> $LANG['bugs.labels.fields.reproduction_method'],
		'L_SEARCH' 		=> $LANG['search'],
		'L_UPDATE' 		=> $LANG['update'],
		'L_PREVIEW' 		=> $LANG['preview'],
		'L_RESET' 		=> $LANG['reset'],
		'L_YES' 		=> $LANG['yes'],
		'L_NO'	 		=> $LANG['no'],
		'L_JOKER' 		=> $LANG['bugs.notice.joker'],
		'CONTENTS_KERNEL_EDITOR'=> $contents_editor->display(),
		'METHOD_KERNEL_EDITOR' 	=> $reproduction_method_editor->display(),
		'TOKEN'			=> $Session->get_token()
	));
	
	//Types
	$selected = (empty($result['type'])) ? 'selected="selected"' : '';
	$Template->assign_block_vars('edit.select_type', array(
		'TYPE' => '<option value="" ' . $selected . '></option>'
	));
	foreach ($types as $type)
	{
		$selected = ($result['type'] == $type) ? 'selected="selected"' : '';
		$Template->assign_block_vars('edit.select_type', array(
			'TYPE' => '<option value="' . $type . '" ' . $selected . '>' . stripslashes($type) . '</option>'
		));
	}
	
	//Categories
	$selected = (empty($result['category'])) ? 'selected="selected"' : '';
	$Template->assign_block_vars('edit.select_category', array(
		'CATEGORY' => '<option value="" ' . $selected . '></option>'
	));
	foreach ($categories as $category)
	{
		$selected = ($result['category'] == $category) ? 'selected="selected"' : '';
		$Template->assign_block_vars('edit.select_category', array(
			'CATEGORY' => '<option value="' . $category . '" ' . $selected . '>' . stripslashes($category) . '</option>'
		));
	}
	
	//Statut
	foreach ($status_list as $s)
	{
		$selected = ($result['status'] == $s) ? 'selected="selected"' : '';
		$Template->assign_block_vars('edit.select_status', array(
			'STATUS' => '<option value="' . $s . '" ' . $selected . '>' . $LANG['bugs.status.' . $s] . '</option>'
		));
	}
	
	//Gravit�s
	foreach ($severities as $s)
	{
		$selected = ($result['severity'] == $s) ? 'selected="selected"' : '';
		$Template->assign_block_vars('edit.select_severity', array(
			'SEVERITY' => '<option value="' . $s . '" ' . $selected . '>' . $LANG['bugs.severity.' . $s] . '</option>'
		));
	}
	
	//Priorit�s
	foreach ($priorities as $p)
	{
		$selected = ($result['priority'] == $p) ? 'selected="selected"' : '';
		$Template->assign_block_vars('edit.select_priority', array(
			'PRIORITY' => '<option value="' . $p . '" ' . $selected . '>' . $LANG['bugs.priority.' . $p] . '</option>'
		));
	}
	
	//Detected in
	$selected = ($result['detected_in'] == '') ? 'selected="selected"' : '';
	$Template->assign_block_vars('edit.select_detected_in', array(
		'VERSION' => '<option value="" ' . $selected . '></option>'
	));
	foreach ($versions as $version)
	{
		if ($version['detected_in'] == true)
		{
			$selected = ($result['detected_in'] == $version['name']) ? 'selected="selected"' : '';
			$Template->assign_block_vars('edit.select_detected_in', array(
				'VERSION' => '<option value="' . $version['name'] . '" ' . $selected . '>' . stripslashes($version['name']) . '</option>'
			));
		}

	}
	
	//Fixed in
	$selected = ($result['fixed_in'] == '') ? 'selected="selected"' : '';
	$Template->assign_block_vars('edit.select_fixed_in', array(
		'VERSION' => '<option value="" ' . $selected . '></option>'
	));
	foreach ($versions as $version)
	{
		if ($version['fixed_in'] == true)
		{
			$selected = ($result['fixed_in'] == $version['name']) ? 'selected="selected"' : '';
			$Template->assign_block_vars('edit.select_fixed_in', array(
				'VERSION' => '<option value="' . $version['name'] . '" ' . $selected . '>' . stripslashes($version['name']) . '</option>'
			));
		}
	}
	
	//Gestion erreur.
	$get_error = retrieve(GET, 'error', '');
	switch ($get_error)
	{
		case 'incomplete':
		$errstr = $LANG['e_incomplete'];
		$errtyp = E_USER_NOTICE;
		break;
		case 'unexist_user':
		$errstr = $LANG['e_unexist_user'];
		$errtyp = E_USER_WARNING;
		break;
		case 'no_user_assigned':
		$errstr = $LANG['bugs.error.e_no_user_assigned'];
		$errtyp = E_USER_WARNING;
		break;
		case 'no_closed_version':
		$errstr = $LANG['bugs.error.e_no_closed_version'];
		$errtyp = E_USER_WARNING;
		break;
		default:
		$errstr = '';
		$errtyp = E_USER_NOTICE;
	}
	if (!empty($errstr))
		$Template->put('message_helper', MessageHelper::display($errstr, $errtyp));
}
else if (isset($_GET['history']) && is_numeric($id)) // Affichage de l'historique du bug
{
	//checking authorization
	if (!$auth_read)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}
	
	if (empty($id))
		AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php?error=unexist_bug#message_helper');
	
	$Template->set_filenames(array(
		'bugtracker' => 'bugtracker/bugtracker.tpl'
	));
	
	$Template->assign_block_vars('history', array(
		'ID'			=> $id
	));
	
	//Nombre de lignes concernant ce bug dans l'historique..
	$nbr_history = $Sql->query("SELECT COUNT(*) FROM " . PREFIX . "bugtracker_history WHERE bug_id='" . $id . "'", __LINE__, __FILE__);
	
	$result = $Sql->query_while("SELECT *
	FROM " . PREFIX . "bugtracker b
	JOIN " . PREFIX . "bugtracker_history bh ON (bh.bug_id = b.id)
	JOIN " . DB_TABLE_MEMBER . " a ON (a.user_id = bh.updater_id)
	WHERE b.id = '" . $id . "'
	ORDER BY update_date DESC", __LINE__, __FILE__);
	
	while ($row = $Sql->fetch_assoc($result))
	{
		switch ($row['updated_field']) //Coloration du membre suivant son level d'autorisation. 
		{ 		
			case 'title':
			$old_value = $row['old_value'];
			$new_value = $row['new_value'];
			break;

			case 'priority':
			$old_value = $LANG['bugs.priority.' . $row['old_value']];
			$new_value = $LANG['bugs.priority.' . $row['new_value']];
			break;
			
			case 'severity':
			$old_value = $LANG['bugs.severity.' . $row['old_value']];
			$new_value = $LANG['bugs.severity.' . $row['new_value']];
			break;
			
			case 'status': 
			$old_value = $LANG['bugs.status.' . $row['old_value']];
			$new_value = $LANG['bugs.status.' . $row['new_value']];
			break;
			
			case 'reproductible': 
			$old_value = ($row['old_value'] == true) ? $LANG['yes'] : $LANG['no'];
			$new_value = ($row['new_value'] == true) ? $LANG['yes'] : $LANG['no'];
			break;
			
			case 'assigned_to_id': 
			$old_user = !empty($row['old_value']) ? $Sql->query_array(DB_TABLE_MEMBER, 'login, level', "WHERE user_id = '" . $row['old_value'] . "'", __LINE__, __FILE__) : '';
			$new_user = !empty($row['new_value']) ? $Sql->query_array(DB_TABLE_MEMBER, 'login, level', "WHERE user_id = '" . $row['new_value'] . "'", __LINE__, __FILE__) : '';
			$old_value = !empty($old_user) ? '<a href="' . UserUrlBuilder::profile($row['old_value'])->absolute() . '" class="' . UserService::get_level_class($old_user['level']) . '">' . $old_user['login'] . '</a>' : $LANG['bugs.notice.no_one'];
			$new_value = !empty($new_user) ? '<a href="' . UserUrlBuilder::profile($row['new_value'])->absolute() . '" class="' . UserService::get_level_class($new_user['level']) . '">' . $new_user['login'] . '</a>' : $LANG['bugs.notice.no_one'];
			break;
			
			default:
			$old_value = $row['old_value'];
			$new_value = $row['new_value'];
		}
		
		$Template->assign_block_vars('history.bug', array(
			'TOKEN' 	=> $Session->get_token(),
			'UPDATED_FIELD'	=> $LANG['bugs.labels.fields.' . $row['updated_field']],
			'OLD_VALUE'	=> stripslashes($old_value),
			'NEW_VALUE'	=> stripslashes($new_value),
			'COMMENT'	=> $row['change_comment'],
			'UPDATER' 	=> !empty($row['login']) ? '<a href="' . UserUrlBuilder::profile($row['user_id'])->absolute() . '" class="' . UserService::get_level_class($row['level']) . '">' . $row['login'] . '</a>': $LANG['guest'],
			'DATE' 		=> gmdate_format('date_format', $row['update_date'])
		));
	}
	$Sql->query_close($result);
	
	$Template->assign_vars(array(
		'C_NO_HISTORY' 		=> empty($nbr_history) ? true : false,
		'L_HISTORY_BUG'		=> $LANG['bugs.titles.history_bug'],
		'L_NO_HISTORY'		=> $LANG['bugs.notice.no_history'],
		'L_ID' 			=> $LANG['bugs.labels.fields.id'],
		'L_UPDATED_FIELD'	=> $LANG['bugs.labels.fields.updated_field'],
		'L_OLD_VALUE'		=> $LANG['bugs.labels.fields.old_value'],
		'L_NEW_VALUE'		=> $LANG['bugs.labels.fields.new_value'],
		'L_COMMENT'		=> $LANG['bugs.labels.fields.change_comment'],
		'L_UPDATER'		=> $LANG['bugs.labels.fields.updater_id'],
		'L_DATE'		=> $LANG['bugs.labels.fields.update_date'],
		'ID' 			=> $id,
	));
}
else if (isset($_GET['view']) && is_numeric($id)) // Visualisation d'une fiche Bug
{
	//checking authorization
	if (!$auth_read)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}
	
	if (empty($id))
		AppContext::get_response()->redirect(HOST . DIR . '/bugtracker/bugtracker.php?error=unexist_bug#message_helper');
	
  	$Template->set_filenames(array(
		'bugtracker' => 'bugtracker/bugtracker.tpl'
	));
	
	//R�cup�ration de l'id de l'auteur du bug
	$author_id = $Sql->query("SELECT author_id FROM " . PREFIX . "bugtracker WHERE id = '" . $id . "'", __LINE__, __FILE__);
	
	if ($author_id == '-1')
	{
		$result = $Sql->query_array(PREFIX . 'bugtracker', '*', "
		WHERE id = '" . $id . "'", __LINE__, __FILE__);
	}
	else
	{
		$result = $Sql->query_array(PREFIX . 'bugtracker b', '*', "
		JOIN " . DB_TABLE_MEMBER . " a ON (a.user_id = b.author_id)
		WHERE b.id = '" . $id . "'", __LINE__, __FILE__);
	}
	
	if ($User->is_admin() || $auth_moderate || ($User->get_id() == $result['author_id'] && $result['author_id'] != '-1') || (!empty($result['assigned_to_id']) && $User->get_attribute('user_id') == $result['assigned_to_id']))
	{
		$Template->assign_vars(array(
			'C_EDIT_BUG'	=> true,
			'L_UPDATE'	=> $LANG['update']
		));
	}
	
	if ($User->is_admin() || $auth_moderate)
	{
		$Template->assign_vars(array(
			'C_DELETE_BUG'		=> true,
			'L_CONFIRM_DEL_BUG'	=> $LANG['bugs.actions.confirm.del_bug'],
			'L_DELETE' 		=> $LANG['delete']
		));
	}
	
	$Template->assign_vars(array(
		'C_EMPTY_TYPES' 	=> empty($types) ? true : false,
		'C_EMPTY_CATEGORIES' 	=> empty($categories) ? true : false,
		'C_EMPTY_VERSIONS' 	=> empty($versions) ? true : false,
		'C_REPRODUCTIBLE' 	=> ($result['reproductible'] == true && !empty($result['reproduction_method'])) ? true : false,
		'L_VIEW_BUG' 		=> $LANG['bugs.titles.view_bug'],
		'L_BUG_INFOS' 		=> $LANG['bugs.titles.bugs_infos'],
		'L_BUG_TREATMENT_STATE'	=> $LANG['bugs.titles.bugs_treatment_state'],
		'L_ID' 			=> $LANG['bugs.labels.fields.id'],
		'L_DETECTED_IN'		=> $LANG['bugs.labels.fields.detected_in'],
		'L_FIXED_IN'		=> $LANG['bugs.labels.fields.fixed_in'],
		'L_STATUS'		=> $LANG['bugs.labels.fields.status'],
		'L_TYPE'		=> $LANG['bugs.labels.fields.type'],
		'L_CATEGORY'		=> $LANG['bugs.labels.fields.category'],
		'L_SEVERITY'		=> $LANG['bugs.labels.fields.severity'],
		'L_PRIORITY'		=> $LANG['bugs.labels.fields.priority'],
		'L_REPRODUCTIBLE'	=> $LANG['bugs.labels.fields.reproductible'],
		'L_REPRODUCTION_METHOD'	=> $LANG['bugs.labels.fields.reproduction_method'],
		'L_ASSIGNED_TO'		=> $LANG['bugs.labels.fields.assigned_to_id'],
		'L_DETECTED_BY' 	=> $LANG['bugs.labels.fields.author_id'],
		'C_HISTORY_BUG'		=> true,
		'L_HISTORY'	 	=> $LANG['bugs.actions.history'],
		'L_ON' 			=> $LANG['on'],
		'U_COM' 		=> $comments_activated ? '<a href="' . PATH_TO_ROOT . '/bugtracker/bugtracker' . url('.php?view&amp;id=' . $id . '&amp;com=0', '-' . $id . '.php?com=0') . '">' . CommentsService::get_number_and_lang_comments('bugtracker', $id) . '</a>' : ''
	));
	
	if (!empty($result['assigned_to_id'])) {
		$result_assigned = $Sql->query_array(DB_TABLE_MEMBER, "login, level", "WHERE user_id = " . $result['assigned_to_id'], __LINE__, __FILE__);
		$user_assigned = '<a href="' . UserUrlBuilder::profile($result['assigned_to_id'])->absolute() . '" class="' . UserService::get_level_class($result_assigned['level']) . '">' . $result_assigned['login'] . '</a>';
	}
	else
		$user_assigned = $LANG['bugs.notice.no_one'];
		
	$Template->assign_block_vars('view', array(
		'TOKEN' 		=> $Session->get_token(),
		'ID' 			=> $id,
		'TITLE' 		=> $result['title'],
		'CONTENTS' 		=> FormatingHelper::second_parse($result['contents']),
		'STATUS' 		=> $LANG['bugs.status.' . $result['status']],
		'TYPE'			=> !empty($result['type']) ? stripslashes($result['type']) : $LANG['bugs.notice.none'],
		'CATEGORY'		=> !empty($result['category']) ? stripslashes($result['category']) : $LANG['bugs.notice.none_e'],
		'PRIORITY' 		=> $LANG['bugs.priority.' . $result['priority']],
		'SEVERITY' 		=> $LANG['bugs.severity.' . $result['severity']],
		'REPRODUCTIBLE'		=> ($result['reproductible'] == true) ? $LANG['yes'] : $LANG['no'],
		'REPRODUCTION_METHOD'	=> FormatingHelper::second_parse($result['reproduction_method']),
		'DETECTED_IN' 		=> !empty($result['detected_in']) ? $result['detected_in'] : $LANG['bugs.notice.not_defined'],
		'FIXED_IN' 		=> !empty($result['fixed_in']) ? $result['fixed_in'] : $LANG['bugs.notice.not_defined'],
		'USER_ASSIGNED'		=> $user_assigned,
		'AUTHOR' 		=> !empty($result['login']) ? '<a href="' . UserUrlBuilder::profile($result['user_id'])->absolute() . '" class="' . UserService::get_level_class($result['level']) . '">' . $result['login'] . '</a>': $LANG['guest'],
		'SUBMIT_DATE'		=> gmdate_format('date_format', $result['submit_date'])
	));
	
	//Affichage des commentaires
	if (isset($_GET['com']) && is_numeric($id))
	{
		$comments_topic = new BugtrackerCommentsTopic();
		$comments_topic->set_id_in_module($id);
		$comments_topic->set_url(new Url('/bugtracker/bugtracker.php?view&amp;id=' . $id . '&com=0'));
		$Template->put_all(array(
			'COMMENTS' => CommentsService::display($comments_topic)->render()
		));
	}      
}
else if (isset($_GET['solved'])) // liste des bugs corrig�s
{
	//checking authorization
	if (!$auth_read)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}

  	$Template->set_filenames(array(
		'bugtracker' => 'bugtracker/bugtracker.tpl'
	));
	
	//Nombre de bugs
	$nbr_bugs = $Sql->query("SELECT COUNT(*) FROM " . PREFIX . "bugtracker WHERE status = 'closed' OR status = 'rejected'", __LINE__, __FILE__);
	
	$Pagination = new DeprecatedPagination();
	
	$get_sort = retrieve(GET, 'sort', '');
	switch ($get_sort)
	{
		case 'id' :
			$sort = 'id';
			break;
		case 'title' :
			$sort = 'title';
			break;
		case 'type' :
			$sort = 'type';
			break;
		case 'severity' :
			$sort = 'severity';
			break;
		case 'status' :
			$sort = 'status';
			break;
		case 'comments' :
			$sort = 'nbr_com';
			break;
		case 'date' :
			$sort = 'submit_date';
			break;
		default :
			$sort = 'submit_date';
	}
	
	$get_mode = retrieve(GET, 'mode', '');
	$mode = ($get_mode == 'asc') ? 'ASC' : 'DESC';
	
	if ($auth_create || $User->is_admin())
	{
		$Template->assign_vars(array(
			'ADD_BUG' 	=> '<a href="bugtracker' . url('.php?add') . '">' . $LANG['bugs.actions.add'] . '</a>'
		));
	}
	
	$no_bugs_colspan = 6;
	//Activation de la colonne "Actions" si administrateur
	if ($User->is_admin() || $auth_moderate)
	{
		$Template->assign_vars(array(
			'C_IS_ADMIN'	=> true
		));
		$no_bugs_colspan = $no_bugs_colspan + 1;
	}

	if ($comments_activated == true) $no_bugs_colspan = $no_bugs_colspan + 1;
	
	$Template->assign_vars(array(
		'C_DISPLAY_TYPES' 	=> $display_types,
		'C_NO_BUGS' 		=> empty($nbr_bugs) ? true : false,
		'NO_BUGS_COLSPAN' 	=> $no_bugs_colspan,
		'C_COM' 		=> ($comments_activated == true) ? true : false,
		'PAGINATION' 		=> $Pagination->display('bugtracker' . url('.php?solved&amp;p=%d' . (!empty($get_sort) ? '&amp;sort=' . $get_sort : '') . (!empty($get_mode) ? '&amp;mode=' . $get_mode : '')), $nbr_bugs, 'p', $items_per_page, 3),
		'L_CONFIRM_DEL_BUG' 	=> $LANG['bugs.actions.confirm.del_bug'],
		'L_BUGS_LIST' 		=> $LANG['bugs.titles.bugs_list'],
		'L_ID' 			=> $LANG['bugs.labels.fields.id'],
		'L_TITLE'		=> $LANG['bugs.labels.fields.title'],
		'L_TYPE'		=> $LANG['bugs.labels.fields.type'],
		'L_SEVERITY'		=> $LANG['bugs.labels.fields.severity'],
		'L_STATUS'		=> $LANG['bugs.labels.fields.status'],
		'L_DATE'		=> $LANG['bugs.labels.fields.submit_date'],
		'L_COMMENTS'		=> $LANG['title_com'],
		'L_NO_BUG' 		=> $LANG['bugs.notice.no_bug'],
		'L_NO_SOLVED_BUG'	=> $LANG['bugs.notice.no_bug_solved'],
		'L_ACTIONS' 		=> $LANG['bugs.actions'],
		'L_UPDATE' 		=> $LANG['update'],
		'L_HISTORY' 		=> $LANG['bugs.actions.history'],
		'L_DELETE' 		=> $LANG['delete'],
		'L_UNSOLVED' 		=> $LANG['bugs.titles.unsolved_bug'],
		'L_SOLVED' 		=> $LANG['bugs.titles.solved_bug'],
		'L_STATS' 		=> $LANG['bugs.titles.bugs_stats'],
		'U_BUG_ID_TOP' 		=> url('.php?solved&amp;sort=id&amp;mode=desc'),
		'U_BUG_ID_BOTTOM' 	=> url('.php?solved&amp;sort=id&amp;mode=asc'),
		'U_BUG_TITLE_TOP' 	=> url('.php?solved&amp;sort=title&amp;mode=desc'),
		'U_BUG_TITLE_BOTTOM' 	=> url('.php?solved&amp;sort=title&amp;mode=asc'),
		'U_BUG_TYPE_TOP' 	=> url('.php?solved&amp;sort=type&amp;mode=desc'),
		'U_BUG_TYPE_BOTTOM' 	=> url('.php?solved&amp;sort=type&amp;mode=asc'),
		'U_BUG_SEVERITY_TOP' 	=> url('.php?solved&amp;sort=severity&amp;mode=desc'),
		'U_BUG_SEVERITY_BOTTOM'	=> url('.php?solved&amp;sort=severity&amp;mode=asc'),
		'U_BUG_STATUS_TOP'	=> url('.php?solved&amp;sort=status&amp;mode=desc'),
		'U_BUG_STATUS_BOTTOM'	=> url('.php?solved&amp;sort=status&amp;mode=asc'),
		'U_BUG_COMMENTS_TOP' 	=> url('.php?solved&amp;sort=comments&amp;mode=desc'),
		'U_BUG_COMMENTS_BOTTOM'	=> url('.php?solved&amp;sort=comments&amp;mode=asc'),
		'U_BUG_DATE_TOP' 	=> url('.php?solved&amp;sort=date&amp;mode=desc'),
		'U_BUG_DATE_BOTTOM' 	=> url('.php?solved&amp;sort=date&amp;mode=asc')
	));
	
	$Template->assign_block_vars('solved', array());
	
	$result = $Sql->query_while("SELECT *
	FROM " . PREFIX . "bugtracker
	WHERE status = 'closed' OR status = 'rejected'
	ORDER BY " . $sort . " " . $mode .
	$Sql->limit($Pagination->get_first_msg($items_per_page, 'p'), $items_per_page), __LINE__, __FILE__); //Bugs enregistr�s.
	while ($row = $Sql->fetch_assoc($result))
	{
		switch ($row['status'])
		{
		case 'closed' :
			$color = $severity_color = 'style="background-color:#' . $closed_bug_color . ';"';
			break;
		case 'rejected' :
			$color = $severity_color = 'style="background-color:#' . $rejected_bug_color . ';"';
			break;
		default :
			$color = '';
			$severity_color = 'style="background-color:#' . $bugtracker_config->get_severity_color($row['severity']) . ';"';
		}
		
		//Nombre de commentaires
		$nbr_coms = $Sql->query("SELECT number_comments FROM " . PREFIX . "comments_topic WHERE module_id = 'bugtracker' AND id_in_module = '" . $row['id'] . "'", __LINE__, __FILE__);

		$Template->assign_block_vars('solved.bugclosed', array(
			'ID'			=> $row['id'],
			'TITLE'			=> $row['title'],
			'TYPE'			=> !empty($row['type']) ? stripslashes($row['type']) : $LANG['bugs.notice.none'],
			'SEVERITY'		=> $LANG['bugs.severity.' . $row['severity']],
			'STATUS'		=> $LANG['bugs.status.' . $row['status']],
			'COLOR' 		=> $color,
			'SEVERITY_COLOR'	=> $severity_color,
			'COMMENTS'		=> '<a href="bugtracker' . url('.php?view&id=' . $row['id'] . '&com=0#anchor_bugtracker') . '">' . (empty($nbr_coms) ? 0 : $nbr_coms) . '</a>',
			'DATE' 			=> gmdate_format('date_format', $row['submit_date'])
		));

	}
	$Sql->query_close($result);
	
	//Gestion erreur.
	$get_error = retrieve(GET, 'error', '');
	switch ($get_error)
	{
		case 'edit_success':
		$errstr = $LANG['bugs.error.e_edit_success'];
		$errtyp = E_USER_SUCCESS;
		break;
		case 'unexist_bug':
		$errstr = $LANG['bugs.error.e_unexist_bug'];
		$errtyp = E_USER_WARNING;
		break;
		default:
		$errstr = '';
		$errtyp = E_USER_NOTICE;
	}
	if (!empty($errstr))
		$Template->put('message_helper', MessageHelper::display($errstr, $errtyp));
}
else if (isset($_GET['stats'])) // Statistiques
{
	//checking authorization
	if (!$auth_read)
	{
		$error_controller = PHPBoostErrors::user_not_authorized();
		DispatchManager::redirect($error_controller);
	}

  	$Template->set_filenames(array(
		'bugtracker' => 'bugtracker/bugtracker.tpl'
	));
	
	//Nombre de bugs
	$nbr_bugs = $Sql->query("SELECT COUNT(*) FROM " . PREFIX . "bugtracker", __LINE__, __FILE__);
	$nbr_bugs_not_rejected = $Sql->query("SELECT COUNT(*) FROM " . PREFIX . "bugtracker WHERE status != 'rejected'", __LINE__, __FILE__);
	
	if ($auth_create || $User->is_admin())
	{
		$Template->assign_vars(array(
			'ADD_BUG' 	=> '<a href="bugtracker' . url('.php?add') . '">' . $LANG['bugs.actions.add'] . '</a>'
		));
	}
	
	$Template->assign_vars(array(
		'C_NO_BUGS' 		=> empty($nbr_bugs) ? true : false,
		'C_NO_BUGS_NOT_REJECTED'=> empty($nbr_bugs_not_rejected) ? true : false,
		'L_BUGS_STATS' 		=> $LANG['bugs.titles.bugs_stats'],
		'L_BUGS_LIST' 		=> $LANG['bugs.titles.bugs_list'],
		'L_UNSOLVED' 		=> $LANG['bugs.titles.unsolved_bug'],
		'L_SOLVED' 		=> $LANG['bugs.titles.solved_bug'],
		'L_STATS' 		=> $LANG['bugs.titles.bugs_stats'],
		'L_STATUS'		=> $LANG['bugs.labels.fields.status'],
		'L_NUMBER'		=> $LANG['bugs.labels.number'],
		'L_NO_BUG' 		=> $LANG['bugs.notice.no_bug'],
		'L_TOP_TEN_POSTERS'	=> $LANG['bugs.labels.top_10_posters'],
		'L_PSEUDO'		=> $LANG['pseudo']
	));
	
	$Template->assign_block_vars('stats', array());
	
	$result = $Sql->query_while("SELECT status, COUNT(*) as nb_bugs
	FROM " . PREFIX . "bugtracker
	GROUP BY status
	ORDER BY status ASC
	", __LINE__, __FILE__);
	while ($row = $Sql->fetch_assoc($result))
	{
		$Template->assign_block_vars('stats.status', array(
			'NAME'		=> $LANG['bugs.status.' . $row['status']],
			'NUMBER'	=> $row['nb_bugs']
		));
	}
	$Sql->query_close($result);
	
	$i = 1;
	$result = $Sql->query_while("SELECT user_id, login, level, COUNT(*) as nb_bugs
	FROM " . PREFIX . "bugtracker b
	JOIN " . DB_TABLE_MEMBER . " a ON (a.user_id = b.author_id)
	WHERE status != 'rejected'
	GROUP BY author_id
	ORDER BY nb_bugs DESC
	" . $Sql->limit(0, 10), __LINE__, __FILE__);
	while ($row = $Sql->fetch_assoc($result))
	{
		$Template->assign_block_vars('stats.top_poster', array(
			'ID' 		=> $i,
			'U_USER_PROFILE'=> UserUrlBuilder::profile($row['user_id'])->absolute(),
			'LOGIN' 	=> !empty($row['login']) ? '<a href="' . UserUrlBuilder::profile($row['user_id'])->absolute() . '" class="' . UserService::get_level_class($row['level']) . '">' . $row['login'] . '</a>': $LANG['guest'],
			'USER_BUGS' 	=> $row['nb_bugs']
		));

		$i++;
	}
	$Sql->query_close($result);
}
else // Affichage de la liste
{
	$modulesLoader = AppContext::get_extension_provider_service();
	$module = $modulesLoader->get_provider('bugtracker');
	if ($module->has_extension_point(HomePageExtensionPoint::EXTENSION_POINT))
	{
		echo $module->get_extension_point(HomePageExtensionPoint::EXTENSION_POINT)->get_home_page()->get_view()->display();
	}
}

$Template->pparse('bugtracker');

include_once('../kernel/footer.php');
?>