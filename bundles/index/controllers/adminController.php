<?

class adminController extends baseController
{
    public function index()
    {
        $htmlPath = '/public/bundles/index/views/admin';

        $this->checkSub(!empty($this->request->get['mode']) && $this->request->get['mode'] === 'subcheck');

        $this->conf->devPrint('info', '/bundles/index/controllers/adminController->index() here');
        $this->request->setViewVariable('page', 'Admin Page');
        $this->request->setViewVariable('header', '');

        $checkDays = [];
        $d = $this->dbConn->query("SELECT DATE_FORMAT(check_date, '%Y-%m-%d') AS check_day FROM subscriptions WHERE user_twitch_id = 40955336 AND granted_point = FALSE ORDER BY check_date ASC");
        while ($checkDate = $d->fetch_assoc()) {
            $checkDays[$checkDate['check_day']] = 0;
        }

        if (isset($this->request->post['type']) && $this->request->post['type'] == "game") {
            $id = $this->dbConn->escape($this->request->post['id']);
            $title = $this->dbConn->escape($this->request->post['title']);
            $overallSubDays = $this->dbConn->escape($this->request->post['status']);
            $comment = $this->dbConn->escape($this->request->post['comment']);
            $poll_count = $this->dbConn->escape($this->request->post['poll_count']);
            $votes_count = $this->dbConn->escape($this->request->post['votes_count']);
            $oldGameState = $this->dbConn->query("SELECT * FROM games WHERE id = $id")->fetch_assoc();
            if ($oldGameState['status_id'] != $overallSubDays) {
                $this->dbConn->query("UPDATE games SET title = '$title', status_id = $overallSubDays, comment = '$comment', poll_count = $poll_count, votes = $votes_count, status_change_date = NOW() WHERE id = $id;");
                $this->dbConn->query('INSERT INTO game_statuses_log (game, status_id, change_date) VALUES (' . $id . ', ' . $overallSubDays . ', NOW());');
            } else {
                $this->dbConn->query("UPDATE games SET title = '$title', comment = '$comment', poll_count = $poll_count, votes = $votes_count WHERE id = $id;");
            }
        } else {
            if (isset($this->request->post['type']) && $this->request->post['type'] == "link") {
                $link = $this->dbConn->escape($this->request->post['link']);
                $this->dbConn->query(
                    'INSERT INTO poll_links SET link = "' . $link . '";'
                );
            }
        }

        if ($_SESSION['id'] === "40955336" || $_SESSION['id'] === "82304594" || $_SESSION['id'] === "50267699") {
            $q = $this->dbConn->query("SELECT * FROM statuses");
            $statuses = [];
            while ($overallSubDays = $q->fetch_assoc()) {
                $statuses[$overallSubDays['id']] = $overallSubDays;
            }

            $games = '';
            $r = $this->dbConn->query('SELECT games.* FROM games JOIN statuses ON (statuses.id = games.status_id) ORDER BY statuses.admin_order');
            while ($game = $r->fetch_assoc()) {
                $statusSelect = '<select name="status">';
                foreach ($statuses as $overallSubDays) {
                    if ($overallSubDays['id'] == $game['status_id']) {
                        $statusSelect .= '<option selected value="' . $overallSubDays['id'] . '">' . $overallSubDays['title'] . '</option>';
                    } else {
                        $statusSelect .= '<option value="' . $overallSubDays['id'] . '">' . $overallSubDays['title'] . '</option>';
                    }
                }
                $statusSelect .= '</select>';

                $gameTile = file_get_contents($this->conf->getPath("$htmlPath/gameTile.html"));
                $games .= $this->request->renderLayout($gameTile, [
                    'gameId' => $game['id'],
                    'gameTitle' => $game['title'],
                    'gameComment' => $game['comment'],
                    'gamePollCount' => $game['poll_count'],
                    'gameVotes' => $game['votes'],
                    'statusSelector' => $statusSelect
                ]);
            }

            $this->request->setViewVariable('games', $games);
        } else {
            // TODO: Пошёл в жёппу
        }
    }

    public function polls()
    {
        $htmlPath = '/public/bundles/index/views/admin';

        $this->conf->devPrint('info', '/bundles/index/controllers/adminController->polls() here');
        $this->request->setViewVariable('page', 'Опросы');
        $this->request->setViewVariable('header', '');

        if ($_SESSION['id'] === "40955336" || $_SESSION['id'] === "82304594" || $_SESSION['id'] === "50267699") {
            $tableRows = '';
            $r = $this->dbConn->query('SELECT * FROM polls');
            while ($row = $r->fetch_assoc()) {
                $formattedOpenDate = (new DateTime($row['open_date']))->format('Y-m-d H:i:s');
                $closeDate = new DateTime($row['close_date']);
                if ($closeDate->getTimestamp() > 0) {
                    $formattedCloseDate = $closeDate->format('Y-m-d H:i:s');
                } else {
                    $formattedCloseDate = '&mdash;';
                }
                $tableRows .= "
                <tr>
                    <td>{$row['id']}</td>
                    <td>{$row['title']}</td>
                    <td>$formattedOpenDate</td>
                    <td>$formattedCloseDate</td>
                    <td>
                        <a href='/bioadminr/polls/edit?id={$row['id']}'>Edit</a>
                        <a href='/bioadminr/polls/close?id={$row['id']}'>Close</a>
                    </td>
                </tr>
                ";
            }

            $this->request->setViewVariable('tableRows', $tableRows);
        } else {
            // TODO: Пошёл в жёппу
        }
    }

    public function editPoll()
    {
        $pollId = $this->request->get['id'];

        if (!empty($this->request->post)) {
            $isMajor = array_key_exists('isMajor', $this->request->post) ? 'TRUE' : 'FALSE';
            $isSubOnly = array_key_exists('isSubOnly', $this->request->post) ? 'TRUE' : 'FALSE';
            $this->dbConn->query("UPDATE polls SET title = '{$this->request->post['pollTitle']}', major = $isMajor, sub_only = $isSubOnly WHERE id = $pollId");

            $saveOptions = [];
            if (array_key_exists('optionTitles', $this->request->post)) {
                foreach ($this->request->post['optionTitles'] as $key => $title) {
                    $description = $this->request->post['optionDescs'][$key];
                    $this->dbConn->query("UPDATE poll_options SET title = '$title', description = '$description' WHERE id = $key");
                    $saveOptions[] = $key;
                }
            }

            $saveOptionsStr = implode(',', $saveOptions);
            $this->dbConn->query("DELETE FROM poll_options WHERE poll_id = $pollId AND id NOT IN ({$saveOptionsStr})");

            if (array_key_exists('newOptionTitles', $this->request->post)) {
                foreach ($this->request->post['newOptionTitles'] as $key => $title) {
                    $description = $this->request->post['newOptionDescs'][$key];
                    $this->dbConn->query("INSERT INTO poll_options (poll_id, title, description) VALUES ($pollId, '$title', '$description')");
                }
            }
        }

        $this->conf->devPrint('info', '/bundles/index/controllers/adminController->editPoll() here');
        $this->request->setViewVariable('page', 'Редактировать опрос');
        $this->request->setViewVariable('header', '');

        $poll = $this->dbConn->query("SELECT * FROM polls WHERE id = $pollId")->fetch_assoc();
        $sumVoteCount = (int) $this->dbConn->query("SELECT COUNT(*) FROM poll_votes WHERE poll_id = $pollId")->fetch_row()[0];

        $tableRows = '';
        $r = $this->dbConn->query("SELECT po.*, COUNT(pv.user_twitch_id) AS voteCount FROM poll_options AS po
              LEFT JOIN poll_votes AS pv ON pv.poll_option_id = po.id
            WHERE po.poll_id = $pollId
            GROUP BY po.id");
        while ($pollOption = $r->fetch_assoc()) {
            $votePercent = $sumVoteCount ? round($pollOption['voteCount'] / $sumVoteCount * 100, 2) : 0;
            $tableRows .= "
            <tr>
                <td>
                    <input type='text' name='optionTitles[{$pollOption['id']}]' value='{$pollOption['title']}'>
                    <div class='gameList'></div>
                </td>
                <td><input type='text' name='optionDescs[{$pollOption['id']}]' value='{$pollOption['description']}' style='width: 100%'></td>
                <td><button type='button' class='removeOption'>-</button></td>
                <td>Голосов: {$pollOption['voteCount']} ($votePercent%)</td>
            </tr>
            ";
        }

        $this->request->setViewVariable('pollTitle', $poll['title']);
        $this->request->setViewVariable('tableRows', $tableRows);
        $this->request->setViewVariable('isMajor', $poll['major'] ? 'checked' : '');
        $this->request->setViewVariable('isSubOnly', $poll['sub_only'] ? 'checked' : '');
    }

    public function closePoll()
    {
        $pollId = $this->request->get['id'];

        $this->conf->devPrint('info', '/bundles/index/controllers/adminController->closePoll() here');

        $poll = $this->dbConn->query("SELECT * FROM polls WHERE id = $pollId")->fetch_assoc();
        if ($poll !== null) {
            $this->dbConn->query("UPDATE polls SET close_date = NOW() WHERE id = $pollId");
        }

        header('Location: /bioadminr/polls');
    }

    public function createPoll()
    {
        $this->dbConn->query('INSERT INTO polls (title) VALUES ("Новое голосование")');
        $id = $this->dbConn->query('SELECT LAST_INSERT_ID() AS id')->fetch_assoc()['id'];
        header("Location: /bioadminr/polls/edit?id=$id");
    }

    public function givePoints() {
        $this->conf->devPrint('info', '/bundles/index/controllers/adminController->givePoints() here');
        $this->request->setViewVariable('page', 'Admin Page');
        $this->request->setViewVariable('header', '');
        $this->request->setViewVariable('subList', '');
        $this->request->setViewVariable('body', '');

        $checkDays = [];
        $d = $this->dbConn->query("SELECT DATE_FORMAT(check_date, '%Y-%m-%d') AS check_day FROM subscriptions WHERE user_twitch_id = 40955336 AND granted_point = FALSE ORDER BY check_date ASC");
        while ($checkDate = $d->fetch_assoc()) {
            $checkDays[$checkDate['check_day']] = 0;
        }

//        if ($this->isAuth && $this->authInfo['id'] == '82304594') { // Харон
        if ($this->isAuth && $this->authInfo['id'] == '40955336') { // Илья
            $thresholdDate = 1570838400; // 12.10.19 - дата ввода новой системы счёта поинтов
            $reviveThresholdDate = 1559520000; // 03.06.19 - дата увеличения стоимости ревайва
            $tableRows = [];
            $drawUsers = [];
            $q = $this->dbConn->query("SELECT * FROM users WHERE last_sub_date IS NOT NULL");
            while ($sub = $q->fetch_assoc()) {
                $personalCheckDays = $checkDays;
                $d = $this->dbConn->query("SELECT id, DATE_FORMAT(check_date, '%Y-%m-%d') AS check_day, overall_sub_days FROM subscriptions WHERE user_twitch_id = {$sub['twitch_id']} AND granted_point = FALSE ORDER BY check_date ASC");
                $firstOverallSubDays = 0;
                while ($subDate = $d->fetch_assoc()) {
                    $personalCheckDays[$subDate['check_day']] = $subDate['overall_sub_days'];
                    if ($firstOverallSubDays === 0) {
                        $firstOverallSubDays = $subDate['overall_sub_days'];
                    }
                }

                ksort($personalCheckDays);

                $pointsGained = floor($firstOverallSubDays / 30);
                $cumulativeSubDays = 0;
                $isFirstSubAfterThreshold = false;
                $prevRow = [
                    'timestamp' => 0,
                    'date' => '',
                    'sub_overall' => '-',
                    'sub_streak' => '-',
                    'event' => '',
                    'isPositive' => false
                ];

                // Расчёт затрат поинтов
                $a = $this->dbConn->query("SELECT a.*, g.title FROM actions AS a JOIN games AS g ON g.id = a.game WHERE a.author_id = {$sub['twitch_id']} ORDER BY a.date ASC");
                $actualUsedPoints = 0;
                $overallUsedPoints = 0;
                while ($action = $a->fetch_assoc()) {
                    $row = [
                        'date' => $action['date'],
                        'sub_overall' => '',
                        'sub_streak' => '',
                        'event' => '',
                    ];

                    $pointsUsage = 1;
                    if ($action['action_type'] == 'revive') {
                        $pointsUsage++;
                        if (strtotime($action['date']) > $reviveThresholdDate) {
                            $pointsUsage++;
                        }
                    }
                    $row['event'] = "-$pointsUsage поинт";

                    $overallUsedPoints += $pointsUsage;
                    if (strtotime($action['date']) >= $thresholdDate) {
                        $actualUsedPoints += $pointsUsage;
//                echo("{$sub['username']} {$action['date']} -{$pointsUsage} point <br>");
                        /*
                        $this->dbConn->query("UPDATE users SET sub_points = sub_points - {$pointsUsage} WHERE twitch_id = {$sub['twitch_id']}");
                        $tableRows[$sub['username']][] = $row;
                        */
                    }
                }

                if ($sub['sub_points'] !== '0' ||
                    $this->dbConn->query("SELECT id FROM subscriptions WHERE user_twitch_id = {$sub['twitch_id']} AND check_date < '" . array_keys($checkDays)[0] . "'")->fetch_array() !== null ||
                    $this->dbConn->query("SELECT id FROM actions WHERE author_id = {$sub['twitch_id']} AND date < '" . array_keys($checkDays)[0] . "'")->fetch_array() !== null
                ) {
//            echo "{$sub['username']} subbed before " . array_keys($checkDays)[0] . "<br>";
                    $isFirstSub = false;
                } else {
                    $isFirstSub = true;
                }

                foreach ($personalCheckDays as $checkDay => $cumulativeSubDays) {
                    $checkTimestamp = strtotime($checkDay);
                    $gap = $prevRow['timestamp'] === 0 ? 0 : ($checkTimestamp - $prevRow['timestamp']) / 86400;
                    $row = [
                        'timestamp' => $checkTimestamp,
                        'date' => $checkDay,
                        'sub_overall' => '-',
                        'sub_streak' => '-',
                        'event' => '',
                        'isPositive' => false
                    ];
                    if ($cumulativeSubDays > 0) {
//                $cumulativeSubDays = $cumulativeSubDays + ($prevRow['isPositive'] ? $gap : 1);
                        $row['sub_overall'] = $cumulativeSubDays;
                        $row['sub_streak'] = $prevRow['isPositive'] ? ($prevRow['sub_streak'] + $gap) : 1;
                        $drawUsers[$sub['username']] = true;
                        $row['isPositive'] = true;
//                $this->dbConn->query("UPDATE subscriptions SET overall_sub_days = {$cumulativeSubDays} WHERE user_twitch_id = {$sub['twitch_id']} AND DATE_FORMAT(check_date, '%Y-%m-%d') = '$checkDay'");
                    } else {
//                echo("{$sub['username']}_no_sub_true $checkDay <br>");
                        $this->dbConn->query("UPDATE subscriptions SET granted_point = TRUE WHERE user_twitch_id = {$sub['twitch_id']} AND DATE_FORMAT(check_date, '%Y-%m-%d') <= '$checkDay' AND granted_point = FALSE");
                        $row['event'] .= $prevRow['isPositive'] ? ' Стрик прерван' : '';
                    }

                    if ($cumulativeSubDays > 0 && $isFirstSub) {
                        $row['event'] = '+3 поинт';
                        $isFirstSub = false;

                        if ($checkTimestamp > $thresholdDate) {
                            $pointsGained += 3;
//                            $this->dbConn->query("UPDATE users SET sub_points = sub_points + 3 WHERE twitch_id = {$sub['twitch_id']}");
                            $isFirstSubAfterThreshold = true;
                        }
                    }

                    if ($isFirstSubAfterThreshold) $adaptedPoints = $pointsGained - 2;
                    else $adaptedPoints = $pointsGained + 1;

                    if ($prevRow['isPositive'] && $cumulativeSubDays >= $adaptedPoints * 30 && $overallUsedPoints >= 3) {
//                echo("{$sub['username']}_sub_true $checkDay +1 point <br>");
                        $this->dbConn->query("UPDATE subscriptions SET granted_point = TRUE WHERE user_twitch_id = {$sub['twitch_id']} AND DATE_FORMAT(check_date, '%Y-%m-%d') <= '$checkDay' AND granted_point = FALSE");
                        $this->dbConn->query("UPDATE users SET sub_points = sub_points + 1 WHERE twitch_id = {$sub['twitch_id']}");
                        $row['event'] = '+1 поинт';
                        $pointsGained++;
                    } elseif ($prevRow['isPositive'] && $cumulativeSubDays > 0 && $overallUsedPoints < 3) {
                        $this->dbConn->query("UPDATE subscriptions SET granted_point = TRUE WHERE user_twitch_id = {$sub['twitch_id']} AND DATE_FORMAT(check_date, '%Y-%m-%d') <= '$checkDay' AND granted_point = FALSE");
                    }

                    $tableRows[$sub['username']][] = $row;
                    $prevRow = $row;
                }

                usort($tableRows[$sub['username']], function($a, $b) {
                    return $a['date'] < $b['date'] ? -1 : 1;
                });
            }

            $pointsInfo = '<details>
        <summary>Информация о подписках</summary>
        <table>
            <tr>
                <th rowspan="2">Дата проверки</th>
                <th colspan="2">Подписка</th>
                <th rowspan="2">Событие</th>
            </tr>
            <tr>
                <th>Стрик, дней</th>
                <th>Всего, дней</th>
            </tr>';
            foreach ($tableRows as $username => $userTable) {
                if (!array_key_exists($username, $drawUsers)) continue;
                $pointsInfo .= "<tr>
                <th colspan='3'>$username</th>
            </tr>";
                foreach ($userTable as $tableRow) {
                    $pointsInfo .= "<tr>
                <td>{$tableRow['date']}</td>
                <td>{$tableRow['sub_streak']}</td>
                <td>{$tableRow['sub_overall']}</td>
                <td>{$tableRow['event']}</td>
            </tr>";
                }
            }
            $pointsInfo .= '</table></details>';

            $this->request->setViewVariable('body', $pointsInfo);
        }
    }
}