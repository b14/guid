<?php

//
namespace B14\Rest;

/**
 * A REST Server.
 *
 * It's a modular server, where you can easily add new output formats, services
 * or handlers (a handler allows you to read and edit the output, before the
 * formatter).
 *
 * For a quick setup use the {@link prepare()} function, coupled with .htaccess
 * rewrite. Using clean urls a simple index.php example could be:
 * <code>
 * <?php
 * $server = new B14\Rest\Server;
 * $server
 *   ->prepare()
 *   ->handle();
 * ?>
 * </code>
 *
 * This will startup a simple REST, with only the standard services.
 *
 * You need to create your own service classes, to create your own callable
 * methods. Have a look at the {@link B14\Rest\Service\Ping} class for a simple
 * example.
 */
class Server
{
  /** Name of the rest server. */
  const NAME = 'B14 REST-Server';

  /** Version of the server. */
  const VERSION = '1.0';

  /** Output status success. */
  const STATUS_SUCCESS = 'success';
  /** Output status error. */
  const STATUS_ERROR = 'error';

  const INFO_NONE = 0;
  const INFO_MESSAGE = 0b1;
  const INFO_TIMERS = 0b10;

  /**
   * The selected format.
   *
   * @see prepareFormat()
   */
  protected $format;

  /**
   * The selected service.
   *
   * @see prepareService()
   */
  protected $service;

  /**
   * The method to call.
   *
   * @see prepareMethod()
   */
  protected $method;

  /**
   * The arguments as given by the client.
   *
   * @see prepareArguments()
   */
  protected $arguments;

  /**
   * The arguments sorted to match the parameters order in the method.
   *
   * @see prepareArguments()
   */
  protected $argument_list = array();

  /**
   * Options passed to the format.
   *
   * @see prepareFormatOptions()
   */
  protected $format_options = array();

  /**
   * The list of available services.
   *
   * Note that the blank service is the default service called.
   * You can remove any service with the {@link removeProcess()} function.
   *
   * @see addService()
   */
  protected $services = array(
    '' => 'B14\Rest\Service\Ping',
    'ping' => 'B14\Rest\Service\Ping',
    'doc' => 'B14\Rest\Service\Doc',
  );

  /**
   * The list of available formats.
   *
   * Always have a default fallback (the blank), so if there's an error before
   * the server has setup the selected fallback, that error can be represented
   * to the user.
   *
   * @see addFormat()
   */
  protected $formats = array(
    '' => 'B14\Rest\Format\JSON',
    'json' => 'B14\Rest\Format\JSON',
    'php' => 'B14\Rest\Format\PHP',
    'xml' => 'B14\Rest\Format\XML',
  );

  /**
   * The list handlers to run.
   *
   * @see addHandler()
   */
  protected $handlers = array(
    'caller' => 'B14\Rest\Handler\Caller'
  );

  /**
   * Extra information
   *
   * @see addInformation()
   */
  protected $info_flags = 0;

  /**
   * Extra information is added to this array as key value.
   *
   * @see addInformation()
   */
  protected $information = array();

  /** Instantiate. */
  public function __construct($info_flags = self::INFO_NONE) {
    $this->info_flags = $info_flags;
  }

  /**
   * Add an information variable.
   *
   * @param string $key
   *   The key.
   * @param string $value
   *   The value.
   */
  public function addInformation($key, $value, $type = self::INFO_MESSAGE) {
    if ($this->info_flags & $type) {
      $this->information[$key] = $value;
    }
  }

  /**
   * Prepare the output format.
   *
   * @param string $format
   *   Name of format.
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function prepareFormat($format) {
    // If the format selected doesn't exist set the default fallback and
    // report an error back to the user.
    if (!isset($this->formats[$format])) {
      $this->format = $this->getProcess('formats', '');
      $this->handleError('pre-1', 'Unknown format');
    }

    $this->format = new $this->formats[$format]($this);
    return $this;
  }

  /**
   * Prepare the format options.
   *
   * These options are passed onto the format object.
   *
   * @param mixed $options
   *   The options to pass on.
   *   Note that if you don't pass an array, the option will be packed into an
   *   array, to unify the format options.
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function prepareFormatOptions($options) {
    $this->format_options = $options;

    // If format_options is not an array put them into an array.
    // This way all formatters can expect an array when their out() function is
    // called.
    if (!is_array($this->format_options)) {
      $this->format_options = array($this->format_options);
    }

    return $this;
  }

  /**
   * Prepare the service.
   *
   * @param string $service
   *   Name of the service.
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function prepareService($service) {
    if (!isset($this->services[$service])) {
      $this->handleError('pre-2', 'Unknown service');
    }
    $this->service = $this->getProcess('services', $service);

    return $this;
  }

  /**
   * Prepare the method.
   *
   * Checks if the method exists.
   *
   * @param string $method
   *   Name of the method.
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function prepareMethod($method) {
    $this->method = $method;

    if (!method_exists($this->service, $method)) {
      if (empty($method)) {
        if (isset($this->service->default_method)) {
          $this->method = $this->service->default_method;
        } else {
          $this->handleError('pre-3', 'Missing method');
        }
      } else {
        $this->handleError('pre-4', 'Missing method');
      }
    }

    if ($this->method[0] === '_') {
      $this->handleError('pre-5', 'Invalid method name');
    }

    return $this;
  }

  /**
   * Prepare the arguments.
   *
   * This will filter out any argument not supported by the method.
   *
   * @param array $arguments
   *   A mapped array of arguments.
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function prepareArguments($arguments) {
    $this->arguments = $arguments;

    // Run through the parameters of the selected method, and sort the given
    // arguments into an array, so they match the order of the method
    // parameters.
    $rm = new \ReflectionMethod($this->service, $this->method);
    foreach ($rm->getParameters() as $parameter) {
      if (isset($this->arguments[$parameter->name])) {
        $this->argument_list[] = $this->arguments[$parameter->name];
      } elseif (!$parameter->isOptional()) {
        // If a non optional parameter is missing let the client know.
        $this->handleError('pre-6', 'Missing argument: \'' . $parameter->name . '\'');
      }
    }

    return $this;
  }

  /**
   * Read the current call, and call all the needed functions.
   *
   * The schema is a simple RegEx:
   * <code>/\/(?'service'\w+)?\/?(?'method'\w+)?\.?(?'format'\w+)?$/</code>
   *
   * If you want a custom schema for your service you can skip calling this
   * function, and instead call the single prepare functions manually.
   * Read this function to get a feeling of what you need to call to create
   * a proper custom schema.
   *
   * @param string $path
   *   The path to parse, this defaults to $_SERVER['REQUEST_URI'].
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function prepare($path = '') {
    if (empty($path)) {
      $path = explode('?', $_SERVER['REQUEST_URI'])[0];
    }

    // /service/method.format OR /service.format OR /.format
    preg_match("/\/(?'service'\w+)?\/?(?'method'\w+)?\.?(?'format'\w+)?$/", $path, $matches);

    $input = array('service' => '', 'method' => '', 'format' => '');
    // 1. Merge the matches with our default inputs.
    // 2. Intersect with the keys from input only, this way we get rid of the
    //    numbered array in matches.
    $input = array_intersect_key(array_merge($input, $matches), $input);

    // Prepare all our parsed data.
    return $this
      ->prepareFormat($input['format'])
      ->prepareFormatOptions(isset($_GET['format-options']) ? $_GET['format-options'] : array())
      ->prepareService($input['service'])
      ->prepareMethod($input['method'])
      ->prepareArguments(array_merge($_GET, $_POST));
  }

  /**
   * Handle the request.
   */
  public function handle() {
    $this->send($this->getOutput());
  }

  /**
   * Get the output.
   *
   * @param string $status
   *   Status of the output.
   *   The handlers can react to different statuses.
   * @param mixed $output
   *   The start output.
   */
  public function getOutput($status = self::STATUS_SUCCESS, $output = null) {
    foreach ($this->handlers as $name => $handler) {
      $pretime = microtime(true);

      // var_dump($this->service);
      $output = $this->getProcess('handlers', $name)
        ->getOutput($status, $output);

      $this->addInformation('handler-' . $name, microtime(true) - $pretime, self::INFO_TIMERS);
    }

    return $output;
  }

  /**
   * Handle an error.
   */
  public function handleError($code, $message, $http_code = 400) {
    $output = $this->getOutput(self::STATUS_ERROR, array('error' => $message, 'code' => $code));
    $this->send($output, $http_code);
  }

  /**
   * Send data to the client.
   *
   * @param mixed $output
   *   The output to send. The output will be formatted by the selected format
   *   class before send.
   * @param integer $http_code
   *   The HTTP status code.
   * @param bool $end
   *   If true (default), this will exit() the script.
   */
  public function send($output, $http_code = 200, $end = true) {
    http_response_code($http_code);
    header('Content-Type: ' . $this->format->getContentType() . ';charset=utf-8');
    
    // Insert any information in the header.
    foreach ($this->information as $key => $value) {
      header('X-INFO-' . $key . ': ' . $value);
    }

    echo $this->format->out($output, $this->format_options);

    if ($end === true) {
      exit();
    }
  }

  /**
   * Add a new process.
   *
   * The process is attached to the $formats, $services or $handlers list.
   *
   * @param string $list
   *   The list to add the class to.
   * @param class $class
   *   The class to add.
   * @param string|bool $name
   *   Name of the process. When false, the $class parameter is used as the
   *   name.
   * @param bool $prepend
   *   Instead of appending the process it will be prepended.
   *   This gives us a very primitive priority system.
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function addProcess($list, $class, $name = false, $prepend = false) {
    if ($name === false) {
      $name = $class;
    }

    if (!$prepend) {
      $this->{$list}[$name] = $class;
    } else {
      $this->$list = array($name => $class) + $this->$list;
    }

    return $this;
  }

  /**
   * Remove a process
   *
   * @param string $list
   *   The list to add the class to.
   * @param string $name
   *   Name of the process.
   * @param bool $prepend
   *   Instead of appending the process it will be prepended.
   *   This gives us a very primitive priority system.
   *
   * @return B14\Rest\Server
   *   Chainable.
   */
  public function removeProcess($list, $name) {
    unset($this->{$list}[$name]);
    return $this;
  }

  /**
   * Get an instance of a process.
   *
   * This will make the process into a singleton.
   *
   * @param string $list
   *   The list to add the class to.
   * @param string $name
   *   Name of the process.
   *
   * @return object
   *   The instantiated object.
   */
  public function getProcess($list, $name) {
    if (is_string($this->{$list}[$name])) {
      $this->{$list}[$name] = new $this->{$list}[$name]($this);
    }

    return $this->{$list}[$name];
  }
  
  public function getProcessName($list, $class_name) {
    if (is_object($class_name)) {
      $class_name = get_class($class_name);
    }
    
    foreach ($this->{$list} as $process_name => $process) {
      if (!empty($process_name)) {
        if ($class_name == $process
          || (is_object($process) && get_class($process) == $class_name)) {
          return $process_name;
        }
      }
    }
    
    return false;
  }

  /**
   * Wrapper for adding a new format.
   *
   * @param class $class
   *   The class to add.
   * @param string|bool $name
   *   Name of the process. When false, the $class parameter is used as the
   *   name.
   *
   * @return B14\Rest\Server
   *   Chainable.
   *
   * @see addProcess()
   */
  public function addFormat($class, $name = false) {
    return $this->addProcess('formats', $class, $name);
  }

  /**
   * Wrapper for adding a new service.
   *
   * @param class $class
   *   The class to add.
   * @param string|bool $name
   *   Name of the process. When false, the $class parameter is used as the
   *   name.
   *
   * @return B14\Rest\Server
   *   Chainable.
   *
   * @see addProcess()
   */
  public function addService($class, $name) {
    return $this->addProcess('services', $class, $name);
  }

  /**
   * Wrapper for adding a new handler.
   *
   * @param class $class
   *   The class to add.
   * @param string|bool $name
   *   Name of the process. When false, the $class parameter is used as the
   *   name.
   *
   * @return B14\Rest\Server
   *   Chainable.
   *
   * @see addProcess()
   */
  public function addHandler($class, $name) {
    return $this->addProcess('handlers', $class, $name);
  }

  /**
   * Expose the protected variables.
   */
  public function __get($name) {
    return $this->$name;
  }
}
