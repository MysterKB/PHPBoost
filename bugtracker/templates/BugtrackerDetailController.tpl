<script type="text/javascript">
	<!--
	# IF C_REOPEN_BUG #
	function Confirm_reopen() {
		return confirm("{@bugs.actions.confirm.reopen_bug}");
	}
	# ENDIF #
	
	# IF C_REJECT_BUG #
	function Confirm_reject() {
		return confirm("{@bugs.actions.confirm.reject_bug}");
	}
	# ENDIF #
	-->
</script>

<fieldset>
	<legend>
		{@bugs.titles.bugs_treatment_state}
		&nbsp;
		# IF C_REOPEN_BUG #
			<a href="{U_REOPEN}" onclick="javascript:return Confirm_reopen();" class="icon-bugtracker-rejected" title="{@bugs.actions.reopen}"></a>
		# ENDIF #
		# IF C_REJECT_BUG #
			<a href="{U_REJECT}" onclick="javascript:return Confirm_reject();" class="icon-bugtracker-opened" title="{@bugs.actions.reject}"></a>
		# ENDIF #
		# IF C_EDIT_BUG #
			<a href="{U_EDIT}" title="${LangLoader::get_message('edit', 'main')}" class="icon-edit"></a>
		# ENDIF #
		# IF C_HISTORY_BUG #
			<a href="{U_HISTORY}" title="{@bugs.actions.history}" class="icon-bugtracker-history"></a>
		# ENDIF #
		# IF C_DELETE_BUG #
			<a href="{U_DELETE}" title="${LangLoader::get_message('delete', 'main')}" class="icon-delete" data-confirmation="delete-element"></a>
		# ENDIF #
	</legend>
	# IF C_PROGRESS #
	<div class="form-element">
		<label>{@bugs.labels.fields.progress}</label>
		<div class="form-field" style="width: 25%;">
			{PROGRESS}%
			<div class="progressbar-container">
				<div class="progressbar" style="width:{PROGRESS}%"></div>
			</div>
			</div>
		</div>
	# ENDIF #
	<div class="form-element">
		<label>{@bugs.labels.fields.status}</label>
		<div class="form-field">{STATUS}</div>
	</div>
	<div class="form-element">
		<label>{@bugs.labels.fields.assigned_to_id}</label>
		<div class="form-field"># IF USER_ASSIGNED #<a href="{LINK_USER_ASSIGNED_PROFILE}" class="small_link {USER_ASSIGNED_LEVEL_CLASS}" # IF C_USER_ASSIGNED_GROUP_COLOR # style="color:{USER_ASSIGNED_GROUP_COLOR}" # ENDIF #>{USER_ASSIGNED}</a># ELSE #{@bugs.notice.no_one}# ENDIF #</div>
	</div>
	# IF C_FIXED_IN #
	<div class="form-element">
		<label>{@bugs.labels.fields.fixed_in}</label>
		<div class="form-field">{FIXED_IN}</div>
	</div>
	# ENDIF #
</fieldset>

<fieldset>
	<legend>{TITLE}</legend>
	<div class="content">{CONTENTS}</div>
</fieldset>

<fieldset>
	<legend>{@bugs.titles.bugs_infos}</legend>
	# IF NOT C_EMPTY_TYPES #
	<div class="form-element">
		<label>{@bugs.labels.fields.type}</label>
		<div class="form-field">{TYPE}</div>
	</div>
	# ENDIF #
	# IF NOT C_EMPTY_CATEGORIES #
	<div class="form-element">
		<label>{@bugs.labels.fields.category}</label>
		<div class="form-field">{CATEGORY}</div>
	</div>
	# ENDIF #
	# IF NOT C_EMPTY_SEVERITIES #
	<div class="form-element">
		<label>{@bugs.labels.fields.severity}</label>
		<div class="form-field">{SEVERITY}</div>
	</div>
	# ENDIF #
	# IF NOT C_EMPTY_PRIORITIES #
	<div class="form-element">
		<label>{@bugs.labels.fields.priority}</label>
		<div class="form-field">{PRIORITY}</div>
	</div>
	# ENDIF #
	# IF NOT C_EMPTY_VERSIONS #
	<div class="form-element">
		<label>{@bugs.labels.fields.detected_in}</label>
		<div class="form-field">{DETECTED_IN}</div>
	</div>
	# ENDIF #
	<div class="form-element">
		<label>{@bugs.labels.fields.reproductible}</label>
		<div class="form-field"># IF C_REPRODUCTIBLE #${LangLoader::get_message('yes', 'main')}# ELSE #${LangLoader::get_message('no', 'main')}# ENDIF #</div>
	</div>
</fieldset>

# IF C_REPRODUCTION_METHOD #
<fieldset>
	<legend>{@bugs.labels.fields.reproduction_method}</legend>
	<div class="content">{REPRODUCTION_METHOD}</div>
</fieldset>
# ENDIF #

<div class="text_small float-right">
	{@bugs.labels.fields.author_id} # IF AUTHOR #<a href="{U_AUTHOR_PROFILE}" class="small_link {AUTHOR_LEVEL_CLASS}" # IF C_AUTHOR_GROUP_COLOR # style="color:{AUTHOR_GROUP_COLOR}" # ENDIF #>{AUTHOR}</a># ELSE #${LangLoader::get_message('guest', 'main')}# ENDIF #, ${LangLoader::get_message('on', 'main')} # IF C_IS_DATE_FORM_SHORT #{SUBMIT_DATE_SHORT}# ELSE #{SUBMIT_DATE}# ENDIF #
</div>

# INCLUDE COMMENTS #
