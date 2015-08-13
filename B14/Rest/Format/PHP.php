<?php

//
namespace B14\Rest\Format;

/**
 * Get output serialized with the PHP serialize function.
 */
class PHP extends \B14\Rest\Format\Base
{
  /** {@inheritdoc} */
  const NAME = 'php';

  /** {@inheritdoc} */
  public function getContentType() {
    return 'text/plain';
  }

  /** {@inheritdoc} */
  public function out($output, $options = array()) {
    return serialize($output);
  }
}
