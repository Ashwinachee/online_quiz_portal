<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

page_header('Online Quiz Portal');
?>
<main class="hero">
    <div class="hero-content">
        <p class="eyebrow">LEARN · PRACTICE · IMPROVE</p>
        <h1>Test your knowledge.<br><span>Grow with every quiz.</span></h1>
        <p class="hero-copy">Take quizzes, see your results, and keep track of your progress in one place.</p>
        <div class="hero-actions">
            <a class="button" href="register.php">Create an account</a>
            <a class="button button-secondary" href="login.php">Sign in</a>
        </div>
    </div>
    <aside class="hero-card">
        <span class="hero-card-icon" aria-hidden="true">✓</span>
        <h2>Ready when you are</h2>
        <p>Browse available quizzes and get instant feedback when you finish.</p>
    </aside>
</main>
<?php page_footer(); ?>
