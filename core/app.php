<?

class App {
    public function start($mode)
    {
        require_once 'conf.php';
        require_once 'request.php';
        require_once 'db.php';

        $conf = new Conf($mode);

        include_once $conf->getPath('bundles/baseController.php');

        $request = new Request($_SERVER['REQUEST_URI'], $conf->getPath('bundles/'));
        $db = new Db($conf);

        echo sprintf('Including /bundles/%s/controllers/%sController and calling %s() method'."\n",
          $request->bundle, $request->controller, $request->method);
        include_once $conf->getPath(sprintf(
            'bundles/%s/controllers/%sController.php',
            $request->bundle,
            $request->controller)
        );
        $controllerName = sprintf('%sController', $request->controller);
        $controller = new $controllerName($db);
        $controller->{$request->method}();
    }
}
