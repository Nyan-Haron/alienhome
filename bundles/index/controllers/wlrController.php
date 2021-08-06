<?

class wlrController extends baseController
{
    public function index()
    {
        $htmlPath = '/public/bundles/index/views/wlr';

        $this->request->setLayout(file_get_contents($this->conf->getPath("$htmlPath/index.html")));

        $rate = $this->dbConn->query("SELECT * FROM siege_win_rate")->fetch_assoc();

        $this->request->setViewVariable('win', $rate['win']);
        $this->request->setViewVariable('lose', $rate['lose']);
    }

    public function forget() {
        $this->request->setLayout('');

        $this->dbConn->query("UPDATE siege_win_rate SET lose = 0, win = 0");
        $phrase = $this->dbConn->query("SELECT * FROM phrase_pool WHERE type = 'forget' ORDER BY RAND() LIMIT 1;")->fetch_assoc()['phrase'];

        printf("%s // Текущий счёт: 0-0", $phrase);
    }

    public function lose() {
        $this->request->setLayout('');

        $this->dbConn->query("UPDATE siege_win_rate SET lose = lose + 1");
        $rate = $this->dbConn->query("SELECT * FROM siege_win_rate")->fetch_assoc();
        $phrase = $this->dbConn->query("SELECT * FROM phrase_pool WHERE type = 'lose' ORDER BY RAND() LIMIT 1;")->fetch_assoc()['phrase'];

        printf("%s // Текущий счёт: %d-%d", $phrase, $rate['win'], $rate['lose']);
    }

    public function moderate() {
        $htmlPath = '/public/bundles/index/views/wlr';

        $this->request->setLayout('');

        $moders = [(string) $this->coolGuy, (string) $this->kewlProgrammer, "165373962", "59271050", "101009981", "50267699", "60632525", "84508869", "51198830"];

        if (in_array($this->authInfo['id'], $moders)) {
            if (!empty($_POST)) {
                $win = $_POST['win'];
                $lose = $_POST['lose'];
                $this->dbConn->query("UPDATE siege_win_rate SET win = {$win}, lose = {$lose}");

                header('Location: /r6s_wlr/moderate');
            }

            $this->request->setLayout(file_get_contents($this->conf->getPath("$htmlPath/moderate.html")));

            $rate = $this->dbConn->query("SELECT * FROM siege_win_rate")->fetch_assoc();

            $this->request->setViewVariable('win', $rate['win']);
            $this->request->setViewVariable('lose', $rate['lose']);
        }
    }

    public function win() {
        $this->request->setLayout('');

        $this->dbConn->query("UPDATE siege_win_rate SET win = win + 1");
        $rate = $this->dbConn->query("SELECT * FROM siege_win_rate")->fetch_assoc();
        $phrase = $this->dbConn->query("SELECT * FROM phrase_pool WHERE type = 'win' ORDER BY RAND() LIMIT 1;")->fetch_assoc()['phrase'];

        printf("%s // Текущий счёт: %d-%d", $phrase, $rate['win'], $rate['lose']);
    }

    public function load()
    {
        $this->request->setLayout('');
        $rate = $this->dbConn->query("SELECT * FROM siege_win_rate")->fetch_assoc();
        echo json_encode(['win' => $rate['win'], 'lose' => $rate['lose']]);
    }
}