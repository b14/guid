<?php

//
namespace B14\Rest\Handler;

/**
 * The simple service caller.
 */
class Caller extends Base
{
  /** {@inheritdoc} */
  public function getOutput($status, $output = null) {
    if ($status === \B14\Rest\Server::STATUS_SUCCESS) {
      return call_user_func_array(array($this->server->service, $this->server->method), $this->server->argument_list);
    }

    return $output;
  }
}
