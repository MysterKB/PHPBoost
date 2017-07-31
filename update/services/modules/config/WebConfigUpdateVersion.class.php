<?php
/*##################################################
 *                       WebConfigUpdateVersion.class.php
 *                            -------------------
 *   begin                : April 5, 2017
 *   copyright            : (C) 2017 Julien BRISWALTER
 *   email                : j1.seth@phpboost.com
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

class WebConfigUpdateVersion extends ConfigUpdateVersion
{
	public function __construct()
	{
		parent::__construct('web-config', false);
	}
	
	protected function build_new_config()
	{
		$old_config = $this->get_old_config();
		
		if ($old_config)
		{
			if (!$old_config->get_property('comments_enabled'))
			{
				$comments_config = CommentsConfig::load();
				$unauthorized_modules = $comments_config->get_comments_unauthorized_modules();
				$unauthorized_modules[] = 'web';
				$comments_config->set_comments_unauthorized_modules($unauthorized_modules);
				CommentsConfig::save();
			}
			
			if (!$old_config->get_property('notation_enabled'))
			{
				$content_management_config = ContentManagementConfig::load();
				$unauthorized_modules = $content_management_config->get_notation_unauthorized_modules();
				$unauthorized_modules[] = 'web';
				$content_management_config->set_notation_unauthorized_modules($unauthorized_modules);
				ContentManagementConfig::save();
			}
			
			return true;
		}
		return false;
	}
}
?>