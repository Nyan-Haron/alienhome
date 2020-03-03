<?

class Request
{
    private $routes = [
        '/' => 'index/index/index',
        '/auth' => 'index/auth/auth',
        '/login' => 'index/auth/index',
        '/logout' => 'index/auth/logout',
        '/cron' => 'index/index/cron',
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

        var_dump($request);
        $this->checkPath($request);
    }

    private function checkPath($path)
    {
        if (!array_key_exists($path, $this->routes)) exit('Wrong route');

        $pathParams = explode('/', $this->routes[$path]);
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
        $variableDifference = array_diff([$neededVariables[1]], array_keys($viewVariables));

        if (!empty($variableDifference)) {
            return 'ERROR! Не получится отрендерить шаблон: не хватает переменных: ' . json_encode($variableDifference);
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