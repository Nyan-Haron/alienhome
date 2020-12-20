<?

class Request
{
    private $routes = [
        '/' => 'index/index/index',
        '/auth' => 'index/auth/auth',
        '/bioadminr' => 'index/admin/index',
        '/bioadminr/polls' => 'index/admin/polls',
        '/bioadminr/polls/close' => 'index/admin/closePoll',
        '/bioadminr/polls/create' => 'index/admin/createPoll',
        '/bioadminr/polls/edit' => 'index/admin/editPoll',
        '/get_twitch_games' => 'index/index/getTwitchGames',
        '/boost_game' => 'index/index/boostGame',
        '/login' => 'index/auth/index',
        '/logout' => 'index/auth/logout',
        '/lk' => 'index/lk/index',
        '/order_game' => 'index/index/orderGame',
        '/poll' => 'index/polls/index',
        '/revive_game' => 'index/index/reviveGame'
    ];

    public $get = [];
    public $post = [];
    public $bundle = 'index';
    public $controller = 'index';
    public $method = 'index';
    public $layout = '';
    public $viewVariables = [];

    public function __construct($requestUri, $fileRoot)
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $request = explode('?', $requestUri)[0];

        $this->checkPath($request);
    }

    private function checkPath($path)
    {
        $truePath = '';
        if (array_key_exists($path, $this->routes)) {
            $truePath = $path;
        }

        if (strlen($path) > 1) {
            $path = preg_replace('/\/$/', '', $path);
            if (array_key_exists($path, $this->routes)) {
                $truePath = $path;
            }
        }
        if ($truePath === '') exit('Wrong route');

        $pathParams = explode('/', $this->routes[$truePath]);
        $this->bundle = $pathParams[0];
        $this->controller = $pathParams[1];
        $this->method = $pathParams[2];

        return true;
    }

    public function setLayout($html)
    {
        $this->layout = $html;
    }

    public function getLayout() {
        return $this->renderLayout($this->layout, $this->viewVariables);
    }

    public function renderLayout($layout, $viewVariables, $recursionDepth = 0)
    {
        if ($recursionDepth > 100) {
            return 'ERROR! Рекурсия при рендере шаблона достигла 100 уровня. Похоже, что-то пошло не так.';
        }

        $neededVariables = [];
        preg_match('/\{\{(\w+)\}\}/', $layout, $neededVariables);
        if (!empty($neededVariables)) {
            $variableDifference = array_diff([$neededVariables[1]], array_keys($viewVariables));

            if (!empty($variableDifference)) {
                return 'ERROR! Не получится отрендерить шаблон: не хватает переменных: ' . json_encode($variableDifference);
            }
        }

        foreach ($viewVariables as $k => $v) {
            $layout = str_replace(sprintf('{{%s}}', $k), $v, $layout);
        }

        if (preg_match('/\{\{\w+\}\}/', $layout, $neededVariables)) {
            $layout = $this->renderLayout($layout, $viewVariables, ++$recursionDepth);
        }

        return $layout;
    }

    public function setViewVariable($key, $value)
    {
        $this->viewVariables[$key] = $value;
    }
}