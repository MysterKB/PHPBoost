<?php
/*##################################################
 *                                topic.php
 *                            -------------------
 *   begin                : October 26, 2005
 *   copyright          : (C) 2005 Viarre R�gis / Sautel Beno�t
 *   email                : mickaelhemri@gmail.com / ben.popeye@gmail.com
 *
 *  
###################################################
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 * 
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
###################################################*/

include_once('../includes/begin.php'); 
include_once('../forum/lang/' . $CONFIG['lang'] . '/forum_' . $CONFIG['lang'] . '.php'); //Chargement de la langue du module.
include_once('../forum/forum_auth.php');

//Variable GET.
$page = !empty($_GET['pt']) ? numeric($_GET['pt']) : 1;
$id_get = !empty($_GET['id']) ? numeric($_GET['id']) : '';
$quote_get = !empty($_GET['quote']) ? numeric($_GET['quote']) : '';	

//On va chercher les infos sur le topic	
$topic = !empty($id_get) ? $sql->query_array('forum_topics', 'user_id', 'idcat', 'title', 'subtitle', 'nbr_msg', 'last_msg_id', 'first_msg_id', 'last_timestamp', 'status', 'display_msg', "WHERE id = '" . $id_get . "'", __LINE__, __FILE__) : '';

//Existance de la cat�gorie.
if( !isset($CAT_FORUM[$topic['idcat']]) || $CAT_FORUM[$topic['idcat']]['aprob'] == 0 || $CAT_FORUM[$topic['idcat']]['level'] == 0 )
{
	$errorh->error_handler('e_unexist_cat', E_USER_REDIRECT);
	exit;
}

//R�cup�ration de la barre d'arborescence.
$speed_bar = array($CONFIG_FORUM['forum_name'] => 'index.php' . SID);
foreach($CAT_FORUM as $idcat => $array_info_cat)
{
	if( $CAT_FORUM[$topic['idcat']]['id_left'] > $array_info_cat['id_left'] && $CAT_FORUM[$topic['idcat']]['id_right'] < $array_info_cat['id_right'] && $array_info_cat['level'] < $CAT_FORUM[$topic['idcat']]['level'] )
		$speed_bar[$array_info_cat['name']] = ($array_info_cat['level'] == 0) ? transid('index.php?id=' . $idcat, 'cat-' . $idcat . '+' . url_encode_rewrite($array_info_cat['name']) . '.php') : 'forum' . transid('.php?id=' . $idcat, '-' . $idcat . '+' . url_encode_rewrite($array_info_cat['name']) . '.php');
}
if( !empty($CAT_FORUM[$topic['idcat']]['name']) ) //Nom de la cat�gorie courante.
	$speed_bar[$CAT_FORUM[$topic['idcat']]['name']] = 'forum' . transid('.php?id=' . $topic['idcat'], '-' . $topic['idcat'] . '+' . url_encode_rewrite($CAT_FORUM[$topic['idcat']]['name']) . '.php');
$speed_bar[$topic['title']] = '';

define('TITLE', $LANG['title_topic'] . ' - ' . addslashes($topic['title']));
define('ALTERNATIVE_CSS', 'forum');
include_once('../includes/header.php'); 

//Acc�s au module.
if( !$groups->check_auth($SECURE_MODULE['forum'], ACCESS_MODULE) )
{
	$errorh->error_handler('e_auth', E_USER_REDIRECT); 
	exit;
}

if( !empty($id_get) || !empty($_POST['change_cat']) )
{	
	if( empty($topic['idcat']) )
	{
		$errorh->error_handler('e_unexist_topic_forum', E_USER_REDIRECT);
		exit;
	}	
	
	//On encode l'url pour un �ventuel rewriting, c'est une op�ration assez gourmande
	$rewrited_cat_title = ($CONFIG['rewrite'] == 1) ? '+' . url_encode_rewrite($CAT_FORUM[$topic['idcat']]['name']) : '';
	//On encode l'url pour un �ventuel rewriting, c'est une op�ration assez gourmande
	$rewrited_title = ($CONFIG['rewrite'] == 1) ? '+' . url_encode_rewrite($topic['title']) : '';
	
	//Redirection changement de cat�gorie.
	if( !empty($_POST['change_cat']) )
	{	
		header('location:' . HOST . DIR . '/forum/forum' . transid('.php?id=' . $_POST['change_cat'], '-' . $_POST['change_cat'] . $rewrited_cat_title . '.php', '&'));
		exit;
	}
	
	//Autorisation en lecture.
	if( !$groups->check_auth($CAT_FORUM[$topic['idcat']]['auth'], READ_CAT_FORUM) )
	{
		$errorh->error_handler('e_auth', E_USER_REDIRECT);
		exit;
	}
	
	$template->set_filenames(array(
		'forum_topic' => '../templates/' . $CONFIG['theme'] . '/forum/forum_topic.tpl'
	));

	$module_data_path = $template->module_data_path('forum');
	
	//Si l'utilisateur a le droit de d�placer le topic, ou le verrouiller.	
	$check_group_edit_auth = $groups->check_auth($CAT_FORUM[$topic['idcat']]['auth'], EDIT_CAT_FORUM);
	if( $check_group_edit_auth )
	{
		$java = "function Confirm_topic() {
		return confirm('" . $LANG['alert_delete_topic'] . "');
		}		
		function Confirm_lock() {
		return confirm('" . $LANG['alert_lock_topic'] . "');
		}		
		function Confirm_unlock() {
		return confirm('" . $LANG['alert_unlock_topic'] . "');
		}		
		function Confirm_move() {
		return confirm('" . $LANG['alert_move_topic'] . "');
		}
		function Confirm_cut() {
		return confirm('" . $LANG['alert_cut_topic'] . "');
		}
		";
		
		$lock_status = '';
		if( $topic['status'] == '1' ) //Unlocked, affiche lien pour verrouiller.
			$lock_status = '<a href="action' . transid('.php?id=' . $id_get . '&amp;lock=true') . '" onClick="javascript:return Confirm_lock();" title="' . $LANG['forum_lock']  . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/lock.png" alt="' . $LANG['forum_lock']  . '" title="' . $LANG['forum_lock']  . '" class="valign_middle" /></a>';
		elseif( $topic['status'] == '0' ) //Lock, affiche lien pour d�verrouiler.
			$lock_status = '<a href="action' . transid('.php?id=' . $id_get . '&amp;lock=false') . '" onClick="javascript:return Confirm_unlock();" title="' . $LANG['forum_unlock']  . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/unlock.png" alt="' . $LANG['forum_unlock']  . '" title="' . $LANG['forum_unlock']  . '" class="valign_middle" /></a>';
		
		$template->assign_vars(array(
			'MOVE' => '<a href="move' . transid('.php?id=' . $id_get) . '" onClick="javascript:return Confirm_move();" title="' . $LANG['forum_move'] . '"><img src="' . $module_data_path . '/images/move.png" alt="' . $LANG['forum_move'] . '" title="' . $LANG['forum_move'] . '" class="valign_middle" /></a>',
			'LOCK' => $lock_status,
			'JAVA' => $java
		));
	}
	
	//Message(s) dans le topic non lu ( non prise en compte des topics trop vieux (x semaine) ou d�j� lus).
	$last_view_forum = $sql->query("SELECT last_view_forum FROM ".PREFIX."member_extend WHERE user_id = '" . $session->data['user_id'] . "'", __LINE__, __FILE__);
	$max_time_config = (time() - $CONFIG_FORUM['view_time']);
	$max_time = $last_view_forum > $max_time_config ? $last_view_forum : $max_time_config;
	if( $session->data['user_id'] !== -1 && $topic['last_timestamp'] >= $max_time )
	{
		$check_view_id = $sql->query("SELECT last_view_id FROM ".PREFIX."forum_view WHERE user_id = '" . $session->data['user_id'] . "' AND idtopic = '" . $id_get . "'", __LINE__, __FILE__);
		if( !empty($check_view_id) && $check_view_id != $topic['last_msg_id'] ) 
		{
			$sql->query_inject("UPDATE ".LOW_PRIORITY." ".PREFIX."forum_topics SET nbr_views = nbr_views + 1 WHERE id = '" . $id_get . "'", __LINE__, __FILE__);
			$sql->query_inject("UPDATE ".LOW_PRIORITY." ".PREFIX."forum_view SET last_view_id = '" . $topic['last_msg_id'] . "', timestamp = '" . time() . "' WHERE idtopic = '" . $id_get . "' AND user_id = '" . $session->data['user_id'] . "'", __LINE__, __FILE__);
		}
		elseif( empty($check_view_id) )
		{			
			$sql->query_inject("UPDATE ".LOW_PRIORITY." ".PREFIX."forum_topics SET nbr_views= nbr_views + 1 WHERE id = '" . $id_get . "'", __LINE__, __FILE__);
			$sql->query_inject("INSERT INTO ".PREFIX."forum_view (idtopic,last_view_id,user_id,timestamp) VALUES('" . $id_get . "', '" . $topic['last_msg_id'] . "', '" . $session->data['user_id'] . "', '" . time() . "')", __LINE__, __FILE__);			
		}
		else
			$sql->query_inject("UPDATE ".LOW_PRIORITY." ".PREFIX."forum_topics SET nbr_views = nbr_views + 1 WHERE id = '" . $id_get . "'", __LINE__, __FILE__);
	}
	else
		$sql->query_inject("UPDATE ".LOW_PRIORITY." ".PREFIX."forum_topics SET nbr_views = nbr_views + 1 WHERE id = '" . $id_get . "'", __LINE__, __FILE__);
		
	//Gestion de la page si redirection vers le dernier message lu.
	$idm = !empty($_GET['idm']) ? numeric($_GET['idm']) : '';
	if( !empty($idm) )
	{
		//Calcul de la page sur laquelle se situe le message.
		$nbr_msg_before = $sql->query("SELECT COUNT(*) as nbr_msg_before FROM ".PREFIX."forum_msg WHERE idtopic = " . $id_get . " AND id < '" . $idm . "'", __LINE__, __FILE__); //Nombre de message avant le message de destination.
		
		//Dernier message de la page? Redirection vers la page suivante pour prendre en compte la reprise du message pr�c�dent.
		if( is_int(($nbr_msg_before + 1) / $CONFIG_FORUM['pagination_msg']) ) 
		{	
			//On redirige vers la page suivante, seulement si ce n'est pas la derni�re.
			if( $topic['nbr_msg'] != ($nbr_msg_before + 1) )
				$nbr_msg_before++;
		}
		
		$_GET['pt'] = ceil(($nbr_msg_before + 1) / $CONFIG_FORUM['pagination_msg']); //Modification de la page affich�e.
	}	
		
	//On cr�e une pagination si le nombre de msg est trop important.
	include_once('../includes/pagination.class.php'); 
	$pagination = new Pagination();	

	//Affichage de l'arborescence des cat�gories.
	$i = 0;
	$forum_cats = '';	
	array_pop($speed_bar);
	foreach($speed_bar as $cat_name => $cat_url)
	{
		if( $i == 2 )
			$forum_cats .= '<a href="' . $cat_url . '">' . $cat_name . '</a>';
		elseif( $i > 2 )		
			$forum_cats .= ' &raquo; <a href="' . $cat_url . '">' . $cat_name . '</a>';
		$i++;
	}
	
	$template->assign_vars(array(
		'FORUM_NAME' => $CONFIG_FORUM['forum_name'],
		'SID' => SID,		
		'MODULE_DATA_PATH' => $module_data_path,
		'DESC' => !empty($topic['subtitle']) ? $topic['subtitle'] : '',
		'PAGINATION' => $pagination->show_pagin('topic' . transid('.php?id=' . $id_get . '&amp;pt=%d', '-' . $id_get . '-%d.php'), $topic['nbr_msg'], 'pt', $CONFIG_FORUM['pagination_msg'], 3),
		'THEME' => $CONFIG['theme'],
		'LANG' => $CONFIG['lang'],
		'USER_ID' => $topic['user_id'],
		'ID' => $topic['idcat'],
		'IDTOPIC' => $id_get,
		'PAGE' => $page,
		'U_SEARCH' => '<a class="small_link" href="search.php' . SID . '" title="' . $LANG['search'] . '">' . $LANG['search'] . '</a> &bull;',
		'U_TOPIC_TRACK' => '<a class="small_link" href="../forum/track.php' .SID . '" title="' . $LANG['show_topic_track'] . '">' . $LANG['show_topic_track'] . '</a> &bull;',
		'U_MSG_NOT_READ' => '<a class="small_link" href="../forum/unread.php' . SID . '" title="' . $LANG['show_not_reads'] . '">' . $LANG['show_not_reads'] . '</a>',
		'U_LAST_MSG_READ' => '<a class="small_link" href="../forum/lastread.php' . SID . '" title="' . $LANG['show_last_read'] . '">' . $LANG['show_last_read'] . '</a> &bull;',
		'U_CHANGE_CAT'=> transid('.php?id=' . $id_get, '-' . $id_get . $rewrited_cat_title . '.php'),
		'U_ONCHANGE' => "'forum" . transid(".php?id=' + this.options[this.selectedIndex].value + '", "-' + this.options[this.selectedIndex].value + '.php") . "'",		
		'U_FORUM_CAT' => !empty($forum_cats) ? $forum_cats . ' &raquo;' : '',
		'U_TITLE_T' => '<a href="topic' . transid('.php?id=' . $id_get, '-' . $id_get . $rewrited_title . '.php') . '">' . (($CONFIG_FORUM['activ_display_msg'] && $topic['display_msg']) ? $CONFIG_FORUM['display_msg'] . ' ' : '') . ucfirst($topic['title']) . '</a>',
		'L_REQUIRE_MESSAGE' => $LANG['require_text'],
		'L_DELETE_MESSAGE' => $LANG['alert_delete_msg'],
		'L_FORUM_INDEX' => $LANG['forum_index'],
		'L_QUOTE' => $LANG['quote'],
		'L_RESPOND' => $LANG['respond'],
		'L_SUBMIT' => $LANG['submit'],
		'L_PREVIEW' => $LANG['preview'],
		'L_RESET' => $LANG['reset']
	));
	
	//Cr�ation du tableau des rangs.
	$array_ranks = array(-1 => $LANG['guest_s'], 0 => $LANG['member_s'], 1 => $LANG['modo_s'], 2 => $LANG['admin_s']);
	
	$track = false;
	$poll_done = false; //N'execute qu'une fois les actions propres au sondage.
	$cache->load_file('ranks'); //R�cup�re les rangs en cache.
	$page = isset($_GET['pt']) ? numeric($_GET['pt']) : 0; //Red�finition de la variable $page pour prendre en compte les redirections.
	$quote_last_msg = ($page > 1) ? 1 : 0; //On enl�ve 1 au limite si on est sur une page > 1, afin de r�cup�rer le dernier msg de la page pr�c�dente.
	$i = 0;		
	$result = $sql->query_while("SELECT msg.id, msg.user_id, msg.timestamp, msg.timestamp_edit, msg.user_id_edit, m.user_groups, p.question, p.answers, p.voter_id, p.votes, p.type, m.login, m.level, m.user_mail, m.user_show_mail, m.timestamp AS registered, m.user_avatar, m.user_msg, m.user_local, m.user_web, m.user_sex, m.user_msn, m.user_yahoo, m.user_sign, m.user_warning, m.user_readonly, m.user_ban, m2.login as login_edit, s.user_id AS connect, tr.id AS track, msg.contents
	FROM ".PREFIX."forum_msg AS msg
	LEFT JOIN ".PREFIX."forum_poll AS p ON p.idtopic = '" . $id_get . "'
	LEFT JOIN ".PREFIX."member AS m ON m.user_id = msg.user_id
	LEFT JOIN ".PREFIX."member AS m2 ON m2.user_id = msg.user_id_edit
	LEFT JOIN ".PREFIX."forum_track AS tr ON tr.idtopic = '" . $id_get . "' AND tr.user_id = '" . $session->data['user_id'] . "'
	LEFT JOIN ".PREFIX."sessions AS s ON s.user_id = msg.user_id AND s.session_time > '" . (time() - $CONFIG['site_session_invit']) . "' AND s.user_id != -1
	WHERE msg.idtopic = '" . $id_get . "'	
	ORDER BY msg.timestamp 
	" . $sql->sql_limit(($pagination->first_msg($CONFIG_FORUM['pagination_msg'], 'pt') - $quote_last_msg), ($CONFIG_FORUM['pagination_msg'] + $quote_last_msg)), __LINE__, __FILE__);
	while ( $row = $sql->sql_fetch_assoc($result) )
	{
		$row['user_id'] = (int)$row['user_id'];
		//Invit�?
		$is_guest = ($row['user_id'] === -1);
		if( $is_guest )
			$row['level'] = -1;
			
		$edit = '';
		$del = '';
		$cut = '';
		$warning = '';
		$readonly = '';
		$first_message = ($row['id'] == $topic['first_msg_id']) ? true : false;
		//Gestion du niveau d'autorisation.
		if( $check_group_edit_auth || ($session->data['user_id'] === $row['user_id'] && !$is_guest && !$first_message) )
		{
			$valid = ($first_message) ? 'topic' : 'msg';
			$edit = '&nbsp;&nbsp;<a href="post' . transid('.php?new=msg&amp;idm=' . $row['id'] . '&amp;id=' . $topic['idcat'] . '&amp;idt=' . $id_get) . '" title=""><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/edit.png" alt="' . $LANG['edit'] . '" title="' . $LANG['edit'] . '" /></a>';
			$del = (!$first_message) ? '&nbsp;&nbsp;<script type="text/javascript"><!-- 
			document.write(\'<img style="cursor: pointer;" onClick="del_msg(\\\'' . $row['id'] . '\\\');" src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/delete.png" alt="' . $LANG['delete'] . '" title="' . $LANG['delete'] . '"/>\'); 
			--></script><noscript><a href="action' . transid('.php?del=1&amp;id=' . $row['id']) . '" title="" onClick="javascript:return Confirm_' . $valid . '();"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/delete.png" alt="' . $LANG['delete'] . '" title="' . $LANG['delete'] . '" /></a></noscript>'
			: '&nbsp;&nbsp;<a href="action' . transid('.php?del=1&amp;id=' . $row['id']) . '" title="" onClick="javascript:return Confirm_' . $valid . '();"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/delete.png" alt="' . $LANG['delete'] . '" title="' . $LANG['delete'] . '" /></a>';
			
			//Fonctions r�serv�es � ceux poss�dants les droits de mod�rateurs seulement.
			if( $check_group_edit_auth )
			{
				$cut = (!$first_message) ? '&nbsp;&nbsp;<a href="move' . transid('.php?idm=' . $row['id']) . '" title="' . $LANG['cut_topic'] . '" onClick="javascript:return Confirm_cut();"><img src="' . $module_data_path . '/images/cut.png" alt="' . $LANG['cut_topic'] .  '" />' : '';
				$warning = !$is_guest ? '&nbsp;<a href="moderation_forum' . transid('.php?action=warning&amp;id=' . $row['user_id']) . '" title="' . $LANG['warning_management'] . '"><img src="../templates/' . $CONFIG['theme'] . '/images/admin/important.png" alt="' . $LANG['warning_management'] .  '" class="valign_middle" /></a>' : ''; 
				$readonly = !$is_guest ? '<a href="moderation_forum' . transid('.php?action=punish&amp;id=' . $row['user_id']) . '" title="' . $LANG['punishment_management'] . '"><img src="../templates/' . $CONFIG['theme'] . '/images/readonly.png" alt="' . $LANG['punishment_management'] .  '" class="valign_middle" /></a>' : ''; 
			}
		}
		elseif( $session->data['user_id'] === $row['user_id'] && !$is_guest && $first_message ) //Premier msg du topic => suppression du topic non autoris� au membre auteur du message.
			$edit = '&nbsp;&nbsp;<a href="post' . transid('.php?new=msg&amp;idm=' . $row['id'] . '&amp;id=' . $topic['idcat'] . '&amp;idt=' . $id_get) . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/edit.png" alt="' . $LANG['edit'] . '" title="'. $LANG['edit'] . '" /></a>';
				
		//Gestion des sondages => execut� une seule fois.
		if( !empty($row['question']) && $poll_done === false )
		{
			$template->assign_block_vars('poll', array(				
				'QUESTION' => $row['question'],				
				'U_POLL_RESULT' => transid('.php?id=' . $id_get . '&amp;r=1'),
				'U_POLL_ACTION' => transid('.php?id=' . $id_get),
				'L_POLL' => $LANG['poll'], 
				'L_VOTE' => $LANG['poll_vote'],
				'L_RESULT' => $LANG['poll_result']
			));
						
			$array_voter = explode('|', $row['voter_id']);			
			if( in_array($session->data['user_id'], $array_voter) || !empty($_GET['r']) || $session->data['user_id'] === -1 ) //D�j� vot�.
			{
				$array_answer = explode('|', $row['answers']);
				$array_vote = explode('|', $row['votes']);
				
				$sum_vote = array_sum($array_vote);	
				$sum_vote = ($sum_vote == 0) ? 1 : $sum_vote; //Emp�che la division par 0.
	
				$array_poll = array_combine($array_answer, $array_vote);
				foreach($array_poll as $answer => $nbrvote)
				{
					$template->assign_block_vars('poll.result', array(
						'ANSWERS' => $answer, 
						'NBRVOTE' => $nbrvote,
						'WIDTH' => arrondi(($nbrvote * 100 / $sum_vote), 1) * 4, //x 4 Pour agrandir la barre de vote.					
						'PERCENT' => arrondi(($nbrvote * 100 / $sum_vote), 1)
					));
				}
			}
			else //Affichage des formulaires (radio/checkbox)  pour voter.
			{
				$template->assign_block_vars('poll.question', array(
				));
				
				$z = 0;
				$array_answer = explode('|', $row['answers']);
				if( $row['type'] == 0 )
				{
					foreach($array_answer as $answer)
					{						
						$template->assign_block_vars('poll.radio', array(
							'NAME' => $z,
							'TYPE' => 'radio',
							'ANSWERS' => $answer
						));
						$z++;
					}
				}	
				elseif( $row['type'] == 1 ) 
				{
					foreach($array_answer as $answer)
					{						
						$template->assign_block_vars('poll.checkbox', array(
							'NAME' => $z,
							'TYPE' => 'checkbox',
							'ANSWERS' => $answer
						));
						$z++;	
					}
				}
			}
			$poll_done = true;	
		}
		
		//Rang de l'utilisateur.			
		$user_rank = ($row['level'] === '0') ? $LANG['member'] : $LANG['guest'];
		$user_group = $user_rank;
		if( $row['level'] === '2' ) //Rang sp�cial (admins).  
		{
			$user_rank = $_array_rank[-2][0];
			$user_group = $user_rank;
			$user_rank_icon = $_array_rank[-2][1];
		}
		elseif( $row['level'] === '1' ) //Rang sp�cial (modos).  
		{
			$user_rank = $_array_rank[-1][0];
			$user_group = $user_rank;
			$user_rank_icon = $_array_rank[-1][1];
		}
		else
		{
			foreach($_array_rank as $msg => $ranks_info)
			{
				if( $msg >= 0 && $msg <= $row['user_msg'] )
				{ 
					$user_rank = $ranks_info[0];
					$user_rank_icon = $ranks_info[1];
					break;
				}
			}
		}

		//Image associ�e au rang.
		$user_assoc_img = isset($user_rank_icon) ? '<img src="../templates/' . $CONFIG['theme'] . '/images/ranks/' . $user_rank_icon . '" alt="" />' : '';
					
		//Affichage des groupes du membre.		
		if( !empty($row['user_groups']) && $_array_groups_auth ) 
		{	
			$user_groups = '';
			$array_user_groups = explode('|', $row['user_groups']);
			foreach($_array_groups_auth as $idgroup => $array_group_info)
			{
				if( is_numeric(array_search($idgroup, $array_user_groups)) )
					$user_groups .= !empty($array_group_info[1]) ? '<img src="../images/group/' . $array_group_info[1] . '" alt="' . $array_group_info[0] . '" title="' . $array_group_info[0] . '"/><br />' : $LANG['group'] . ': ' . $array_group_info[0];
			}
		}
		else
			$user_groups = $LANG['group'] . ': ' . $user_group;

		//Avatar			
		if( empty($row['user_avatar']) ) 
			$user_avatar = ($CONFIG_MEMBER['activ_avatar'] == '1' && !empty($CONFIG_MEMBER['avatar_url'])) ? '<img src="../templates/' . $CONFIG['theme'] . '/images/' .  $CONFIG_MEMBER['avatar_url'] . '" alt="" />' : '';
		else
			$user_avatar = '<img src="' . $row['user_avatar'] . '" alt=""	/>';
			
		//Affichage du sexe et du statut (connect�/d�connect�).	
		if( $row['user_sex'] == 1 )	
			$user_sex = $LANG['sex'] . ': <img src="../templates/' . $CONFIG['theme'] . '/images/man.png" alt="" /><br />';	
		elseif( $row['user_sex'] == 2 ) 
			$user_sex = $LANG['sex'] . ': <img src="../templates/' . $CONFIG['theme'] . '/images/woman.png" alt="" /><br />';
		else $user_sex = '';
				
		//Localisation.
		if( !empty($row['user_local']) ) 
			$user_local = $LANG['place'] . ': ' . (strlen($row['user_local']) > 15 ? substr_html($row['user_local'], 0, 15) . '...<br />' : $row['user_local'] . '<br />');	
		else 
			$user_local = '';

		//Reprise du dernier message de la page pr�c�dente.
		$row['contents'] = ($quote_last_msg == 1 && $i == 0) ? '<span class="text_strong">' . $LANG['forum_quote_last_msg'] . '</span><br /><br />' . $row['contents'] : $row['contents'];
		$i++;
		
		//Ajout du marqueur d'�dition si activ�.
		$edit_mark = ($row['timestamp_edit'] > 0 && $CONFIG_FORUM['edit_mark'] == '0') ? '<br /><br /><br /><span style="padding: 10px;font-size:10px;font-style:italic;">' . $LANG['edit_by'] . ' ' . (!empty($row['login_edit']) ? '<a class="edit_pseudo" href="../member/member' . transid('.php?id=' . $row['user_id_edit'], '-' . $row['user_id_edit'] . '.php') . '">' . $row['login_edit'] . '</a>' : '<em>' . $LANG['guest'] . '</em>') . ' ' . $LANG['on'] . ' ' . date($LANG['date_format'] . ' ' . $LANG['at'] . ' H\hi', $row['timestamp_edit']) . '</span><br />' : '';
		
		//Affichage du nombre de message.
		if( $row['user_msg'] >= 1 )
			$user_msg = '<a href="../forum/membermsg' . transid('.php?id=' . $row['user_id'], '') . '" class="small_link">' . $LANG['message_s'] . '</a>: ' . $row['user_msg'];
		else		
			$user_msg = (!$is_guest) ? '<a href="../forum/membermsg' . transid('.php?id=' . $row['user_id'], '') . '" class="small_link">' . $LANG['message'] . '</a>: 0' : $LANG['message'] . ': 0';		
		
		$template->assign_block_vars('msg', array(
			'CONTENTS' => second_parse($row['contents']),
			'DATE' => $LANG['on'] . ' ' . date($LANG['date_format'] . ' ' . $LANG['at'] . ' H\hi', $row['timestamp']),
			'ID' => $row['id'],
			'USER_ONLINE' => '<img src="../templates/' . $CONFIG['theme'] . '/images/' . ((!empty($row['connect']) && !$is_guest) ? 'online' : 'offline') . '.png" alt="" class="valign_middle" />',
			'USER_PSEUDO' => !empty($row['login']) ? '<a class="msg_link_pseudo" href="../member/member' . transid('.php?id=' . $row['user_id'], '-' . $row['user_id'] . '.php') . '">' . wordwrap_html($row['login'], 13) . '</a>' : '<em>' . $LANG['guest'] . '</em>',			
			'USER_RANK' => ($row['user_warning'] < '100' || (time() - $row['user_ban']) < 0) ? $user_rank : $LANG['banned'],
			'USER_IMG_ASSOC' => $user_assoc_img,
			'USER_AVATAR' => $user_avatar,			
			'USER_GROUP' => $user_groups,
			'USER_DATE' => (!$is_guest) ? $LANG['registered_on'] . ': ' . date($LANG['date_format'], $row['registered']) : '',
			'USER_SEX' => $user_sex,
			'USER_MSG' => (!$is_guest) ? $user_msg : '',
			'USER_LOCAL' => $user_local,
			'USER_MAIL' => ( !empty($row['user_mail']) && ($row['user_show_mail'] == '1' ) ) ? '<a href="mailto:' . $row['user_mail'] . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/email.png" alt="' . $row['user_mail']  . '" title="' . $row['user_mail']  . '" /></a>' : '',			
			'USER_MSN' => (!empty($row['user_msn'])) ? '<a href="mailto:' . $row['user_msn'] . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/msn.png" alt="' . $row['user_msn']  . '" title="' . $row['user_msn']  . '" /></a>' : '',
			'USER_YAHOO' => (!empty($row['user_yahoo'])) ? '<a href="mailto:' . $row['user_yahoo'] . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/yahoo.png" alt="' . $row['user_yahoo']  . '" title="' . $row['user_yahoo']  . '" /></a>' : '',
			'USER_EDIT' => $edit_mark,
			'USER_SIGN' => (!empty($row['user_sign'])) ? '____________________<br />' . $row['user_sign'] : '',
			'USER_WEB' => (!empty($row['user_web'])) ? '<a href="' . $row['user_web'] . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/user_web.png" alt="' . $row['user_web']  . '" title="' . $row['user_web']  . '" /></a>' : '',
			'WARNING' => (!empty($row['user_warning']) ? $row['user_warning'] : '0') . '%' . $warning,
			'PUNISHMENT' => $readonly,
			'EDIT' => $edit,
			'CUT' => $cut,
			'DEL' => $del,'U_VARS_ANCRE' => transid('.php?id=' . $id_get . (!empty($page) ? '&amp;pt=' . $page : ''), '-' . $id_get . (!empty($page) ? '-' . $page : '') . $rewrited_title . '.php'),
			'U_VARS_QUOTE' => transid('.php?quote=' . $row['id'] . '&amp;id=' . $id_get . (!empty($page) ? '&amp;pt=' . $page : ''), '-' . $id_get . (!empty($page) ? '-' . $page : '-0') . '-0-' . $row['id'] . $rewrited_title . '.php'),
			'USER_PM' => !$is_guest ? '<a href="../member/pm' . transid('.php?pm=' . $row['user_id'], '-' . $row['user_id'] . '.php') . '"><img src="../templates/' . $CONFIG['theme'] . '/images/' . $CONFIG['lang'] . '/pm.png" alt="pm" /></a>' : '',
		));
		
		//Marqueur de suivis du sujet.
		if( !empty($row['track']) ) $track = true;
	}
	$sql->close($result);

	$total_admin = 0;
	$total_modo = 0;
	$total_member = 0;
	$total_visit = 0;
	$user_pseudo = '';
	
	$result = $sql->query_while("SELECT s.user_id, s.level, m.login 
	FROM ".PREFIX."sessions AS s 
	LEFT JOIN ".PREFIX."member AS m ON m.user_id = s.user_id 
	WHERE s.session_time > '" . (time() - $CONFIG['site_session_invit']) . "' AND s.session_script = '" . DIR . "/forum/topic.php' AND s.session_script_get LIKE '%id=" . $id_get . "%'
	ORDER BY s.session_time DESC", __LINE__, __FILE__);
	while( $row = $sql->sql_fetch_assoc($result) )
	{
		switch ($row['level']) //Coloration du membre suivant son level d'autorisation. 
		{ 		
			case -1:
			$status = 'visiteur';
			$total_visit++;
			break;
			
			case 0:
			$status = 'member';
			$total_member++;
			break;
			
			case 1: 
			$status = 'modo';
			$total_modo++;
			break;
			
			case 2: 
			$status = 'admin';
			$total_admin++;
			break;
		} 
		
		$coma = !empty($user_pseudo) && $status != 'visiteur' ? ', ' : '';

		$user_pseudo .= ( !empty($row['login']) && $status != 'visiteur' ) ?  $coma . '<a href="../member/member' . transid('.php?id=' . $row['user_id'], '-' . $row['user_id'] . '.php') . '" class="' . $status . '">' . $row['login'] . '</a>' : '';
	}
	
	$template->assign_block_vars('online', array(
		'ONLINE' =>  $user_pseudo
	));
	$sql->close($result);

	$l_admin = ($total_admin > 1) ? $LANG['admin_s'] : $LANG['admin'];
	$l_modo = ($total_modo > 1) ? $LANG['modo_s'] : $LANG['modo'];
	$l_member = ($total_member > 1) ? $LANG['member_s'] : $LANG['member'];
	$l_visit = ($total_visit > 1) ? $LANG['guest_s'] : $LANG['guest'];

	$total_online = $total_admin + $total_modo + $total_member + $total_visit;
	$l_online = ($total_online > 1) ? $LANG['user_s'] : $LANG['user'];
				
	$template->assign_vars(array(
		'TOTAL_ONLINE' => $total_online,
		'ADMIN' => $total_admin,
		'MODO' => $total_modo,
		'MEMBER' => $total_member,
		'GUEST' => $total_visit,
		'SELECT_CAT' => forum_list_cat($session->data), //Retourne la liste des cat�gories, avec les v�rifications d'acc�s qui s'imposent.
		'U_SUSCRIBE' => ($track === false) ? transid('.php?t=' . $id_get) : transid('.php?ut=' . $id_get),
		'U_ALERT' => transid('.php?id=' . $id_get),
		'L_SUSCRIBE' => ($track === false) ? $LANG['track_topic'] : $LANG['untrack_topic'],
		'L_ALERT' => $LANG['alert_topic'],
		'L_USER' => $l_online,
		'L_ADMIN' => $l_admin,
		'L_MODO' => $l_modo ,
		'L_MEMBER' => $l_member,
		'L_GUEST' => $l_visit,
		'L_AND' => $LANG['and'],
		'L_ONLINE' => strtolower($LANG['online']),
	));

	if( ($total_online - $total_visit) == 0  )
	{
		$template->assign_block_vars('online', array(
			'ONLINE' =>  '<em>' . $LANG['no_member_online'] . '</em>'
		));
	}	
			
	//R�cup�ration du message quot�.
	$contents = '';
	if( !empty($quote_get) )
	{	
		$quote_msg = $sql->query_array('forum_msg', 'user_id', 'contents', "WHERE id = '" . $quote_get . "'", __LINE__, __FILE__);
		$pseudo = $sql->query("SELECT login FROM ".PREFIX."member WHERE user_id = '" . $quote_msg['user_id'] . "'", __LINE__, __FILE__);
		
		$contents = '[quote=' . $pseudo . ']' . unparse($quote_msg['contents']) . '[/quote]';
	}
	
	//Formulaire de r�ponse, non pr�sent si verrouill�.
	if( $topic['status'] == '0' && !$check_group_edit_auth )
	{
		$template->assign_block_vars('error_auth_write', array(
			'L_ERROR_AUTH_WRITE' => $LANG['e_topic_lock_forum']
		));
	}	
	elseif( !$groups->check_auth($CAT_FORUM[$topic['idcat']]['auth'], WRITE_CAT_FORUM) ) //On v�rifie si l'utilisateur a les droits d'�critures.
	{
		$template->assign_block_vars('error_auth_write', array(
			'L_ERROR_AUTH_WRITE' => $LANG['e_cat_write']
		));
	}
	else
	{
		$template->assign_block_vars('post', array(
			'CONTENTS' => $contents,
			'U_FORUM_ACTION_POST' => transid('.php?idt=' . $id_get . '&amp;id=' . $topic['idcat'] . '&amp;new=n_msg')
		));
	
		//Affichage du lien pour changer le display_msg du topic et autorisation d'�dition du statut.
		if( $CONFIG_FORUM['activ_display_msg'] == 1 && ($check_group_edit_auth || $session->data['user_id'] == $topic['user_id']) )
		{
			$img_display = $topic['display_msg'] ? 'msg_display2.png' : 'msg_display.png';
			$template->assign_block_vars('post.display_msg', array(
				'ICON_DISPLAY_MSG' => $CONFIG_FORUM['icon_activ_display_msg'] ? '<img src="' . $module_data_path . '/images/' . $img_display . '" alt="" class="valign_middle" />' : '',
				'L_EXPLAIN_DISPLAY_MSG' => $topic['display_msg'] ? $CONFIG_FORUM['explain_display_msg_bis'] : $CONFIG_FORUM['explain_display_msg'],
				'U_ACTION_MSG_DISPLAY' => transid('.php?msg_d=1&amp;id=' . $id_get)
			));
		}
	}
		
	include('../includes/bbcode.php');
	$template->assign_var_from_handle('BBCODE', 'bbcode');

	
	$template->pparse('forum_topic');
}
else
{
	header('Location:' . HOST . DIR . '/forum/index.php' . SID2);
	exit;
}

include('../includes/footer.php');

?>