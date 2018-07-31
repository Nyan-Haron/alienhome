<?

class Request
{
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
        $request = explode('/', $requestUri);

        if ($requestUri != '/') {
            if (count($request) >= 4) {
                $this->bundle = $request[1];
                $this->controller = $request[2];
                $this->method = $request[3];
            } elseif (count($request) == 3) {
                $this->checkPath([$request[1], $request[2]], $fileRoot);
            } elseif (count($request) == 2) {
                $this->checkPath([$request[1]], $fileRoot);
            } else {
                exit('Wrong route');
            }
        }
    }

    private function checkPath($pathParams, $fileRoot)
    {
        if (count($pathParams) == 2) {
            if (glob($fileRoot . 'index/controllers/' . $pathParams[0] . 'Controller.php')) {
                $controllerName = sprintf('%sController', $pathParams[0]);
                include $fileRoot . 'index/controllers/' . $controllerName . '.php';
                $controller = new $controllerName(null, null);
                if (method_exists($controller, $pathParams[1])) {
                    $this->controller = $pathParams[0];
                    $this->method = $pathParams[1];
                } else {
                    exit('Wrong route');
                }
            } elseif (glob($fileRoot . $pathParams[0] . '/controllers/' . $pathParams[1] . 'Controller.php')) {
                $controllerName = sprintf('%sController', $pathParams[1]);
                include $fileRoot . $pathParams[0] . 'index/controllers/' . $controllerName . '.php';
                $controller = new $controllerName(null, null);
                if (method_exists($controller, 'index')) {
                    $this->bundle = $pathParams[0];
                    $this->controller = $pathParams[1];
                } else {
                    exit('Wrong route');
                }
            } elseif (glob($fileRoot . $pathParams[0] . '/controllers/indexController.php')) {
                include $fileRoot . $pathParams[0] . '/controllers/indexController.php';
                $controller = new indexController(null, null);
                if (method_exists($controller, $pathParams[1])) {
                    $this->bundle = $pathParams[0];
                    $this->method = $pathParams[1];
                } else {
                    exit('Wrong route');
                }
            }
        } else {
            if (count($pathParams) == 1) {
                if (glob($fileRoot . $pathParams[0] . '/controllers/indexController.php')) {
                    include $fileRoot . $pathParams[0] . '/controllers/indexController.php';
                    $controller = new indexController(null, null);
                    if (method_exists($controller, 'index')) {
                        $this->bundle = $pathParams[0];
                    } else {
                        exit('Wrong route');
                    }
                } elseif (glob($fileRoot . 'index/controllers/' . $pathParams[0] . 'Controller.php')) {
                    $controllerName = sprintf('%sController', $pathParams[0]);
                    include $fileRoot . 'index/controllers/' . $controllerName . '.php';
                    $controller = new $controllerName(null, null);
                    if (method_exists($controller, 'index')) {
                        $this->controller = $pathParams[0];
                    } else {
                        exit('Wrong route');
                    }
                } elseif (glob($fileRoot . 'index/controllers/indexController.php')) {
                    include $fileRoot . 'index/controllers/indexController.php';
                    $controller = new indexController(null, null);
                    if (method_exists($controller, $pathParams[0])) {
                        $this->method = $pathParams[0];
                    } else {
                        exit('Wrong route');
                    }
                }
            }
        }

        return true;
    }

    public function setLayout($html)
    {
        $this->layout = $html;
    }

    public function getLayout()
    {
        $layout = $this->layout;
        foreach ($this->viewVariables as $k => $v) {
            $layout = str_replace(sprintf('{{%s}}', $k), $v, $layout);
        }

        return $layout;
    }

    public function setViewVariable($key, $value)
    {
        $this->viewVariables[$key] = $value;
    }
}