<?php

//
namespace app\Services;

class System extends \B14\Rest\Service\Base
{
  const NAME = 'system';
  
  public function _blank() {
    return 'version';
  }
  
  /**
   * Get the version of the GUID service.
   *
   * @return string
   *   A formatted version string, with ([PREFIX])[MAJOR].[MINOR].([SUFFIX]).
   */
  public function version() {
    return VERSION_PREFIX . VERSION_MAJOR . '.' . VERSION_MINOR . VERSION_SUFFIX;
  }
  
  /**
   * Get stats.
   */
  public function stats() {
    return $this->server->getHandler('app\Handler\Stats')->getStats();
  }
  
  /**
   * Reset the stats.
   *
   * But only if you know the secret password.
   *
   * @param string $secret
   *   The password required to clear the stats.
   */
  public function resetStats($secret) {
    if ($secret !== GUID_RESET_STATS_SECRET) {
      return FALSE;
    }
    return $this->server->getHandler('app\Handler\Stats')->resetStats();
  }
}