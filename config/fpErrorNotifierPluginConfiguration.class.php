<?php

/** 
 *
 * @package    fpErrorNotifier
 * @subpackage config 
 * 
 * @author     Maksim Kotlyar <mkotlar@ukr.net>
 */
class fpErrorNotifierPluginConfiguration extends sfPluginConfiguration
{
  /**
   * 
   * @return void
   */
  public function initialize()
  {
    if (!sfConfig::get('app_fp_error_notifier_driver')) return;  
    $this->notifier()->handler()->initialize();
  }
  
  /**
   * 
   * @return fpErrorNotifier
   */
  protected function notifier()
  {
    if (!$instance = fpErrorNotifier::getInstance()) {
      $instance = new fpErrorNotifier($this->configuration->getEventDispatcher());
      
      fpErrorNotifier::setInstance($instance);
    }
    
    return $instance;
  }
  
}