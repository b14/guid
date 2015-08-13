<?php

//
namespace app\Handler;

/**
 *
 */
class Stats extends \B14\Rest\Handler\Base
{
  protected $mc = FALSE;
  
  protected $interval = 'm_d_H';
  
  public function __construct($server, $options = array()) {
    parent::__construct($server, $options);
    
    if (class_exists('Memcached') && MEMCACHE_STATUS === TRUE) {
      $this->mc = new \Memcached();
      // increment is only supported when using BINARY_PROTOCOL.
      $this->mc->setOption(\Memcached::OPT_BINARY_PROTOCOL, TRUE);
      $this->mc->addServer(MEMCACHE_HOST, MEMCACHE_PORT);
    }
  }
  
  protected function updateStat($status, $service, $method) {
    if ($this->mc === FALSE) {
      return;
    }
    
    // Get IP list.
    $ip_list = $this->mc->get(MEMCACHE_PREFIX . '__IP_list');
    if ($ip_list === FALSE) {
      $ip_list = array();
    }
    
    // Set IP list if IP missing.
    if (!in_array($_SERVER['REMOTE_ADDR'], $ip_list)) {
      $ip_list[] = $_SERVER['REMOTE_ADDR'];
      $this->mc->set(MEMCACHE_PREFIX . '__IP_list', $ip_list);
    }
    
    // Get function list.
    $function_list = $this->mc->get(MEMCACHE_PREFIX . '__Function_list');
    if ($function_list === FALSE) {
      $function_list = array();
    }
    
    // Set IP list if IP missing.
    if (!in_array($service . '__' . $method, $function_list)) {
      $function_list[] = $service . '__' . $method;
      $this->mc->set(MEMCACHE_PREFIX . '__Function_list', $function_list);
    }
    
    // Create key
    $key = implode('__', array(
      MEMCACHE_PREFIX,
      $_SERVER['REMOTE_ADDR'],
      $service,
      $method
    ));
    
    // Set global 
    if ($this->mc->get($key) === FALSE) {
      $this->mc->set($key, 0);
    }
    $this->mc->increment($key);
    
    // Set current hour
    $key .= '__' . date($this->interval);
    if ($this->mc->get($key) === FALSE) {
      $this->mc->set($key, 0);
    }
    $this->mc->increment($key, 1, 0, mktime(date('H'), 0, 0, date('n'), date('j') + 1));
  }
  
  public function preHandle($status) {
    if ($status === \B14\Rest\Server::STATUS_SUCCESS) {
      $this->updateStat($status, $this->server->service->_getName(), $this->server->method);
    }
    return $this;
  }
  
  public function getStats() {
    if ($this->mc === FALSE) {
      return FALSE;
    }
    
    $stats = array();
    
    $ip_list = $this->mc->get(MEMCACHE_PREFIX . '__IP_list');
    if ($ip_list === FALSE) { $ip_list = array(); }
    $function_list = $this->mc->get(MEMCACHE_PREFIX . '__Function_list');
    if ($function_list === FALSE) { $function_list = array(); }
    
    foreach ($ip_list as $ip) {
      $stats[$ip] = array('global' => array());
      foreach ($function_list as $function) {
        $key = implode('__', array(MEMCACHE_PREFIX, $ip, $function));
        if ($count = $this->mc->get($key)) {
          $stats[$ip]['global'][$function] = $count;
        }
        for ($i = 0; $i < 12; $i++) {
          $hour = date($this->interval, mktime(date("H") - $i));
          if ($count = $this->mc->get($key . '__' . $hour)) {
            if (!isset($stats[$ip][$hour])) {
              $stats[$ip][$hour] = array();
            }
            $stats[$ip][$hour][$function] = $count;
          } else {
            break;
          }
        }
      }
    }
    
    foreach ($stats as &$ip) {
      foreach ($ip as &$item) {
        arsort($item, SORT_DESC);
      }
    }
    
    return $stats;
  }
  
  public function resetStats() {
    $ip_list = $this->mc->get(MEMCACHE_PREFIX . '__IP_list');
    if ($ip_list === FALSE) { $ip_list = array(); }
    $function_list = $this->mc->get(MEMCACHE_PREFIX . '__Function_list');
    if ($function_list === FALSE) { $function_list = array(); }
    
    foreach ($ip_list as $ip) {
      $stats[$ip] = array('global' => array());
      foreach ($function_list as $function) {
        $key = implode('__', array(MEMCACHE_PREFIX, $ip, $function));
        $this->mc->delete($key);
        for ($i = 0; $i < 12; $i++) {
          $hour = date('d_H', mktime(date("H") - $i));
          if (!$this->mc->delete($key . '__' . $hour)) {
            break;
          }
        }
      }
    }
    
    $this->mc->delete(MEMCACHE_PREFIX . '__IP_list');
    $this->mc->delete(MEMCACHE_PREFIX . '__Function_list');
    
    return TRUE;
  }
}