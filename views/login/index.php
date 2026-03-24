<?php include __DIR__ . '/../login/header.php'; ?>

<div class="login-container">
    <div class="login-card">
        <!-- Logo -->
        <div class="login-logo-container">
            <img src="/img/logo.png" alt="Deal File Manager" class="login-logo" onerror="this.style.display='none'">
            <h1 class="login-title">Deal File Manager</h1>
        </div>

        <!-- Login Form -->
        <form method="POST" action="/login" class="login-form">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input
                    type="text"
                    class="form-control"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
        </form>

        <!-- Skip Button -->
        <div class="login-skip-container">
            <a href="/dashboard" class="btn btn-outline-secondary btn-sm">Skip for Now</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../login/footer.php'; ?>
