<?php
require_once __DIR__ . '/config.php';
require_login();

$quizStatement = db()->query(
    'SELECT q.id, q.title, q.description, q.created_at,
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count
     FROM quizzes q
     WHERE q.is_published = 1
     ORDER BY q.created_at DESC'
);
$quizzes = $quizStatement->fetchAll();

$resultStatement = db()->prepare(
    'SELECT a.id, a.score, a.total_questions, a.completed_at, q.title
     FROM attempts a
     INNER JOIN quizzes q ON q.id = a.quiz_id
     WHERE a.user_id = ?
     ORDER BY a.completed_at DESC
     LIMIT 10'
);
$resultStatement->execute(array($_SESSION['user_id']));
$results = $resultStatement->fetchAll();

page_header('Dashboard');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">YOUR LEARNING SPACE</p>
            <h1>Hello, <?php echo e($_SESSION['name']); ?></h1>
            <p class="muted">Choose a quiz below or review your recent results.</p>
        </div>
    </div>

    <section class="section-block">
        <div class="section-heading">
            <div><h2>Available quizzes</h2><p class="muted">Pick a quiz to get started.</p></div>
        </div>
        <?php if (!$quizzes) { ?>
            <div class="empty-state"><h3>No quizzes yet</h3><p>Check back soon for new quizzes.</p></div>
        <?php } else { ?>
            <div class="card-grid">
                <?php foreach ($quizzes as $quiz) { ?>
                    <article class="panel quiz-card">
                        <div class="quiz-card-top"><span class="tag"><?php echo (int) $quiz['question_count']; ?> questions</span></div>
                        <h3><?php echo e($quiz['title']); ?></h3>
                        <p><?php echo e($quiz['description'] !== '' ? $quiz['description'] : 'Test your knowledge with this quiz.'); ?></p>
                        <?php if ((int) $quiz['question_count'] > 0) { ?>
                            <a class="button" href="take_quiz.php?id=<?php echo (int) $quiz['id']; ?>">Start quiz</a>
                        <?php } else { ?>
                            <span class="muted">Questions are being added.</span>
                        <?php } ?>
                    </article>
                <?php } ?>
            </div>
        <?php } ?>
    </section>

    <section class="section-block">
        <div class="section-heading"><div><h2>Recent results</h2><p class="muted">Your latest completed quizzes.</p></div></div>
        <?php if (!$results) { ?>
            <div class="empty-state compact"><p>You have not completed a quiz yet.</p></div>
        <?php } else { ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Quiz</th><th>Score</th><th>Completed</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $result) { ?>
                        <tr>
                            <td><?php echo e($result['title']); ?></td>
                            <td><?php echo (int) $result['score']; ?> / <?php echo (int) $result['total_questions']; ?></td>
                            <td><?php echo e(date('M j, Y H:i', strtotime($result['completed_at']))); ?></td>
                            <td><a href="result.php?id=<?php echo (int) $result['id']; ?>">View result</a></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </section>
</main>
<?php page_footer(); ?>
