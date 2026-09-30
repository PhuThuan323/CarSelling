{block name="content"}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/feedback-stories.css">

    {include file="home/topbar.tpl"}

    <section class="stories-page">

        <div class="stories-container">

            <div class="stories-hero">

                <span class="stories-kicker">Câu chuyện khách hàng</span>

                <h1>Khách hàng thật, xe thật, giá thật</h1>

                <p>
                    Mỗi câu chuyện là một hành trình mua bán xe có thật tại FastCar.
                    Đọc để hiểu rõ hơn cách chúng tôi đồng hành cùng khách hàng.
                </p>

            </div>

            <form class="stories-toolbar" method="get" action="/customer-stories">

                <div class="stories-search">

                    <input type="search" name="q" value="{$keyword|escape}"
                        placeholder="Tìm theo tên khách, tiêu đề hoặc nội dung..." aria-label="Tìm câu chuyện">

                    <button type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i> Tìm
                    </button>

                </div>

            </form>

            {if $stories|@count == 0}

                <div class="stories-empty">

                    <i class="fa-regular fa-comment-dots"></i>

                    {if $keyword ne ''}
                        <strong>Không tìm thấy câu chuyện phù hợp</strong>
                        <span>Thử từ khóa khác hoặc <a href="/customer-stories">xem tất cả câu chuyện</a>.</span>
                    {else}
                        <strong>Chưa có câu chuyện nào</strong>
                        <span>Các câu chuyện khách hàng sẽ sớm được cập nhật tại đây.</span>
                    {/if}

                </div>

            {else}

                <div class="stories-grid">

                    {foreach $stories as $story}

                        <a class="story-card" href="/customer-stories/{$story.slug|escape:'url'}">

                            {if $story.image ne ''}
                                <img class="story-card-image" src="{$story.image|escape}" alt="{$story.author_name|escape}"
                                    loading="lazy">
                            {else}
                                <span class="story-card-image is-empty">
                                    <i class="fa-regular fa-image"></i>
                                </span>
                            {/if}

                            <div class="story-card-body">

                                <span class="story-card-badge">
                                    {if $story.location ne ''}{$story.location|escape}{else}Khách hàng FastCar{/if}
                                </span>

                                <h2 class="story-card-title">
                                    {$story.title|escape}
                                </h2>

                                <p class="story-card-text">
                                    {$story.excerpt|escape}
                                </p>

                                <div class="story-card-foot">

                                    <span class="story-card-author">
                                        {$story.author_name|escape}
                                    </span>

                                    <span class="story-card-link">
                                        Đọc câu chuyện →
                                    </span>

                                </div>

                            </div>

                        </a>

                    {/foreach}

                </div>

                {if $page_count > 1}

                    <div class="stories-pagination">

                        {for $p=1 to $page_count}

                            {if $keyword ne ''}
                                {assign var="pageUrl" value="/customer-stories?q=`$keyword|escape:'url'`&page=`$p`"}
                            {elseif $p eq 1}
                                {assign var="pageUrl" value="/customer-stories"}
                            {else}
                                {assign var="pageUrl" value="/customer-stories?page=`$p`"}
                            {/if}

                            <a class="{if $p eq $current_page}is-active{/if}" href="{$pageUrl}">
                                {$p}
                            </a>

                        {/for}

                    </div>

                {/if}

            {/if}

        </div>

        {* Khoi keu goi o cuoi trang, ngay truoc footer *}
        <div class="stories-final-cta">

            <h2>
                Chiếc xe của bạn cũng có<br>
                thể là câu chuyện tiếp theo
            </h2>

            <p>
                Kiểm tra giá thị trường miễn phí, xem người mua trả giá rồi mới quyết định bán.
            </p>

            <a href="/sell-car" class="stories-final-cta-btn">

                <span class="stories-final-cta-icon">
                    <i class="fa-solid fa-car-side"></i>
                </span>

                <span class="stories-final-cta-copy">
                    <strong>Xem giá xe của tôi</strong>
                    <span>Kiểm tra giá thị trường miễn phí trước khi quyết định bán</span>
                </span>

                <i class="fa-solid fa-chevron-right"></i>

            </a>

            <div class="stories-final-perks">

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

    </section>

    {include file="home/footer.tpl"}

{/block}