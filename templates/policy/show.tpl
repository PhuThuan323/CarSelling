{block name="content"}

<link
    rel="stylesheet"
    href="/assets/css/footer.css"
>

<style>

.policy-page {
    padding:
        130px
        24px
        70px;

    background:
        #f6f9fc;
}


.policy-container {
    width:
        min(
            950px,
            100%
        );

    margin:
        0 auto;

    padding:
        38px
        42px;

    border:
        1px solid
        #e1e8ef;

    border-radius:
        14px;

    background:
        #ffffff;

    box-shadow:
        0 10px 30px
        rgba(30, 65, 100, .08);
}


.policy-title {
    margin:
        0
        0
        28px;

    color:
        #18324c;

    font-size:
        30px;

    font-weight:
        800;
}


.policy-content {
    color:
        #40566d;

    font-size:
        15px;

    line-height:
        1.8;

    white-space:
        pre-wrap;
}


.policy-back {
    display:
        inline-block;

    margin-bottom:
        22px;

    color:
        #0798e8;

    font-size:
        14px;

    text-decoration:
        none;
}


@media (max-width: 640px) {

    .policy-container {
        padding:
            25px
            20px;
    }


    .policy-title {
        font-size:
            24px;
    }

}

</style>


<section class="policy-page">

    <div class="policy-container">

        <a
            href="/"
            class="policy-back"
        >
            ← Quay lại trang chủ
        </a>


        <h1 class="policy-title">
            {$policy_title|escape}
        </h1>


        <div class="policy-content">{$policy_content|escape}</div>

    </div>

</section>


{include file="../home/footer.tpl"}

{/block}