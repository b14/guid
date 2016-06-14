<?php

// Versioning
define('VERSION_MAJOR', '1');
define('VERSION_MINOR', '0');
define('VERSION_PREFIX', 'GUID Service: ');
define('VERSION_SUFFIX', '-rc1');


include_once __DIR__ . '/settings.php';

// Setup the Autoloader
include_once __DIR__ . '/contrib/Psr4AutoloaderClass.php';
$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('B14', __DIR__ . '/B14');
$loader->addNamespace('app', __DIR__ . '/app');
$loader->addNamespace('phpDocumentor', __DIR__ . '/contrib/phpDocumentor');


// Setup the server.
$server = new B14\Rest\Server;
$server
  ->addService('app\Services\GUID', '')
  ->addService('app\Services\GUID', 'guid')
  ->addService('app\Services\System', 'system')
  
  ->addProcess('handlers', 'app\Handler\Stats', 'stats', TRUE)
  
  ->prepare()
  
  ->handle();