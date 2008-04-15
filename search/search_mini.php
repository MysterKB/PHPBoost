<?php
/*##################################################
*                               search.php
*                            -------------------
*   begin                : January 27, 2008
*   copyright            : (C) 2008 Rouchon Loïc
*   email                : horn@phpboost.com
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
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*
###################################################*/

if( defined('PHPBOOST') !== true ) exit;

//------------------------------------------------------------------- Language
load_module_lang('search');

$Template->Set_filenames(array(
	'search_mini' => '../templates/'.$CONFIG['theme'].'/search/search_mini.tpl',
));

//--------------------------------------------------------------------- Params
// A protéger impérativement;
$search = !empty($_POST['search']) ? securit($_POST['search']) : '';

//--------------------------------------------------------------------- Header

$Template->Assign_vars(Array(
    'TITLE_SEARCH' => TITLE,
    'SEARCH' => $LANG['title_search'],
    'TEXT_SEARCHED' => !empty($search) ? $search : $LANG['search'] . '...',
    'WARNING_LENGTH_STRING_SEARCH' => $LANG['warning_length_string_searched'],
	'L_SEARCH' => $LANG['search'],
    'U_FORM_VALID' => transid('../search/search.php#results'),
    'L_ADVANCED_SEARCH' => $LANG['advanced_search'],
    'U_ADVANCED_SEARCH' => transid('../search/search.php#results'),
));

//------------------------------------------------------------- Other includes

//----------------------------------------------------------------------- Main

// parsage de la page
$Template->Pparse('search_mini');

//--------------------------------------------------------------------- Footer


?>