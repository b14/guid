<?php

//
namespace B14\Rest\Format;

/**
 * The base class all formats should extend.
 */
abstract class Base
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

  /**
   * Get the formatted output.
   *
   * @param mixed $output
   *   The output to format.
   *   Note that this can be any kind of data type, it will depend primary on
   *   what the called method returns.
   * @param array $options
   *   The format options.
   */
  abstract public function out($output, $options = array());

  /**
   * Get the MIME type of the specific format.
   *
   * @return string
   *   The clean MIME type, that will be inserted into the HTTP
   *   Content-Type header
   */
  abstract public function getContentType();
}
