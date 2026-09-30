{block name="content"}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/feedback-stories.css">

    {include file="home/topbar.tpl"}

    <section class="story-detail">

        <div class="story-detail-container">

            <a href="/customer-stories" class="story-back">
                <i class="fa-solid fa-arrow-left"></i> Tất cả câu chuyện
            </a>

        <div class="story-detail-hero">

            <div class="story-detail-intro">

                <div class="story-detail-tags">

                    <span class="story-detail-tag">Câu chuyện khách hàng</span>

                    {if $story.location ne ''}
                        <span class="story-detail-city">
                            <i class="fa-solid fa-location-dot"></i>
                            {$story.location|escape}
                        </span>
                    {/if}

                </div>

                <h1 class="story-detail-title">
                    {$story.title|escape}
                </h1>

                <p class="story-detail-excerpt">
                    {$story.excerpt|escape}
                </p>

                <div class="story-detail-highlights">

                    {if $story.highlight_price ne ''}
                        <div class="story-highlight">
                            <span>Giá chốt bán</span>
                            <strong>{$story.highlight_price|escape}</strong>
                        </div>
                    {/if}

                    {if $story.highlight_time ne ''}
                        <div class="story-highlight">
                            <span>Thời gian bán</span>
                            <strong>{$story.highlight_time|escape}</strong>
                        </div>
                    {/if}

                    {if $story.highlight_fee ne ''}
                        <div class="story-highlight">
                            <span>Phí dịch vụ</span>
                            <strong>{$story.highlight_fee|escape}</strong>
                        </div>
                    {/if}

                </div>

                <a href="/sell-car" class="story-detail-cta-banner">

                    <span class="story-detail-cta-icon">
                        <i class="fa-regular fa-comment-dots"></i>
                    </span>

                    <span class="story-detail-cta-copy">
                        <strong>Định giá xe trong 30 giây</strong>
                        <span>Định giá AI miễn phí, xem giá người mua trả rồi mới quyết định bán</span>
                    </span>

                    <i class="fa-solid fa-chevron-right"></i>

                </a>

                <div class="story-detail-perks">

                    <span>
                        <i class="fa-solid fa-check"></i>
                        Miễn phí 100%
                    </span>

                    <span>
                        <i class="fa-solid fa-check"></i>
                        Không bắt buộc bán
                    </span>

                    <span>
                        <i class="fa-solid fa-check"></i>
                        Không lộ thông tin xe
                    </span>

                </div>

            </div>

            <div class="story-detail-photo">

                {if $story.story_image ne ''}
                    <img
                        class="story-detail-hero-image"
                        src="{$story.story_image|escape}"
                        alt="Hình ảnh đồng xe của {$story.author_name|escape}"
                    >
                {else}
                    <span class="story-detail-hero-image is-empty">
                        <i class="fa-regular fa-image"></i>
                    </span>
                {/if}

                <span class="story-detail-hero-caption">
                    Hình ảnh đồng xe trong câu chuyện bán qua FastCar.
                </span>

            </div>

        </div>

        <div class="story-detail-commitments">

            <div class="story-commitment">
                <i class="fa-regular fa-file-lines"></i>
                <div>
                    <strong>Không can thiệp đăng tin</strong>
                    <span>FastCar chuẩn hóa hồ sơ và đưa xe tới đúng nhóm người mua.</span>
                </div>
            </div>

            <div class="story-commitment">
                <i class="fa-regular fa-handshake"></i>
                <div>
                    <strong>Không phải tự mặc cả</strong>
                    <span>Đội ngũ FastCar đứng giữa thương lượng khi phát sinh vướng mắc.</span>
                </div>
            </div>

            <div class="story-commitment">
                <i class="fa-solid fa-user-check"></i>
                <div>
                    <strong>Người mua đã xác thực</strong>
                    <span>Nhiều người mua cùng xem một bộ thông tin rõ ràng.</span>
                </div>
            </div>

            <div class="story-commitment">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Chi thu khi thành công</strong>
                    <span>Phí chỉ phát sinh sau khi giao dịch được chốt.</span>
                </div>
            </div>

        </div>

        <article class="story-detail-body">

            <span class="story-detail-badge">Câu chuyện khách hàng</span>

            <h2 class="story-detail-heading">
                Câu chuyện của {$story.author_name|escape}
            </h2>

            <div class="story-detail-lead">{$story.summary|escape}</div>

            {if $story.problems|@count > 0}

                <div class="story-detail-problems-title">Những vấn đề khách gặp phải:</div>

                <ul class="story-detail-problems">

                    {foreach $story.problems as $problem}
                        <li>
                            <i class="fa-regular fa-circle-xmark"></i>
                            <span>{$problem|escape}</span>
                        </li>
                    {/foreach}

                </ul>

            {/if}

            <div class="story-detail-content">{$story.story|escape}</div>

            <blockquote class="story-detail-quote">
                “{$story.excerpt|escape}”
            </blockquote>

            <div class="story-detail-footer-cta">
                <div>
                    <strong>Bạn muốn có trải nghiệm tương tự?</strong>
                    <span>Đăng bán xe để nhận định giá minh bạch từ FastCar.</span>
                </div>
                <a href="/sell-car">Bán xe ngay</a>
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