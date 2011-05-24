<?php
/*##################################################
 *                               admin_system_report.php
 *                            -------------------
 *   begin                : July 14 2008
 *   copyright            : (C) 2008 Sautel Benoit
 *   email                : ben.popeye@phpboost.com
 *
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

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ###################################################*/

require_once('../admin/admin_begin.php');
define('TITLE', $LANG['administration']);
require_once('../admin/admin_header.php');

$server_configuration = new ServerConfiguration();

$template = new FileTemplate('admin/admin_system_report.tpl');

$template->put_all(array(
	'L_YES' => $LANG['yes'],
	'L_NO' => $LANG['no'],
	'L_UNKNOWN' => $LANG['unknown'],
	'L_SYSTEM_REPORT' => $LANG['system_report'],
	'L_SERVER' => $LANG['server'],
	'L_PHPINFO' => $LANG['phpinfo'],
	'L_PHP_VERSION' => $LANG['php_version'],
	'L_DBMS_VERSION' => $LANG['dbms_version'],
	'L_GD_LIBRARY' => $LANG['dg_library'],
	'L_URL_REWRITING' => $LANG['url_rewriting'],
	'L_REGISTER_GLOBALS_OPTION' => $LANG['register_globals_option'],
	'L_SERVER_URL' => $LANG['serv_name'],
	'L_SITE_PATH' => $LANG['serv_path'],
	'L_PHPBOOST_CONFIG' => $LANG['phpboost_config'],
	'L_KERNEL_VERSION' => $LANG['kernel_version'],
	'L_DEFAULT_THEME' => $LANG['default_theme'],
	'L_DEFAULT_LANG' => $LANG['default_language'],
	'L_DEFAULT_EDITOR' => $LANG['choose_editor'],
	'L_START_PAGE' => $LANG['start_page'],
	'L_OUTPUT_GZ' => $LANG['output_gz'],
	'L_COOKIE_NAME' => $LANG['cookie_name'],
	'L_SESSION_LENGTH' => $LANG['session_time'],
	'L_SESSION_GUEST_LENGTH' => $LANG['session invit'],
	'L_DIRECTORIES_AUTH' => $LANG['directories_auth'],
	'L_SUMMERIZATION' => $LANG['system_report_summerization'],
	'L_SUMMERIZATION_EXPLAIN' => $LANG['system_report_summerization_explain']
));


$server_path = !empty($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
if (!$server_path)
$server_path = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
$server_path = trim(str_replace('/admin', '', dirname($server_path)));
$server_name = 'http://' . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv('HTTP_HOST'));

$lang_ini_file = load_ini_file(PATH_TO_ROOT .'/lang/', get_ulang());
$template_ini_file = load_ini_file(PATH_TO_ROOT .'/templates/' . get_utheme() . '/config/', get_ulang());

$directories_summerization = '';
foreach (PHPBoostFoldersPermissions::get_permissions() as $key => $folder)
{
	$template->assign_block_vars('directories', array(
		'NAME' => $key,
		'C_AUTH_DIR' => $folder->is_writable()
	));
	$directories_summerization .= $key . str_repeat(' ', 25 - strlen($key)) . ": " . (int)($folder->is_writable()) . "
";
}

$general_config = GeneralConfig::load();
$content_formatting_config = ContentFormattingConfig::load();
$server_environment_config = ServerEnvironmentConfig::load();
$sessions_config = SessionsConfig::load();


$url_rewriting_available = false;
$url_rewriting_known = true;
try
{
	$url_rewriting_available = $server_configuration->has_url_rewriting();
}
catch (UnsupportedOperationException $ex)
{
	$url_rewriting_known = false;
}

$summerization =
"---------------------------------System report---------------------------------
-----------------------------generated by PHPBoost-----------------------------

SERVER CONFIGURATION-----------------------------------------------------------

php version              : " . ServerConfiguration::get_phpversion() . "
dbms version             : " . PersistenceContext::get_dbms_utils()->get_dbms_version() . "
gd library               : " . (int)$server_configuration->has_gd_library() . "
url rewriting            : " . ($url_rewriting_known ? (int) $url_rewriting_available : 'N/A') . "
register globals         : " . (int)(@ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on') . "
server url               : " . $server_name . "
site path                : " . $server_path  . "

PHPBOOST CONFIGURATION---------------------------------------------------------

phpboost version         : " . Environment::get_phpboost_version() . "
server url               : " . $general_config->get_site_url() . "
site path                : " . $general_config->get_site_path()  . "
default theme            : " . $template_ini_file['name'] . "
default language         : " . $lang_ini_file['name'] . "
default editor           : " . $content_formatting_config->get_default_editor() . "
home page                : " . $general_config->get_home_page() . "
url rewriting            : " . $server_environment_config->is_url_rewriting_enabled() . "
output gzip              : " . $server_environment_config->is_output_gziping_enabled() . "
session cookie name      : " . $sessions_config->get_cookie_name() . "
session duration         : " . $sessions_config->get_session_duration() . "
active session duration  : " . $sessions_config->get_active_session_duration() . "

DIRECTORIES AUTHORIZATIONS-----------------------------------------------------

" . $directories_summerization;

$template->put_all(array(
	'PHP_VERSION' => ServerConfiguration::get_phpversion(),
	'DBMS_VERSION' => PersistenceContext::get_dbms_utils()->get_dbms_version(),
	'C_SERVER_GD_LIBRARY' => $server_configuration->has_gd_library(),
	'C_URL_REWRITING_KNOWN' => $url_rewriting_known,
	'C_SERVER_URL_REWRITING' => $url_rewriting_available,
	'C_REGISTER_GLOBALS' => @ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on',
	'SERV_SERV_URL' => $server_name,
	'SERV_SITE_PATH' => $server_path,
	'KERNEL_VERSION' => Environment::get_phpboost_version(),
	'KERNEL_SERV_URL' => $general_config->get_site_url(),
	'KERNEL_SITE_PATH' => $general_config->get_site_path(),
	'KERNEL_DEFAULT_THEME' => $template_ini_file['name'],
	'KERNEL_DEFAULT_LANGUAGE' => $lang_ini_file['name'],
	'KERNEL_DEFAULT_EDITOR' => $content_formatting_config->get_default_editor() == 'tinymce' ? 'TinyMCE' : 'BBCode',
	'KERNEL_START_PAGE' => $general_config->get_home_page(),
	'C_KERNEL_URL_REWRITING' => $server_environment_config->is_url_rewriting_enabled(),
	'C_KERNEL_OUTPUT_GZ' => $server_environment_config->is_output_gziping_enabled(),
	'COOKIE_NAME' => $sessions_config->get_cookie_name(),
	'SESSION_LENGTH' => $sessions_config->get_session_duration(),
	'SESSION_LENGTH_GUEST' => $sessions_config->get_active_session_duration(),
	'SUMMERIZATION' => $summerization
));

$template->display();

require_once('../admin/admin_footer.php');

?>