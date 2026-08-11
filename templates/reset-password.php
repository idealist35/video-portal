<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title"><?= te('auth.reset_title') ?></h1>
        <p class="auth-subtitle"><?= te('auth.reset_subtitle') ?></p>

        <form method="POST" action="/reset-password">
            <?= $csrf ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <div class="form-group">
                <label for="password"><?= te('auth.new_password') ?></label>
                <input type="password" id="password" name="password" required
                       minlength="6" placeholder="<?= te('auth.min_chars') ?>" class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= te('auth.update_password') ?></button>
        </form>
    </div>
</div>
