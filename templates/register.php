<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title"><?= te('auth.register_title') ?></h1>
        <p class="auth-subtitle"><?= te('auth.register_subtitle') ?></p>

        <form method="POST" action="/register">
            <?= $csrf ?>

            <div class="form-group">
                <label for="email"><?= te('auth.email') ?></label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="<?= te('auth.email_placeholder') ?>" class="form-input">
            </div>

            <div class="form-group">
                <label for="password"><?= te('auth.password') ?></label>
                <input type="password" id="password" name="password" required
                       minlength="6" placeholder="<?= te('auth.min_chars') ?>" class="form-input">
            </div>

            <div class="form-group">
                <label for="password_confirm"><?= te('auth.confirm_password') ?></label>
                <input type="password" id="password_confirm" name="password_confirm" required
                       minlength="6" placeholder="<?= te('auth.repeat_password') ?>" class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= te('auth.create_account') ?></button>
        </form>

        <p class="auth-footer">
            <?= te('auth.have_account') ?> <a href="/login" class="link-accent"><?= te('auth.sign_in') ?></a>
        </p>
    </div>
</div>
