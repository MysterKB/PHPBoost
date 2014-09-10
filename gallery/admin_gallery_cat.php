<?php
/*##################################################
 *                               admin_gallery_cat.php
 *                            -------------------
 *   begin                : August 01, 2007
 *   copyright            : (C) 2007 Viarre R�gis
 *   email                : crowkait@phpboost.com
 *
 *  
 *
 ###################################################
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 ###################################################*/

require_once('../admin/admin_begin.php');
load_module_lang('gallery'); //Chargement de la langue du module.
define('TITLE', $LANG['administration']);
require_once('../admin/admin_header.php');

$config = GalleryConfig::load();

$id = !empty($_GET['id']) ? NumberHelper::numeric($_GET['id']) : 0;
$del = !empty($_GET['del']) ? NumberHelper::numeric($_GET['del']) : 0;
$move = !empty($_GET['move']) ? trim($_GET['move']) : 0;
$root = !empty($_GET['root']) ? NumberHelper::numeric($_GET['root']) : 0;

//Si c'est confirm� on execute
if (!empty($_POST['valid']) && !empty($id))
{
	$Cache->load('gallery');
	
	$to = !empty($_POST['category']) ? NumberHelper::numeric($_POST['category']) : 0;
	$name = !empty($_POST['name']) ? TextHelper::strprotect($_POST['name']) : '';
	$contents = !empty($_POST['desc']) ? TextHelper::strprotect($_POST['desc']) : '';
	$status = isset($_POST['status']) ? NumberHelper::numeric($_POST['status']) : 1;  
	$aprob = isset($_POST['aprob']) ? NumberHelper::numeric($_POST['aprob']) : 1;  

	//G�n�ration du tableau des droits.
	$array_auth_all = Authorizations::build_auth_array_from_form(GalleryAuthorizationsService::READ_AUTHORIZATIONS, GalleryAuthorizationsService::WRITE_AUTHORIZATIONS, GalleryAuthorizationsService::MODERATION_AUTHORIZATIONS);

	if (!empty($name))
	{
		$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET name = '" . $name . "', contents = '" . $contents . "', aprob = '" . $aprob . "', status = '" . $status . "', auth = '" . TextHelper::strprotect(serialize($array_auth_all), TextHelper::HTML_NO_PROTECT) . "' WHERE id = '" . $id . "'");

		//Emp�che le d�placement dans une cat�gorie fille.
		$to = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats WHERE id = '" . $to . "' AND id_left NOT BETWEEN '" . $CAT_GALLERY[$id]['id_left'] . "' AND '" . $CAT_GALLERY[$id]['id_right'] . "'");
		 
		//Cat�gorie parente chang�e?
		$change_cat = !empty($to) ? !($CAT_GALLERY[$to]['id_left'] < $CAT_GALLERY[$id]['id_left'] && $CAT_GALLERY[$to]['id_right'] > $CAT_GALLERY[$id]['id_right'] && ($CAT_GALLERY[$id]['level'] - 1) == $CAT_GALLERY[$to]['level']) : $CAT_GALLERY[$id]['level'] > 0;		
		if ($change_cat)
		{
			//On v�rifie si la cat�gorie contient des sous galeries.
			$nbr_cat = (($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$id]['id_left'] - 1) / 2) + 1;
		
			//Sous galeries de la galerie � supprimer.
			$list_cats = '';
			$result = $Sql->query_while("SELECT id
			FROM " . PREFIX . "gallery_cats 
			WHERE id_left BETWEEN '" . $CAT_GALLERY[$id]['id_left'] . "' AND '" . $CAT_GALLERY[$id]['id_right'] . "'
			ORDER BY id_left");
			while ($row = $Sql->fetch_assoc($result))
			{
				$list_cats .= $row['id'] . ', ';
			}
			$result->dispose();
			$list_cats = trim($list_cats, ', ');
			
			//Galeries parentes de la galerie � supprimer.
			$list_parent_cats = '';
			$result = $Sql->query_while("SELECT id 
			FROM " . PREFIX . "gallery_cats 
			WHERE id_left < '" . $CAT_GALLERY[$id]['id_left'] . "' AND id_right > '" . $CAT_GALLERY[$id]['id_right'] . "'");
			while ($row = $Sql->fetch_assoc($result))
			{
				$list_parent_cats .= $row['id'] . ', ';
			}
			$result->dispose();
			$list_parent_cats = trim($list_parent_cats, ', ');
			
			//Pr�caution pour �viter erreur fatale, cas impossible si coh�rence de l'arbre respect�e.
			if (empty($list_cats))
				AppContext::get_response()->redirect(HOST . REWRITED_SCRIPT);
			
			//Galeries parentes de la galerie cible.
			if (!empty($to))
			{
				$list_parent_cats_to = '';
				$result = $Sql->query_while("SELECT id 
				FROM " . PREFIX . "gallery_cats 
				WHERE id_left <= '" . $CAT_GALLERY[$to]['id_left'] . "' AND id_right >= '" . $CAT_GALLERY[$to]['id_right'] . "'");
				while ($row = $Sql->fetch_assoc($result))
				{
					$list_parent_cats_to .= $row['id'] . ', ';
				}
				$result->dispose();
				$list_parent_cats_to = trim($list_parent_cats_to, ', ');
						
				if (empty($list_parent_cats_to))
					$clause_parent_cats_to = " id = '" . $to . "'";
				else
					$clause_parent_cats_to = " id IN (" . $list_parent_cats_to . ")";
			}

			########## Suppression ##########
			//On supprime virtuellement (changement de signe des bornes) les enfants.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = - id_left, id_right = - id_right WHERE id IN (" . $list_cats . ")");					
			
			//R�cup�ration du nombre d'images de la galerie.
			$nbr_pics_aprob = $Sql->query("SELECT nbr_pics_aprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $id . "'");
			$nbr_pics_unaprob = $Sql->query("SELECT nbr_pics_unaprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $id . "'");
			
			//On modifie les bornes droites des parents et le nbr d'images.
			if (!empty($list_parent_cats))
			{
				$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right - '" . ( $nbr_cat*2) . "', nbr_pics_aprob = nbr_pics_aprob - " . NumberHelper::numeric($nbr_pics_aprob) . ", nbr_pics_unaprob = nbr_pics_unaprob - " . NumberHelper::numeric($nbr_pics_unaprob) . " WHERE id IN (" . $list_parent_cats . ")");
			}
			
			//On r�duit la taille de l'arbre du nombre de galeries supprim�es � partir de la position de celui-ci.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left - '" . ($nbr_cat*2) . "', id_right = id_right - '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_GALLERY[$id]['id_right'] . "'");

			########## Ajout ##########
			if (!empty($to)) //Galerie cible diff�rent de la racine.
			{
				//On modifie les bornes droites et le nbr d'images des parents de la cible.
				$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right + '" . ($nbr_cat*2) . "', nbr_pics_aprob = nbr_pics_aprob + " . NumberHelper::numeric($nbr_pics_aprob) . ", nbr_pics_unaprob = nbr_pics_unaprob + " . NumberHelper::numeric($nbr_pics_unaprob) . " WHERE " . $clause_parent_cats_to);

				//On augmente la taille de l'arbre du nombre de galeries supprim�es � partir de la position de la galerie cible.
				if ($CAT_GALLERY[$id]['id_left'] > $CAT_GALLERY[$to]['id_left'] ) //Direction galerie source -> galerie cible.
				{	
					$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_GALLERY[$to]['id_right'] . "'");						
					$limit = $CAT_GALLERY[$to]['id_right'];
					$end = $limit + ($nbr_cat*2) - 1;
				}
				else
				{	
					$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . ($CAT_GALLERY[$to]['id_right'] - ($nbr_cat*2)) . "'");
					$limit = $CAT_GALLERY[$to]['id_right'] - ($nbr_cat*2);
					$end = $limit + ($nbr_cat*2) - 1;						
				}	
				//On replace les galeries supprim�es virtuellement.
				$array_sub_cats = explode(', ', $list_cats);
				$z = 0;
				for ($i = $limit; $i <= $end; $i = $i + 2)
				{
					$id_left = $limit + ($CAT_GALLERY[$array_sub_cats[$z]]['id_left'] - $CAT_GALLERY[$id]['id_left']);
					$id_right = $end - ($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$array_sub_cats[$z]]['id_right']);
					$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'");
					$z++;
				}
					
				//On met � jour la nouvelle galerie.
				$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET level = level - '" . (($CAT_GALLERY[$id]['level'] - $CAT_GALLERY[$to]['level']) - 1) . "' WHERE id IN (" . $list_cats . ")");
			}
			else //Racine
			{
				$max_id = $Sql->query("SELECT MAX(id_right) FROM " . PREFIX . "gallery_cats");
				//On replace les galeries supprim�es virtuellement.
				$array_sub_cats = explode(', ', $list_cats);
				$z = 0;
				$limit = $max_id + 1;
				$end = $limit + ($nbr_cat*2) - 1;	
				for ($i = $limit; $i <= $end; $i = $i + 2)
				{
					$id_left = $limit + ($CAT_GALLERY[$array_sub_cats[$z]]['id_left'] - $CAT_GALLERY[$id]['id_left']);
					$id_right = $end - ($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$array_sub_cats[$z]]['id_right']);
					$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'");
					$z++;
				}		
				$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET level = level - '" . ($CAT_GALLERY[$id]['level'] - $CAT_GALLERY[$to]['level']) . "' WHERE id IN (" . $list_cats . ")");		
			}
		}
		
		$Cache->Generate_module_file('gallery');
	}
	else
		AppContext::get_response()->redirect('/gallery/admin_gallery_cat.php?id=' . $id . '&error=incomplete');

	AppContext::get_response()->redirect('/gallery/admin_gallery_cat.php');
}
elseif (!empty($_POST['valid_root'])) //Modification des autorisations de la racine.
{
	$Cache->load('gallery');
	
	$config->set_authorizations(Authorizations::build_auth_array_from_form(GalleryAuthorizationsService::READ_AUTHORIZATIONS, GalleryAuthorizationsService::WRITE_AUTHORIZATIONS, GalleryAuthorizationsService::MODERATION_AUTHORIZATIONS));
	
	GalleryConfig::save();
	
	$Cache->Generate_module_file('gallery');
	
	AppContext::get_response()->redirect('/gallery/admin_gallery_cat.php');
}
elseif (!empty($del)) //Suppression de la cat�gorie/sous-cat�gorie.
{
	AppContext::get_session()->csrf_get_protect(); //Protection csrf
	
	$Cache->load('gallery');
	
	$confirm_delete = false;	
	
	$idcat = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats WHERE id = '" . $del . "'");
	if (!empty($idcat) && isset($CAT_GALLERY[$idcat]))
	{
		//On v�rifie si la cat�gorie contient des sous galeries.
		$nbr_sub_cat = (($CAT_GALLERY[$idcat]['id_right'] - $CAT_GALLERY[$idcat]['id_left'] - 1) / 2);
		//On v�rifie si la cat�gorie ne contient pas d'images.
		$check_pics = PersistenceContext::get_querier()->count(PREFIX . "gallery", 'WHERE idcat=:idcat', array('idcat' => $idcat));
		
		if ($check_pics == 0 && $nbr_sub_cat == 0) //Si vide on supprime simplement, la cat�gorie.
		{
			$confirm_delete = true;
		}
		else //Sinon on propose de d�placer les images existantes dans une autre galerie.
		{
			if (empty($_POST['del_cat']))
			{
				$tpl = new FileTemplate('gallery/admin_gallery_cat_del.tpl');

				if ($check_pics > 0) //Conserve les images.
				{
					//Listing des galeries disponibles, sauf celle qui va �tre supprim�e.		
					$subgallery = '<option value="0">' . $LANG['root'] . '</option>';
					$result = $Sql->query_while("SELECT id, name, level
					FROM " . PREFIX . "gallery_cats 
					WHERE id_left NOT BETWEEN '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND '" . $CAT_GALLERY[$idcat]['id_right'] . "'
					ORDER BY id_left");
					while ($row = $Sql->fetch_assoc($result))
					{	
						$margin = ($row['level'] > 0) ? str_repeat('--------', $row['level']) : '--';
						$disabled = ($row['level'] > 0) ? '' : ' disabled="disabled"';
						$subgallery .= '<option value="' . $row['id'] . '"' . $disabled . '>' . $margin . ' ' . $row['name'] . '</option>';
					}
					$result->dispose();
					
					$tpl->assign_block_vars('pics', array(
						'GALLERIES' => $subgallery,
						'L_KEEP' => $LANG['keep_pics'],
						'L_MOVE_PICS' => $LANG['move_pics_to'],
						'L_EXPLAIN_CAT' => sprintf($LANG['error_warning_tiny'], sprintf((($check_pics > 1) ? $LANG['explain_pics'] : $LANG['explain_pic']), $check_pics), '', '')
					));
				}
				if ($nbr_sub_cat > 0) //Converse uniquement les sous-galeries.
				{
					//Listing des cat�gories disponibles, sauf celle qui va �tre supprim�e.		
					$subgallery = '<option value="0">' . $LANG['root'] . '</option>';
					$result = $Sql->query_while("SELECT id, name, level
					FROM " . PREFIX . "gallery_cats 
					WHERE id_left NOT BETWEEN '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND '" . $CAT_GALLERY[$idcat]['id_right'] . "'
					ORDER BY id_left");
					while ($row = $Sql->fetch_assoc($result))
					{
						$margin = ($row['level'] > 0) ? str_repeat('--------', $row['level']) : '--';
						$subgallery .= '<option value="' . $row['id'] . '">' . $margin . ' ' . $row['name'] . '</option>';
					}
					$result->dispose();
					
					$tpl->assign_block_vars('subgalleries', array(
						'GALLERIES' => $subgallery,
						'L_KEEP' => $LANG['keep_subgallery'],
						'L_MOVE_GALLERIES' => $LANG['move_subgalleries_to'],
						'L_EXPLAIN_CAT' => sprintf($LANG['error_warning'], sprintf((($nbr_sub_cat > 1) ? $LANG['explain_subgalleries'] : $LANG['explain_subgallery']), $nbr_sub_cat), '', '')
					));
				}
		
				$gallery_name = $Sql->query("SELECT name FROM " . PREFIX . "gallery_cats WHERE id = '" . $idcat . "'");
				$tpl->put_all(array(
					'IDCAT' => $idcat,
					'GALLERY_NAME' => $gallery_name,
					'L_REQUIRE_SUBCAT' => $LANG['require_subcat'],
					'L_GALLERY_MANAGEMENT' => $LANG['gallery_management'], 
					'L_GALLERY_PICS_ADD' => $LANG['gallery_pics_add'], 
					'L_GALLERY_CAT_MANAGEMENT' => $LANG['gallery_cats_management'], 
					'L_GALLERY_CAT_ADD' => $LANG['gallery_cats_add'],
					'L_GALLERY_CONFIG' => $LANG['gallery_config'],
					'L_CAT_TARGET' => $LANG['cat_target'],
					'L_DEL_ALL' => $LANG['del_all'],
					'L_DEL_GALLERY_CONTENTS' => sprintf($LANG['del_gallery_contents'], $gallery_name),
					'L_SUBMIT' => $LANG['submit'],
				));
				
				$tpl->display();
			}
			else //Traitements.
			{			
				if (!empty($_POST['del_conf']))
				{
					$confirm_delete = true;
				}
				else
				{
					//D�placement de sous galeries.
					$f_to = !empty($_POST['f_to']) ? NumberHelper::numeric($_POST['f_to']) : 0;
					$f_to = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats WHERE id = '" . $f_to . "' AND id_left NOT BETWEEN '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
					
					//D�placement d'images
					$t_to = !empty($_POST['t_to']) ? NumberHelper::numeric($_POST['t_to']) : 0;
					$t_to = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats WHERE id = '" . $t_to . "' AND id <> '" . $idcat . "'");
					
					####D�placement des images dans la cat�gorie s�lectionn�e.####
					//Galeries parentes de la galerie � supprimer.
					$list_parent_cats = '';
					$result = $Sql->query_while("SELECT id
					FROM " . PREFIX . "gallery_cats 
					WHERE id_left < '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND id_right > '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
					while ($row = $Sql->fetch_assoc($result))
					{
						$list_parent_cats .= $row['id'] . ', ';
					}
					$result->dispose();
					$list_parent_cats = trim($list_parent_cats, ', ');
					
					//On va chercher la somme du nombre d'images
					$nbr_pics_aprob = $Sql->query("SELECT nbr_pics_aprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $idcat . "'");
					$nbr_pics_aprob = !empty($nbr_pics_aprob) ? $nbr_pics_aprob : 0;
					$nbr_pics_unaprob = $Sql->query("SELECT nbr_pics_unaprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $idcat . "'");
					$nbr_pics_unaprob = !empty($nbr_pics_unaprob) ? $nbr_pics_unaprob : 0;
					
					//On d�place les images dans la nouvelle galerie.
					$Sql->query_inject("UPDATE " . PREFIX . "gallery SET idcat = '" . $t_to . "' WHERE idcat = '" . $idcat . "'");

					//On met � jour la nouvelle galerie.
					$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET nbr_pics_aprob = nbr_pics_aprob + " . NumberHelper::numeric($nbr_pics_aprob) . ", nbr_pics_unaprob = nbr_pics_unaprob + " . NumberHelper::numeric($nbr_pics_unaprob) . " WHERE id = '" . $t_to . "'");
					
					//On modifie les bornes droites des parents et le nbr d'images.
					if (!empty($list_parent_cats))
					{
						$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET nbr_pics_aprob = nbr_pics_aprob - " . NumberHelper::numeric($nbr_pics_aprob) . ", nbr_pics_unaprob = nbr_pics_unaprob - " . NumberHelper::numeric($nbr_pics_unaprob) . " WHERE id IN (" . $list_parent_cats . ")");
					}
					
					//On supprime l'ancienne galerie.
					PersistenceContext::get_querier()->delete(PREFIX . 'gallery_cats', 'WHERE id=:id', array('id' => $idcat));
					
					//Pr�sence de sous-galeries => d�placement de celles-ci.
					if ($nbr_sub_cat > 0)
					{
						//Sous galeries de la galerie � supprimer.
						$list_sub_cats = '';
						$result = $Sql->query_while("SELECT id
						FROM " . PREFIX . "gallery_cats 
						WHERE id_left BETWEEN '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND '" . $CAT_GALLERY[$idcat]['id_right'] . "' AND id != '" . $idcat . "'
						ORDER BY id_left");
						while ($row = $Sql->fetch_assoc($result))
						{
							$list_sub_cats .= $row['id'] . ', ';
						}
						$result->dispose();
						$list_sub_cats = trim($list_sub_cats, ', ');
						
						//Galeries parentes de la galerie � supprimer.
						$list_parent_cats = '';
						$result = $Sql->query_while("SELECT id
						FROM " . PREFIX . "gallery_cats 
						WHERE id_left < '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND id_right > '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
						while ($row = $Sql->fetch_assoc($result))
						{
							$list_parent_cats .= $row['id'] . ', ';
						}
						$result->dispose();
						$list_parent_cats = trim($list_parent_cats, ', ');
						
						//Pr�caution pour �viter erreur fatale, cas impossible si coh�rence de l'arbre respect�e.
						if (empty($list_sub_cats))
							AppContext::get_response()->redirect(HOST . REWRITED_SCRIPT);

						//Galeries parentes de la galerie cible.
						if (!empty($f_to))
						{							
							$list_parent_cats_to = '';
							$result = $Sql->query_while("SELECT id
							FROM " . PREFIX . "gallery_cats 
							WHERE id_left <= '" . $CAT_GALLERY[$f_to]['id_left'] . "' AND id_right >= '" . $CAT_GALLERY[$f_to]['id_right'] . "'");
							while ($row = $Sql->fetch_assoc($result))
							{
								$list_parent_cats_to .= $row['id'] . ', ';
							}
							$result->dispose();
							$list_parent_cats_to = trim($list_parent_cats_to, ', ');
						
							if (empty($list_parent_cats_to))
								$clause_parent_cats_to = " id = '" . $f_to . "'";
							else
								$clause_parent_cats_to = " id IN (" . $list_parent_cats_to . ")";
						}
							
						########## Suppression ##########
						//On supprime l'ancienne galerie.
						PersistenceContext::get_querier()->delete(PREFIX . 'gallery_cats', 'WHERE id=:id', array('id' => $idcat));
						
						//On supprime virtuellement (changement de signe des bornes) les enfants.
						$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = - id_left, id_right = - id_right WHERE id IN (" . $list_sub_cats . ")");					
						
						//R�cup�ration du nombre d'images de la galerie.
						$nbr_pics_aprob = $Sql->query("SELECT nbr_pics_aprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $idcat . "'");
						$nbr_pics_unaprob = $Sql->query("SELECT nbr_pics_unaprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $idcat . "'");
			
						//On modifie les bornes droites des parents et le nbr d'images.
						if (!empty($list_parent_cats))
						{
							$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right - '" . (2 + $nbr_sub_cat*2) . "', nbr_pics_aprob = nbr_pics_aprob - " . NumberHelper::numeric($nbr_pics_aprob) . ", nbr_pics_unaprob = nbr_pics_unaprob - " . NumberHelper::numeric($nbr_pics_unaprob) . " WHERE id IN (" . $list_parent_cats . ")");
						}
						
						//On r�duit la taille de l'arbre du nombre de galerie supprim�es � partir de la position de celui-ci.
						$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left - '" . (2 + $nbr_sub_cat*2) . "', id_right = id_right - '" . (2 + $nbr_sub_cat*2) . "' WHERE id_left > '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
					
						########## Ajout ##########
						if (!empty($f_to)) //Galerie cible diff�rent de la racine.
						{
							//On modifie les bornes droites et le nbr d'images des parents de la cible.
							$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right + '" . ($nbr_sub_cat*2) . "', nbr_pics_aprob = nbr_pics_aprob + " . NumberHelper::numeric($nbr_pics_aprob) . ", nbr_pics_unaprob = nbr_pics_unaprob + " . NumberHelper::numeric($nbr_pics_unaprob) . " WHERE " . $clause_parent_cats_to);
							
							//On augmente la taille de l'arbre du nombre de galerie supprim�es � partir de la position de la galerie cible.
							if ($CAT_GALLERY[$idcat]['id_left'] > $CAT_GALLERY[$f_to]['id_left']) //Direction galerie source -> galerie cible.
							{	
								$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left + '" . ($nbr_sub_cat*2) . "', id_right = id_right + '" . ($nbr_sub_cat*2) . "' WHERE id_left > '" . $CAT_GALLERY[$f_to]['id_right'] . "'");						
								$limit = $CAT_GALLERY[$f_to]['id_right'];
								$end = $limit + ($nbr_sub_cat*2) - 1;
							}
							else
							{	
								$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left + '" . ($nbr_sub_cat*2) . "', id_right = id_right + '" . ($nbr_sub_cat*2) . "' WHERE id_left > '" . ($CAT_GALLERY[$f_to]['id_right'] - (2 + $nbr_sub_cat*2)) . "'");
								$limit = $CAT_GALLERY[$f_to]['id_right'] - (2 + $nbr_sub_cat*2);
								$end = $limit + ($nbr_sub_cat*2) - 1;						
							}
							
							//On replace les galeries supprim�es virtuellement.
							$array_sub_cats = explode(', ', $list_sub_cats);
							$z = 0;
							for ($i = $limit; $i <= $end; $i = $i + 2)
							{
								$id_left = $limit + ($CAT_GALLERY[$array_sub_cats[$z]]['id_left'] - $CAT_GALLERY[$idcat]['id_left']) - 1;
								$id_right = $end - ($CAT_GALLERY[$idcat]['id_right'] - $CAT_GALLERY[$array_sub_cats[$z]]['id_right']) + 1;
								$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'");
								$z++;
							}								

							//On met � jour le nouveau galerie.
							$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET level = level - '" . ($CAT_GALLERY[$idcat]['level'] - $CAT_GALLERY[$f_to]['level']) . "' WHERE id IN (" . $list_sub_cats . ")");
						}
						else //Racine
						{
							$max_id = $Sql->query("SELECT MAX(id_right) FROM " . PREFIX . "gallery_cats");
							//On replace les galeries supprim�es virtuellement.
							$array_sub_cats = explode(', ', $list_sub_cats);
							$z = 0;
							$limit = $max_id + 1;
							$end = $limit + ($nbr_sub_cat*2) - 1;	
							for ($i = $limit; $i <= $end; $i = $i + 2)
							{
								$id_left = $limit + ($CAT_GALLERY[$array_sub_cats[$z]]['id_left'] - $CAT_GALLERY[$idcat]['id_left']) - 1;
								$id_right = $end - ($CAT_GALLERY[$idcat]['id_right'] - $CAT_GALLERY[$array_sub_cats[$z]]['id_right']) + 1;
								$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'");
								$z++;
							}		
							$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET level = level - '" . ($CAT_GALLERY[$idcat]['level'] - $CAT_GALLERY[$f_to]['level'] + 1) . "' WHERE id IN (" . $list_sub_cats . ")");
						}
					}
					else //On r�tabli l'arbre intervallaire.
					{
						$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right - 2 WHERE id_left < '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND id_right > '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
						$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left - 2, id_right = id_right - 2 WHERE id_left > '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
					}
					
					$Cache->Generate_module_file('gallery');
					
					AppContext::get_response()->redirect(HOST . REWRITED_SCRIPT);
				}	
			}
		}

		if ($confirm_delete) //Confirmation de suppression, on supprime dans la bdd.
		{
			//Galeries parentes de la galerie � supprimer.
			$list_parent_cats = '';
			$result = $Sql->query_while("SELECT id
			FROM " . PREFIX . "gallery_cats 
			WHERE id_left < '" . $CAT_GALLERY[$idcat]['id_left'] . "' AND id_right > '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
			while ($row = $Sql->fetch_assoc($result))
			{
				$list_parent_cats .= $row['id'] . ', ';
			}
			$result->dispose();
			$list_parent_cats = trim($list_parent_cats, ', ');
			
			$nbr_del = $CAT_GALLERY[$idcat]['id_right'] - $CAT_GALLERY[$idcat]['id_left'] + 1;
			if (!empty($list_parent_cats))
			{
				//R�cup�ration du nombre d'images de la galerie.
				$nbr_pics_aprob = $Sql->query("SELECT nbr_pics_aprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $idcat . "'");
				$nbr_pics_unaprob = $Sql->query("SELECT nbr_pics_unaprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $idcat . "'");
				
				$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right - '" . $nbr_del . "', nbr_pics_aprob = nbr_pics_aprob - '" . NumberHelper::numeric($nbr_pics_aprob) . "', nbr_pics_unaprob = nbr_pics_unaprob - '" . NumberHelper::numeric($nbr_pics_unaprob) . "' WHERE id IN (" . $list_parent_cats . ")");
			}		
			
			PersistenceContext::get_querier()->delete(PREFIX . 'gallery_cats', 'WHERE id_left BETWEEN :id_left AND :id_right', array('id_left' => $CAT_GALLERY[$idcat]['id_left'], 'id_right' => $CAT_GALLERY[$idcat]['id_right']));
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left - '" . $nbr_del . "', id_right = id_right - '" . $nbr_del . "' WHERE id_left > '" . $CAT_GALLERY[$idcat]['id_right'] . "'");
			PersistenceContext::get_querier()->delete(PREFIX . 'gallery', 'WHERE idcat=:id', array('id' => $idcat));
			
			###### Reg�n�ration du cache #######
			$Cache->Generate_module_file('gallery');
			
			AppContext::get_response()->redirect('/gallery/admin_gallery_cat.php');
		}		
	}
	else
		AppContext::get_response()->redirect('/gallery/admin_gallery_cat.php');
}
elseif (!empty($id) && !empty($move)) //Monter/descendre.
{
	AppContext::get_session()->csrf_get_protect(); //Protection csrf
	
	$Cache->load('gallery');
	
	//Cat�gorie existe?
	if (!isset($CAT_GALLERY[$id]))
		AppContext::get_response()->redirect('/gallery/admin_gallery_cat.php');
	
	//Galeries parentes de la galerie � d�placer.
	$list_parent_cats = '';
	$result = $Sql->query_while("SELECT id 
	FROM " . PREFIX . "gallery_cats 
	WHERE id_left < '" . $CAT_GALLERY[$id]['id_left'] . "' AND id_right > '" . $CAT_GALLERY[$id]['id_right'] . "'");
	while ($row = $Sql->fetch_assoc($result))
	{
		$list_parent_cats .= $row['id'] . ', ';
	}
	$result->dispose();
	$list_parent_cats = trim($list_parent_cats, ', ');
	
	$to = 0;
	if ($move == 'up')
	{	
		//M�me cat�gorie
		$switch_id_cat = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats
		WHERE '" . $CAT_GALLERY[$id]['id_left'] . "' - id_right = 1");		
		if (!empty($switch_id_cat))
		{
			//On monte la cat�gorie � d�placer, on lui assigne des id n�gatifs pour assurer l'unicit�.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = - id_left + '" . ($CAT_GALLERY[$switch_id_cat]['id_right'] - $CAT_GALLERY[$switch_id_cat]['id_left'] + 1) . "', id_right = - id_right + '" . ($CAT_GALLERY[$switch_id_cat]['id_right'] - $CAT_GALLERY[$switch_id_cat]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_GALLERY[$id]['id_left'] . "' AND '" . $CAT_GALLERY[$id]['id_right'] . "'");
			//On descend la cat�gorie cible.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left + '" . ($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$id]['id_left'] + 1) . "', id_right = id_right + '" . ($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$id]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_GALLERY[$switch_id_cat]['id_left'] . "' AND '" . $CAT_GALLERY[$switch_id_cat]['id_right'] . "'");
			
			//On r�tablit les valeurs absolues.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = - id_left WHERE id_left < 0");
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = - id_right WHERE id_right < 0");	
			
			$Cache->Generate_module_file('gallery');
		}		
		elseif (!empty($list_parent_cats) )
		{
			//Changement de cat�gorie.
			$to = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats
			WHERE id_left < '" . $CAT_GALLERY[$id]['id_left'] . "' AND level = '" . ($CAT_GALLERY[$id]['level'] - 1) . "' AND
			id NOT IN (" . $list_parent_cats . ")
			ORDER BY id_left DESC" . 
			$Sql->limit(0, 1));
		}
	}
	elseif ($move == 'down')
	{
		//Doit-on changer de cat�gorie parente ou non ?
		$switch_id_cat = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats
		WHERE id_left - '" . $CAT_GALLERY[$id]['id_right'] . "' = 1");
		if (!empty($switch_id_cat))
		{
			//On monte la cat�gorie � d�placer, on lui assigne des id n�gatifs pour assurer l'unicit�.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = - id_left - '" . ($CAT_GALLERY[$switch_id_cat]['id_right'] - $CAT_GALLERY[$switch_id_cat]['id_left'] + 1) . "', id_right = - id_right - '" . ($CAT_GALLERY[$switch_id_cat]['id_right'] - $CAT_GALLERY[$switch_id_cat]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_GALLERY[$id]['id_left'] . "' AND '" . $CAT_GALLERY[$id]['id_right'] . "'");
			//On descend la cat�gorie cible.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left - '" . ($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$id]['id_left'] + 1) . "', id_right = id_right - '" . ($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$id]['id_left'] + 1) . "' WHERE id_left BETWEEN '" . $CAT_GALLERY[$switch_id_cat]['id_left'] . "' AND '" . $CAT_GALLERY[$switch_id_cat]['id_right'] . "'");
			
			//On r�tablit les valeurs absolues.
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = - id_left WHERE id_left < 0");
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = - id_right WHERE id_right < 0");
			
			$Cache->Generate_module_file('gallery');
		}
		elseif (!empty($list_parent_cats) )
		{
			//Changement de cat�gorie.
			$to = $Sql->query("SELECT id FROM " . PREFIX . "gallery_cats
			WHERE id_left > '" . $CAT_GALLERY[$id]['id_left'] . "' AND level = '" . ($CAT_GALLERY[$id]['level'] - 1) . "'
			ORDER BY id_left" . 
			$Sql->limit(0, 1));
		}
	}

	if (!empty($to)) //Changement de cat�gorie possible?
	{
		//On v�rifie si la cat�gorie contient des sous galeries.
		$nbr_cat = (($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$id]['id_left'] - 1) / 2) + 1;
	
		//Sous galeries de la galerie � d�placer.
		$list_cats = '';
		$result = $Sql->query_while("SELECT id
		FROM " . PREFIX . "gallery_cats 
		WHERE id_left BETWEEN '" . $CAT_GALLERY[$id]['id_left'] . "' AND '" . $CAT_GALLERY[$id]['id_right'] . "'
		ORDER BY id_left");
		while ($row = $Sql->fetch_assoc($result))
		{
			$list_cats .= $row['id'] . ', ';
		}
		$result->dispose();
		$list_cats = trim($list_cats, ', ');
	
		if (empty($list_cats))
			$clause_cats = " id = '" . $id . "'";
		else
			$clause_cats = " id IN (" . $list_cats . ")";
			
		//R�cup�ration du nombre d'images de la galerie.
		$nbr_pics_aprob = $Sql->query("SELECT nbr_pics_aprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $id . "'");
		$nbr_pics_unaprob = $Sql->query("SELECT nbr_pics_unaprob FROM " . PREFIX . "gallery_cats WHERE id = '" . $id . "'");
		
		//Galeries parentes de la galerie cible.
		$list_parent_cats_to = '';
		$result = $Sql->query_while("SELECT id, level 
		FROM " . PREFIX . "gallery_cats 
		WHERE id_left <= '" . $CAT_GALLERY[$to]['id_left'] . "' AND id_right >= '" . $CAT_GALLERY[$to]['id_right'] . "'");
		while ($row = $Sql->fetch_assoc($result))
		{
			$list_parent_cats_to .= $row['id'] . ', ';
		}
		$result->dispose();
		$list_parent_cats_to = trim($list_parent_cats_to, ', ');
	
		if (empty($list_parent_cats_to))
			$clause_parent_cats_to = " id = '" . $to . "'";
		else
			$clause_parent_cats_to = " id IN (" . $list_parent_cats_to . ")";
			
		########## Suppression ##########
		//On supprime virtuellement (changement de signe des bornes) les enfants.
		$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = - id_left, id_right = - id_right WHERE " . $clause_cats);
		//On modifie les bornes droites des parents.
		if (!empty($list_parent_cats))
		{
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right - '" . ( $nbr_cat*2) . "', nbr_pics_aprob = nbr_pics_aprob - '" . $nbr_pics_aprob . "', nbr_pics_unaprob = nbr_pics_unaprob - '" . $nbr_pics_unaprob . "' WHERE id IN (" . $list_parent_cats . ")");
		}
		
		//On r�duit la taille de l'arbre du nombre de galeries supprim�es � partir de la position de celui-ci.
		$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left - '" . ($nbr_cat*2) . "', id_right = id_right - '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_GALLERY[$id]['id_right'] . "'");

		########## Ajout ##########
		//On modifie les bornes droites des parents de la cible.
		$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_right = id_right + '" . ($nbr_cat*2) . "', nbr_pics_aprob = nbr_pics_aprob + '" . $nbr_pics_aprob . "', nbr_pics_unaprob = nbr_pics_unaprob + '" . $nbr_pics_unaprob . "' WHERE " . $clause_parent_cats_to);

		//On augmente la taille de l'arbre du nombre de galeries supprim�es � partir de la position de la galerie cible.
		if ($CAT_GALLERY[$id]['id_left'] > $CAT_GALLERY[$to]['id_left'] ) //Direction galerie source -> galerie cible.
		{
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . $CAT_GALLERY[$to]['id_right'] . "'");
			$limit = $CAT_GALLERY[$to]['id_right'];
			$end = $limit + ($nbr_cat*2) - 1;
		}
		else
		{
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = id_left + '" . ($nbr_cat*2) . "', id_right = id_right + '" . ($nbr_cat*2) . "' WHERE id_left > '" . ($CAT_GALLERY[$to]['id_right'] - ($nbr_cat*2)) . "'");
			$limit = $CAT_GALLERY[$to]['id_right'] - ($nbr_cat*2);
			$end = $limit + ($nbr_cat*2) - 1;
		}

		//On replace les galeries supprim�es virtuellement.
		$array_sub_cats = explode(', ', $list_cats);
		$z = 0;
		for ($i = $limit; $i <= $end; $i = $i + 2)
		{
			$id_left = $limit + ($CAT_GALLERY[$array_sub_cats[$z]]['id_left'] - $CAT_GALLERY[$id]['id_left']);
			$id_right = $end - ($CAT_GALLERY[$id]['id_right'] - $CAT_GALLERY[$array_sub_cats[$z]]['id_right']);
			$Sql->query_inject("UPDATE " . PREFIX . "gallery_cats SET id_left = '" . $id_left . "', id_right = '" . $id_right . "' WHERE id = '" . $array_sub_cats[$z] . "'");
			$z++;
		}
		
		$Cache->Generate_module_file('gallery');
	}
		
	AppContext::get_response()->redirect(HOST . REWRITED_SCRIPT);
}
elseif (!empty($id)) //Edition des cat�gories.
{
	$Cache->load('gallery');
	
	$tpl = new FileTemplate('gallery/admin_gallery_cat_edit.tpl');
	
	$gallery_info = $Sql->query_array(PREFIX . "gallery_cats", "id_left", "id_right", "level", "name", "contents", "status", "aprob", "auth", "WHERE id = '" . $id . "'");
	
	if (!isset($CAT_GALLERY[$id]))
		AppContext::get_response()->redirect('/gallery/admin_gallery_cat.php?error=unexist_cat');
	
	//Listing des cat�gories disponibles, sauf celle qui va �tre supprim�e.
	$galeries = '<option value="0">' . $LANG['root'] . '</option>';
	$result = $Sql->query_while("SELECT id, id_left, id_right, name, level
	FROM " . PREFIX . "gallery_cats 
	WHERE id_left NOT BETWEEN '" . $CAT_GALLERY[$id]['id_left'] . "' AND '" . $CAT_GALLERY[$id]['id_right'] . "'
	ORDER BY id_left");
	while ($row = $Sql->fetch_assoc($result))
	{	
		$margin = ($row['level'] > 0) ? str_repeat('--------', $row['level']) : '--';
		$selected = ($row['id_left'] < $gallery_info['id_left'] && $row['id_right'] > $gallery_info['id_right'] && ($gallery_info['level'] - 1) == $row['level'] ) ? ' selected="selected"' : '';
		$galeries .= '<option value="' . $row['id'] . '"' . $selected . '>' . $margin . ' ' . $row['name'] . '</option>';
	}
	$result->dispose();
	
	$array_auth = !empty($gallery_info['auth']) ? unserialize($gallery_info['auth']) : array(); //R�cup�ration des tableaux des autorisations et des groupes.
	
	//Gestion erreur.
	$get_error = !empty($_GET['error']) ? trim($_GET['error']) : '';
	if ($get_error == 'incomplete')
		$tpl->put('message_helper', MessageHelper::display($LANG['e_incomplete'], MessageHelper::NOTICE));

	$tpl->put_all(array(
		'ID' => $id,
		'CATEGORIES' => $galeries,
		'NAME' => $gallery_info['name'],
		'DESC' => $gallery_info['contents'],
		'CHECKED_APROB' => ($gallery_info['aprob'] == 1) ? 'checked="checked"' : '',
		'UNCHECKED_APROB' => ($gallery_info['aprob'] == 0) ? 'checked="checked"' : '',
		'CHECKED_STATUS' => ($gallery_info['status'] == 1) ? 'checked="checked"' : '',
		'UNCHECKED_STATUS' => ($gallery_info['status'] == 0) ? 'checked="checked"' : '',
		'AUTH_READ' => Authorizations::generate_select(GalleryAuthorizationsService::READ_AUTHORIZATIONS, $array_auth),
		'AUTH_WRITE' => Authorizations::generate_select(GalleryAuthorizationsService::WRITE_AUTHORIZATIONS, $array_auth),
		'AUTH_EDIT' => Authorizations::generate_select(GalleryAuthorizationsService::MODERATION_AUTHORIZATIONS, $array_auth),
		'L_REQUIRE_TITLE' => $LANG['require_title'],
		'L_GALLERY_MANAGEMENT' => $LANG['gallery_management'], 
		'L_GALLERY_PICS_ADD' => $LANG['gallery_pics_add'], 
		'L_GALLERY_CAT_MANAGEMENT' => $LANG['gallery_cats_management'], 
		'L_GALLERY_CAT_ADD' => $LANG['gallery_cats_add'],
		'L_GALLERY_CONFIG' => $LANG['gallery_config'],
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
		'L_LOCK' => $LANG['gallery_lock'],
		'L_UNLOCK' => $LANG['gallery_unlock'],
		'L_GUEST' => $LANG['guest'],
		'L_USER' => $LANG['member'],
		'L_MODO' => $LANG['modo'],
		'L_ADMIN' => $LANG['admin'],
		'L_UPDATE' => $LANG['update'],
		'L_AUTH_READ' => $LANG['auth_read'],
		'L_AUTH_WRITE' => $LANG['auth_upload'],
		'L_AUTH_EDIT' => $LANG['auth_edit'],
		'L_EXPLAIN_SELECT_MULTIPLE' => $LANG['explain_select_multiple'],
		'L_SELECT_ALL' => $LANG['select_all'],
		'L_SELECT_NONE' => $LANG['select_none']
	));
	
	$tpl->display();
}
elseif (!empty($root)) //Edition de la racine.
{
	$Cache->load('gallery');
	
	$tpl = new FileTemplate('gallery/admin_gallery_cat_edit2.tpl');
			
	//Gestion erreur.
	$get_error = !empty($_GET['error']) ? trim($_GET['error']) : '';
	if ($get_error == 'incomplete')
		$tpl->put('message_helper', MessageHelper::display($LANG['e_incomplete'], MessageHelper::NOTICE));	
	
	$tpl->put_all(array(
		'AUTH_READ' => Authorizations::generate_select(GalleryAuthorizationsService::READ_AUTHORIZATIONS, $config->get_authorizations()),
		'AUTH_WRITE' => Authorizations::generate_select(GalleryAuthorizationsService::WRITE_AUTHORIZATIONS, $config->get_authorizations()),
		'AUTH_EDIT' => Authorizations::generate_select(GalleryAuthorizationsService::MODERATION_AUTHORIZATIONS, $config->get_authorizations()),
		'L_ROOT' => $LANG['root'],
		'L_GALLERY_MANAGEMENT' => $LANG['gallery_management'], 
		'L_GALLERY_PICS_ADD' => $LANG['gallery_pics_add'], 
		'L_GALLERY_CAT_MANAGEMENT' => $LANG['gallery_cats_management'], 
		'L_GALLERY_CAT_ADD' => $LANG['gallery_cats_add'],
		'L_GALLERY_CONFIG' => $LANG['gallery_config'],
		'L_EDIT_CAT' => $LANG['cat_edit'],
		'L_RESET' => $LANG['reset'],
		'L_UPDATE' => $LANG['update'],
		'L_AUTH_READ' => $LANG['auth_read'],
		'L_AUTH_WRITE' => $LANG['auth_upload'],
		'L_AUTH_EDIT' => $LANG['auth_edit'],
		'L_EXPLAIN_SELECT_MULTIPLE' => $LANG['explain_select_multiple'],
		'L_SELECT_ALL' => $LANG['select_all'],
		'L_SELECT_NONE' => $LANG['select_none']
	));
	
	$tpl->display();
}
else
{
	$tpl = new FileTemplate('gallery/admin_gallery_cat.tpl');
		
	//Gestion erreur.
	$get_error = !empty($_GET['error']) ? trim($_GET['error']) : '';
	if ($get_error == 'unexist_cat')
		$tpl->put('message_helper', MessageHelper::display($LANG['e_unexist_cat'], MessageHelper::NOTICE));
		
	$tpl->put_all(array(
		'L_GALLERY_MANAGEMENT' => $LANG['gallery_management'], 
		'L_GALLERY_PICS_ADD' => $LANG['gallery_pics_add'], 
		'L_GALLERY_CAT_MANAGEMENT' => $LANG['gallery_cats_management'], 
		'L_GALLERY_CAT_ADD' => $LANG['gallery_cats_add'],
		'L_GALLERY_CONFIG' => $LANG['gallery_config'],
		'L_ROOT' => $LANG['root']
	));

	$max_cat = $Sql->query("SELECT MAX(id_left) FROM " . PREFIX . "gallery_cats");
	$list_cats_js = '';
	$array_js = '';	
	$i = 0;
	$result = $Sql->query_while("SELECT id, id_left, id_right, level, name, contents, status
	FROM " . PREFIX . "gallery_cats 
	ORDER BY id_left");
	while ($row = $Sql->fetch_assoc($result))
	{
		//On assigne les variables pour le POST en pr�cisant l'idurl.
		$tpl->assign_block_vars('list', array(
			'C_DESC' => !empty($row['contents']),
			'C_LOCK' => ($row['status'] == 0),
			'I' => $i,
			'ID' => $row['id'],
			'NAME' => $row['name'],
			'DESC' => $row['contents'],
			'INDENT' => ($row['level'] + 1) * 30, //Indentation des sous cat�gories.
			'U_GALLERY_VARS' => url('.php?cat=' . $row['id'], '-' . $row['id'] . '+' . Url::encode_rewrite($row['name']) . '.php')
		));
		
		$list_cats_js .= $row['id'] . ', ';
		
		$array_js .= 'array_cats[' . $row['id'] . '] = new Array();' . "\n"; 
		$array_js .= 'array_cats[' . $row['id'] . '][\'id\'] = ' . $row['id'] . ";\n";
		$array_js .= 'array_cats[' . $row['id'] . '][\'id_left\'] = ' . $row['id_left'] . ";\n";
		$array_js .= 'array_cats[' . $row['id'] . '][\'id_right\'] = ' . $row['id_right'] . ";\n";
		$array_js .= 'array_cats[' . $row['id'] . '][\'level\'] = ' . $row['level'] . ";\n";
		$array_js .= 'array_cats[' . $row['id'] . '][\'i\'] = ' . $i . ";\n";
		$i++;
	}
	$result->dispose();
	
	$tpl->put_all(array(
		'LIST_CATS' => trim($list_cats_js, ', '),
		'ARRAY_JS' => $array_js,
		'ID_END' => ($i - 1)
	));

	$tpl->display();
}
	
require_once('../admin/admin_footer.php');

?>