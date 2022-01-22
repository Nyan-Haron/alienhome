let explosionDate = new Date();
explosionDate.setTime(1642878082000);
let interval = setInterval(function() {
    let now = new Date();
    let diff = Math.floor((explosionDate.getTime() - now.getTime()) / 1000);
    if (diff > 0) {
        let hours = Math.floor(diff / (60 * 60))
        let mins = Math.floor(diff / 60 % 60)
        let secs = Math.floor(diff % 60)
        $('#explosionCountdownHours').html(hours);
        $('#explosionCountdownMins').html(mins < 10 ? '0' + mins : mins);
        $('#explosionCountdownSecs').html(secs < 10 ? '0' + secs : secs);
    } else {
        $('#explosionCounter').html('BOOM, BITCH!');
        clearInterval(interval);
    }
}, 200);