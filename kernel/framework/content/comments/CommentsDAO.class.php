<?php
/*##################################################
 *                              CommentsDAO.class.php
 *                            -------------------
 *   begin                : September 25, 2011
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
 * @package {@package}
 */
class CommentsDAO
{
	private static $comments_cache;
	private static $db_querier;
	
	public static function __static()
	{
		self::$comments_cache = CommentsCache::load();
		self::$db_querier = PersistenceContext::get_querier();
	}
	
	public static function delete_comments_by_topic($id_topic)
	{
		$condition = "WHERE id_topic = :id_topic";
		$parameters = array('id_topic' => $id_topic);
		self::$db_querier->delete(DB_TABLE_COMMENTS, $condition, $parameters);
	}
	
	public static function delete_comments_topic_module($module_id, $id_in_module)
	{
		$condition = "WHERE module_id = :module_id AND id_in_module = :id_in_module";
		$parameters = array('module_id' => $module_id, 'id_in_module' => $id_in_module);
		self::$db_querier->delete(DB_TABLE_COMMENTS, $condition, $parameters);
	}

	public static function delete_comment($comment_id)
	{
		$condition = "WHERE id = :id";
		$parameters = array('id' => $comment_id);
		self::$db_querier->delete(DB_TABLE_COMMENTS, $condition, $parameters);
	}
	
	public static function get_user_id_posted_comment($comment_id)
	{
		$comment = self::$comments_cache->get_comment($comment_id);
		return $comment['user_id'];
	}
	
	public static function get_last_comment_added($user_id)
	{
		if ($user_id !== '-1')
		{
			return self::$db_querier->inject("SELECT MAX(timestamp) as timestamp FROM " . DB_TABLE_COMMENTS . " WHERE user_id = '" . AppContext::get_current_user()->get_id() . "'");
		}
		else
		{
			return 0;
		}
	}

	public static function get_number_comments($module_id, $id_in_module, $topic_identifier)
	{
		$comments = self::$comments_cache->get_comments_by_module($module_id, $id_in_module, $topic_identifier);
		if (!empty($comments))
		{
			return count($comments);
		}
		return 0;
	}
	
	public static function comment_exists($comment_id)
	{
		return self::$comments_cache->comment_exists($comment_id);
	}
	
	public static function add_comment($id_topic, $message, $user_id, $pseudo, $user_ip)
	{
		$columns = array(
			'id_topic' => $id_topic,
			'user_id' => $user_id,
			'pseudo' => TextHelper::htmlspecialchars($pseudo),
			'user_ip' => TextHelper::htmlspecialchars($user_ip),
			'timestamp' => time(),
			'message' => $message
		);
		$result = self::$db_querier->insert(DB_TABLE_COMMENTS, $columns);
		return $result->get_last_inserted_id();
	}
	
	public static function edit_comment($comment_id, $message)
	{
		$columns = array(
			'message' => $message
		);
		$condition = "WHERE id = :id";
		$parameters = array(
			'id' => $comment_id
		);
		self::$db_querier->update(DB_TABLE_COMMENTS, $columns, $condition, $parameters);
	}
}
?>