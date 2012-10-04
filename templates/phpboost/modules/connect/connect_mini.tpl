# IF C_VERTICAL #
	
# ELSE #
	# IF C_USER_NOTCONNECTED #	
	<script type="text/javascript">
	<!--
	function check_connect(){
		if(document.getElementById('login').value == "") {
			alert("{L_REQUIRE_PSEUDO}");
			return false;
		}
		if(document.getElementById('password').value == "") {
			alert("{L_REQUIRE_PASSWORD}");
			return false;
		}
		return true;
	}
	-->
	</script>

	<div class="connect_align">
		<ul>
			<li class="subscribe">
				<a href='{U_REGISTER}'>{L_REGISTER}</a>
			</li>
			<li class="submenu connect"><a href='#'>{L_CONNECT}</a>
				<ul>
					<form action="{U_CONNECT}" method="post" onsubmit="return check_connect();" class="connect_align">
						<input type="text" id="login" name="login" value="{L_PSEUDO}" class="connect_form" onfocus="if( this.value == '{L_PSEUDO}' ) this.value = '';" maxlength="25" />
						<input type="password" id="password" name="password" class="connect_form" value="******" onfocus="if( this.value == '******' ) this.value = '';" maxlength="30" />
						<input checked="checked" type="checkbox" name="auto" class="submit" />
						<input type="hidden" name="redirect" value="{REWRITED_SCRIPT}" />
						<input type="submit" name="connect" value="{L_CONNECT}" class="submit" />
					</form>
				</ul>
			</li>
		</ul>



	</div>
	# ELSE #
	
	<div class="welcome" >
		Bienvenue, <a href='#'>MasterMasterMasterMaster</a>
	</div>
	
	<div class="connect_align">
		<ul>
			<li class="submenu"><a href='#'>Mon Profil</a>
				<ul>
					<img src="{PATH_TO_ROOT}/templates/{THEME}/modules/connect/images/avatar.png" alt="avatar" title="Avatar" class="connect_avatar"/>
					<li>
		         		<img src="{PATH_TO_ROOT}/templates/{THEME}/images/admin/members_mini.png" alt="" class="valign_middle" /> <a href="{U_HOME_PROFILE}" class="small_link">{L_PRIVATE_PROFIL}</a>
					</li>
					<li>
		         		<img src="{PATH_TO_ROOT}/templates/{THEME}/images/{IMG_PM}" class="valign_middle" alt="" /> <a href="{U_USER_PM}" class="small_link">{L_NBR_PM}</a>
					</li>
					
					# IF C_ADMIN_AUTH #
					<li>
						<img src="{PATH_TO_ROOT}/templates/{THEME}/images/admin/ranks_mini.png" alt="" class="valign_middle" /> <a href="{U_ADMINISTRATION}" class="small_link">{L_ADMIN_PANEL} # IF C_UNREAD_ALERT # ({NUMBER_UNREAD_ALERTS}) # ENDIF # </a>
					</li> 
					# ENDIF #
					# IF C_UNREAD_CONTRIBUTION #
						# IF C_KNOWN_NUMBER_OF_UNREAD_CONTRIBUTION #
						<li>
							<img src="{PATH_TO_ROOT}/templates/{THEME}/images/contribution_panel_mini_new.gif" alt="" class="valign_middle" /> <a href="{U_CONTRIBUTION}" class="small_link">{L_CONTRIBUTION_PANEL} ({NUM_UNREAD_CONTRIBUTIONS})</a>
						</li>
						# ELSE #
						<li>
							<img src="{PATH_TO_ROOT}/templates/{THEME}/images/contribution_panel_mini_new.gif" alt="" class="valign_middle" /> <a href="{U_CONTRIBUTION}" class="small_link">{L_CONTRIBUTION_PANEL}</a>
						</li>
						# ENDIF #
					# ELSE #
					<li>
						<img src="{PATH_TO_ROOT}/templates/{THEME}/images/contribution_panel_mini.png" alt="" class="valign_middle" /> <a href="{U_CONTRIBUTION}" class="small_link">{L_CONTRIBUTION_PANEL}</a>
					</li>
					# ENDIF #	
					<li>
						<img src="{PATH_TO_ROOT}/templates/{THEME}/images/admin/home_mini.png" alt="" class="valign_middle" /> <a href="{U_DISCONNECT}" class="small_link">{L_DISCONNECT}</a>	         
					</li>
				</ul>
			</li>
			<li class="disconnect">
				<a href="{U_DISCONNECT}" class="small_link">{L_DISCONNECT}</a>			
			</li>
		</ul>
	</div>
	# ENDIF #
# ENDIF #