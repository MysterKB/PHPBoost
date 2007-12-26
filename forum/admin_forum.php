<?php
/*##################################################
 *                               admin_forum.php
 *                            -------------------
 *   begin                : October 30, 2005
 *   copyright          : (C) 2005 Viarre R�gis
 *   email                : crowkait@phpboost.com
 *
 *
 *
###################################################
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
###################################################*/

include_once('../includes/admin_begin.php');
include_once('../forum/lang/' . $CONFIG['lang'] . '/forum_' . $CONFIG['lang'] . '.php'); //Chargement de la langue du module.
define('TITLE', $LANG['administration']);
include_once('../includes/admin_header.php');

$id = !empty($_GET['id']) ? numeric($_GET['id']) : 0;
$del = !empty($_GET['del']) ? numeric($_GET['del']) : 0;
$move = !empty($_GET['move']) ? trim($_GET['move']) : 0;

//Si c'est confirm� on execute
if( !empty($_POST['valid']) && !empty($id) )
{
	$cache->load_file('forum');
	
	$to = !empty($_POST['category']) ? numeric($_POST['category']) : 0;
	$name = !empty($_POST['name']) ? securit($_POST['name']) : '';
	$subname = !empty($_POST['desc']) ? securit($_POST['desc']) : '';
	$status = isset($_POST['status']) ? numeric($_POST['status']) : 1;
	$aprob = isset($_POST['aprob']) ? numeric($_POST['aprob']) : 0;  
	$auth_read = isset($_POST['groups_authr']) ? $_POST['groups_authr'] : ''; 
	$auth_write = isset($_POST['groups_authw']) ? $_POST['groups_authw'] : ''; 
	$auth_edit = isset($_POST['groups_authx']) ? $_POST['groups_authx'] : ''; 

	//G�n�ration du tableau des droits.
	$array_auth_all = $groups->return_array_auth($auth_read, $auth_write, $auth_edit);
		
	if( !empty($name) )
	{
		$sql->query_inject("UPDATE ".PREFIX."forum_cats SET name = '" . $name . "', subname = '" . $subname . "', status = '" . $status . "', aprob = '" . $aprob . "', auth = '" . securit(serialize($array_auth_all), HTML_NO_PROTECT) . "' WHERE id = '" . $id . "'", __LINE__, __FILE__);

		//Emp�che le d�placement dans une cat�gorie fille.
		$to = $sql->query("SELECT id FROM ".PREFIX."forum_cats WHERE id = '" . $to . "' AND id_left NOT BETWEEN '" . $CAT_FORUM[$id]['id_left'] . "' AND '" . $CAT_FORUM[$id]['id_right'] . "'", __LINE__, __FILE__);
		 
		//Cat�gorie parente chang�e?
		$change_cat = !empty($to) ? !($CAT_FORUM[$to]['id_left'] < $CAT_FORUM[$id]['id_left'] && $CAT_FORUM[$to]['id_right'] > $CAT_FORUM[$id]['id_right'] && ($CAT_FORUM[$id]['level'] - 1) == $CAT_FORUM[$to]['level']) : $CAT_FORUM[$id]['level'] > 0;		
		if( $change_cat )
		{
			//On v�rifie si la cat�gorie contient des sous forums.
			$nbr_cat = (($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$id]['id_left'] - 1) / 2) + 1;
		
			//Sous forums du forum � supprimer.
			$list_cats = '';
			$result = $sql->query_while("SELECT id
			FROM ".PREFIX."forum_cats 
			WHERE id_left BETWEEN '" . $CAT_FORUM[$id]['id_left'] . "' AND '" . $CAT_FORUM[$id]['id_right'] . "'
			ORDER BY id_left", __LINE__, __FILE__);
			while( $row = $sql->sql_fetch_assoc($result) )
			{
				$list_cats .= $row['id'] . ', ';
			}
			$sql->close($result);
			$list_cats = trim($list_cats, ', ');
			
			//Forums parent du forum � supprimer.
			$list_parent_cats = '';
			$result = $sql->query_while("SELECT id 
			FROM ".PREFIX."forum_cats 
			WHERE id_left < '" . $CAT_FORUM[$id]['id_left'] . "' AND id_right > '" . $CAT_FORUM[$id]['id_right'] . "'", __LINE__, __FILE__);
			while( $row = $sql->sql_fetch_assoc($result) )
			{
				$list_parent_cats .= $row['id'] . ', ';
			}
			$sql->close($result);
			$list_parent_cats = trim($list_parent_cats, ', ');
			
			//Pr�caution pour �viter erreur fatale, cas impossible si coh�rence de l'arbre respect�e.
			if( empty($list_cats) )
			{
				header('location:' . HOST . SCRIPT);
				exit;
			}

			//Dernier topic des parents du forum � supprimer.
			if( !empty($list_parent_cats) )
			{
				$max_timestamp_parent = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
				$max_topic_id_parent = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . $max_timestamp_parent . "'", __LINE__, __FILE__); 
			}
			
			//Dernier topic des enfants du forum � supprimer.
			if( !empty($to) )
			{
				//Forums parents du forum cible.
				$list_parent_cats_to = '';
				$result = $sql->query_while("SELECT id, level 
				FROM ".PREFIX."forum_cats 
				WHERE id_left <= '" . $CAT_FORUM[$to]['id_left'] . "' AND id_right >= '" . $CAT_FORUM[$to]['id_right'] . "'", __LINE__, __FILE__);
				while( $row = $sql->sql_fetch_assoc($result) )
				{
					$list_parent_cats_to .= $row['id'] . ', ';
				}
				$sql->close($result);
				$list_parent_cats_to = trim($list_parent_cats_to, ', ');
						
				if( empty($list_parent_cats_to) )
					$clause_parent_cats_to = " id = '" . $to . "'";
				else
					$clause_parent_cats_to = " id IN (" . $list_parent_cats_to . ")";
					
				//R�cup�ration de l'id de dernier topic.
				$max_timestamp = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_cats . ")", __LINE__, __FILE__);
				if( empty($list_parent_cats_to) )
					$max_timestamp_to = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat = '" . $to . "'", __LINE__, __FILE__); 
				else
					$max_timestamp_to = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_parent_cats_to . ")", __LINE__, __FILE__);
				
				$max_topic_id = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . max($max_timestamp, $max_timestamp_to) . "'", __LINE__, __FILE__);
			}

			########## Suppression ##########
			//On supprime virtuellement (changement de signe des bornes) les enfants.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = - id_left, id_right = - id_right WHERE id IN (" . $list_cats . ")", __LINE__, __FILE__);					
			
			//On modifie les bornes droites et le last_topic_id des parents.
			if( !empty($list_parent_cats) )
			{
				$sql->query_inject("UPDATE ".PREFIX."forum_cats SET last_topic_id = '" . numeric($max_topic_id_parent) . "', id_right = id_right - '" . ( $nbr_cat*2) . "' WHERE id IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
			}
			
			//On r�duit la taille de l'arbre du nombre de forum supprim� � partir de la position de celui-ci.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left - '" . ($nbr_cat*2) . "', id_right = id_right - '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_FORUM[$id]['id_right'] . "'", __LINE__, __FILE__);

			########## Ajout ##########
			if( !empty($to) ) //Forum cible diff�rent de la racine.
			{
				//On modifie les bornes droites des parents de la cible.
				$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_right = id_right + '" . ($nbr_cat*2) . "' WHERE " . $clause_parent_cats_to, __LINE__, __FILE__);

				//On augmente la taille de l'arbre du nombre de forum supprim� � partir de la position du forum cible.
				if( $CAT_FORUM[$id]['id_left'] > $CAT_FORUM[$to]['id_left']  ) //Direction forum source -> forum cible.
				{	
					$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_FORUM[$to]['id_right'] . "'", __LINE__, __FILE__);						
					$limit = $CAT_FORUM[$to]['id_right'];
					$end = $limit + ($nbr_cat*2) - 1;
				}
				else
				{	
					$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . ($CAT_FORUM[$to]['id_right'] - ($nbr_cat*2)) . "'", __LINE__, __FILE__);
					$limit = $CAT_FORUM[$to]['id_right'] - ($nbr_cat*2);
					$end = $limit + ($nbr_cat*2) - 1;						
				}	
				//On replace les forums supprim�s virtuellement.
				$array_sub_cats = explode(', ', $list_cats);
				$z = 0;
				for($i = $limit; $i <= $end; $i = $i + 2)
				{
					$id_left = $limit + ($CAT_FORUM[$array_sub_cats[$z]]['id_left'] - $CAT_FORUM[$id]['id_left']);
					$id_right = $end - ($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$array_sub_cats[$z]]['id_right']);
					$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'", __LINE__, __FILE__);
					$z++;
				}
					
				//On met � jour le nouveau forum.
				$sql->query_inject("UPDATE ".PREFIX."forum_cats SET level = level - '" . (($CAT_FORUM[$id]['level'] - $CAT_FORUM[$to]['level']) - 1) . "' WHERE id IN (" . $list_cats . ")", __LINE__, __FILE__);
				$sql->query_inject("UPDATE ".PREFIX."forum_cats SET last_topic_id = '" . numeric($max_topic_id) . "' WHERE " . $clause_parent_cats_to, __LINE__, __FILE__);
			}
			else //Racine
			{
				$max_id = $sql->query("SELECT MAX(id_right) FROM ".PREFIX."forum_cats", __LINE__, __FILE__);
				//On replace les forums supprim�s virtuellement.
				$array_sub_cats = explode(', ', $list_cats);
				$z = 0;
				$limit = $max_id + 1;
				$end = $limit + ($nbr_cat*2) - 1;	
				for($i = $limit; $i <= $end; $i = $i + 2)
				{
					$id_left = $limit + ($CAT_FORUM[$array_sub_cats[$z]]['id_left'] - $CAT_FORUM[$id]['id_left']);
					$id_right = $end - ($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$array_sub_cats[$z]]['id_right']);
					$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'", __LINE__, __FILE__);
					$z++;
				}		
				$sql->query_inject("UPDATE ".PREFIX."forum_cats SET level = level - '" . ($CAT_FORUM[$id]['level'] - $CAT_FORUM[$to]['level']) . "' WHERE id IN (" . $list_cats . ")", __LINE__, __FILE__);		
			}
		}
		
		$cache->generate_module_file('forum');
	}
	else
	{
		header('location:' . HOST . DIR . '/forum/admin_forum.php?id=' . $id . '&error=incomplete');
		exit;
	}

	header('location:' . HOST . DIR . '/forum/admin_forum.php');
	exit;
}
elseif( !empty($del) ) //Suppression de la cat�gorie/sous-cat�gorie.
{
	$cache->load_file('forum');
	
	$confirm_delete = false;	
	
	$idcat = $sql->query("SELECT id FROM ".PREFIX."forum_cats WHERE id = '" . $del . "'", __LINE__, __FILE__);
	if( !empty($idcat) && isset($CAT_FORUM[$idcat]) )
	{
		//On v�rifie si la cat�gorie contient des sous forums.
		$nbr_sub_cat = (($CAT_FORUM[$idcat]['id_right'] - $CAT_FORUM[$idcat]['id_left'] - 1) / 2);
		//On v�rifie si la cat�gorie ne contient pas de topic.
		$check_topic = $sql->query("SELECT COUNT(*) FROM ".PREFIX."forum_topics WHERE idcat = '" . $idcat . "'", __LINE__, __FILE__);
		
		if( $check_topic == 0 && $nbr_sub_cat == 0 ) //Si vide on supprime simplement, la cat�gorie.
		{
			$confirm_delete = true;
		}
		else //Sinon on propose de d�placer les topics existants dans une autre cat�gorie.
		{
			if( empty($_POST['del_cat']) )
			{
				$template->set_filenames(array(
					'admin_forum_cat_del' => '../templates/' . $CONFIG['theme'] . '/forum/admin_forum_cat_del.tpl'
				));

				if( $check_topic > 0 ) //Conserve les topics.
				{
					//Listing des cat�gories disponibles, sauf celle qui va �tre supprim�e.		
					$forums = '';
					$result = $sql->query_while("SELECT id, name, level
					FROM ".PREFIX."forum_cats 
					WHERE id_left NOT BETWEEN '" . $CAT_FORUM[$idcat]['id_left'] . "' AND '" . $CAT_FORUM[$idcat]['id_right'] . "'
					ORDER BY id_left", __LINE__, __FILE__);
					while( $row = $sql->sql_fetch_assoc($result) )
					{	
						$margin = ($row['level'] > 0) ? str_repeat('--------', $row['level']) : '--';
						$disabled = ($row['level'] > 0) ? '' : ' disabled="disabled"';
						$forums .= '<option value="' . $row['id'] . '"' . $disabled . '>' . $margin . ' ' . $row['name'] . '</option>';
					}
					$sql->close($result);
					
					$template->assign_block_vars('topics', array(
						'FORUMS' => $forums,
						'L_KEEP' => $LANG['keep_topic'],
						'L_MOVE_TOPICS' => $LANG['move_topics_to'],
						'L_EXPLAIN_CAT' => sprintf($LANG['error_warning'], sprintf((($check_topic > 1) ? $LANG['explain_topics'] : $LANG['explain_topic']), $check_topic), '', '')
					));
				}		
				if( $nbr_sub_cat > 0 ) //Converse uniquement les sous-forums.
				{			
					//Listing des cat�gories disponibles, sauf celle qui va �tre supprim�e.		
					$forums = '<option value="0">' . $LANG['root'] . '</option>';
					$result = $sql->query_while("SELECT id, name, level
					FROM ".PREFIX."forum_cats 
					WHERE id_left NOT BETWEEN '" . $CAT_FORUM[$idcat]['id_left'] . "' AND '" . $CAT_FORUM[$idcat]['id_right'] . "'
					ORDER BY id_left", __LINE__, __FILE__);
					while( $row = $sql->sql_fetch_assoc($result) )
					{	
						$margin = ($row['level'] > 0) ? str_repeat('--------', $row['level']) : '--';
						$forums .= '<option value="' . $row['id'] . '">' . $margin . ' ' . $row['name'] . '</option>';
					}
					$sql->close($result);
					
					$template->assign_block_vars('subforums', array(
						'FORUMS' => $forums,
						'L_KEEP' => $LANG['keep_subforum'],
						'L_MOVE_FORUMS' => $LANG['move_sub_forums_to'],
						'L_EXPLAIN_CAT' => sprintf($LANG['error_warning'], sprintf((($nbr_sub_cat > 1) ? $LANG['explain_subcats'] : $LANG['explain_subcat']), $nbr_sub_cat), '', '')
					));
				}
		
				$forum_name = $sql->query("SELECT name FROM ".PREFIX."forum_cats WHERE id = '" . $idcat . "'", __LINE__, __FILE__);
				$template->assign_vars(array(
					'IDCAT' => $idcat,
					'FORUM_NAME' => $forum_name,
					'L_REQUIRE_SUBCAT' => $LANG['require_subcat'],
					'L_FORUM_MANAGEMENT' => $LANG['forum_management'],
					'L_CAT_MANAGEMENT' => $LANG['cat_management'],
					'L_ADD_CAT' => $LANG['cat_add'],
					'L_FORUM_CONFIG' => $LANG['forum_config'],
					'L_FORUM_GROUPS' => $LANG['forum_groups_config'],
					'L_CAT_TARGET' => $LANG['cat_target'],
					'L_DEL_ALL' => $LANG['del_all'],
					'L_DEL_FORUM_CONTENTS' => sprintf($LANG['del_forum_contents'], $forum_name),
					'L_SUBMIT' => $LANG['submit'],
				));
				
				$template->pparse('admin_forum_cat_del'); //Traitement du modele	
			}
			else //Traitements.
			{			
				if( !empty($_POST['del_conf']) )
				{
					$confirm_delete = true;
				}
				else
				{
					//D�placement de sous forums.
					$f_to = !empty($_POST['f_to']) ? numeric($_POST['f_to']) : 0;
					$f_to = $sql->query("SELECT id FROM ".PREFIX."forum_cats WHERE id = '" . $f_to . "' AND id_left NOT BETWEEN '" . $CAT_FORUM[$idcat]['id_left'] . "' AND '" . $CAT_FORUM[$idcat]['id_right'] . "'", __LINE__, __FILE__);
					
					//D�placement de topics.
					$t_to = !empty($_POST['t_to']) ? numeric($_POST['t_to']) : 0;
					$t_to = $sql->query("SELECT id FROM ".PREFIX."forum_cats WHERE id = '" . $t_to . "' AND id != '" . $idcat . "'", __LINE__, __FILE__);
					
					//D�placement des topics dans la cat�gorie s�lectionn�e.
					if( !empty($t_to) )
					{
						//On va chercher la somme du nombre de messages dans la table topics
						$nbr_msg = $sql->query("SELECT SUM(nbr_msg) FROM ".PREFIX."forum_topics WHERE idcat = '" . $idcat . "'", __LINE__, __FILE__);
						$nbr_msg = !empty($nbr_msg) ? $nbr_msg : 0;
						//Nombre de topics.
						$nbr_topic = $sql->query("SELECT COUNT(*) FROM ".PREFIX."forum_topics WHERE idcat = '" . $idcat . "'", __LINE__, __FILE__); 
						$nbr_topic = !empty($nbr_topic) ? $nbr_topic : 0;
						
						$max_timestamp = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat = '" . $idcat . "'", __LINE__, __FILE__);
						$max_timestamp_to = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat = '" . $t_to . "'", __LINE__, __FILE__); 
						$max_topic_id = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . max($max_timestamp, $max_timestamp_to) . "'", __LINE__, __FILE__);
						
						//On d�place les topics dans le nouveau forum.
						$sql->query_inject("UPDATE ".PREFIX."forum_topics SET idcat = '" . $t_to . "' WHERE idcat = '" . $idcat . "'", __LINE__, __FILE__);

						//On met � jour le nouveau forum.
						$sql->query_inject("UPDATE ".PREFIX."forum_cats SET nbr_msg = nbr_msg + " . $nbr_msg . ", nbr_topic = nbr_topic + " . $nbr_topic . ", last_topic_id = '" . numeric($max_topic_id) . "' WHERE id = '" . $t_to . "'", __LINE__, __FILE__);
						
						//On supprime l'ancien forum.
						$sql->query_inject("DELETE FROM ".PREFIX."forum_cats WHERE id = '" . $idcat . "'", __LINE__, __FILE__);
					}
					
					//Pr�sence de sous-forums => d�placement de ceux-ci.
					if( $nbr_sub_cat > 0 )
					{
						//Sous forums du forum � supprimer.
						$list_sub_cats = '';
						$result = $sql->query_while("SELECT id
						FROM ".PREFIX."forum_cats 
						WHERE id_left BETWEEN '" . $CAT_FORUM[$idcat]['id_left'] . "' AND '" . $CAT_FORUM[$idcat]['id_right'] . "' AND id != '" . $idcat . "'
						ORDER BY id_left", __LINE__, __FILE__);
						while( $row = $sql->sql_fetch_assoc($result) )
						{
							$list_sub_cats .= $row['id'] . ', ';
						}
						$sql->close($result);
						$list_sub_cats = trim($list_sub_cats, ', ');
						
						//Forums parent du forum � supprimer.
						$list_parent_cats = '';
						$result = $sql->query_while("SELECT id
						FROM ".PREFIX."forum_cats 
						WHERE id_left < '" . $CAT_FORUM[$idcat]['id_left'] . "' AND id_right > '" . $CAT_FORUM[$idcat]['id_right'] . "'", __LINE__, __FILE__);
						while( $row = $sql->sql_fetch_assoc($result) )
						{
							$list_parent_cats .= $row['id'] . ', ';
						}
						$sql->close($result);
						$list_parent_cats = trim($list_parent_cats, ', ');
						
						//Pr�caution pour �viter erreur fatale, cas impossible si coh�rence de l'arbre respect�e.
						if( empty($list_sub_cats) )
						{
							header('location:' . HOST . SCRIPT);
							exit;
						}

						//Dernier topic des parents du forum � supprimer.
						if( !empty($list_parent_cats) )
						{
							$max_timestamp_parent = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
							$max_topic_id_parent = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . $max_timestamp_parent . "'", __LINE__, __FILE__); 
						}
						
						//Dernier topic des enfants du forum � supprimer.
						if( !empty($f_to) )
						{
							//Forums parents du forum cible.
							$list_parent_cats_to = '';
							$result = $sql->query_while("SELECT id
							FROM ".PREFIX."forum_cats 
							WHERE id_left <= '" . $CAT_FORUM[$f_to]['id_left'] . "' AND id_right >= '" . $CAT_FORUM[$f_to]['id_right'] . "'", __LINE__, __FILE__);
							while( $row = $sql->sql_fetch_assoc($result) )
							{
								$list_parent_cats_to .= $row['id'] . ', ';
							}
							$sql->close($result);
							$list_parent_cats_to = trim($list_parent_cats_to, ', ');
						
							if( empty($list_parent_cats_to) )
								$clause_parent_cats_to = " id = '" . $f_to . "'";
							else
								$clause_parent_cats_to = " id IN (" . $list_parent_cats_to . ")";
								
							//R�cup�ration de l'id de dernier topic.
							$max_timestamp = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_sub_cats . ")", __LINE__, __FILE__);
							if( empty($list_parent_cats_to) )
								$max_timestamp_to = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat = '" . $f_to . "'", __LINE__, __FILE__); 
							else
								$max_timestamp_to = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_parent_cats_to . ")", __LINE__, __FILE__);
							
							$max_topic_id = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . max($max_timestamp, $max_timestamp_to) . "'", __LINE__, __FILE__);
						}
							
						########## Suppression ##########
						//On supprime l'ancien forum.
						$sql->query_inject("DELETE FROM ".PREFIX."forum_cats WHERE id = '" . $idcat . "'", __LINE__, __FILE__);
						
						//On supprime virtuellement (changement de signe des bornes) les enfants.
						$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = - id_left, id_right = - id_right WHERE id IN (" . $list_sub_cats . ")", __LINE__, __FILE__);					
						
						//On modifie les bornes droites et le last_topic_id des parents.
						if( !empty($list_parent_cats) )
						{
							$sql->query_inject("UPDATE ".PREFIX."forum_cats SET last_topic_id = '" . numeric($max_topic_id_parent) . "', id_right = id_right - '" . (2 + $nbr_sub_cat*2) . "' WHERE id IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
						}
						
						//On r�duit la taille de l'arbre du nombre de forum supprim� � partir de la position de celui-ci.
						$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left - '" . (2 + $nbr_sub_cat*2) . "', id_right = id_right - '" . (2 + $nbr_sub_cat*2) . "' WHERE id_left > '" . $CAT_FORUM[$idcat]['id_right'] . "'", __LINE__, __FILE__);
					
						########## Ajout ##########
						if( !empty($f_to) ) //Forum cible diff�rent de la racine.
						{
							//On modifie les bornes droites des parents de la cible.
							$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_right = id_right + '" . ($nbr_sub_cat*2) . "' WHERE " . $clause_parent_cats_to, __LINE__, __FILE__);
							
							//On augmente la taille de l'arbre du nombre de forum supprim� � partir de la position du forum cible.
							if( $CAT_FORUM[$idcat]['id_left'] > $CAT_FORUM[$f_to]['id_left'] ) //Direction forum source -> forum cible.
							{	
								$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left + '" . ($nbr_sub_cat*2) . "', id_right = id_right + '" . ($nbr_sub_cat*2) . "' WHERE id_left > '" . $CAT_FORUM[$f_to]['id_right'] . "'", __LINE__, __FILE__);						
								$limit = $CAT_FORUM[$f_to]['id_right'];
								$end = $limit + ($nbr_sub_cat*2) - 1;
							}
							else
							{	
								$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left + '" . ($nbr_sub_cat*2) . "', id_right = id_right + '" . ($nbr_sub_cat*2) . "' WHERE id_left > '" . ($CAT_FORUM[$f_to]['id_right'] - (2 + $nbr_sub_cat*2)) . "'", __LINE__, __FILE__);
								$limit = $CAT_FORUM[$f_to]['id_right'] - (2 + $nbr_sub_cat*2);
								$end = $limit + ($nbr_sub_cat*2) - 1;						
							}
							
							//On replace les forums supprim�s virtuellement.
							$array_sub_cats = explode(', ', $list_sub_cats);
							$z = 0;
							for($i = $limit; $i <= $end; $i = $i + 2)
							{
								$id_left = $limit + ($CAT_FORUM[$array_sub_cats[$z]]['id_left'] - $CAT_FORUM[$idcat]['id_left']) - 1;
								$id_right = $end - ($CAT_FORUM[$idcat]['id_right'] - $CAT_FORUM[$array_sub_cats[$z]]['id_right']) + 1;
								$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'", __LINE__, __FILE__);
								$z++;
							}								

							//On met � jour le nouveau forum.
							$sql->query_inject("UPDATE ".PREFIX."forum_cats SET level = level - '" . ($CAT_FORUM[$idcat]['level'] - $CAT_FORUM[$f_to]['level']) . "' WHERE id IN (" . $list_sub_cats . ")", __LINE__, __FILE__);
							$sql->query_inject("UPDATE ".PREFIX."forum_cats SET last_topic_id = '" . numeric($max_topic_id) . "' WHERE " . $clause_parent_cats_to, __LINE__, __FILE__);
						}
						else //Racine
						{
							$max_id = $sql->query("SELECT MAX(id_right) FROM ".PREFIX."forum_cats", __LINE__, __FILE__);
							//On replace les forums supprim�s virtuellement.
							$array_sub_cats = explode(', ', $list_sub_cats);
							$z = 0;
							$limit = $max_id + 1;
							$end = $limit + ($nbr_sub_cat*2) - 1;	
							for($i = $limit; $i <= $end; $i = $i + 2)
							{
								$id_left = $limit + ($CAT_FORUM[$array_sub_cats[$z]]['id_left'] - $CAT_FORUM[$idcat]['id_left']) - 1;
								$id_right = $end - ($CAT_FORUM[$idcat]['id_right'] - $CAT_FORUM[$array_sub_cats[$z]]['id_right']) + 1;
								$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'", __LINE__, __FILE__);
								$z++;
							}		
							$sql->query_inject("UPDATE ".PREFIX."forum_cats SET level = level - '" . ($CAT_FORUM[$idcat]['level'] - $CAT_FORUM[$f_to]['level'] + 1) . "' WHERE id IN (" . $list_sub_cats . ")", __LINE__, __FILE__);
						}
					}
					$cache->generate_module_file('forum');
					
					header('location:' . HOST . SCRIPT);
					exit;
				}	
			}
		}

		if( $confirm_delete ) //Confirmation de suppression, on supprime dans la bdd.
		{
			//Forums parent du forum � supprimer.
			$list_parent_cats = '';
			$result = $sql->query_while("SELECT id
			FROM ".PREFIX."forum_cats 
			WHERE id_left < '" . $CAT_FORUM[$idcat]['id_left'] . "' AND id_right > '" . $CAT_FORUM[$idcat]['id_right'] . "'", __LINE__, __FILE__);
			while( $row = $sql->sql_fetch_assoc($result) )
			{
				$list_parent_cats .= $row['id'] . ', ';
			}
			$sql->close($result);
			$list_parent_cats = trim($list_parent_cats, ', ');
			
			$nbr_del = $CAT_FORUM[$idcat]['id_right'] - $CAT_FORUM[$idcat]['id_left'] + 1;
			if( !empty($list_parent_cats) )
			{
				$max_timestamp_parent = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
				$max_topic_id_parent = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . $max_timestamp_parent . "'", __LINE__, __FILE__);		
				$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_right = id_right - '" . $nbr_del . "', last_topic_id = '" . numeric($max_topic_id_parent) . "' WHERE id IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
			}		
			
			$sql->query_inject("DELETE FROM ".PREFIX."forum_cats WHERE id_left BETWEEN '" . $CAT_FORUM[$idcat]['id_left'] . "' AND '" . $CAT_FORUM[$idcat]['id_right'] . "'", __LINE__, __FILE__);	
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left - '" . $nbr_del . "', id_right = id_right - '" . $nbr_del . "' WHERE id_left > '" . $CAT_FORUM[$idcat]['id_right'] . "'", __LINE__, __FILE__);
			$sql->query_inject("DELETE FROM ".PREFIX."forum_topics WHERE idcat = '" . $idcat . "'", __LINE__, __FILE__);	
			
			###### Reg�n�ration du cache des cat�gories (liste d�roulante dans le forum) #######
			$cache->generate_module_file('forum');
			
			header('location:' . HOST . SCRIPT);
			exit;
		}		
	}
	else
	{
		header('location:' . HOST . SCRIPT);
		exit;
	}
}
elseif( !empty($id) && !empty($move) ) //Monter/descendre.
{
	$cache->load_file('forum');
	
	//Cat�gorie existe?
	if( !isset($CAT_FORUM[$id]) )
	{
		header('location:' . HOST . DIR . '/forum/admin_forum.php');
		exit;
	}
	
	//Forums parents du forum � d�placer.
	$list_parent_cats = '';
	$result = $sql->query_while("SELECT id 
	FROM ".PREFIX."forum_cats 
	WHERE id_left < '" . $CAT_FORUM[$id]['id_left'] . "' AND id_right > '" . $CAT_FORUM[$id]['id_right'] . "'", __LINE__, __FILE__);
	while( $row = $sql->sql_fetch_assoc($result) )
	{
		$list_parent_cats .= $row['id'] . ', ';
	}
	$sql->close($result);
	$list_parent_cats = trim($list_parent_cats, ', ');
	
	$to = 0;
	if( $move == 'up' )
	{	
		//M�me cat�gorie
		$switch_id_cat = $sql->query("SELECT id FROM ".PREFIX."forum_cats
		WHERE '" . $CAT_FORUM[$id]['id_left'] . "' - id_right = 1", __LINE__, __FILE__);		
		if( !empty($switch_id_cat) )
		{
			//On monte la cat�gorie � d�placer, on lui assigne des id n�gatifs pour assurer l'unicit�.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = - id_left + '" . ($CAT_FORUM[$switch_id_cat]['id_right'] - $CAT_FORUM[$switch_id_cat]['id_left'] + 1) . "', id_right = - id_right + '" . ($CAT_FORUM[$switch_id_cat]['id_right'] - $CAT_FORUM[$switch_id_cat]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_FORUM[$id]['id_left'] . "' AND '" . $CAT_FORUM[$id]['id_right'] . "'", __LINE__, __FILE__);
			//On descend la cat�gorie cible.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left + '" . ($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$id]['id_left'] + 1) . "', id_right = id_right + '" . ($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$id]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_FORUM[$switch_id_cat]['id_left'] . "' AND '" . $CAT_FORUM[$switch_id_cat]['id_right'] . "'", __LINE__, __FILE__);
			
			//On r�tablit les valeurs absolues.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = - id_left WHERE id_left < 0", __LINE__, __FILE__);
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_right = - id_right WHERE id_right < 0", __LINE__, __FILE__);	
			
			$cache->generate_module_file('forum');
		}		
		elseif( !empty($list_parent_cats)  )
		{
			//Changement de cat�gorie.
			$to = $sql->query("SELECT id FROM ".PREFIX."forum_cats
			WHERE id_left < '" . $CAT_FORUM[$id]['id_left'] . "' AND level = '" . ($CAT_FORUM[$id]['level'] - 1) . "' AND
			id NOT IN (" . $list_parent_cats . ")
			ORDER BY id_left DESC" . 
			$sql->sql_limit(0, 1), __LINE__, __FILE__);
		}
	}
	elseif( $move == 'down' )
	{
		//Doit-on changer de cat�gorie parente ou non ?
		$switch_id_cat = $sql->query("SELECT id FROM ".PREFIX."forum_cats
		WHERE id_left - '" . $CAT_FORUM[$id]['id_right'] . "' = 1", __LINE__, __FILE__);
		if( !empty($switch_id_cat) )
		{
			//On monte la cat�gorie � d�placer, on lui assigne des id n�gatifs pour assurer l'unicit�.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = - id_left - '" . ($CAT_FORUM[$switch_id_cat]['id_right'] - $CAT_FORUM[$switch_id_cat]['id_left'] + 1) . "', id_right = - id_right - '" . ($CAT_FORUM[$switch_id_cat]['id_right'] - $CAT_FORUM[$switch_id_cat]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_FORUM[$id]['id_left'] . "' AND '" . $CAT_FORUM[$id]['id_right'] . "'", __LINE__, __FILE__);
			//On descend la cat�gorie cible.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left - '" . ($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$id]['id_left'] + 1) . "', id_right = id_right - '" . ($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$id]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_FORUM[$switch_id_cat]['id_left'] . "' AND '" . $CAT_FORUM[$switch_id_cat]['id_right'] . "'", __LINE__, __FILE__);
			
			//On r�tablit les valeurs absolues.
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = - id_left WHERE id_left < 0", __LINE__, __FILE__);
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_right = - id_right WHERE id_right < 0", __LINE__, __FILE__);
			
			$cache->generate_module_file('forum');
		}
		elseif( !empty($list_parent_cats)  )
		{
			//Changement de cat�gorie.
			$to = $sql->query("SELECT id FROM ".PREFIX."forum_cats
			WHERE id_left > '" . $CAT_FORUM[$id]['id_left'] . "' AND level = '" . ($CAT_FORUM[$id]['level'] - 1) . "'
			ORDER BY id_left" . 
			$sql->sql_limit(0, 1), __LINE__, __FILE__);
			
		}
	}

	if( !empty($to) ) //Changement de cat�gorie possible?
	{
		//On v�rifie si la cat�gorie contient des sous forums.
		$nbr_cat = (($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$id]['id_left'] - 1) / 2) + 1;
	
		//Sous forums du forum � d�placer.
		$list_cats = '';
		$result = $sql->query_while("SELECT id
		FROM ".PREFIX."forum_cats 
		WHERE id_left BETWEEN '" . $CAT_FORUM[$id]['id_left'] . "' AND '" . $CAT_FORUM[$id]['id_right'] . "'
		ORDER BY id_left", __LINE__, __FILE__);
		while( $row = $sql->sql_fetch_assoc($result) )
		{
			$list_cats .= $row['id'] . ', ';
		}
		$sql->close($result);
		$list_cats = trim($list_cats, ', ');
	
		//Pr�caution pour �viter erreur fatale, cas impossible si coh�rence de l'arbre respect�e.
		if( empty($list_cats) )
		{
			header('location:' . HOST . SCRIPT);
			exit;
		}
					
		//Dernier topic des parents du forum � supprimer.
		if( !empty($list_parent_cats) )
		{
			$max_timestamp_parent = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
			$max_topic_id_parent = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . $max_timestamp_parent . "'", __LINE__, __FILE__); 
		}
		
		## Dernier topic des enfants du forum � supprimer ##
		//Forums parents du forum cible.
		$list_parent_cats_to = '';
		$result = $sql->query_while("SELECT id, level 
		FROM ".PREFIX."forum_cats 
		WHERE id_left <= '" . $CAT_FORUM[$to]['id_left'] . "' AND id_right >= '" . $CAT_FORUM[$to]['id_right'] . "'", __LINE__, __FILE__);
		while( $row = $sql->sql_fetch_assoc($result) )
		{
			$list_parent_cats_to .= $row['id'] . ', ';
		}
		$sql->close($result);
		$list_parent_cats_to = trim($list_parent_cats_to, ', ');
	
		if( empty($list_parent_cats_to) )
			$clause_parent_cats_to = " id = '" . $to . "'";
		else
			$clause_parent_cats_to = " id IN (" . $list_parent_cats_to . ")";
			
		//R�cup�ration de l'id de dernier topic.
		$max_timestamp = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_cats . ")", __LINE__, __FILE__);
		if( empty($list_parent_cats_to) )
			$max_timestamp_to = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat = '" . $to . "'", __LINE__, __FILE__); 
		else
			$max_timestamp_to = $sql->query("SELECT MAX(last_timestamp) FROM ".PREFIX."forum_topics WHERE idcat IN (" . $list_parent_cats_to . ")", __LINE__, __FILE__);
		
		$max_topic_id = $sql->query("SELECT id FROM ".PREFIX."forum_topics WHERE last_timestamp = '" . max($max_timestamp, $max_timestamp_to) . "'", __LINE__, __FILE__);

		########## Suppression ##########
		//On supprime virtuellement (changement de signe des bornes) les enfants.
		$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = - id_left, id_right = - id_right WHERE id IN (" . $list_cats . ")", __LINE__, __FILE__);					
		
		//On modifie les bornes droites et le last_topic_id des parents.
		if( !empty($list_parent_cats) )
		{
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET last_topic_id = '" . numeric($max_topic_id_parent) . "', id_right = id_right - '" . ( $nbr_cat*2) . "' WHERE id IN (" . $list_parent_cats . ")", __LINE__, __FILE__);
		}
		
		//On r�duit la taille de l'arbre du nombre de forum supprim� � partir de la position de celui-ci.
		$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left - '" . ($nbr_cat*2) . "', id_right = id_right - '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_FORUM[$id]['id_right'] . "'", __LINE__, __FILE__);

		########## Ajout ##########
		//On modifie les bornes droites des parents de la cible.
		$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_right = id_right + '" . ($nbr_cat*2) . "' WHERE " . $clause_parent_cats_to, __LINE__, __FILE__);

		//On augmente la taille de l'arbre du nombre de forum supprim� � partir de la position du forum cible.
		if( $CAT_FORUM[$id]['id_left'] > $CAT_FORUM[$to]['id_left']  ) //Direction forum source -> forum cible.
		{	
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_FORUM[$to]['id_right'] . "'", __LINE__, __FILE__);						
			$limit = $CAT_FORUM[$to]['id_right'];
			$end = $limit + ($nbr_cat*2) - 1;
		}
		else
		{	
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . ($CAT_FORUM[$to]['id_right'] - ($nbr_cat*2)) . "'", __LINE__, __FILE__);
			$limit = $CAT_FORUM[$to]['id_right'] - ($nbr_cat*2);
			$end = $limit + ($nbr_cat*2) - 1;						
		}	

		//On replace les forums supprim�s virtuellement.
		$array_sub_cats = explode(', ', $list_cats);
		$z = 0;
		for($i = $limit; $i <= $end; $i = $i + 2)
		{
			$id_left = $limit + ($CAT_FORUM[$array_sub_cats[$z]]['id_left'] - $CAT_FORUM[$id]['id_left']);
			$id_right = $end - ($CAT_FORUM[$id]['id_right'] - $CAT_FORUM[$array_sub_cats[$z]]['id_right']);
			$sql->query_inject("UPDATE ".PREFIX."forum_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'", __LINE__, __FILE__);
			$z++;
		}
				
		//On met � jour le nouveau forum.
		$sql->query_inject("UPDATE ".PREFIX."forum_cats SET last_topic_id = '" . numeric($max_topic_id) . "' WHERE " . $clause_parent_cats_to, __LINE__, __FILE__);
		
		$cache->generate_module_file('forum');
	}
		
	header('location:' . HOST . SCRIPT);
	exit;
}
elseif( !empty($id) )
{
	$cache->load_file('forum');
	
	$template->set_filenames(array(
		'admin_forum_cat_edit' => '../templates/' . $CONFIG['theme'] . '/forum/admin_forum_cat_edit.tpl'
	));
			
	$forum_info = $sql->query_array("forum_cats", "id_left", "id_right", "level", "name", "subname", "status", "aprob", "auth", "WHERE id = '" . $id . "'", __LINE__, __FILE__);
	
	//Listing des cat�gories disponibles, sauf celle qui va �tre supprim�e.			
	$forums = '<option value="0" checked="checked">' . $LANG['root'] . '</option>';
	$result = $sql->query_while("SELECT id, id_left, id_right, name, level
	FROM ".PREFIX."forum_cats 
	WHERE id_left NOT BETWEEN '" . $CAT_FORUM[$id]['id_left'] . "' AND '" . $CAT_FORUM[$id]['id_right'] . "'
	ORDER BY id_left", __LINE__, __FILE__);
	while( $row = $sql->sql_fetch_assoc($result) )
	{	
		$margin = ($row['level'] > 0) ? str_repeat('--------', $row['level']) : '--';
		$selected = ($row['id_left'] < $forum_info['id_left'] && $row['id_right'] > $forum_info['id_right'] && ($forum_info['level'] - 1) == $row['level'] ) ? ' selected="selected"' : '';
		$forums .= '<option value="' . $row['id'] . '"' . $selected . '>' . $margin . ' ' . $row['name'] . '</option>';
	}
	$sql->close($result);
	
	//Cr�ation du tableau des groupes.
	$array_groups = array();
	foreach($_array_groups_auth as $idgroup => $array_group_info)
		$array_groups[$idgroup] = $array_group_info[0];	
		
	//Cr�ation du tableau des rangs.
	$array_ranks = array(-1 => $LANG['guest'], 0 => $LANG['member'], 1 => $LANG['modo'], 2 => $LANG['admin']);
	
	//R�cup�ration des tableaux des autorisations et des groupes.
	$array_auth = !empty($forum_info['auth']) ? unserialize($forum_info['auth']) : array();
	
	//G�n�ration d'une liste � s�lection multiple des rangs et groupes
	function generate_select_groups($array_auth, $auth_id, $auth_level, $disabled = '')
	{
		global $array_groups, $array_ranks, $LANG;
		
		$j = 0;
		//Liste des rangs
		$select_groups = '<select id="groups_auth' . $auth_id . '" name="groups_auth' . $auth_id . '[]" size="8" multiple="multiple" onclick="document.getElementById(\'' . $auth_id . 'r3\').selected = true;"><optgroup label="' . $LANG['ranks'] . '">';
		foreach($array_ranks as $idgroup => $group_name)
		{
			$selected = '';	
			if( array_key_exists('r' . $idgroup, $array_auth) && ((int)$array_auth['r' . $idgroup] & (int)$auth_level) !== 0 && empty($disabled) )
				$selected = 'selected="selected"';
				
			$selected = ($idgroup == 2 && empty($disabled)) ? 'selected="selected"' : $selected;
			
			$select_groups .=  '<option ' . $disabled . ' value="r' . $idgroup . '" id="' . $auth_id . 'r' . $j . '" ' . $selected . ' onclick="check_select_multiple_ranks(\'' . $auth_id . 'r\', ' . $j . ')">' . $group_name . '</option>';
			$j++;
		}
		$select_groups .=  '</optgroup>';
		
		//Liste des groupes.
		$j = 0;
		$select_groups .= '<optgroup label="' . $LANG['groups'] . '">';
		foreach($array_groups as $idgroup => $group_name)
		{
			$selected = '';		
			if( array_key_exists($idgroup, $array_auth) && ((int)$array_auth[$idgroup] & (int)$auth_level) !== 0 && empty($disabled) )
				$selected = 'selected="selected"';

			$select_groups .= '<option ' . $disabled . ' value="' . $idgroup . '" id="' . $auth_id . 'g' . $j . '" ' . $selected . '>' . $group_name . '</option>';
			$j++;
		}
		$select_groups .= '</optgroup></select>';
		
		return $select_groups;
	}
	
	//Gestion erreur.
	$get_error = !empty($_GET['error']) ? $_GET['error'] : '';
	if( $get_error == 'incomplete' )
		$errorh->error_handler($LANG['e_incomplete'], E_USER_NOTICE);	
	
	$is_root = ($forum_info['level'] > 0);
	
	$template->assign_vars(array(
		'THEME' => $CONFIG['theme'],
		'MODULE_DATA_PATH' => $template->module_data_path('forum'),
		'NBR_GROUP' => count($array_groups),
		'ID' => $id,
		'CATEGORIES' => $forums,
		'NAME' => $forum_info['name'],
		'DESC' => $forum_info['subname'],
		'CHECKED_APROB' => ($forum_info['aprob'] == 1) ? 'checked="checked"' : '',
		'UNCHECKED_APROB' => ($forum_info['aprob'] == 0) ? 'checked="checked"' : '',
		'CHECKED_STATUS' => ($forum_info['status'] == 1) ? 'checked="checked"' : '',
		'UNCHECKED_STATUS' => ($forum_info['status'] == 0) ? 'checked="checked"' : '',
		'AUTH_READ' => generate_select_groups($array_auth, 'r', 1),
		'AUTH_WRITE' => $is_root ? generate_select_groups($array_auth, 'w', 2) : generate_select_groups($array_auth, 'w', 2, 'disabled="disabled"'),
		'AUTH_EDIT' => $is_root ? generate_select_groups($array_auth, 'x', 4) : generate_select_groups($array_auth, 'x', 4, 'disabled="disabled"'),
		'DISABLED' => $is_root ? '0' : '1',
		'L_REQUIRE_TITLE' => $LANG['require_title'],
		'L_FORUM_MANAGEMENT' => $LANG['forum_management'],
		'L_CAT_MANAGEMENT' => $LANG['cat_management'],
		'L_ADD_CAT' => $LANG['cat_add'],
		'L_FORUM_CONFIG' => $LANG['forum_config'],
		'L_FORUM_GROUPS' => $LANG['forum_groups_config'],
		'L_EDIT_CAT' => $LANG['cat_edit'],
		'L_REQUIRE' => $LANG['require'],
		'L_APROB' => $LANG['aprob'],
		'L_STATUS' => $LANG['status'],
		'L_RANK' => $LANG['rank'],
		'L_DELETE' => $LANG['delete'],
		'L_PARENT_CATEGORY' => $LANG['parent_category'],
		'L_NAME' => $LANG['name'],
		'L_DESC' => $LANG['description'],
		'L_RESET' => $LANG['reset'],		
		'L_YES' => $LANG['yes'],
		'L_NO' => $LANG['no'],
		'L_LOCK' => $LANG['lock'],
		'L_UNLOCK' => $LANG['unlock'],
		'L_GUEST' => $LANG['guest'],
		'L_MEMBER' => $LANG['member'],
		'L_MODO' => $LANG['modo'],
		'L_ADMIN' => $LANG['admin'],
		'L_UPDATE' => $LANG['update'],
		'L_AUTH_READ' => $LANG['auth_read'],
		'L_AUTH_WRITE' => $LANG['auth_write'],
		'L_AUTH_EDIT' => $LANG['auth_edit'],
		'L_EXPLAIN_SELECT_MULTIPLE' => $LANG['explain_select_multiple'],
		'L_SELECT_ALL' => $LANG['select_all'],
		'L_SELECT_NONE' => $LANG['select_none']
	));
	
	$template->pparse('admin_forum_cat_edit'); // traitement du modele
}
else	
{		
	$template->set_filenames(array(
	'admin_forum_cat' => '../templates/' . $CONFIG['theme'] . '/forum/admin_forum_cat.tpl'
	));
		
	//Cr�ation du tableau des groupes.
	$array_groups = array();
	foreach($_array_groups_auth as $idgroup => $array_group_info)
		$array_groups[$idgroup] = $array_group_info[0];
		
	$template->assign_vars(array(
		'THEME' => $CONFIG['theme'],
		'MODULE_DATA_PATH' => $template->module_data_path('forum'),
		'NBR_GROUP' => count($array_groups),
		'L_CONFIRM_DEL' => $LANG['del_entry'],
		'L_REQUIRE_TITLE' => $LANG['require_title'],
		'L_FORUM_MANAGEMENT' => $LANG['forum_management'],
		'L_CAT_MANAGEMENT' => $LANG['cat_management'],
		'L_ADD_CAT' => $LANG['cat_add'],
		'L_FORUM_CONFIG' => $LANG['forum_config'],
		'L_FORUM_GROUPS' => $LANG['forum_groups_config'],
		'L_DELETE' => $LANG['delete'],
		'L_NAME' => $LANG['name'],
		'L_DESC' => $LANG['description'],
		'L_UPDATE' => $LANG['update'],
		'L_RESET' => $LANG['reset'],		
		'L_YES' => $LANG['yes'],
		'L_NO' => $LANG['no'],
		'L_LOCK' => $LANG['lock'],
		'L_UNLOCK' => $LANG['unlock'],
		'L_GUEST' => $LANG['guest'],
		'L_MEMBER' => $LANG['member'],
		'L_MODO' => $LANG['modo'],
		'L_ADMIN' => $LANG['admin'],
		'L_ADD' => $LANG['add'],
		'L_AUTH_READ' => $LANG['auth_read'],
		'L_AUTH_WRITE' => $LANG['auth_write'],
		'L_AUTH_EDIT' => $LANG['auth_edit'],
		'L_EXPLAIN_SELECT_MULTIPLE' => $LANG['explain_select_multiple'],
		'L_SELECT_ALL' => $LANG['select_all'],
		'L_SELECT_NONE' => $LANG['select_none']
	));

	$max_cat = $sql->query("SELECT MAX(id_left) FROM ".PREFIX."forum_cats", __LINE__, __FILE__);
	$list_cats_js = '';
	$array_js = '';	
	$i = 0;
	$result = $sql->query_while("SELECT id, id_left, id_right, level, name, subname, status
	FROM ".PREFIX."forum_cats 
	ORDER BY id_left", __LINE__, __FILE__);
	while( $row = $sql->sql_fetch_assoc($result) )
	{
		//On assigne les variables pour le POST en pr�cisant l'idurl.
		$template->assign_block_vars('list', array(
			'I' => $i,
			'ID' => $row['id'],
			'NAME' => $row['name'],
			'DESC' => $row['subname'],
			'INDENT' => $row['level'] * 75, //Indentation des sous cat�gories.
			'LOCK' => ($row['status'] == 0) ? '<img class="valign_middle" src="../templates/' . $CONFIG['theme'] . '/images/readonly.png" alt="" title="' . $LANG['lock'] . '" />' : '',
			'U_FORUM_VARS' => ($row['level'] > 0) ? 'forum' . transid('.php?id=' . $row['id'], '-' . $row['id'] . '+' . url_encode_rewrite($row['name']) . '.php') : transid('index.php?id=' . $row['id'], 'cat-' . $row['id'] . '+' . url_encode_rewrite($row['name']) . '.php')
		));
		
		$list_cats_js .= $row['id'] . ', ';
		
		$array_js .= 'array_cats[' . $row['id'] . '] = new Array();' . "\n"; 
		$array_js .= 'array_cats[' . $row['id'] . '][\'id\'] = ' . $row['id'] . ";\n";
		$array_js .= 'array_cats[' . $row['id'] . '][\'id_left\'] = ' . $row['id_left'] . ";\n";
		$array_js .= 'array_cats[' . $row['id'] . '][\'id_right\'] = ' . $row['id_right'] . ";\n";
		$array_js .= 'array_cats[' . $row['id'] . '][\'i\'] = ' . $i . ";\n";
		$i++;
	}
	$sql->close($result);
	
	$template->assign_vars(array(
		'LIST_CATS' => trim($list_cats_js, ', '),
		'ARRAY_JS' => $array_js,
		'ID_END' => ($i - 1)
	));

	$template->pparse('admin_forum_cat'); // traitement du modele	
}

include_once('../includes/admin_footer.php');

?>