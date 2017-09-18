<?php

error_reporting(E_ALL);

try {

	/**
	 * Read the configuration
	 */
	$config = include(__DIR__."/../app/config/config.php");

	$loader = new \Phalcon\Loader();

	/**
	 * We're a registering a set of directories taken from the configuration file
	 */
	$loader->registerDirs(
		array(
			$config->application->controllersDir,
			$config->application->modelsDir,
			$config->application->libraryDir
		)
	)->register();

	/**
	 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
	 */
	$di = new \Phalcon\DI\FactoryDefault();

	/**
	 * The URL component is used to generate all kind of urls in the application
	 */
	$di->set('url', function() use ($config) {
		$url = new \Phalcon\Mvc\Url();
		$url->setBaseUri($config->application->baseUri);
		return $url;
	});

	/**
	 * Setting up the view component
	 */
	$di->set('view', function() use ($config) {
		$view = new \Phalcon\Mvc\View();
		$view->setViewsDir($config->application->viewsDir);
		return $view;
	});

        $di->set('mongodb', function() use($config) {
    	//$l = DIRECTORY_SEPARATOR;
    	//include dirname(dirname(__FILE__)) . "{$l}app{$l}libs{$l}mongodb.php";
    	return new MongoDB\Driver\Manager($config->mongodb['host']);
    });

	/**
	 * Database connection is created based in the parameters defined in the configuration file
	 */
	$di->set('db', function() use ($config) {
		return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
			"host" => $config->database->host,
			"port" => $config->database->port,
			"username" => $config->database->username,
			"password" => $config->database->password,
			"dbname" => $config->database->dbname
		));
	});

	//Set the models cache service
	$di->set('cache', function() use ($config) {

		//Cache data for one day by default
		$frontCache = new \Phalcon\Cache\Frontend\Data(array(
				"lifetime" => 86400
		));

		//Create the Cache setting redis connection options
		$redis = new Phalcon\Cache\Backend\Redis($frontCache, array(
				'host' => $config->redis->host,
				'port' => $config->redis->port,
// 				'auth' => $config->redis->auth,
//				'persistent' => true,
// 				'statsKey' => 'info',
				'index' => 1

		));

		$frontCache = new \Phalcon\Cache\Frontend\Data(array(
				"lifetime" => 86400
		));
		$file = new \Phalcon\Cache\Backend\File($frontCache, array(
				"cacheDir" => "../app/cache/"
		));

		$cache = new stdClass();
		$cache->redis = $redis;
		$cache->file = $file;
		//$cache->mem = $mem;

		//return $cache;
		return $redis;
	});

	/**
	 * If the configuration specify the use of metadata adapter use it or use memory otherwise
	 */
	$di->set('modelsMetadata', function() use ($config) {
		if (isset($config->models->metadata)) {
			$metadataAdapter = 'Phalcon\Mvc\Model\Metadata\\'.$config->models->metadata->adapter;
			return new $metadataAdapter();
		} else {
			return new \Phalcon\Mvc\Model\Metadata\Memory();
		}
	});
 $di->set('objToArray', function() {
        $l = DIRECTORY_SEPARATOR;
        if (!class_exists('objToArray')) {
            include dirname(dirname(__FILE__)) . "{$l}app{$l}libs{$l}objToArray.php";
        }
        $obj = new objToArray();
        return $obj;
    });
	/**
	 * Start the session the first time some component request the session service
	 */
	$di->set('session', function() {
		$session = new \Phalcon\Session\Adapter\Files();
		$session->start();
		return $session;
	});

	$di->set('templates', function () {
		return new Templates();
	});
	/**
	 * Handle the request
	 */
	$application = new \Phalcon\Mvc\Application();
	$application->setDI($di);

	echo $application->handle()->getContent();

} catch (Phalcon\Exception $e) {
	error_log($e->getMessage()) ;
} catch (PDOException $e){
	error_log($e->getMessage()) ;
}
