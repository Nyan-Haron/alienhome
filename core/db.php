<?

class Db
{
    /**
     * @var mysqli
     */
    private $conn;

    /**
     * @param Conf $conf
     */
    public function __construct($conf)
    {
        $this->conn = mysqli_connect(
          $conf->getDbHost(),
          $conf->getDbUser(),
          $conf->getDbPwd(),
          $conf->getDbName()
        );
    }

    /**
     * @param string $q
     *
     * @return mysqli_result|bool
     */
    public function query($q) {
        $res = mysqli_query($this->conn, $q);
        return $res;
    }
}