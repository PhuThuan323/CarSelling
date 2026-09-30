{block name="content"}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/feedback-stories.css">

    {include file="home/topbar.tpl"}

    <section class="story-detail">

        <div class="story-detail-container">

            <a href="/customer-stories" class="story-back">
                <i class="fa-solid fa-arrow-left"></i> Tất cả câu chuyện
            </a>

            <article class="story-detail-card">

                {if $story.image ne ''}
                    <img class="story-detail-cover" src="{$story.image|escape}" alt="{$story.author_name|escape}">
                {/if}

                <div class="story-detail-body">

                    <span class="story-detail-badge">
                        {if $story.location ne ''}{$story.location|escape}{else}Câu chuyện khách hàng{/if}
                    </span>

                    <h1 class="story-detail-title">
                        {$story.title|escape}
                    </h1>

                    <div class="story-detail-meta">
                        <span>
                            <i class="fa-regular fa-user"></i>
                            <strong>{$story.author_name|escape}</strong>
                        </span>
                        <span>
                            <i class="fa-regular fa-calendar"></i>
                            {$story.created_at|date_format:"%d/%m/%Y"}
                        </span>
                    </div>

                    <div class="story-detail-content">{$story.story|escape}</div>

                    <blockquote class="story-detail-quote">
                        “{$story.excerpt|escape}”
                    </blockquote>

                    <div class="story-detail-cta">
                        <div>
                            <strong>Bạn muốn có trải nghiệm tương tự?</strong>
                            <span>Đăng bán xe để nhận định giá minh bạch từ FastCar.</span>
                        </div>
                        <a href="/sell-car">Bán xe ngay</a>
                    </div>

                </div>

            </article>

            {if $other_stories|@count > 0}

                <section class="story-related">

                    <h2>Câu chuyện khác</h2>

                    <div class="stories-grid">

                        {foreach $other_stories as $other}

                            <a class="story-card" href="/customer-stories/{$other.slug|escape:'url'}">

                                {if $other.image ne ''}
                                    <img class="story-card-image" src="{$other.image|escape}" alt="{$other.author_name|escape}"
                                        loading="lazy">
                                {else}
                                    <span class="story-card-image is-empty">
                                        <i class="fa-regular fa-image"></i>
                                    </span>
                                {/if}

                                <div class="story-card-body">

                                    <span class="story-card-badge">
                                        {$other.author_name|escape}
                                    </span>

                                    <h3 class="story-card-title">
                                        {$other.title|escape}
                                    </h3>

                                    <div class="story-card-foot">

                                        <span class="story-card-author">
                                            {if $other.location ne ''}{$other.location|escape}{else}FastCar{/if}
                                        </span>

                                        <span class="story-card-link">
                                            Đọc →
                                        </span>

                                    </div>

                                </div>

                            </a>

                        {/foreach}

                    </div>

                </section>

            {/if}

        </div>

    </section>

    {include file="home/footer.tpl"}

{/block}