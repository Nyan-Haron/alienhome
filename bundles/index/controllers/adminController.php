<?

class adminController extends baseController
{
    public function index()
    {
        $htmlPath = '/public/bundles/index/views/admin';

        $this->checkSub();

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
            $this->dbConn->query("UPDATE polls SET title = '{$this->request->post['pollTitle']}' WHERE id = $pollId");
            $this->dbConn->query("DELETE FROM poll_options WHERE poll_id = $pollId");

            foreach ($this->request->post['optionTitles'] as $key => $title) {
                $description = $this->request->post['optionDescs'][$key];
                $this->dbConn->query("INSERT INTO poll_options (poll_id, title, description) VALUES ($pollId, '$title', '$description')");
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
            $votePercent = ($pollOption['voteCount']) ? round($sumVoteCount / $pollOption['voteCount'] * 100, 2) : 0;
            $tableRows .= "
            <tr>
                <td>
                    <input type='text' name='optionTitles[]' value='{$pollOption['title']}'>
                    <div class='gameList'></div>
                </td>
                <td><input type='text' name='optionDescs[]' value='{$pollOption['description']}' style='width: 100%'></td>
                <td><button type='button' class='removeOption'>-</button></td>
                <td>Голосов: {$pollOption['voteCount']} ($votePercent%)</td>
            </tr>
            ";
        }

        $this->request->setViewVariable('pollTitle', $poll['title']);
        $this->request->setViewVariable('tableRows', $tableRows);
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
}