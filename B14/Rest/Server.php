<?php

//
namespace B14\Rest;

/**
 *
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

  /**
   * The selected format.
   * @see prepareFormat()
   */
  protected $format;

  /**
   * The selected service.
   * @see prepareService()
   */
  protected $service;

  /**
   * The method to call.
   * @see prepareMethod()
   */
  protected $method;

  /**
   * The arguments as given by the client.
   * @see prepareArguments()
   */
  protected $arguments;

  /**
   * The arguments sorted to match the parameters order in the method.
   * @see prepareArguments()
   */
  protected $argument_list = array();

  /**
   * Options passed to the format.
   * @see prepareFormatOptions()
   */
  protected $format_options = array();

  /**
   * The list of available services.
   * @see addService()
   */
  protected $services = array(
    '' => 'B14\Rest\Service\Ping',
    'ping' => 'B14\Rest\Service\Ping',
    'doc' => 'B14\Rest\Service\Doc',
  );

  /**
   * The list of available formats.
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
   * @see addHandler()
   */
  protected $handlers = array(
    'B14\Rest\Handler\Caller' => 'B14\Rest\Handler\Caller'
  );

  /**
   * A keyed array of instantiated handlers.
   * @see getHandler()
   */
  private $handler_map = array();

  /** Instantiate. */
  public function  __construct() { }

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
    if (!isset($this->formats[$format])) {
      $this->format = new $this->formats['']($this);
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

    // If format_options is not an array put them into an array, so all
    // formatters can expect an array.
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
    $this->service = new $this->services[$service]($this);

    return $this;
  }

  /**
   * Prepare the method.
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
      if ($method === NULL || $method === '') {
        if (method_exists($this->service, '_blank')) {
          $this->method = $this->service->_blank();
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

    $rm = new \ReflectionMethod($this->service, $this->method);
    foreach ($rm->getParameters() as $parameter) {
      if (isset($this->arguments[$parameter->name])) {
        $this->argument_list[] = $this->arguments[$parameter->name];
      } else if (!$parameter->isOptional()) {
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
  public function getOutput($status = self::STATUS_SUCCESS, $output = NULL) {
    foreach ($this->handlers as $name => $handler) {
      $output = $this->getHandler($name)
        ->preHandle($status)
        ->handle($status, $output);
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
  public function send($output, $http_code = 200, $end = TRUE) {
    http_response_code($http_code);
    header('Content-Type: ' . $this->format->getContentType() . ';charset=utf-8');

    echo $this->format->out($output, $this->format_options);

    if ($end === TRUE) {
      exit();
    }
  }

  /**
   * A wrapper function for adding a new process.
   *
   * The process is attached to the $formats, $services or $handlers list.
   *
   * @param string $list
   *   The list to add the class to.
   * @param class $class
   *   The class to add.
   * @param string|bool $name
   *   If not used the NAME constant from the class will be used.
   * @param bool $prepend
   *   Instead of appending the process it will be prepended.
   *   This gives us a very primitive priority system.
   */
  protected function addProcess($list, $class, $name = FALSE, $prepend = FALSE) {
    if ($name === FALSE) {
      $name = $class::NAME;
    }

    if (!$prepend) {
      $this->{$list}[$name] = $class;
    } else {
      $this->$list = array($name => $class) + $this->$list;
    }

    return $this;
  }

  public function addFormat($class, $name = FALSE) {
    return $this->addProcess('formats', $class, $name);
  }

  public function addService($class, $name = FALSE) {
    return $this->addProcess('services', $class, $name);
  }

  public function addHandler($class, $prepend = FALSE) {
    return $this->addProcess('handlers', $class, $class, $prepend);
  }

  public function getHandler($name) {
    if (is_string($this->handlers[$name])) {
      $this->handlers[$name] = new $this->handlers[$name]($this);
    }

    return $this->handlers[$name];
  }

  /**
   * Expose the protected variables.
   */
  public function __get($name) {
    return $this->$name;
  }
}
