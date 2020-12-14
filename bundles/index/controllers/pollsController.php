<?

class pollsController extends baseController
{
    public function index()
    {
        $htmlPath = '/public/bundles/index/views/polls';

        $this->conf->devPrint('info', '/bundles/index/controllers/indexController->index() here');
        $this->loadHeader();
        $this->request->setViewVariable('page', 'Home Page');
        if (!empty($this->authInfo)) {
            $this->request->setViewVariable('userId', $this->authInfo['id']);
            $this->request->setViewVariable('userName', $this->authInfo['name']);
        }
        if (!empty($this->authInfo['sub'])) {
            $this->request->setViewVariable('subCheck', '<br/><span>You are subscribed to BioAlienR</span>');
        }

        $optionTile = file_get_contents($this->conf->getPath("$htmlPath/optionTile.html"));

        if (!isset($this->request->get['poll_id'])) {
            $poll = $this->dbConn->query('SELECT *, IF(close_date IS NULL, 0, 1) AS closed FROM polls ORDER BY open_date DESC')->fetch_assoc();
            header('Location: /poll?' . http_build_query(['poll_id' => $poll['id']]));
        } else {
            $poll = $this->dbConn->query('SELECT *, IF(close_date IS NULL, 0, 1) AS closed FROM polls WHERE id = ' . ((int) $this->request->get['poll_id']))->fetch_assoc();
        }

        if ($poll === null) {
            header('Location: /');
        }

        $currentVote = $this->dbConn->query("SELECT * FROM poll_votes WHERE poll_id = {$poll['id']} AND user_twitch_id = {$this->authInfo['id']}")->fetch_assoc();

        if (!empty($this->request->post['vote']) && $currentVote === null) {
            $vote = $this->request->post['vote'];
            $option = $this->dbConn->query("SELECT * FROM poll_options WHERE poll_id = {$poll['id']} AND id = $vote")->fetch_assoc();
            if ($option !== null) {
                $this->dbConn->query("INSERT INTO poll_votes (user_twitch_id, poll_id, poll_option_id) VALUES ({$this->authInfo['id']}, {$poll['id']}, $vote)");
            }
        }

        $sumVoteCount = (int) $this->dbConn->query("SELECT COUNT(*) FROM poll_votes WHERE poll_id = {$poll['id']}")->fetch_row()[0];

        $options = '';
        $r = $this->dbConn->query("SELECT po.*, COUNT(pv.user_twitch_id) AS voteCount FROM poll_options AS po
              LEFT JOIN poll_votes AS pv ON pv.poll_option_id = po.id
            WHERE po.poll_id = {$poll['id']}
            GROUP BY po.id ORDER BY voteCount DESC");
        while($option = $r->fetch_assoc()) {
            $votePercent = ($option['voteCount']) ? round($sumVoteCount / $option['voteCount'] * 100, 2) : 0;
            if ($poll['closed'] || $currentVote !== null) {
                $voteInfo = "Голосов: {$option['voteCount']} ($votePercent%)";
            } else {
                $voteInfo = '<button class="voteButton" type="submit" name="vote" value="{{optionId}}">Проголосовать</button>';
            }

            $options .= $this->request->renderLayout($optionTile, [
                'title' => $option['title'],
                'desc' => $option['description'],
                'optionId' => $option['id'],
                'vote' => $voteInfo
            ]);
        }

        $this->request->setViewVariable('title', $poll['title']);
        $this->request->setViewVariable('options', $options);
    }
}