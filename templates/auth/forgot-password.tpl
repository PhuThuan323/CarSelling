{extends file="layouts/auth.tpl"}

{block name="content"}

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1>Forgot Password</h1>
            <p>
                Enter your email address and
                we will send you a verification code.
            </p>
        </div>

        {if isset($error)}

            <div class="alert alert-error">
                {$error|escape}
            </div>

        {/if}

        {if isset($message)}

            <div class="alert alert-success">
                {$message|escape}
            </div>

        {/if}

        <form method="POST" action="/auth/forgot-password" class="login-form">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">

            <div class="form-group">
                <label for="email">
                    Email Address
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="{$email|default:''|escape}"
                    required
                >
            </div>
            <button type="submit" class="btn btn-primary">
                Send Reset Code
            </button>
        </form>

        <div class="login-links">
            <a href="/auth/login" class="link">
                Back to Login
            </a>
        </div>
    </div>
</div>

{/block}