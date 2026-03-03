<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Join and unlock exclusive content</p>

        <form method="POST" action="/register">
            <?= $csrf ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="your@email.com" class="form-input">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       minlength="6" placeholder="Minimum 6 characters" class="form-input">
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required
                       minlength="6" placeholder="Repeat your password" class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <p class="auth-footer">
            Already have an account? <a href="/login" class="link-accent">Sign In</a>
        </p>
    </div>
</div>
