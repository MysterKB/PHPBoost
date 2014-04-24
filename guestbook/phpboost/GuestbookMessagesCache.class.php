<?php
/*##################################################
 *                           GuestbookMessagesCache.class.php
 *                            -------------------
 *   begin                : February 1, 2011
 *   copyright            : (C) 2011 Kevin MASSY
 *   email                : soldier.weasel@gmail.com
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

/**
 * @author Kevin MASSY <soldier.weasel@gmail.com>
 */
class GuestbookMessagesCache implements CacheData
{
	private $messages = array();
	
	public function synchronize()
	{
		$this->messages = array();
		$items_number_per_page = GuestbookConfig::load()->get_items_per_page();
		
		$result = PersistenceContext::get_querier()->select("SELECT g.id, g.login, g.contents, g.timestamp, m.user_id, m.login as mlogin, m.level, m.user_groups
		FROM " . GuestbookSetup::$guestbook_table . " g
		LEFT JOIN " . DB_TABLE_MEMBER . " m ON m.user_id = g.user_id
		GROUP BY g.id
		ORDER BY RAND()
		LIMIT 50");
		
		$page = $i = 1;
		foreach ($result as $msg)
		{
			if ($i > ($page * $items_number_per_page))
				$page ++;
			
			$this->messages[$msg['id']] = array(
				'id' => $msg['id'],
				'contents' => strip_tags(FormatingHelper::second_parse($msg['contents'])),
				'user_id' => $msg['user_id'],
				'login' => $msg['mlogin'] ? $msg['mlogin'] : $msg['login'],
				'level' => $msg['level'],
				'user_groups' => $msg['user_groups'],
				'timestamp' => $msg['timestamp'],
				'page' => $page
			);
			$i++;
		}
	}
	
	public function get_messages()
	{
		return $this->messages;
	}
	
	public function get_message($identifier)
	{
		if (isset($this->messages[$identifier]))
		{
			return $this->messages[$identifier];
		}
		return null;
	}
	
	/**
	 * Loads and returns the messages guestbook cached data.
	 * @return GuestbookMessagesCache The cached data
	 */
	public static function load()
	{
		return CacheManager::load(__CLASS__, 'guestbook', 'messages');
	}
	
	/**
	 * Invalidates the current Guestbook messages cached data.
	 */
	public static function invalidate()
	{
		CacheManager::invalidate('guestbook', 'messages');
	}
}
?>
