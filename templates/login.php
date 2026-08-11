<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title"><?= te('auth.login_title') ?></h1>
        <p class="auth-subtitle"><?= te('auth.login_subtitle') ?></p>

        <form method="POST" action="/login">
            <?= $csrf ?>

            <div class="form-group">
                <label for="email"><?= te('auth.email') ?></label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="<?= te('auth.email_placeholder') ?>" class="form-input">
            </div>

            <div class="form-group">
                <label for="password"><?= te('auth.password') ?></label>
                <input type="password" id="password" name="password" required
                       placeholder="<?= te('auth.password_holder') ?>" class="form-input">
            </div>

            <div class="form-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span><?= te('auth.remember') ?></span>
                </label>
                <a href="/forgot-password" class="link-muted"><?= te('auth.forgot_link') ?></a>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= te('auth.sign_in') ?></button>
        </form>

        <p class="auth-footer">
            <?= te('auth.no_account') ?> <a href="/register" class="link-accent"><?= te('auth.sign_up') ?></a>
        </p>
    </div>
</div>
