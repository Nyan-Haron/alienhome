$(document).ready(function () {
    let optionTemplate = $('#pollOptionTemplate');

    $('#addOption').click(function () {
        let newOption = optionTemplate.clone();
        newOption[0].removeAttribute('id');
        newOption.find('.title')[0].setAttribute('name', 'optionTitles[]');
        newOption.find('.desc')[0].setAttribute('name', 'optionDescs[]');
        $('#lastRow').before(newOption);
    });

    $(document).on('click', '.removeOption', function () {
        $(this).closest('tr').remove();
    });
})