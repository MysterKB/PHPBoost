<?php
/**
 * @copyright   &copy; 2005-2020 PHPBoost
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Julien BRISWALTER <j1.seth@phpboost.com>
 * @version     PHPBoost 5.3 - last update: 2020 03 03
 * @since       PHPBoost 4.0 - 2013 12 17
 * @contributor Julien BRISWALTER <j1.seth@phpboost.com>
 * @contributor Arnaud GENET <elenwii@phpboost.com>
 * @contributor Sebastien LARTIGUE <babsolune@phpboost.com>
*/

####################################################
#                    French                        #
####################################################

// --- Module titles
$lang['sandbox.module.title'] = 'Bac à sable';

$lang['title.config'] = 'Configuration';
$lang['title.admin.fwkboost'] = 'Rendus dans l\'admin';
$lang['title.theme.fwkboost'] = 'Dans le thème';
$lang['title.form'] = 'Les formulaires';
$lang['title.framework'] = 'Composants HTML/CSS';
$lang['title.multitabs'] = 'Multitabs';
$lang['title.plugins'] = 'Plugins';
$lang['title.bbcode'] = 'BBCode';
$lang['title.menu'] = 'Menus de liens';
$lang['title.icons'] = 'Icônes';
$lang['title.table'] = 'Tableaux';
$lang['title.email'] = 'Email';
$lang['title.string.template'] = 'Génération de template';

$lang['see.render'] = 'Voir le rendu';

// --- Page d'accueil
$lang['welcome.message'] = '<p>Bienvenue dans le module Bac à sable.</p>
<p>Vous pouvez tester dans ce module les différents composants du framework de PHPBoost : <span class="pinned visitor big"><i class="fa iboost fa-iboost-phpboost"></i> FWKBoost</span></p>
<ul class="sandbox-home-list">
<li><i class="fa fa-fw fa-asterisk"></i> Le rendu des différents champs utilisables avec le <a href="' . SandboxUrlBuilder::form()->absolute() . '">constructeur de formulaires</a></li>
<li><i class="fab fa-fw fa-css3"></i> Le rendu des principales <a href="' . SandboxUrlBuilder::css()->absolute() . '">classes CSS</a></li>
<li><i class="fa fa-fw fa-list"></i> Le rendu du <a href="' . SandboxUrlBuilder::multitabs()->absolute() . '">plugin  Multitabs</a></li>
<li><i class="fa fa-fw fa-cube"></i> Le rendu des <a href="' . SandboxUrlBuilder::plugins()->absolute() . '">plugins jQuery</a> intégrés dans PHPBoost</li>
<li><i class="far fa-fw fa-file-code"></i> Le rendu des styles spécifiques du <a href="' . SandboxUrlBuilder::bbcode()->absolute() . '">BBCode</a></li>
<li><i class="fab fa-fw fa-font-awesome-flag"></i> Un tutoriel sur l\'utilisation des icônes de la librairie <a href="' . SandboxUrlBuilder::icons()->absolute() . '">Font Awesome</a></li>
<li><i class="fa fa-fw fa-list"></i> Le rendu des <a href="' . SandboxUrlBuilder::menus()->absolute() . '">menus de navigation cssmenu</a></li>
<li><i class="fa fa-fw fa-table"></i> La génération de <a href="' . SandboxUrlBuilder::table()->absolute() . '">tableaux dynamiques</a></li>
<li><i class="fa fa-fw fa-at"></i> L\'<a href="' . SandboxUrlBuilder::email()->absolute() . '">envoi d\'emails</a></li>
<li><i class="fa fa-fw fa-code"></i> La <a href="' . SandboxUrlBuilder::template()->absolute() . '">génération de template</a> avec ou sans cache</li>
</ul>
<br />
';

$lang['welcome.front'] = 'En front';
$lang['welcome.admin'] = 'En admin';

$lang['welcome.form'] = 'Le rendu des différents champs et fonctionnailtés utilisables avec le constructeur php. Champs de formulaire, maps, menus de liens, etc.';

// Lorem
$lang['lorem.short.content'] = 'Etiam hendrerit, tortor et faucibus dapibus, eros orci porta eros, in facilisis ipsum ipsum at nisl';
$lang['lorem.medium.content'] = 'Fusce vitae consequat nisl. Fusce vestibulum porta ipsum ac consectetur. Duis finibus mauris eu feugiat congue.
Aenean aliquam accumsan ipsum, ac dapibus dui ultricies non. In hac habitasse platea dictumst. Aenean mi nibh, varius vel lacus at, tincidunt luctus eros.
In hac habitasse platea dictumst. Vestibulum luctus lorem nisl, et hendrerit lectus dapibus ut. Phasellus sit amet nisl tortor.
Aenean pulvinar tellus nulla, sit amet mattis nisl semper eu. Phasellus efficitur nisi a laoreet dignissim. Aliquam erat volutpat.';
$lang['lorem.large.content'] = ' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut hendrerit odio urna, blandit pharetra elit
scelerisque tempor. Nulla dapibus felis orci, at consectetur orci auctor eget. Donec eros lectus, mollis eget auctor vel, convallis ac mauris.
Cras imperdiet, erat ac semper volutpat, libero orci varius mi, et ullamcorper quam urna vitae augue. Maecenas maximus vitae diam vel porta.
Pellentesque dignissim dolor eu neque aliquet viverra. Maecenas tincidunt, mi non gravida tincidunt, lectus elit gravida massa,
sed viverra tortor diam pretium metus. In hac habitasse platea dictumst. Ut velit turpis, sollicitudin non risus et, pretium efficitur leo.
Integer elementum faucibus finibus. Nullam et felis sit amet felis blandit iaculis. Vestibulum massa arcu, finibus id enim ac, commodo aliquam metus.
Vestibulum feugiat urna nunc, et eleifend velit posuere ac. Vestibulum sagittis tempus nunc, sit amet dignissim ipsum sollicitudin eget.';

// Common
$lang['sandbox.summary'] = 'Sommaire';
$lang['sandbox.source.code'] = 'Voir le code source';
$lang['sandbox.pbt.doc'] = 'la documentation de PHPBoost';

// Wiki
$lang['wiki.not'] = 'Le module Wiki n\'est pas installé et/ou activé';
$lang['wiki.conditions'] = 'Vous devez porter le module wiki dans votre thème pour que vos modifications soient actives.';
$lang['wiki.module'] = 'Module Wiki';
$lang['wiki.table.of.contents'] = 'Table des matières';
$lang['wiki.contents'] = 'Contenu du wiki';

// Mail
$lang['mail.title'] = 'Email';
$lang['mail.sender_mail'] = 'Email de l\'expéditeur';
$lang['mail.sender_name'] = 'Nom de l\'expéditeur';
$lang['mail.recipient_mail'] = 'Email du destinataire';
$lang['mail.recipient_name'] = 'Nom du destinataire';
$lang['mail.subject'] = 'Objet de l\'email';
$lang['mail.content'] = 'Contenu';
$lang['mail.smtp_config'] = 'Configuration SMTP';
$lang['mail.smtp_config.explain'] = 'Cochez la case si vous voulez utiliser une connexion SMTP directe pour envoyer l\'email.';
$lang['mail.use_smtp'] = 'Utiliser SMTP';
$lang['mail.smtp_configuration'] = 'Configuration des paramètres SMTP pour l\'envoi';
$lang['mail.smtp.host'] = 'Nom d\'hôte';
$lang['mail.smtp.port'] = 'Port';
$lang['mail.smtp.login'] = 'Identifiant';
$lang['mail.smtp.password'] = 'Mot de passe';
$lang['mail.smtp.secure_protocol'] = 'Protocole de sécurisation';
$lang['mail.smtp.secure_protocol.none'] = 'Aucun';
$lang['mail.smtp.secure_protocol.tls'] = 'TLS';
$lang['mail.smtp.secure_protocol.ssl'] = 'SSL';
$lang['mail.success'] = 'L\'email a été envoyé';

// Template
$lang['string_template.result'] = 'Temps de génération du template sans cache : :non_cached_time secondes<br />Temps de génération du template avec cache : :cached_time secondes<br />Longueur de la chaîne : :string_length caractères.';

?>
