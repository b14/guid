<?php

//
namespace B14\Rest\Format;

/**
 * Output as XML, using the WDDX (Web Distributed Data eXchange) specification.
 */
class XML extends \B14\Rest\Format\Base
{
  /** {@inheritdoc} */
  const NAME = 'xml';

  /** {@inheritdoc} */
  public function getContentType() {
    return 'text/xml';
  }

  /** {@inheritdoc} */
  public function out($output, $options = array()) {
    return wddx_serialize_value($output);
  }
}