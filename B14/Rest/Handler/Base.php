<?php

//
namespace B14\Rest\Handler;

/**
 * The base class all handlers should extend.
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
  public function __construct($server, $options = array()) {
    $this->server = $server;
  }

  /**
   * Get the output.
   *
   * @param mixed $output
   *   The current output.
   *
   * @return mixed
   *   The altered output.
   */
  public function getOutput($status, $output = null) {
    return $output;
  }
}
