<?php

//
namespace B14\Rest\Service;

/**
 * Get documentation about the service.
 */
class Doc extends Base
{
  /** {@inheritdoc} */
  const NAME = 'doc';

  /** Tells availability of the \phpDocumentor\Reflection\DocBlock class. */
  private $doc_block_available = FALSE;

  /**
   * Get the default method of this service.
   *
   * @return string
   *   Name of the method.
   */
  public function _blank() {
    return 'services';
  }

  /** {@inheritdoc} */
  public function __construct($server) {
    parent::__construct($server);

    // Make sure the DocBlock class exists.
    $this->doc_block_available = class_exists('\phpDocumentor\Reflection\DocBlock');
  }

  /**
   * Get the DocBlock class.
   *
   * @param mixed $reflection
   *   A Reflection class that has the getDocComment() function.
   *
   * @param mixed
   *   Will return a \phpDocumentor\Reflection\DocBlock or FALSE if it failed
   *   to instantiate the object.
   */
  private function getDocBlock($reflection) {
    if ($this->doc_block_available === TRUE) {
      return new \phpDocumentor\Reflection\DocBlock($reflection->getDocComment());
    }

    return FALSE;
  }

  /**
   * Get available formats.
   *
   * @return object
   *   An array that lists all the available formats.
   */
  public function formats($depth = 1) {
    $formats = array();

    foreach ($this->server->formats as $format_name => $format) {
      if (empty($format_name)) {
        continue;
      }

      if ($depth === 0) {
        $formats[] = $format_name;
      } else {
        $formats[$format_name] = $this->format($format_name);
      }
    }

    return $formats;
  }
  
  /**
   * Get information about a specific format.
   *
   * @return object
   *   A structure containing the information about the format.
   */
  public function format($format) {
    $format_instance = new $this->server->formats[$format]($this->server);
    $rc = new \ReflectionClass($format_instance);
    $info = array(
      'content_type' => $format_instance->getContentType()
    );
    
    if ($db = $this->getDocBlock($rc)) {
      $info['description'] = $db->getText();
    }
    
    return $info;
  }

  /**
   * Get a list of services available.
   *
   * @param integer $depth
   *   The deeper you go, the more info you get.
   *
   * @return object
   *   A structure containing the information about the services.
   */
  public function services($depth = 3) {
    $services = array();

    foreach ($this->server->services as $service_name => $service) {
      if (empty($service_name)) {
        continue;
      }

      if ($depth == 0) {
        $services[] = $service_name;
      } else {
        $services[$service_name] = $this->service($service_name, $depth - 1);
      }
    }

    return $services;
  }

  /**
   * Get a list of methods in a service.
   *
   * @param string $service
   *   Name of the service.
   * @param integer $depth
   *   The deeper you go, the more info you get.
   *
   * @return object
   *   A structure containing the information about the methods.
   */
  public function service($service, $depth = 2) {
    if (!isset($this->server->services[$service])) {
      $this->server->handleError('doc-1', 'Unknown service: \'' . $service . '\'');
    }

    // Create an instance of the server.
    $service_instance = new $this->server->services[$service]($this->server);
    $rc = new \ReflectionClass($service_instance);

    // Prepare the information structure.
    $info = array(
      'default_method' => FALSE,
      'methods' => array(),
    );

    // Use DocBlock for more information, if it's available.
    if ($db = $this->getDocBlock($rc)) {
      $info['description'] = $db->getText();
    }

    // Run through all the public methods of the service.
    foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {

      // Public methods prepended with _ is not callable by the REST interface.
      if ($method->name[0] === '_') {
        // If it's the '_blank' method call it to get the default method.
        if ($method->name === '_blank') {
          $info['default_method'] = $service_instance->_blank();
        }
        continue;
      }

      if ($depth == 0) {
        $info['methods'][] = $method->name;
      } else {
        $info['methods'][$method->name] = $this->method($service, $method->name, $depth - 1);
      }
    }

    return $info;
  }

  /**
   * Get information about a specific method.
   *
   * @return object
   *   A structure containing the information about the method.
   */
  public function method($service, $method, $depth = 1) {
    if (!isset($this->server->services[$service])) {
      $this->server->handleError('doc-1', 'Unknown service: \'' . $service . '\'');
    }
    if ($method[0] === '_' || !method_exists($this->server->services[$service], $method)) {
      $this->server->handleError('doc-2', 'Unknown method \'' . $method . '\'');
    }

    $service_instance = new $this->server->services[$service]($this->server);
    $rc = new \ReflectionClass($service_instance);
    $rm = $rc->getMethod($method);

    $data = array(
      'parameters' => array()
    );

    // If DocBlock is available use it to give more information.
    $param_tags = array();
    if ($db = $this->getDocBlock($rm)) {
      $data['description'] = $db->getText();

      foreach ($db->getTagsByName('return') as $return) {
        $data['return'] = array(
          'type' => $return->getType(),
          'description' => $return->getDescription()
        );
      }

      foreach ($db->getTagsByName('param') as $param) {
        $param_tags[substr($param->getVariableName(), 1)] = $param;
      }
    }

    // Fill in all the parameters
    foreach ($rm->getParameters() as $parameter) {
      if ($depth == 0) {
        $data['parameters'][] = $parameter->name;
      } else {
        $parameter_data = array(
          'required' => !$parameter->isOptional(),
        );
        if ($parameter_data['required'] === FALSE) {
          $parameter_data['default'] = $parameter->isOptional() ? $parameter->getDefaultValue() : '';
        }

        if (isset($param_tags[$parameter->name])) {
          $pt = $param_tags[$parameter->name];
          $parameter_data['description'] = $pt->getDescription();
          $parameter_data['type'] = $pt->getType();
        }

        $data['parameters'][$parameter->name] = $parameter_data;
      }
    }

    // If there's no parameters set it to FALSE
    if (empty($data['parameters'])) {
      $data['parameters'] = FALSE;
    }

    return $data;
  }
}
