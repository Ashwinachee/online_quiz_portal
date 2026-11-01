<?php
require_once __DIR__ . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid()) {
    http_response_code(400);
    flash_set('error', 'Your quiz could not be submitted. Please try again.');
    redirect('dashboard.php');
}

$quizId = filter_var(isset($_POST['quiz_id']) ? $_POST['quiz_id'] : null, FILTER_VALIDATE_INT);
$submittedAnswers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : array();

if (!$quizId || $quizId < 1) {
    flash_set('error', 'The selected quiz is invalid.');
    redirect('dashboard.php');
}

$pdo = db();
$quizStatement = $pdo->prepare('SELECT id FROM quizzes WHERE id = ? AND is_published = 1 LIMIT 1');
$quizStatement->execute(array($quizId));
if (!$quizStatement->fetch()) {
    flash_set('error', 'That quiz is no longer available.');
    redirect('dashboard.php');
}

$questionStatement = $pdo->prepare('SELECT id FROM questions WHERE quiz_id = ? ORDER BY sort_order, id');
$questionStatement->execute(array($quizId));
$questionIds = array_map('intval', $questionStatement->fetchAll(PDO::FETCH_COLUMN));

if (!$questionIds || count($submittedAnswers) !== count($questionIds)) {
    flash_set('error', 'Please answer every question before submitting.');
    redirect('take_quiz.php?id=' . (int) $quizId);
}

$answerLookup = $pdo->prepare(
    'SELECT o.id, o.is_correct
     FROM options o
     INNER JOIN questions q ON q.id = o.question_id
     WHERE o.id = ? AND q.id = ? AND q.quiz_id = ?'
);
$answersToSave = array();
$score = 0;
foreach ($questionIds as $questionId) {
    if (!isset($submittedAnswers[$questionId]) || is_array($submittedAnswers[$questionId])) {
        flash_set('error', 'Please answer every question before submitting.');
        redirect('take_quiz.php?id=' . (int) $quizId);
    }

    $optionId = filter_var($submittedAnswers[$questionId], FILTER_VALIDATE_INT);
    if (!$optionId || $optionId < 1) {
        flash_set('error', 'One or more submitted answers are invalid.');
        redirect('take_quiz.php?id=' . (int) $quizId);
    }

    $answerLookup->execute(array($optionId, $questionId, $quizId));
    $option = $answerLookup->fetch();
    if (!$option) {
        flash_set('error', 'One or more submitted answers are invalid.');
        redirect('take_quiz.php?id=' . (int) $quizId);
    }
    $isCorrect = (int) $option['is_correct'] === 1;
    if ($isCorrect) {
        $score++;
    }
    $answersToSave[] = array(
        'question_id' => $questionId,
        'option_id' => $optionId,
        'is_correct' => $isCorrect ? 1 : 0,
    );
}

try {
    $pdo->beginTransaction();
    $attemptInsert = $pdo->prepare(
        'INSERT INTO attempts (user_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)'
    );
    $attemptInsert->execute(array($_SESSION['user_id'], $quizId, $score, count($questionIds)));
    $attemptId = (int) $pdo->lastInsertId();
    $answerInsert = $pdo->prepare(
        'INSERT INTO attempt_answers (attempt_id, question_id, selected_option_id, is_correct) VALUES (?, ?, ?, ?)'
    );
    foreach ($answersToSave as $answer) {
        $answerInsert->execute(array($attemptId, $answer['question_id'], $answer['option_id'], $answer['is_correct']));
    }
    $pdo->commit();
    redirect('result.php?id=' . $attemptId);
} catch (Exception $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Quiz submission failed: ' . $exception->getMessage());
    flash_set('error', 'Your result could not be saved. Please try submitting again.');
    redirect('take_quiz.php?id=' . (int) $quizId);
}
