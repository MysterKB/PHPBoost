<table>
	# IF C_SUBSCRIBERS #
	<thead>
		<tr>
			<th></th>
			<th>
				<a href="{SORT_PSEUDO_TOP}" class="icon-table-sort-up"></a>
				{@subscribers.pseudo} 
				<a href="{SORT_PSEUDO_BOTTOM}" class="icon-table-sort-down"></a>
			</th>
			<th>
				{@subscribers.mail}
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th colspan="3">
				{PAGINATION}
			</th>
		</tr>
	</tfoot>
	<tbody>
		# START subscribers_list #
		<tr>
			<td> 
				# IF subscribers_list.C_AUTH_MODO #
					# IF subscribers_list.C_EDIT_LINK #
					<a href="{subscribers_list.EDIT_LINK}" title="${LangLoader::get_message('edit', 'main')}" class="icon-edit"></a>
					# ENDIF #
					<a href="{subscribers_list.DELETE_LINK}" title="${LangLoader::get_message('delete', 'main')}" class="icon-delete" data-confirmation="delete-element"></a>
				# ENDIF #
			</td>
			<td>
				{subscribers_list.PSEUDO}
			</td>
			<td>
				{subscribers_list.MAIL}
			</td>
		</tr>
		# END subscribers_list #
	</tbody>
	# ELSE #
	<tbody>
		<tr>
			<td colspan="3">
				<span class="text_strong">{@subscribers.no_users}</span>
			</td>
		</tr>
	</tbody>
	# ENDIF #
</table>