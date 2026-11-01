<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (!csrf_is_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($name === '' || strlen($name) > 100) {
        $error = 'Enter a name no longer than 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        $statement = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute(array($email));

        if ($statement->fetch()) {
            $error = 'An account with that email address already exists.';
        } else {
            $insert = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "student")');
            $insert->execute(array($name, $email, password_hash($password, PASSWORD_DEFAULT)));
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) db()->lastInsertId();
            $_SESSION['name'] = $name;
            $_SESSION['role'] = 'student';
            redirect('dashboard.php');
        }
    }
}

page_header('Create account');
?>
<main class="auth-shell">
    <section class="panel auth-panel">
        <p class="eyebrow">GET STARTED</p>
        <h1>Create an account</h1>
        <p class="muted">Register to take quizzes and save your results.</p>
        <?php if ($error !== '') { ?><div class="notice notice-error"><?php echo e($error); ?></div><?php } ?>
        <form method="post" action="register.php">
            <?php echo csrf_field(); ?>
            <label for="name">Your name</label>
            <input id="name" name="name" type="text" maxlength="100" autocomplete="name" required value="<?php echo e($name); ?>">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required value="<?php echo e($email); ?>">
            <label for="password">Password <span class="label-hint">(at least 8 characters)</span></label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
            <button class="button button-full" type="submit">Create account</button>
        </form>
        <p class="form-footnote">Already registered? <a href="login.php">Sign in</a></p>
    </section>
</main>
<?php page_footer(); ?>
