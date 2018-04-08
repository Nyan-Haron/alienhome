<?

class Conf
{
    private $root = '';
    private $dbHost = '';
    private $dbUser = '';
    private $dbPwd = '';
    private $dbName = '';

    public function __construct($mode = 'prod')
    {
        $this->setRoot($_SERVER['DOCUMENT_ROOT'] . '/../');

        if ($mode == 'dev') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }

        $dbHost = $dbUser = $dbPwd = $dbName = '';

        require_once $this->getPath('conf.php');

        $this->setDb($dbHost, $dbUser, $dbPwd, $dbName);
    }

    private function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getPath($pathAddon)
    {
        return $this->root . $pathAddon;
    }

    private function setDb($host, $user, $pwd, $name)
    {
        $this->dbHost = $host;
        $this->dbUser = $user;
        $this->dbPwd = $pwd;
        $this->dbName = $name;

        return $this;
    }

    public function getDbHost()
    {
        return $this->dbHost;
    }

    public function getDbUser()
    {
        return $this->dbUser;
    }

    public function getDbPwd()
    {
        return $this->dbPwd;
    }

    public function getDbName()
    {
        return $this->dbName;
    }
}