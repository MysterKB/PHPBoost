<menu class="dynamic_menu right">
	<ul>
		<li><a><i class="icon-cog"></i></a>
			<ul>
				# IF C_ADD #
				<li>
					<a href="${relative_url(NewsUrlBuilder::add_news())}" title="${i18n('news.add')}">${i18n('news.add')}</a>
				</li>
				# ENDIF #
				# IF C_PENDING_NEWS #
				<li>
					<a href="${relative_url(NewsUrlBuilder::display_pending_news())}" title="${i18n('news.pending')}">${i18n('news.pending')}</a>
				</li>
				# ENDIF #
				# IF IS_ADMIN #
				<li>
					<a href="${relative_url(NewsUrlBuilder::config())}" title="${LangLoader::get_message('configuration', 'admin')}">${LangLoader::get_message('configuration', 'admin')}</a>
				</li>
				# ENDIF #
			</ul>
		</li>
	</ul>
</menu>

<section>
	<header>
		<h1>
			<a href="${relative_url(SyndicationUrlBuilder::rss('news'))}" class="icon-syndication" title="${LangLoader::get_message('syndication', 'main')}"></a>
			{L_NEWS_TITLE}
		</h1>
	</header>
# IF C_NEWS_NO_AVAILABLE #
    <div class="center">
        ${i18n('news.message.no_items')}
    </div>
# ELSE #
	# START news #
		# IF news.C_NEWS_ROW #
			<div class="spacer"></div>
		# ENDIF #
		<article # IF C_NEWS_BLOCK_COLUMN # style="float:left;width:{COLUMN_WIDTH}%" # ENDIF # itemscope="itemscope" itemtype="http://schema.org/CreativeWork">
			<header>
				<h1>
					<a href="{news.U_SYNDICATION}" class="icon-syndication" title="${LangLoader::get_message('syndication', 'main')}"></a>
        			<a href="{news.U_LINK}"><span id="name" itemprop="name">{news.NAME}</span></a>
        			<span class="actions">
        				# IF news.C_EDIT #
							<a href="{news.U_EDIT}" title="${LangLoader::get_message('edit', 'main')}" class="icon-edit"></a>
						# ENDIF #
						# IF news.C_DELETE #
							<a href="{news.U_DELETE}" title="${LangLoader::get_message('delete', 'main')}" class="icon-delete" data-confirmation="delete-element"></a>
						# ENDIF #
					</span>
        		</h1>
        		
        		<div class="more">
					Post� par 
					# IF news.PSEUDO #
					<a itemprop="author" class="small_link {news.USER_LEVEL_CLASS}" href="{news.U_AUTHOR_PROFILE}" style="font-size: 12px;" # IF news.C_USER_GROUP_COLOR # style="color:{news.USER_GROUP_COLOR}" # ENDIF #>{news.PSEUDO}</a>, 
					# ENDIF # 
					le <time datetime="{news.DATE_ISO8601}" itemprop="datePublished">{news.DATE}</time>, 
					dans la cat�gorie <a itemprop="about" href="{news.U_CATEGORY}">{news.CATEGORY_NAME}</a>
				</div>
				
        		<meta itemprop="url" content="{news.U_LINK}">
				<meta itemprop="description" content="{news.DESCRIPTION}">
				<meta itemprop="discussionUrl" content="{news.U_COMMENTS}">
				<meta itemprop="interactionCount" content="{news.NUMBER_COMMENTS} UserComments">
				
			</header>
			
			<div class="content">
				# IF news.C_PICTURE #<img itemprop="thumbnailUrl" src="{news.U_PICTURE}" alt="{news.NAME}" title="{news.NAME}" class="right" /># ENDIF #
				<span itemprop="text">{news.CONTENTS}</span>
			</div>
			
			<footer></footer>
		</article>
	# END news #
# ENDIF #
	<footer># IF C_PAGINATION # # INCLUDE PAGINATION # # ENDIF #</footer>
</section>