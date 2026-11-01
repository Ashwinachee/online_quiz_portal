<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'online_quiz_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

function db()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ));
    }

    return $pdo;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        flash_set('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_admin()
{
    require_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        page_header('Access denied');
        echo '<main class="container"><div class="notice notice-error">You do not have permission to access this page.</div></main>';
        page_footer();
        exit;
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_is_valid()
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}

function flash_set($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function flash_get()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function page_header($title)
{
    $flash = flash_get();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e($title); ?> | Online Quiz Portal</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="index.php"><span class="brand-mark">Q</span> Quiz Portal</a>
        <nav class="site-nav" aria-label="Main navigation">
            <?php if (is_logged_in()) { ?>
                <a href="dashboard.php">Dashboard</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                    <a href="admin.php">Admin</a>
                <?php } ?>
                <span class="nav-user"><?php echo e(isset($_SESSION['name']) ? $_SESSION['name'] : 'Account'); ?></span>
                <form class="nav-logout" method="post" action="logout.php">
                    <?php echo csrf_field(); ?>
                    <button class="nav-link" type="submit">Sign out</button>
                </form>
            <?php } else { ?>
                <a href="login.php">Sign in</a>
                <a class="nav-register" href="register.php">Register</a>
            <?php } ?>
        </nav>
    </header>
    <?php if ($flash) { ?>
        <div class="flash-wrap">
            <div class="notice notice-<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
        </div>
    <?php } ?>
    <?php
}

function page_footer()
{
    ?>
    <footer class="site-footer">Online Quiz Portal</footer>
    <script src="assets/quiz.js"></script>
    </body>
    </html>
    <?php
}
