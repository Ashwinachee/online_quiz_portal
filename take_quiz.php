<?php
require_once __DIR__ . '/config.php';
require_login();

$quizId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$quizId || $quizId < 1) {
    http_response_code(404);
    page_header('Quiz not found');
    echo '<main class="container"><div class="notice notice-error">That quiz could not be found.</div><a href="dashboard.php">Return to dashboard</a></main>';
    page_footer();
    exit;
}

$quizStatement = db()->prepare('SELECT id, title, description FROM quizzes WHERE id = ? AND is_published = 1 LIMIT 1');
$quizStatement->execute(array($quizId));
$quiz = $quizStatement->fetch();

if (!$quiz) {
    http_response_code(404);
    page_header('Quiz not found');
    echo '<main class="container"><div class="notice notice-error">That quiz could not be found.</div><a href="dashboard.php">Return to dashboard</a></main>';
    page_footer();
    exit;
}

$questionStatement = db()->prepare(
    'SELECT id, question_text, sort_order FROM questions WHERE quiz_id = ? ORDER BY sort_order, id'
);
$questionStatement->execute(array($quizId));
$questions = $questionStatement->fetchAll();

$optionStatement = db()->prepare('SELECT id, option_text FROM options WHERE question_id = ? ORDER BY id');
foreach ($questions as &$question) {
    $optionStatement->execute(array($question['id']));
    $question['options'] = $optionStatement->fetchAll();
}
unset($question);

page_header($quiz['title']);
?>
<main class="container narrow-container">
    <div class="page-heading">
        <div><p class="eyebrow">QUIZ</p><h1><?php echo e($quiz['title']); ?></h1>
            <?php if ($quiz['description'] !== '') { ?><p class="muted"><?php echo e($quiz['description']); ?></p><?php } ?>
        </div>
        <span class="tag"><?php echo count($questions); ?> questions</span>
    </div>

    <?php if (!$questions) { ?>
        <div class="empty-state"><h2>This quiz is not ready yet</h2><p>There are no questions available.</p><a href="dashboard.php">Back to dashboard</a></div>
    <?php } else { ?>
        <form method="post" action="submit_quiz.php" id="take-quiz-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="quiz_id" value="<?php echo (int) $quiz['id']; ?>">
            <?php foreach ($questions as $index => $question) { ?>
                <fieldset class="panel answer-card">
                    <legend><span class="question-number"><?php echo $index + 1; ?></span> <?php echo e($question['question_text']); ?></legend>
                    <div class="answer-options">
                        <?php foreach ($question['options'] as $option) { ?>
                            <label class="answer-option">
                                <input type="radio" name="answers[<?php echo (int) $question['id']; ?>]" value="<?php echo (int) $option['id']; ?>" required>
                                <span><?php echo e($option['option_text']); ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </fieldset>
            <?php } ?>
            <div class="quiz-submit-row">
                <a class="text-link" href="dashboard.php">Cancel</a>
                <button class="button" type="submit">Submit answers</button>
            </div>
        </form>
    <?php } ?>
</main>
<?php page_footer(); ?>
