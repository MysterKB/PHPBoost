<?php
/**
 * @copyright   &copy; 2005-2020 PHPBoost
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Patrick DUBEAU <daaxwizeman@gmail.com>
 * @version     PHPBoost 5.3 - last update: 2020 02 04
 * @since       PHPBoost 4.0 - 2013 02 13
 * @contributor Julien BRISWALTER <j1.seth@phpboost.com>
*/

class ArticlesCategoriesCache extends DefaultRichCategoriesCache
{
	public function __construct()
	{
		parent::__construct('articles');
	}

	protected function get_category_elements_number($id_category)
	{
		$now = new Date();
		return ItemsService::get_items_manager($this->get_module_identifier())->count('WHERE id_category = :id_category AND (published = 1 OR (published = 2 AND publishing_start_date < :timestamp_now AND (publishing_end_date > :timestamp_now OR publishing_end_date = 0)))',
			array(
				'timestamp_now' => $now->get_timestamp(),
				'id_category'   => $id_category
			)
		);
	}
}
?>
