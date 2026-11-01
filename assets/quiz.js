(function () {
    'use strict';

    var addQuestionButton = document.getElementById('add-question');
    var questionList = document.getElementById('question-list');

    if (addQuestionButton && questionList) {
        addQuestionButton.addEventListener('click', function () {
            var index = questionList.querySelectorAll('.question-editor').length;
            var fieldset = document.createElement('fieldset');
            fieldset.className = 'question-editor';
            fieldset.innerHTML =
                '<legend>Question ' + (index + 1) + '</legend>' +
                '<label>Question text<textarea name="questions[' + index + '][text]" rows="2" maxlength="2000" required></textarea></label>' +
                '<div class="option-fields">' +
                    '<label>Choice A<input name="questions[' + index + '][options][]" type="text" maxlength="500" required></label>' +
                    '<label>Choice B<input name="questions[' + index + '][options][]" type="text" maxlength="500" required></label>' +
                    '<label>Choice C<input name="questions[' + index + '][options][]" type="text" maxlength="500" required></label>' +
                    '<label>Choice D<input name="questions[' + index + '][options][]" type="text" maxlength="500" required></label>' +
                '</div>' +
                '<label>Correct choice<select name="questions[' + index + '][correct]" required>' +
                    '<option value="0">Choice A</option><option value="1">Choice B</option>' +
                    '<option value="2">Choice C</option><option value="3">Choice D</option>' +
                '</select></label>';
            questionList.appendChild(fieldset);
            fieldset.querySelector('textarea').focus();
        });
    }
}());
