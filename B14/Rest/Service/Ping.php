<?php

//
namespace B14\Rest\Service;

/**
 * A simple proof of concept ping pong service.
 *
 * This is actually useful to see if the current service is up and running.
 */
class Ping extends Base
{
  /** {@inheritdoc} */
  const NAME = 'ping';

  /**
   * Get the default method of this service.
   *
   * @return string
   *   Name of the method.
   */
  public function _blank() {
    return 'ping';
  }

  /**
   * A simple ping (upper) pong.
   *
   * @param string $pong
   *   The text you want returned on your ping.
   *
   * @return string
   *   The $pong string, but uppercased.
   */
  public function ping($pong = 'pong') {
    return strtoupper($pong);
  }

  /**
   * Get the Rest Server version.
   *
   * @return string
   *   The Rest server name and version.
   */
  public function version() {
    return \B14\Rest\Server::NAME . ': ' . \B14\Rest\Server::VERSION;
  }
}
