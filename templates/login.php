<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Sign in to access your content</p>

        <form method="POST" action="/login">
            <?= $csrf ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="your@email.com" class="form-input">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Your password" class="form-input">
            </div>

            <div class="form-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>
                <a href="/forgot-password" class="link-muted">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <p class="auth-footer">
            Don't have an account? <a href="/register" class="link-accent">Sign Up</a>
        </p>
    </div>
</div>
