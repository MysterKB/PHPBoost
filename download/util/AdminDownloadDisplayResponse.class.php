<?php
/*##################################################
 *                               AdminDownloadDisplayResponse.class.php
 *                            -------------------
 *   begin                : August 24, 2014
 *   copyright            : (C) 2014 Julien BRISWALTER
 *   email                : julienseth78@phpboost.com
 *
 *
 ###################################################
 *
 * This program is a free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ###################################################*/

 /**
 * @author Julien BRISWALTER <julienseth78@phpboost.com>
 */

class AdminDownloadDisplayResponse extends AdminMenuDisplayResponse
{
	public function __construct($view, $title_page)
	{
		parent::__construct($view);
		
		$lang = LangLoader::get('common', 'download');
		$this->set_title($lang['module_title']);
		
		$this->add_link(LangLoader::get_message('categories.management', 'categories-common'), DownloadUrlBuilder::manage_categories());
		$this->add_link(LangLoader::get_message('category.add', 'categories-common'), DownloadUrlBuilder::add_category());
		$this->add_link($lang['download.management'], DownloadUrlBuilder::manage());
		$this->add_link($lang['download.actions.add'], DownloadUrlBuilder::add());
		$this->add_link(LangLoader::get_message('configuration', 'admin-common'), DownloadUrlBuilder::configuration());
		
		$env = $this->get_graphical_environment();
		$env->set_page_title($title_page, $lang['module_title']);
	}
}
?>
