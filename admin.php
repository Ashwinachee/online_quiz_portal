<?php

require_once __DIR__ . '/config.php';

require_admin();

// Database connection
$pdo = db();


// =====================================
// FETCH STUDENT QUIZ RESULTS
// =====================================

$resultStatement = $pdo->query("
    SELECT
        users.name,
        users.email,
        quizzes.title,
        attempts.score,
        attempts.total_questions,

        ROUND(
            (attempts.score / NULLIF(attempts.total_questions, 0)) * 100,
            2
        ) AS percentage

    FROM attempts

    INNER JOIN users
        ON attempts.user_id = users.id

    INNER JOIN quizzes
        ON attempts.quiz_id = quizzes.id

    ORDER BY attempts.id DESC
");

$results = $resultStatement->fetchAll(PDO::FETCH_ASSOC);


// =====================================
// QUIZ CREATION
// =====================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_is_valid()) {

        $error = 'Your session expired. Please refresh the page and try again.';

    } else {

        $title = trim($_POST['title'] ?? '');

        $description = trim($_POST['description'] ?? '');

        $questions = $_POST['questions'] ?? array();


        if ($title === '' || strlen($title) > 200) {

            $error = 'Enter a valid quiz title.';

        } elseif (strlen($description) > 2000) {

            $error = 'Description is too long.';

        } elseif (!is_array($questions) || count($questions) < 1) {

            $error = 'Add at least one question.';

        } else {

            $validatedQuestions = array();


            // Validate questions

            foreach ($questions as $question) {

                if (!is_array($question)) {

                    $error = 'Invalid question data.';
                    break;

                }

                $questionText = trim($question['text'] ?? '');

                $options = $question['options'] ?? array();

                $correct = (string)($question['correct'] ?? '');


                if (
                    $questionText === '' ||
                    strlen($questionText) > 2000 ||
                    !is_array($options) ||
                    count($options) !== 4 ||
                    !in_array($correct, array('0', '1', '2', '3'), true)
                ) {

                    $error = 'Each question needs text, four choices and one correct answer.';
                    break;

                }


                $cleanOptions = array();


                foreach (array_values($options) as $option) {

                    $option = trim((string)$option);

                    if ($option === '' || strlen($option) > 500) {

                        $error = 'Each option must contain 1 to 500 characters.';
                        break 2;

                    }

                    $cleanOptions[] = $option;

                }


                $validatedQuestions[] = array(

                    'text' => $questionText,

                    'options' => $cleanOptions,

                    'correct' => (int)$correct

                );

            }


            // Save quiz

            if ($error === '') {

                try {

                    $pdo->beginTransaction();


                    // Insert quiz

                    $quizInsert = $pdo->prepare("
                        INSERT INTO quizzes
                        (title, description, created_by)
                        VALUES (?, ?, ?)
                    ");

                    $quizInsert->execute(array(

                        $title,

                        $description,

                        $_SESSION['user_id']

                    ));


                    $quizId = (int)$pdo->lastInsertId();


                    // Insert questions

                    $questionInsert = $pdo->prepare("
                        INSERT INTO questions
                        (quiz_id, question_text, sort_order)
                        VALUES (?, ?, ?)
                    ");


                    // Insert options

                    $optionInsert = $pdo->prepare("
                        INSERT INTO options
                        (question_id, option_text, is_correct)
                        VALUES (?, ?, ?)
                    ");


                    foreach ($validatedQuestions as $index => $question) {

                        $questionInsert->execute(array(

                            $quizId,

                            $question['text'],

                            $index + 1

                        ));


                        $questionId = (int)$pdo->lastInsertId();


                        foreach ($question['options'] as $optionIndex => $optionText) {

                            $optionInsert->execute(array(

                                $questionId,

                                $optionText,

                                ($optionIndex === $question['correct']) ? 1 : 0

                            ));

                        }

                    }


                    $pdo->commit();


                    flash_set('success', 'Quiz created successfully.');

                    redirect('admin.php');


                } catch (Exception $exception) {

                    if ($pdo->inTransaction()) {

                        $pdo->rollBack();

                    }

                    error_log($exception->getMessage());

                    $error = 'Quiz could not be created. Please check the database.';

                }

            }

        }

    }

}


// =====================================
// FETCH EXISTING QUIZZES
// =====================================

$quizStatement = $pdo->query("

    SELECT

        q.id,

        q.title,

        q.created_at,

        (
            SELECT COUNT(*)
            FROM questions
            WHERE quiz_id = q.id
        ) AS question_count

    FROM quizzes q

    ORDER BY q.created_at DESC

");

$quizzes = $quizStatement->fetchAll(PDO::FETCH_ASSOC);


// Display header

page_header('Quiz administration');

?>


<main class="container">


    <!-- ADMIN HEADING -->

    <div class="page-heading">

        <div>

            <p class="eyebrow">PORTAL MANAGEMENT</p>

            <h1>Quiz Administration</h1>

            <p class="muted">
                Create quizzes and manage your learning content.
            </p>

        </div>

    </div>


    <!-- ERROR MESSAGE -->

    <?php if ($error !== '') { ?>

        <div class="notice notice-error">

            <?php echo e($error); ?>

        </div>

    <?php } ?>


    <!-- CREATE QUIZ -->

    <section class="panel admin-form-panel">

        <div class="section-heading">

            <div>

                <h2>Create a Quiz</h2>

                <p class="muted">
                    Add questions with four answer choices.
                </p>

            </div>

        </div>


        <form method="post" action="admin.php" id="quiz-form">

            <?php echo csrf_field(); ?>


            <label for="title">Quiz Title</label>

            <input
                id="title"
                name="title"
                type="text"
                maxlength="200"
                required
            >


            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                rows="3"
                maxlength="2000"
            ></textarea>


            <!-- QUESTIONS -->

            <div id="question-list">


                <fieldset class="question-editor">

                    <legend>Question 1</legend>


                    <label>

                        Question Text

                        <textarea
                            name="questions[0][text]"
                            rows="2"
                            maxlength="2000"
                            required
                        ></textarea>

                    </label>


                    <div class="option-fields">


                        <label>
                            Choice A

                            <input
                                name="questions[0][options][]"
                                type="text"
                                required
                            >

                        </label>


                        <label>
                            Choice B

                            <input
                                name="questions[0][options][]"
                                type="text"
                                required
                            >

                        </label>


                        <label>
                            Choice C

                            <input
                                name="questions[0][options][]"
                                type="text"
                                required
                            >

                        </label>


                        <label>
                            Choice D

                            <input
                                name="questions[0][options][]"
                                type="text"
                                required
                            >

                        </label>


                    </div>


                    <label>

                        Correct Answer

                        <select name="questions[0][correct]" required>

                            <option value="0">Choice A</option>

                            <option value="1">Choice B</option>

                            <option value="2">Choice C</option>

                            <option value="3">Choice D</option>

                        </select>

                    </label>


                </fieldset>


            </div>


            <div class="form-actions">

                <button
                    class="button button-secondary"
                    type="button"
                    id="add-question"
                >
                    Add Another Question
                </button>


                <button class="button" type="submit">

                    Publish Quiz

                </button>

            </div>


        </form>

    </section>



    <!-- EXISTING QUIZZES -->

    <section class="section-block">

        <div class="section-heading">

            <div>

                <h2>Existing Quizzes</h2>

                <p class="muted">
                    All quizzes currently available.
                </p>

            </div>

        </div>


        <?php if (!$quizzes) { ?>

            <div class="empty-state compact">

                <p>No quizzes have been created.</p>

            </div>

        <?php } else { ?>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Quiz Title</th>

                            <th>Questions</th>

                            <th>Created</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($quizzes as $quiz) { ?>

                            <tr>

                                <td>
                                    <?php echo e($quiz['title']); ?>
                                </td>

                                <td>
                                    <?php echo (int)$quiz['question_count']; ?>
                                </td>

                                <td>
                                    <?php echo e(date(
                                        'd M Y',
                                        strtotime($quiz['created_at'])
                                    )); ?>
                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>


        <?php } ?>


    </section>



    <!-- STUDENT RESULTS -->

    <section class="section-block">

        <div class="section-heading">

            <div>

                <h2>Student Quiz Results</h2>

                <p class="muted">
                    View all completed student examinations.
                </p>

            </div>

        </div>


        <?php if (count($results) > 0) { ?>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Student Name</th>

                            <th>Email</th>

                            <th>Quiz</th>

                            <th>Score</th>

                            <th>Percentage</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($results as $row) { ?>

                            <tr>

                                <td>
                                    <?php echo e($row['name']); ?>
                                </td>

                                <td>
                                    <?php echo e($row['email']); ?>
                                </td>

                                <td>
                                    <?php echo e($row['title']); ?>
                                </td>

                                <td>

                                    <?php echo (int)$row['score']; ?>

                                    /

                                    <?php echo (int)$row['total_questions']; ?>

                                </td>

                                <td>

                                    <strong>
                                        <?php echo e($row['percentage']); ?>%
                                    </strong>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>


        <?php } else { ?>


            <div class="empty-state compact">

                <p>No student results available yet.</p>

            </div>


        <?php } ?>


    </section>


</main>


<?php page_footer(); ?>