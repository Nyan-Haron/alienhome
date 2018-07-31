<?

class App
{
    public function start($mode)
    {
        session_start();

        require_once 'conf.php';
        require_once 'request.php';
        require_once 'db.php';

        $conf = new Conf($mode);

        if ($conf->getEnv() == 'prod') {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }

        include_once $conf->getPath('/bundles/baseController.php');
        include_once $conf->getPath('/db/baseEntity.php');
        include_once $conf->getPath('/db/baseRepository.php');

        $request = new Request($_SERVER['REQUEST_URI'], $conf->getPath('/bundles/'));
        $db = new Db($conf);
        $request->setLayout(file_get_contents($conf->getPath('/public/bundles/index/views/layout.html')));
        $conf->devPrint('info', sprintf(
            'Calling /bundles/index/views/%s/%s.html',
            $request->controller,
            $request->method));
        $request->setViewVariable(
            'body',
            file_get_contents($conf->getPublicFolder() . sprintf(
                    '/bundles/index/views/%s/%s.html',
                    $request->controller,
                    $request->method)));

        $conf->devPrint('info', sprintf('Including /bundles/%s/controllers/%sController and calling %s() method' . "\n",
            $request->bundle, $request->controller, $request->method));
        include_once $conf->getPath(sprintf(
                '/bundles/%s/controllers/%sController.php',
                $request->bundle,
                $request->controller)
        );

        $controllerName = sprintf('%sController', $request->controller);
        $controller = new $controllerName($db, $request, $conf);

        /*
         * Вернуть, когда будет продакшн
        if (!$controller->checkAuth() && $request->controller !== 'auth') {
            header("Location: /auth");
        }
        */
        $controller->{$request->method}();

        echo $request->getLayout();
    }
}
