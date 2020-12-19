<?

class indexController extends baseController
{
    public function index()
    {
        $maxBoost = 10;
        $htmlPath = '/public/bundles/index/views/index';

        $this->checkSub();

        $this->conf->devPrint('info', '/bundles/index/controllers/indexController->index() here');
        $this->loadHeader();
        $this->request->setViewVariable('page', 'Home Page');
        $this->request->setViewVariable('subCheck', '');
        if (!empty($this->authInfo)) {
            $this->request->setViewVariable('userId', $this->authInfo['id']);
            $this->request->setViewVariable('userName', $this->authInfo['name']);
        }

        $link = $this->dbConn->query('SELECT * FROM poll_links ORDER BY `date` DESC LIMIT 1;')->fetch_assoc();
        $this->request->setViewVariable('link', $link['link']);

        if (!empty($this->authInfo['sub'])) {
            $this->request->setViewVariable('subCheck', '<br/><span>You are subscribed to BioAlienR</span>');
        }

        $pointsInfo = helper::calculatePoints($this->authInfo['id'], $this->dbConn);
        $pointsCount = (int) $pointsInfo['points'];
        if ($this->authInfo['sub']) {
            $this->request->setViewVariable('pointsCount', $pointsInfo['points']);
            $this->request->setViewVariable('nextPointText', $pointsInfo['nextPointDate'] ? ', следующее: ' . $pointsInfo['nextPointDate'] : '');

            if ($pointsInfo['points'] > 0) {
                $this->request->setViewVariable('gameOrderForm', file_get_contents($this->conf->getPath("$htmlPath/gameOrderForm.html")));
            } else {
                $this->request->setViewVariable('gameOrderForm', file_get_contents($this->conf->getPath("$htmlPath/gameOrderFormNoPoints.html")));
            }
        } else {
            $this->request->setViewVariable('gameOrderForm', file_get_contents($this->conf->getPath("$htmlPath/gameOrderFormNoSub.html")));
        }


        $s = $this->dbConn->query("SELECT g.title AS game, s.title AS status, s.code AS status_code, DATE_FORMAT(gsl.change_date, '%d-%m-%Y') AS change_date FROM game_statuses_log AS gsl JOIN games AS g ON g.id = gsl.game JOIN statuses AS s ON gsl.status_id = s.id ORDER BY gsl.change_date DESC LIMIT 100");
        $statusHistoryTableContent = '';
        while ($statusHistoryRecord = $s->fetch_assoc()) {
            $statusHistoryTableContent .= '<tr class="'.$statusHistoryRecord['status_code'].'"><td>' . $statusHistoryRecord['game'] . '</td><td>Перенесено в "' . $statusHistoryRecord['status'] . '"</td><td>' . $statusHistoryRecord['change_date'] . '</td></tr>';
        }
        $statusHistoryTableContent .= '<tr><td colspan="3">Показаны последние 100 записей. Ну очень много за эти годы скопилось</td></tr>';
        $this->request->setViewVariable('statusHistoryTableContent', $statusHistoryTableContent);

        $games = $this->dbConn->query('SELECT games.id, games.title, games.comment, games.is_revived, users.twitch_id as author_id, users.username as author_name, UNIX_TIMESTAMP(games.order_date) AS order_date, games.poll_count, games.status_id, statuses.title AS status FROM games JOIN statuses ON (statuses.id = games.status_id) JOIN users on games.author_id = users.twitch_id ORDER BY statuses.`order` ASC, order_date DESC;');
        $statuses = $this->dbConn->query("SELECT * FROM statuses;");
        $statusesArr = [];
        while ($statusId = $statuses->fetch_assoc()) {
            $statusesArr[$statusId['id']] = $statusId;
        }

        $gameBoosts = $this->dbConn->query("SELECT games.title FROM actions JOIN games ON (games.id = actions.game) WHERE action_type = 'boost'");
        $gameBoostsCounts = [];
        while ($gameBoost = $gameBoosts->fetch_assoc()) {
            if (!isset($gameBoostsCounts[$gameBoost['title']])) {
                $gameBoostsCounts[$gameBoost['title']] = 1;
            } else {
                $gameBoostsCounts[$gameBoost['title']]++;
            }
        }

        $gamesArr = [];
        $boostedGames = 0;
        while ($game = $games->fetch_assoc()) {
            $gamesArr[$game['status_id']][] = $game;
            if ($statusesArr[$game['status_id']]['code'] == 'boost') $boostedGames++;
        }

        $gameTile = file_get_contents($this->conf->getPath("$htmlPath/gameTile.html"));
        $games = '';
        foreach ($gamesArr as $statusId => $statusedGames) {
            $groupStatus = $statusesArr[$statusId];

            $open = '';
            if ($groupStatus['code'] == 'agree' || $groupStatus['code'] == 'boost') {
                $open = 'open';
            }

            if ($groupStatus['code'] == 'boost') $games .= '<details ' . $open . '><summary><span class="statusTitle">' . $groupStatus['title'] . ' ('.$boostedGames.'/'.$maxBoost.')</span></summary><div id="' . $groupStatus['code'] . '">';
            else $games .= '<details ' . $open . '><summary><span class="statusTitle">' . $groupStatus['title'] . '</span></summary><div id="' . $groupStatus['code'] . '">';

            foreach ($statusedGames as $game) {
                if ($game['author_id'] == $this->authInfo['id']) {
                    $ownage = 'ourGame';
                } else {
                    $ownage = 'otherGame';
                }
                $gameStatus = $statusesArr[$game['status_id']];

                $star = '';
                if (isset($gameBoostsCounts[$game['title']])) {
                    $star = '<div class="starNvalue">
          <img class="star" src="/img/starIconBlack.png" alt="(boost star)">
          <span class="starValue">' . $gameBoostsCounts[$game['title']] . '</span>
        </div>';
                }
                $boostButton = '';
                if ($gameStatus['code'] == 'agree' && $pointsCount >= 1 && $maxBoost > $boostedGames) {
                    $boostButton = '
        <form class="boostButton" action="/boost_game" style="display: inline"><input name="game" value="' . $game['id'] . '" type="hidden"><input value="BOOST!" type="submit"></form>
        <div style="clear: both"></div>';
                }
                $reviveButton = '';
                if ($gameStatus['code'] == 'disagree' && $pointsCount >= 2 && $game['is_revived'] == 0) {
                    $reviveButton = '
        <form class="boostButton" action="/revive_game" style="display: inline"><input name="game" value="' . $game['id'] . '" type="hidden"><input value="HEROES NEVER DIE!" type="submit"></form>
        <div style="clear: both"></div>';
                }

                $statusCode = $gameStatus['code'];
                if ($game['is_revived']) $statusCode .= ' revived';
                $games .= $this->request->renderLayout($gameTile, [
                    'statusCode' => $statusCode,
                    'ownage' => $ownage,
                    'gameDate' => date("Y-m-d", $game['order_date']),
                    'pollCount' => $game['poll_count'],
                    'title' => $game['title'],
                    'authorName' => $game['author_name'],
                    'comment' => (strlen($game['comment']) > 200 ? mb_substr($game['comment'], 0, 197) . '...' : $game['comment']),
                    'star' => $star,
                    'boostButton' => $boostButton,
                    'reviveButton' => $reviveButton
                ]);
            }

            $games .= '<div style="clear: both"></div></div></details>';
        }

        $this->request->setViewVariable('games', $games);

    }

    public function boostGame() {
        $this->conf->devPrint('info', '/bundles/index/controllers/indexController->boost_game() here');
        $this->request->setViewVariable('page', 'Home Page');
        $this->request->setViewVariable('userId', $this->authInfo['id']);
        $this->request->setViewVariable('userName', $this->authInfo['name']);
        $this->request->setViewVariable('subCheck', '');
        $this->request->setViewVariable('body', 'Game has been boosted!');

        $user = $this->dbConn->query("SELECT * FROM users WHERE id = {$this->authInfo['id']}")->fetch_assoc();

        if ($this->authInfo['sub']  && $user['sub_points'] >= 1) {
            $game = $this->dbConn
                ->query('SELECT games.id, statuses.code FROM games JOIN statuses ON (statuses.id = games.status_id) WHERE games.id = ' . $this->dbConn->escape($this->request->get['game']))->fetch_assoc();

            if ($game['code'] == 'agree') {
                $this->dbConn->query("INSERT INTO actions (game, action_type, author_id) VALUES ({$this->dbConn->escape($this->request->get['game'])}, 'boost', {$this->authInfo['id']})");
                $boostStatus = $this->dbConn->query("SELECT * FROM statuses WHERE code = 'boost'")->fetch_assoc();
                $this->dbConn->query("UPDATE games SET status_id = {$boostStatus['id']}, status_change_date = NOW() WHERE id = " . $game['id']);
                $this->dbConn->query("INSERT INTO game_statuses_log (game, status_id, change_date) VALUES ({$game['id']}, {$boostStatus['id']}, NOW());");
                $this->dbConn->query("UPDATE users SET sub_points = sub_points - 1  WHERE twitch_id = {$this->authInfo['id']}");
            }
        }

        header("Location: /");
    }

    public function orderGame() {
        $this->conf->devPrint('info', '/bundles/index/controllers/indexController->order_game() here');
        $this->request->setViewVariable('page', 'Home Page');
        $this->request->setViewVariable('userId', $this->authInfo['id']);
        $this->request->setViewVariable('userName', $this->authInfo['name']);
        $this->request->setViewVariable('subCheck', '');
        $this->request->setViewVariable('body', 'Game has been ordered!');

        $user = $this->dbConn->query("SELECT * FROM users WHERE id = {$this->authInfo['id']}")->fetch_assoc();
        $gameTitle = $this->dbConn->escape($this->request->get['newGameTitle']);
        if ($this->authInfo['sub'] && $user['sub_points'] >= 1 && $gameTitle != '') {
            $authorId = $this->authInfo['id'];
            $this->dbConn->query("INSERT INTO games (title, author_id) VALUES ('$gameTitle', $authorId)");
            $lastGameId = $this->dbConn->query("SELECT id FROM games WHERE title = '$gameTitle' AND author_id = $authorId ORDER BY id DESC LIMIT 1;")->fetch_array()[0];
            $this->dbConn->query("INSERT INTO actions (game, action_type, author_id) VALUES ('$lastGameId', 'order', $authorId)");
            $this->dbConn->query("UPDATE users SET sub_points = sub_points - 1  WHERE twitch_id = $authorId");
        }

        header("Location: /");
    }

    public function reviveGame() {
        $this->conf->devPrint('info', '/bundles/index/controllers/indexController->revive_game() here');
        $this->request->setViewVariable('page', 'Home Page');
        $this->request->setViewVariable('userId', $this->authInfo['id']);
        $this->request->setViewVariable('userName', $this->authInfo['name']);
        $this->request->setViewVariable('subCheck', '');
        $this->request->setViewVariable('body', 'Game has been ordered!');

        $user = $this->dbConn->query("SELECT * FROM users WHERE id = {$this->authInfo['id']}")->fetch_assoc();
        $game = $this->dbConn->query('SELECT games.id, statuses.code, games.is_revived FROM games JOIN statuses ON (statuses.id = games.status_id) WHERE games.id = ' . $this->dbConn->escape($this->request->get['game']))->fetch_assoc();
        if ($this->authInfo['sub'] && $user['sub_points'] >= 3 && $game['is_revived'] == 0) {
            if ($game['code'] == 'disagree') {
                $this->dbConn->query("INSERT INTO actions (game, action_type, author_id) VALUES (" . $this->dbConn->escape($this->request->get['game']) . ", 'revive', {$this->authInfo['id']})");
                $agreeStatus = $this->dbConn->query("SELECT * FROM statuses WHERE code = 'agree'")->fetch_assoc();
                $this->dbConn->query("UPDATE games SET status_id = {$agreeStatus['id']}, status_change_date = NOW(), is_revived = 1 WHERE id = {$game['id']}");
                $revivedStatus = $this->dbConn->query("SELECT * FROM statuses WHERE code = 'revived'")->fetch_assoc();
                $this->dbConn->query("INSERT INTO game_statuses_log (game, status_id, change_date) VALUES ({$game['id']}, {$revivedStatus['id']}, NOW());");
                $this->dbConn->query("UPDATE users SET sub_points = sub_points - 3  WHERE twitch_id = {$this->authInfo['id']}");
            }
        }

        header("Location: /");
    }

}