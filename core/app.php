<?

class App
{
    public function start()
    {
        session_start();

        require_once 'conf.php';
        require_once 'request.php';
        require_once 'db.php';

        $conf = new Conf();

        $this->includeOnce($conf->getPath('/bundles/baseController.php'));
        $this->includeOnce($conf->getPath('/bundles/helper.php'));
        $this->includeOnce($conf->getPath('/db/baseEntity.php'));
        $this->includeOnce($conf->getPath('/db/baseRepository.php'));

        $request = new Request($_SERVER['REQUEST_URI'], $conf->getPath('/bundles/'));
        $db = new Db($conf);
        $request->setLayout(file_get_contents($conf->getPath('/public/bundles/index/views/layout.html')));
        $request->setViewVariable('mode', $conf->getEnv());
        $request->setViewVariable('subList', '');
        $conf->devPrint('htmlInfo', sprintf(
            'Calling /bundles/index/views/%s/%s.html',
            $request->controller,
            $request->method));

        $layoutPath = $conf->getPublicFolder() . sprintf(
                '/bundles/index/views/%s/%s.html',
                $request->controller,
                $request->method);
        if (glob($layoutPath)) {
            $request->setViewVariable('body', file_get_contents($layoutPath));
        }

        $conf->devPrint('methodInfo', sprintf('Including /bundles/%s/controllers/%sController and calling %s() method' . "\n", $request->bundle, $request->controller, $request->method));
        $this->includeOnce($conf->getPath(sprintf(
                '/bundles/%s/controllers/%sController.php',
                $request->bundle,
                $request->controller)
        ));

        $controllerName = sprintf('%sController', $request->controller);
        /** @var baseController $controller */
        $controller = new $controllerName($db, $request, $conf);

        if (!$controller->checkAuth() && $request->controller !== 'auth' && $request->needAuth) {
            header("Location: /login");
        }
        $controller->{$request->method}();

        echo $request->getLayout();
    }

    private function includeOnce($path) {
        include_once $path;
    }
}
