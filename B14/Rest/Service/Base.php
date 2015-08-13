<?php

//
namespace B14\Rest\Service;

/**
 * The base class all services should extend.
 */
class Base
{
  
  /** Name of the format. */
  const NAME = '';

  /** Reference to the Rest server instsance. */
  protected $server;

  /**
   * Instantiate the object.
   *
   * @param B14\Rest\Server $server
   *   A reference to the server using the format.
   */
  public function __construct($server) {
    $this->server = $server;
  }
  
  /**
   * Get the name of the class.
   *
   * You can't call the constant on the instance, but you can call it from the
   * instance it self.
   * This function allows you to get around that.
   *
   * @return string
   *   The self::NAME constant.
   */
  public function _getName() {
    return $this::NAME;
  }
}
