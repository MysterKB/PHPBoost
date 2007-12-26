<?php
/*##################################################
 *                              gallery_mini.php
 *                            -------------------
 *   begin                : August 03, 2005
 *   copyright          : (C) 2005 Viarre R�gis
 *   email                : crowkait@phpboost.com
 *
 *  
###################################################
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 * 
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
###################################################*/

if( defined('PHP_BOOST') !== true ) exit;

$template->set_filenames(array(
	'gallery_mini' => '../templates/' . $CONFIG['theme'] . '/gallery/gallery_mini.tpl'
));

//Chargement de la langue du module.
@include_once('../gallery/lang/' . $CONFIG['lang'] . '/gallery_' . $CONFIG['lang'] . '.php');
$cache->load_file('gallery'); //Requ�te des configuration g�n�rales (gallery), $CONFIG_ALBUM variable globale.

$array_pics_mini = 'var array_pics_mini = new Array();' . "\n";
list($nbr_pics, $sum_height, $sum_width, $scoll_mode, $height_max, $width_max) = array(0, 0, 0, 0, 142, 142);
if( isset($_array_random_pics) && $_array_random_pics !== array() )
{
	if( !defined('READ_CAT_GALLERY') )
		define('READ_CAT_GALLERY', 0x01);
	
	$gallery_mini = array();
	shuffle($_array_random_pics); //On m�lange les �l�ments du tableau.
	
	//Autorisations de la racine.
	$CAT_GALLERY[0]['auth'] = $CONFIG_GALLERY['auth_root'];
	//V�rification des autorisations.
	$break = 0;
	foreach($_array_random_pics as $key => $array_pics_info)
	{
		if( $groups->check_auth($CAT_GALLERY[$array_pics_info['idcat']]['auth'], READ_CAT_GALLERY) )
		{	
			$gallery_mini[] = $array_pics_info;
			$break++;
		}
		if( $break == $CONFIG_GALLERY['nbr_pics_mini'] )
			break;
	}
	
	//Aucune photo ne correspond, on fait une requ�te pour v�rifier.
	if( count($gallery_mini) == 0 ) 
	{
		$result = $sql->query_while("SELECT g.id, g.name, g.path, g.width, g.height, g.idcat, gc.auth 
		FROM ".PREFIX."gallery AS g
		LEFT JOIN ".PREFIX."gallery_cats AS gc on gc.id = g.idcat
		WHERE g.aprob = 1 AND gc.aprob = 1
		ORDER BY RAND() 
		" . $sql->sql_limit(0, $CONFIG_GALLERY['nbr_pics_mini']), __LINE__, __FILE__);
		$_array_random_pics = $sql->sql_fetch_assoc($result);
		
		//V�rification des autorisations.
		$break = 0;
		foreach($_array_random_pics as $key => $array_pics_info)
		{
			if( $groups->check_auth($CAT_GALLERY[$array_pics_info['idcat']]['auth'], READ_CAT_GALLERY) )
			{	
				$gallery_mini[] = $array_pics_info;
				$break++;
			}
			if( $break == $CONFIG_GALLERY['nbr_pics_mini'] )
				break;
		}
	}
	
	switch($CONFIG_GALLERY['scroll_type'])
	{
		case 0:
		$scoll_mode = 'static_scroll';
		$scroll_block = '';
		break;
		case 1:
		$scroll_block = 'vertical_scroll.';
		$template->assign_block_vars('vertical_scroll', array(
		));
		$scoll_mode = 'dynamic_scroll_v';
		break;
		case 2:
		$scroll_block = 'horizontal_scroll.';
		$template->assign_block_vars('horizontal_scroll', array(
		));
		$scoll_mode = 'dynamic_scroll_h';
		break;
	}	
	
	include_once('../gallery/gallery.class.php'); 
	$gallery = new gallery($sql->req);	
			
	//Affichage des miniatures disponibles
	$i = 0;
	foreach($gallery_mini as $key => $row)
	{	
		//Si la miniature n'existe pas (cache vid�) on reg�n�re la miniature � partir de l'image en taille r�elle.
		if( !is_file('../gallery/pics/thumbnails/' . $row['path']) )
			$gallery->resize_pics('../gallery/pics/' . $row['path']); //Redimensionnement + cr�ation miniature
		
		// On recup�re la hauteur et la largeur de l'image.
		if( $row['width'] == 0 || $row['height'] == 0 )
			list($row['width'], $row['height']) = @getimagesize('../gallery/pics/thumbnails/' . $row['path']);
		if( $row['width'] == 0 || $row['height'] == 0 )
			list($row['width'], $row['height']) = array(142, 142);
			
		if( $CONFIG_GALLERY['scroll_type'] == 1 || $CONFIG_GALLERY['scroll_type'] == 2 )
		{
			$template->assign_block_vars($scroll_block . 'pics_mini', array(
				'ID' => $i,
				'PICS' => '../gallery/pics/thumbnails/' . $row['path'],		
				'NAME' => $row['name'],		
				'HEIGHT' => $row['height'],
				'WIDTH' => $row['width'],
				'U_PICS' => '../gallery/gallery' . transid('.php?cat=' . $row['idcat'] . '&amp;id=' . $row['id'], '-' . $row['idcat'] . '-' . $row['id'] . '.php')	
			));
		}
		else
		{
			$array_pics_mini .= 'array_pics_mini[' . $i . '] = new Array();' . "\n";
			$array_pics_mini .= 'array_pics_mini[' . $i . '][\'link\'] = \'' . transid('.php?cat=' . $row['idcat'] . '&amp;id=' . $row['id'], '-' . $row['idcat'] . '-' . $row['id'] . '.php') . '\';' . "\n";
			$array_pics_mini .= 'array_pics_mini[' . $i . '][\'path\'] = \'' . $row['path'] . '\';' . "\n";
		}
		
		$sum_height += $row['height'] + 5;
		$sum_width += $row['width'] + 5;
		$i++;
	}
}

$template->assign_vars(array(
	'SID' => SID,
	'MODULE_DATA_PATH' => $template->module_data_path('gallery'),
	'ARRAY_PICS' => $array_pics_mini,
	'HEIGHT_DIV' => ($CONFIG_GALLERY['nbr_pics_mini'] > 2 && $CONFIG_GALLERY['scroll_type'] == 1) ? ($CONFIG_GALLERY['height'] * 2) : $CONFIG_GALLERY['height'],
	'SUM_HEIGHT' => $sum_height + 10,
	'HIDDEN_HEIGHT' => ($CONFIG_GALLERY['nbr_pics_mini'] > 2) ? ($CONFIG_GALLERY['height'] * 2) + 10 : $CONFIG_GALLERY['height'] + 10,
	'WIDTH_DIV' => ($CONFIG_GALLERY['nbr_pics_mini'] > 2 && $CONFIG_GALLERY['scroll_type'] == 2) ? ($CONFIG_GALLERY['width'] * (($CONFIG_GALLERY['nbr_pics_mini'] <= 3) ? $CONFIG_GALLERY['nbr_pics_mini'] : 3)) : $CONFIG_GALLERY['width'],
	'SUM_WIDTH' => $sum_width + 30,
	'HIDDEN_WIDTH' => ($CONFIG_GALLERY['width'] * 3) + 30,
	'SCROLL_SPEED' => ($CONFIG_GALLERY['scroll_type'] == 1 || $CONFIG_GALLERY['scroll_type'] == 2) ? $CONFIG_GALLERY['speed_mini_pics']*10 : $CONFIG_GALLERY['speed_mini_pics']*500,
	'SCROLL_MODE' => $scoll_mode,
	'L_RANDOM_PICS' => $LANG['random_img'],
	'L_NO_RANDOM_PICS' => ($i == 0) ? '<br /><span class="text_small"><em>' . $LANG['no_random_img']  . '</em></span><br />' : '',
	'L_GALLERY' => $LANG['gallery']
));

$template->pparse('gallery_mini');	

?>