<?

class pollsController extends baseController
{
    public function index()
    {
        $htmlPath = '/public/bundles/index/views/polls';

        $this->conf->devPrint('info', '/bundles/index/controllers/indexController->index() here');
        $this->loadHeader();
        $this->request->setViewVariable('page', 'Home Page');

        $optionTile = file_get_contents($this->conf->getPath("$htmlPath/optionTile.html"));

        if (!isset($this->request->get['poll_id'])) {
            $poll = $this->dbConn->query('SELECT id FROM polls ORDER BY major DESC, open_date DESC')->fetch_assoc();
            header('Location: /poll?' . http_build_query(['poll_id' => $poll['id']]));
        } else {
            $poll = $this->dbConn
                ->query('SELECT *, IF(close_date, TRUE, FALSE) AS closed FROM polls WHERE id = ' . ((int) $this->request->get['poll_id']))
                ->fetch_assoc();
        }

        if ($poll === null) {
            header('Location: /');
        }

        $this->request->setViewVariable('page', $poll['title']);

        $currentVote = $this->dbConn
            ->query("SELECT * FROM poll_votes WHERE poll_id = {$poll['id']} AND user_twitch_id = {$this->authInfo['id']}")
            ->fetch_assoc();

        if (!empty($this->request->post['vote']) && $currentVote === null && !$poll['closed'] && $this->authInfo['sub']) {
            $vote = $this->request->post['vote'];
            $option = $this->dbConn->query("SELECT * FROM poll_options WHERE poll_id = {$poll['id']} AND id = $vote")->fetch_assoc();
            if ($option !== null) {
                $subInfo = $this->getCurrentSubInfo($this->authInfo['id']);
                $weight = 1;
                if ($subInfo !== null) {
                    switch ($subInfo['tier']) {
                        case '1000':
                            $weight = 1;
                            break;
                        case '2000':
                            $weight = 2;
                            break;
                        case '3000':
                            $weight = 3;
                            break;
                    }
                }
                $this->dbConn->query("INSERT INTO poll_votes (user_twitch_id, poll_id, poll_option_id, weight) VALUES ({$this->authInfo['id']}, {$poll['id']}, $vote, $weight)");
            }
            header('Location: /poll?poll_id=' . $poll['id']);
        }

        $sums = $this->dbConn->query("SELECT COUNT(*), SUM(weight) FROM poll_votes WHERE poll_id = {$poll['id']}")->fetch_row();
        $sumVoteCount = (int) $sums[0];
        $sumVoteWeight = (int) $sums[1];

        $submitButton = '';
        $options = '';
        $r = $this->dbConn->query("SELECT po.*, SUM(pv.weight) AS voteCount FROM poll_options AS po
              LEFT JOIN poll_votes AS pv ON pv.poll_option_id = po.id
            WHERE po.poll_id = {$poll['id']}
            GROUP BY po.id");
        $optionsArr = [];
        while($option = $r->fetch_assoc()) {
            $option['order'] = (float) $option['voteCount'] * 2;
            if (strpos($option['title'], 'DANUNA') !== false) {
                $option['order'] += 1;
            }
            $optionsArr[] = $option;
        }

        usort($optionsArr, function (array $a, array $b) { return $b['order'] - $a['order']; });

        foreach ($optionsArr as $option) {
            $votePercent = $sumVoteWeight ? round($option['voteCount'] / $sumVoteWeight * 100, 2) : 0;
            $voteInfo = "Голосов:
                    <span class='votesCount'>{$option['voteCount']}</span>
                    (<span class='votesPercent'>$votePercent</span>%)";
            if (!$poll['closed'] && $currentVote === null && $this->authInfo['id'] && ($this->authInfo['sub'] || !$poll['sub_only'])) {
                $voteInfo .= '<input type="radio" class="voteRadio" name="vote" value="{{optionId}}">';
                $submitButton = '<button type="submit" id="pollSubmit">Подтвердить голос</button>';
            }

            $options .= $this->request->renderLayout($optionTile, [
                'id' => $option['id'],
                'width' => $votePercent,
                'title' => $option['title'],
                'desc' => $option['description'],
                'optionId' => $option['id'],
                'vote' => $voteInfo,
                'chosen' => $currentVote['poll_option_id'] == $option['id'] ? 'chosen' : ''
            ]);
        }

        $title = $poll['title'];
        if ($poll['closed']) {
            $title .= ' [Голосование закрыто]';
        }

        $this->request->setViewVariable('title', $title);
        $this->request->setViewVariable('alreadyVoted', $currentVote ? 'Вы уже проголосовали.' : '');
        $this->request->setViewVariable('overallVotes', $sumVoteCount);
        $this->request->setViewVariable('sumWeight', $sumVoteWeight);
        $this->request->setViewVariable('options', $options);
        $this->request->setViewVariable('pollId', $poll['id']);
        $this->request->setViewVariable('submitButton', $submitButton);
    }

    public function loadPollJson()
    {
        $this->request->setLayout('');

        if (array_key_exists('poll_id', $this->request->get)) {
            $pollId = $this->request->get['poll_id'];
            $votesArr = [];
            $r = $this->dbConn->query("SELECT COUNT(*) AS votesCount, SUM(weight) AS sumWeight, poll_option_id FROM poll_votes WHERE poll_id = $pollId GROUP BY poll_option_id");
            while ($vote = $r->fetch_assoc()) {
                $votesArr[$vote['poll_option_id']] = [
                    'count' => $vote['votesCount'],
                    'weight' => $vote['sumWeight']
                ];
            }

            print(json_encode($votesArr));
        }
    }
}