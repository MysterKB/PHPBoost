<?php
/*##################################################
 *                                forum_changeday.php
 *                            -------------------
 *   begin                : November 25, 2006
 *   copyright            : (C) 2008 Loïc ROUCHON
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

if( defined('PHP_BOOST') !== true) exit;

// Délestage du cache des recherches
$Sql->Query_inject("TRUNCATE ".PREFIX."search_results", __LINE__, __FILE__);
$Sql->Query_inject("TRUNCATE ".PREFIX."search_index", __LINE__, __FILE__);

?>