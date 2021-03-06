<?php
/**
 * @copyright   &copy; 2005-2020 PHPBoost
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Julien BRISWALTER <j1.seth@phpboost.com>
 * @version     PHPBoost 5.3 - last update: 2016 02 11
 * @since       PHPBoost 4.1 - 2015 05 20
*/

class AdminServerDisplayResponse extends AdminMenuDisplayResponse
{
	public function __construct($view, $title_page)
	{
		parent::__construct($view);

		$lang = LangLoader::get('admin');
		$this->set_title($lang['server']);

		$this->add_link($lang['phpinfo'], AdminServerUrlBuilder::phpinfo());
		$this->add_link($lang['system_report'], AdminServerUrlBuilder::system_report());

		$env = $this->get_graphical_environment();
		$env->set_page_title($title_page);
	}
}
?>
