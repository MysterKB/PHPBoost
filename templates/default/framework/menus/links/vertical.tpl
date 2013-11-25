# IF C_MENU # <!-- Menu -->
	# IF C_FIRST_MENU # <!-- Title -->
		<div class="module_mini_container">
			<div class="module_mini_top">
				<h3 class="menu_vertical_{DEPTH} menu_vertical">
					# IF RELATIVE_URL #
						<a href="{PATH_TO_ROOT}{RELATIVE_URL}" title="{TITLE}">
						# IF C_IMG #<img src="{PATH_TO_ROOT}{RELATIVE_IMG}" alt="" /># ENDIF #{TITLE}</a>
					# ELSE #
						# IF C_IMG #<img src="{PATH_TO_ROOT}{RELATIVE_IMG}" alt="" /># ENDIF #{TITLE}
					# ENDIF #
				</h3>
			</div>
			<div class="module_mini_contents">
				# IF C_HAS_CHILD #<ul class="menu_vertical_{DEPTH} menu_vertical"># START elements #{elements.DISPLAY}# END elements #</ul># ENDIF #
			</div>
			<div class="module_mini_bottom">
			</div>
		</div>
	# ENDIF #
	# IF C_NEXT_MENU # <!-- Children -->
		<li>
			<h3 class="menu_vertical_{DEPTH} menu_vertical">
			# IF RELATIVE_URL #
				<a href="{PATH_TO_ROOT}{RELATIVE_URL}" title="{TITLE}">
				# IF C_IMG #<img src="{PATH_TO_ROOT}{RELATIVE_IMG}" alt="" /># ENDIF #
				{TITLE}</a>
			# ELSE #
				<span># IF C_IMG #<img src="{PATH_TO_ROOT}{RELATIVE_IMG}" alt="" /># ENDIF #{TITLE}</span>
			# ENDIF #
			</h3>
			# IF C_HAS_CHILD #<ul class="menu_vertical_{DEPTH} menu_vertical"># START elements #{elements.DISPLAY}# END elements #</ul># ENDIF #
		</li>
	# ENDIF #
# ELSE # <!-- Simple Menu Link -->
	<li>
		<a href="{PATH_TO_ROOT}{RELATIVE_URL}" title="{TITLE}"># IF C_IMG #<img src="{PATH_TO_ROOT}{RELATIVE_IMG}" alt="" /># ENDIF #{TITLE}</a>
	</li>
# ENDIF #
