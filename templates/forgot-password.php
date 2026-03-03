<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Forgot Password</h1>
        <p class="auth-subtitle">Enter your email and we'll send a reset link</p>

        <form method="POST" action="/forgot-password">
            <?= $csrf ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="your@email.com" class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
        </form>

        <p class="auth-footer">
            Remember your password? <a href="/login" class="link-accent">Sign In</a>
        </p>
    </div>
</div>
