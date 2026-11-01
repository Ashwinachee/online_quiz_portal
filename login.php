<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!csrf_is_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        $statement = db()->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $statement->execute(array($email));
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            redirect('dashboard.php');
        }

        $error = 'The email or password is incorrect.';
    }
}

page_header('Sign in');
?>
<main class="auth-shell">
    <section class="panel auth-panel">
        <p class="eyebrow">WELCOME BACK</p>
        <h1>Sign in</h1>
        <p class="muted">Continue to your quizzes and results.</p>
        <?php if ($error !== '') { ?><div class="notice notice-error"><?php echo e($error); ?></div><?php } ?>
        <form method="post" action="login.php">
            <?php echo csrf_field(); ?>
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" required value="<?php echo e($email); ?>">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="button button-full" type="submit">Sign in</button>
        </form>
        <p class="form-footnote">New here? <a href="register.php">Create an account</a></p>
    </section>
</main>
<?php page_footer(); ?>
