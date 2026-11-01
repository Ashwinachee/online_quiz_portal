<?php
require_once __DIR__ . '/config.php';
require_login();

$attemptId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$attemptId || $attemptId < 1) {
    http_response_code(404);
    page_header('Result not found');
    echo '<main class="container"><div class="notice notice-error">That result could not be found.</div><a href="dashboard.php">Return to dashboard</a></main>';
    page_footer();
    exit;
}

$attemptStatement = db()->prepare(
    'SELECT a.id, a.user_id, a.score, a.total_questions, a.completed_at, q.id AS quiz_id, q.title
     FROM attempts a
     INNER JOIN quizzes q ON q.id = a.quiz_id
     WHERE a.id = ? LIMIT 1'
);
$attemptStatement->execute(array($attemptId));
$attempt = $attemptStatement->fetch();

if (!$attempt || ((int) $attempt['user_id'] !== (int) $_SESSION['user_id']
    && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'))) {
    http_response_code(404);
    page_header('Result not found');
    echo '<main class="container"><div class="notice notice-error">That result could not be found.</div><a href="dashboard.php">Return to dashboard</a></main>';
    page_footer();
    exit;
}

$percentage = (int) $attempt['total_questions'] > 0
    ? (int) round(((int) $attempt['score'] / (int) $attempt['total_questions']) * 100)
    : 0;
$answerStatement = db()->prepare(
    'SELECT q.question_text, selected.option_text AS selected_text, correct.option_text AS correct_text, aa.is_correct
     FROM attempt_answers aa
     INNER JOIN questions q ON q.id = aa.question_id
     INNER JOIN options selected ON selected.id = aa.selected_option_id
     INNER JOIN options correct ON correct.question_id = q.id AND correct.is_correct = 1
     WHERE aa.attempt_id = ?
     ORDER BY q.sort_order, q.id'
);
$answerStatement->execute(array($attemptId));
$answers = $answerStatement->fetchAll();

page_header('Quiz result');
?>
<main class="container narrow-container">
    <section class="panel result-summary">
        <p class="eyebrow">QUIZ COMPLETE</p>
        <h1><?php echo e($attempt['title']); ?></h1>
        <div class="score-circle"><strong><?php echo $percentage; ?>%</strong><span>score</span></div>
        <p class="score-line">You answered <strong><?php echo (int) $attempt['score']; ?> of <?php echo (int) $attempt['total_questions']; ?></strong> questions correctly.</p>
        <p class="muted">Completed <?php echo e(date('M j, Y \a\t H:i', strtotime($attempt['completed_at']))); ?></p>
        <a class="button" href="dashboard.php">Back to dashboard</a>
    </section>

    <section class="section-block">
        <div class="section-heading"><div><h2>Answer review</h2><p class="muted">Review each answer from this attempt.</p></div></div>
        <?php foreach ($answers as $index => $answer) { ?>
            <article class="panel review-card">
                <div class="review-heading"><span class="question-number"><?php echo $index + 1; ?></span><span class="review-status <?php echo (int) $answer['is_correct'] === 1 ? 'is-correct' : 'is-incorrect'; ?>"><?php echo (int) $answer['is_correct'] === 1 ? 'Correct' : 'Incorrect'; ?></span></div>
                <h3><?php echo e($answer['question_text']); ?></h3>
                <p>Your answer: <strong><?php echo e($answer['selected_text']); ?></strong></p>
                <?php if ((int) $answer['is_correct'] !== 1) { ?><p>Correct answer: <strong><?php echo e($answer['correct_text']); ?></strong></p><?php } ?>
            </article>
        <?php } ?>
    </section>
</main>
<?php page_footer(); ?>
