<?php
/**
 * @copyright   &copy; 2005-2020 PHPBoost
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Julien BRISWALTER <j1.seth@phpboost.com>
 * @version     PHPBoost 5.3 - last update: 2020 01 27
 * @since       PHPBoost 5.3 - 2019 11 02
*/

class DefaultSitemapCategoriesModule extends SitemapCategoriesModule
{
	/**
	 * @var string the module identifier
	 */
	protected $module_id;

	public function __construct($module_id)
	{
		$this->module_id = $module_id;
		parent::__construct(CategoriesService::get_categories_manager($this->module_id));
	}

	protected function get_category_url(Category $category)
	{
		return CategoriesUrlBuilder::display_category($category->get_id(), $category->get_rewrited_name(), $this->module_id);
	}
}
?>
