$(document).ready(function () {
    let optionTemplate = $('#pollOptionTemplate');

    $('#addOption').click(function () {
        let newOption = optionTemplate.clone();
        newOption[0].removeAttribute('id');
        newOption.find('.title')[0].setAttribute('name', 'newOptionTitles[]');
        newOption.find('.desc')[0].setAttribute('name', 'newOptionDescs[]');
        $('#lastRow').before(newOption);
    });

    $(document).on('click', '.removeOption', function () {
        $(this).closest('tr').remove();
    });

    $(document).on('keyup', '#editPollTable input.title', function () {
        let input = $(this);
        if (input.val().length > 3) {
            $.get({
                url: '/get_twitch_games',
                data: {query: $(this).val()},
                dataType: 'json',
                success: function (data) {
                    input.closest('td').find('.gameList *').remove();
                    for (k in data.data) {
                        let gameData = data.data[k];
                        input.closest('td').find('.gameList').append('<button type="button" class="twitchGame" data-game="' + gameData.name + '">' + gameData.name + '</button>')
                    }
                }
            });
        }
    });

    $(document).on('blur', '#editPollTable input.title', function () {
        let input = $(this);
        setTimeout(function () {
            input.closest('td').find('.gameList *').remove();
        }, 500)
    });

    $(document).on('click', '.twitchGame', function () {
        $(this).closest('td').find('input.title').val($(this).data('game'));
        $('.gameList *').remove();
    });
})