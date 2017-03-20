<?php
/*##################################################
 *                       ShoutboxModuleUpdateVersion.class.php
 *                            -------------------
 *   begin                : March 09, 2017
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

class ShoutboxModuleUpdateVersion extends ModuleUpdateVersion
{
	private $querier;
	
	public function __construct()
	{
		parent::__construct('shoutbox');
		$this->querier = PersistenceContext::get_querier();
	}
	
	public function execute()
	{
		if (ModulesManager::is_module_installed('shoutbox'))
		{
			$this->update_content();
		}
	}
	
	public function update_content()
	{
		$unparser = new OldBBCodeUnparser();
		$parser = new BBCodeParser();
		
		$result = $this->querier->select('SELECT id, contents FROM ' . PREFIX . 'shoutbox');
		
		while($row = $result->fetch())
		{
			$unparser->set_content($row['contents']);
			$unparser->parse();
			$parser->set_content($unparser->get_content());
			$parser->parse();
			
			if ($parser->get_content() != $row['contents'])
				$this->querier->update(PREFIX . 'shoutbox', array('contents' => $parser->get_content()), 'WHERE id=:id', array('id' => $row['id']));
		}
		$result->dispose();
	}
}
?>