{extends file="layouts/auth.tpl"}

{block name="content"}

<div class="login-container">

    <div class="login-box">

        <div class="login-header">

            <h1>Create New Password</h1>

            <p>
                Enter your new password.
            </p>

        </div>

        {if isset($error)}

            <div class="alert alert-error">
                {$error|escape}
            </div>

        {/if}

        <form
            method="POST"
            action="/auth/reset-password"
            class="login-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="{$csrf_token}"
            >

            <div class="form-group">

                <label>
                    New Password
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-input"
                    minlength="8"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    class="form-input"
                    minlength="8"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Change Password
            </button>

        </form>

    </div>

</div>

{/block}