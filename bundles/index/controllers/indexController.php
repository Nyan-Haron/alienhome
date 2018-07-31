<?

class indexController extends baseController
{
    public function index()
    {
        $this->conf->devPrint('info', '/bundles/index/controllers/indexController->index() here');
        $this->request->setViewVariable('page', 'Home Page');
        $body = '';

        if ($this->checkAuth()) {
            $login = $this->authInfo['auth'];
            $token = $this->authInfo['authToken'];
            $subCheck = curl_init("https://api.twitch.tv/kraken/users/{$login}/subscriptions/bioalienr");
            curl_setopt(
                $subCheck,
                CURLOPT_HTTPHEADER,
                ["Client-ID: xe2vjszdqw8whaxxijhwmocndgr8en", "Authorization: OAuth {$token}"]
            );
            curl_setopt($subCheck, CURLOPT_RETURNTRANSFER, true);
            $subInfo = json_decode(curl_exec($subCheck), true);
            curl_close($subCheck);
            //  var_dump($subInfo);
            if ($subInfo === null) {
                header("Location: /auth/logout");
            }

            $body .= '<div id="header">
                <h1 id="caption">BioAlienR Home Page</h1>
                <div id="authInfo">
                    <span>You are logged in as <span id="auth_username" data-id="' . $_SESSION['id'] . '">' . $_SESSION['name'] . '</span></span>';
            if (@$subInfo['created_at']) {
                $_SESSION['sub'] = true;
                $body .= '<br/><span>You are subscribed to BioAlienR</span>';
            }
            $html['body'] .= '<form id="logOut"><input type="hidden" name="logout" value="logout" /><input type="submit" value="logout" /></form>';
            $html['body'] .= '</div></div>';
            $totalGamesCount = 0;
            $gamesCount = 0;
            $boostCount = 0;

            if ($_SESSION['sub'] == true) {
                $link = $db->query('SELECT * FROM poll_links ORDER BY `date` DESC LIMIT 1;')->fetch_assoc();
                $totalGamesCount = $db->query('SELECT count(*) FROM actions WHERE author_id = ' . $_SESSION['id'] . ';')->fetch_array()[0];
                $boostCount = $db->query('SELECT count(*) AS gamesCount FROM actions WHERE author_id = ' . $_SESSION['id'] . ' AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(date) < 2592000 AND action_type = "boost";')->fetch_array()[0];

                $tip = '1) Предлагая игры, делайте выбор разумно: вы можете предложить в первый месяц всего три игры.
2) После того, когда вы исчерпаете свои три предложения, каждый месяц вы сможете либо предложить еще одну игру, либо &quot;забустить&quot; одну из тех, что в списке.
3) Символ ЂЂЂ рядом с игрой означает, что она была &quot;забущена&quot;. Цифра рядом с ней показывает общее количество бустов этой игры.
4) &quot;Буст&quot; дает игре стопроцентный шанс появления в ближайших опросах вне очереди.
5) Каждая предложенная игра гарантированно появится в опросах дважды. Большее количество раз - по желанию стримера либо при помощи &quot;буста&quot;.';
                if ($totalGamesCount < 3) {
                    $html['body'] .= '<form action="/order_game">
      <span>Предложите игру (осталось ' . (3 - $totalGamesCount) . ' в этом месяце)</span>
      <img src="infoIcon.png" height="20" style="margin-bottom: -4px; cursor: help" title="' . $tip . '" />
      <input type="text" name="newGameTitle" placeholder="Название игры"/>
      <input type="submit" value="Предложить" />
      <a href="' . $link['link'] . '" style="float: right" target="_blank">Текущий опрос</a>
    </form>';
                } else {
                    $gamesCount = $db->query("SELECT count(*) AS gamesCount FROM actions WHERE author_id = " . $_SESSION['id'] . " AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(date) < 2592000;")->fetch_array()[0];
                    if ($gamesCount == 0) {
                        $html['body'] .= '<form action="/order_game">
          <span>Предложите игру (осталось 1 в этом месяце)</span>
          <img src="infoIcon.png" height="20" style="margin-bottom: -4px; cursor: help" title="' . $tip . '" />
          <input type="text" name="newGameTitle" placeholder="Название игры"/>
          <input type="submit" value="Предложить" />
          <a href="' . $link['link'] . '" style="float: right" target="_blank">Текущий опрос</a>
        </form>';
                    } else {
                        $html['body'] .= '<span>Вы больше не можете предлагать игры
          <img src="infoIcon.png" height="20" style="margin-bottom: -4px; cursor: help" title="' . $tip . '" />
          <a href="' . $link['link'] . '" style="float: right" target="_blank">Текущий опрос</a></span>';
                    }
                }
            } else {
                $html['body'] .= '<span>
        Вы не подписаны на <a href="http://twitch.tv/bioalienr">Twitch-канал</a> и не можете предлагать игры
        <a href="' . $link['link'] . '" style="float: right" target="_blank">Текущий опрос</a>
      </span>';
            }

            $games = $db->query('SELECT games.id, games.title, games.comment, users.twitch_id as author_id, users.username as author_name, UNIX_TIMESTAMP(games.order_date) AS order_date, games.poll_count, games.status_id, statuses.title AS status FROM games JOIN statuses ON (statuses.id = games.status_id) JOIN users on games.author_id = users.twitch_id ORDER BY statuses.`order`, order_date DESC;');
            $statuses = $db->query("SELECT * FROM statuses;");
            $statusesArr = [];
            while ($status = $statuses->fetch_assoc()) {
                $statusesArr[$status['id']] = $status;
            }

            $gameBoosts = $db->query("SELECT games.title FROM actions JOIN games ON (games.id = actions.game) WHERE action_type = 'boost'");
            $gameBoostsCounts = [];
            while ($gameBoost = $gameBoosts->fetch_assoc()) {
                if (!isset($gameBoostsCounts[$gameBoost['title']])) {
                    $gameBoostsCounts[$gameBoost['title']] = 1;
                } else {
                    $gameBoostsCounts[$gameBoost['title']]++;
                }
            }

            $gamesArr = [];
            while ($game = $games->fetch_assoc()) {
                $gamesArr[$game['status_id']][] = $game;
            }

            foreach ($gamesArr as $status => $statusedGames) {
                $gameStatus = $statusesArr[$status];

                $html['body'] .= '<div id="' . $gameStatus['code'] . '">
    <h3>' . $gameStatus['title'] . '<!--<img class="upButt" src="upIcon.png" alt="(up)"><img class="downButt" src="downIcon.png" alt="(down)" hidden>--></h3>';
                foreach ($statusedGames as $game) {
                    if ($game['author_id'] == $_SESSION['id']) {
                        $ownage = 'ourGame';
                    } else {
                        $ownage = 'otherGame';
                    }

                    $star = '';
                    if (isset($gameBoostsCounts[$game['title']])) {
                        $star = '<div class="starNvalue">
          <img class="star" src="starIconBlack.png" alt="(boost star)">
          <span class="starValue">' . $gameBoostsCounts[$game['title']] . '</span>
        </div>';
                    }
                    $boostButton = '';
                    if ($gameStatus['code'] == 'agree' && $gamesCount == 0 && $boostCount == 0) {
                        $boostButton = '
        <form class="boostButton" action="/boost_game" style="display: inline"><input name="game" value="' . $game['id'] . '" type="hidden"><input value="BOOST!" type="submit"></form>
        <div style="clear: both"></div>';
                    }

                    $html['body'] .= '<div class="game ' . $gameStatus['code'] . ' ' . $ownage . '">
            <span class="gameDate">' . date("Y-m-d", $game['order_date']) . '</span>
            <span class="pollTimes">В опросах: ' . $game['poll_count'] . ' раз(а)</span>
            <span class="gamePolls"></span>
            <span class="gameTitle"><span title="' . $game['title'] . '">' . $game['title'] . '</span>
              <br>by ' . $game['author_name'] . '</span>
            <p class="comm">Коммент: ' . (strlen($game['comment']) > 200 ? mb_substr($game['comment'], 0,
                                197) . '...' : $game['comment']) . '</p>
            <div class="buttonDiv">' . $star . $boostButton . '</div>
          </div>
      ';
                }

                $html['body'] .= '<div style="clear: both"></div></div>';
            }

        }

    }
}