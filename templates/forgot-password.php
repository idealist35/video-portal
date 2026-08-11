<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title"><?= te('auth.forgot_title') ?></h1>
        <p class="auth-subtitle"><?= te('auth.forgot_subtitle') ?></p>

        <form method="POST" action="/forgot-password">
            <?= $csrf ?>

            <div class="form-group">
                <label for="email"><?= te('auth.email') ?></label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="<?= te('auth.email_placeholder') ?>" class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= te('auth.send_reset') ?></button>
        </form>

        <p class="auth-footer">
            <?= te('auth.remember_password') ?> <a href="/login" class="link-accent"><?= te('auth.sign_in') ?></a>
        </p>
    </div>
</div>
