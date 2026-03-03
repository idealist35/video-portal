<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Set New Password</h1>
        <p class="auth-subtitle">Choose a strong new password</p>

        <form method="POST" action="/reset-password">
            <?= $csrf ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required
                       minlength="6" placeholder="Minimum 6 characters" class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Update Password</button>
        </form>
    </div>
</div>
