<?

class Conf
{
    private $root = '';
    private $publicFolder = '';
    private $dbHost = '';
    private $dbUser = '';
    private $dbPwd = '';
    private $dbName = '';
    private $env = 'prod';

    public function __construct($mode = 'prod')
    {
        $this->setRoot($_SERVER['DOCUMENT_ROOT'] . '/..')
            ->setPublicFolder($_SERVER['DOCUMENT_ROOT']);

        if ($mode == 'dev') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }

        $dbHost = $dbUser = $dbPwd = $dbName = '';
        $env = 'prod';

        require_once $this->getPath('/conf.php');

        $this->setDb($dbHost, $dbUser, $dbPwd, $dbName);
        $this->setEnv($env);
    }

    private function setPublicFolder($publicFolder)
    {
        $this->publicFolder = $publicFolder;

        return $this;
    }

    public function getPublicFolder()
    {
        return $this->publicFolder;
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

    private function setEnv($env)
    {
        $this->env = $env;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function devPrint($name = '', $var)
    {
        if ($this->getEnv() == 'dev') {
            printf('<p>%s:</p><pre>', $name);
            var_dump($var);
            print '</pre>';
        }
    }
}