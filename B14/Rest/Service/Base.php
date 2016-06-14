<?php

//
namespace B14\Rest\Service;

/**
 * The base class all services should extend.
 */
class Base
{
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
}
