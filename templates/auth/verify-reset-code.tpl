{extends file="layouts/auth.tpl"}

{block name="content"}

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1>Verification Code</h1>
            <p> Enter the 6-digit code sent to {$email|escape}</p>
        </div>
        {if isset($error)}
            <div class="alert alert-error">{$error|escape}</div>

        {/if}

        <form method="POST" action="/auth/verify-reset-code" class="login-form">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <div class="form-group">
                <label for="code">
                    Verification Code
                </label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    maxlength="6"
                    minlength="6"
                    inputmode="numeric"
                    pattern="[0-9][0-9][0-9][0-9][0-9][0-9]"
                    autocomplete="one-time-code"
                    class="form-input"
                    placeholder="123456"
                    required
                >
            </div>
            <button type="submit" class="btn btn-primary">
                Verify Code
            </button>
        </form>
        <div class="login-links">
            <a href="/auth/forgot-password" class="link">
                Request another code
            </a>
        </div>
    </div>
</div>
{/block}