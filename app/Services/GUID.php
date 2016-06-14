<?php

//
namespace app\Services;

class GUID extends \B14\Rest\Service\Base
{
  public $default_method = 'get';

  /**
   * Get the GUID.
   *
   * @param string $type
   *   The type of identifier.
   * @param string $identifier
   *   The identifier.
   *
   * @return string
   *   The 128 character long GUID.
   */
  public function get($type, $identifier) {
    return hash('sha512', GUID_PREFIX . $type . '__' . $identifier);
  }
}
