<script type="text/javascript">
<!--
	var path = '{PICTURES_DATA_PATH}';
	var selected_cat = {SELECTED_CAT};
-->
</script>
<script type="text/javascript" src="{PICTURES_DATA_PATH}/images/pages.js"></script>


<div class="explorer">
			<div class="cats">
					<h1>{TITLE}</h1>
				<div class="contents">
					<ul>
						<li><a id="class_0" class="{CAT_0}" href="javascript:open_cat(0);"><img src="{PICTURES_DATA_PATH}/images/cat_root.png" alt="" />{L_ROOT}</a>
							<ul>
								# START list #
									{list.DIRECTORY}
								# END list #
								{CAT_LIST}
							</ul>
						</li>
					</ul>
				</div>
			</div>
			<div class="files">
					<h1>{L_CATS}</h1>
				<div class="contents" id="cat_contents">
					<ul>
						{ROOT_CONTENTS}
					</ul>
				</div>
			</div>
		</div>
