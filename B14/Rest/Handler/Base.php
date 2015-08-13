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
   * Called prior to handle().
   *
   * When a handler doesn't alter the output, this is the only function that
   * needs to be implemented.
   *
   * @return B14\Rest\Handler\Base
   *   This MUST return an instance of it self.
   */
  public function preHandle($status) {
    return $this;
  }

  /**
   * Handle the output.
   *
   * @param mixed $output
   *   The current output.
   *
   * @return mixed
   *   The altered output.
   */
  public function handle($status, $output = NULL) {
    return $output;
  }
}
