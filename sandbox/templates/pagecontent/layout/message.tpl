<div id="messages" class="sandbox-block">

    <article>
        <header>
            <h5>{@fwkboost.messages.and.coms}</h5>
        </header>
        <div class="content">
            <article id="comID" class="message-container" itemscope="itemscope" itemtype="http://schema.org/Comment">
                <header class="message-header-container">
                    <img class="message-user-avatar" src="{NO_AVATAR_URL}" alt="Text">
                    <div class="message-header-infos">
                        <div class="message-user-infos hidden-small-screens">
                            <div></div>
                            <div class="message-user-links">
                                <a href="#" class="button submit smaller">MP</a>
                                <a href="#" class="button submit smaller">Facebook</a>
                                <a href="#" class="button submit smaller">Twitter</a>
                                <a href="#" class="button submit smaller"><i class="far fa-envelope"></i></a>
                            </div>
                        </div>
                        <div class="message-user">
                            <h3 class="message-user-pseudo">
                                <a class="Level" href="UrlProfil" itemprop="author">{@fwkboost.messages.login}</a>
                            </h3>
                            <div class="message-actions">
                                <a href="UrlAction" aria-label="ActionName"><i class="far fa-fw fa-edit"></i></a>
                                <a href="UrlAction" aria-label="ActionName"><i class="far fa-fw fa-trash-alt" data-confirmation="delete-element"></i></a>
                            </div>
                        </div>
                        <div class="message-infos">
                            <time datetime="{@fwkboost.messages.date}" itemprop="datePublished">{@fwkboost.messages.date}</time>
                            <a href="#ID" aria-label="${LangLoader::get_message('link.to.anchor', 'comments-common')}">\#ID</a>
                        </div>
                    </div>
                </header>
                <div class="message-content">
                    {@fwkboost.messages.content}
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eligendi autem sequi quam ab amet culpa nobis vitae rerum laborum nulla!</p>
                </div>
                <footer class="message-footer-container">
                    <div class="message-user-assoc">
                        <div></div>
                        <div class="message-user-rank">
                            <span>{@fwkboost.messages.level}</span>
                            <img src="{PATH_TO_ROOT}/forum/templates/images/ranks/rank_admin.png" />
                        </div>
                    </div>
                    <div class="message-user-management">
                        <div></div>
                        <div class="message-moderation-level">0% <i class="fa fa-exclamation-triangle"></i> <i class="fa fa-user-lock"></i></div>
                    </div>
                </footer>
            </article>
        </div>
    </article>
    <!-- Source code -->
    <div class="formatter-container formatter-hide no-js tpl">
        <span class="formatter-title title-perso">{@sandbox.source.code} :</span>
        <div class="formatter-content formatter-code">
            <div class="formatter-content">
<pre class="language-html line-numbers"><code class="language-html">&lt;article id="Id" class="message-container (message-small/message-offset)" itemscope="itemscope" itemtype="http://schema.org/Comment">
    &lt;header class="message-header-container (#IF CURRENT#current-user-message)">
        &lt;img class="message-user-avatar" src="Url" alt="Text">
        &lt;div class="message-header-infos">
            &lt;div class="message-user-infos hidden-small-screens">
                &lt;div>&lt;/div>
                &lt;div class="message-user-links">&lt;/div>
            &lt;/div>
            &lt;div class="message-user">
                &lt;h3 class="message-user-pseudo">
                    &lt;a class="Level" href="UrlProfil" itemprop="author">MemberName&lt;/a>
                &lt;/h3>
                &lt;div class="message-actions">
                    &lt;a href="UrlAction" aria-label="ActionName">&lt;i class="fa fa-fw fa-action" data-confirmation="delete-element">&lt;/i>&lt;/a>
                &lt;/div>
            &lt;/div>
            &lt;div class="message-infos">
                &lt;time datetime="Date" itemprop="datePublished">Date&lt;/time>
                &lt;a href="UrlAnchor" aria-label="${LangLoader::get_message('link.to.anchor', 'comments-common')}">AnchorName&lt;/a>
            &lt;/div>
        &lt;/div>
    &lt;/header>

    &lt;div class="message-content">
        ...
    &lt;/div>

    &lt;div class="message-user-sign">&lt;/div>

    &lt;footer class="message-footer-container">
        &lt;div class="message-user-assoc">
            &lt;div class="message-group-level">RankImg - GroupImg&lt;/div>
            &lt;div class="message-user-rank">UserLevel&lt;/div>
        &lt;/div>
        &lt;div class="message-user-management">
            &lt;div class="">&lt;/div>
            &lt;div class="message-moderation-level">Lorem ipsum&lt;/div>
        &lt;/div>
    &lt;/footer>

&lt;/article></code></pre>
            </div>
        </div>        
    </div>
</div>
