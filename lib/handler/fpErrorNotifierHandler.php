<?php

/**
 *
 * @package    fpErrorNotifier
 * @subpackage handler 
 * 
 * @author     Maksim Kotlyar <mkotlar@ukr.net>
 */
class fpErrorNotifierHandler
{ 
  /**
   * 
   * @var array
   */
  protected $options = array('notify404' => false);
  
  /**
   * 
   * @var string
   */
  protected $memoryReserv = '';
  
  /**
   * @var sfEventDispatcher
   */
  protected $dispatcher;
  
  /**
   * 
   * @var bool
   */
  protected $isInit = false;
  
  /**
   * 
   * @param array $options
   * 
   * @return void
   */
  public function __construct(sfEventDispatcher $dispatcher, array $options = array())
  {
    $this->dispatcher = $dispatcher;
    $this->options = array_merge($this->options, $options);
  }
  
  /**
   * 
   * @return void
   */
  public function initialize()
  {
    if ($this->isInit) return; 

    $this->memoryReserv = str_repeat('x', 1024 * 500);
    
    // Register error handler it will process the most part of erros (but not all)
    set_error_handler(array($this, 'handleError'));
    // Register shutdown handler it will process the rest part of errors
    register_shutdown_function(array($this, 'handleFatalError'));
    
    set_exception_handler(array($this, 'handleException'));
        
    $dispatcher = $this->notifier()->dispatcher();
    $dispatcher->connect('application.throw_exception', array($this, 'handleEvent'));
    $dispatcher->connect('notify.throw_exception', array($this, 'handleEvent'));
    $dispatcher->connect('notify.send_message', array($this, 'handleEventMessage'));
    if ($this->options['notify404']) {
        $dispatcher->connect('controller.page_not_found', array($this, 'handle404'));
    }
    
    
    $this->isInit = true;
  }
  
  public function handle404(sfEvent $event)
  {
    $subject = $event->getSubject();
    if ($subject instanceof Exception){
      return $this->handleException($subject);
    } else {
      return $this->handleException(
          new sfError404Exception('404 - Page not found: "' . $this->notifier()->context()->getRequest()->getUri() . '"')
      );
    }
  }
  
  /**
   * 
   * @param sfEvent $event
   * 
   * @return void
   */
  public function handleEvent(sfEvent $event)
  {
    return $this->handleException($event->getSubject());
  }
  
  /**
   * 
   * @param Exception $e
   * 
   * @return void
   */
  public function handleException(Exception $e)
  {
    $message = $this->notifier()->decoratedMessage($e->getMessage());
    $message->addSection('Exception', $this->notifier()->helper()->formatException($e));
    
    $count = 1;
    while ($previous = $e->getPrevious()) {
      $message->addSection("Previous Exception #{$count}", $this->notifier()->helper()->formatException($previous));
      
      $e = $previous;
      $count++; 
    }
    
    $message->addSection('Server', $this->notifier()->helper()->formatServer());
    
    $this->dispatcher->notify(new sfEvent($message, 'notify.decorate_exception'));
    
    $this->notifier()->driver()->notify($message);
  }
  
  public function handleEventMessage(sfEvent $event)
  {
    $message = $this->notifier()->decoratedMessage($event->getSubject());
    $message->addSection('Message Details', $event->getParameters());
    $message->addSection('Server', $this->notifier()->helper()->formatServer());
    
    $this->dispatcher->notify(new sfEvent($message, 'notify.decorate_message'));
    
    $this->notifier()->driver()->notify($message);
  }

  /**
   * 
   * @param string $errno
   * @param string $errstr
   * @param string $errfile
   * @param string $errline
   * 
   * @return ErrorException
   */
  public function handleError($errno, $errstr, $errfile, $errline)
  {
    $this->handleException(new ErrorException($errstr, 0, $errno, $errfile, $errline));
    
    return false;
  }

  /**
   * 
   * @return void
   */
  public function handleFatalError()
  {
    $error = error_get_last();

    $skipHandling = 
      !$error || 
      !isset($error['type']) || 
      !in_array($error['type'], fpErrorNotifierErrorCode::getFatals());
    if ($skipHandling) return;

    $this->freeMemory();
    
    @$this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
  }
  
  /**
   * 
   * @return void
   */
  protected function freeMemory()
  {
    unset($this->memoryReserv);
  }
  
  /**
   * 
   * @return fpErrorNotifier
   */
  protected function notifier()
  {
    return fpErrorNotifier::getInstance();
  }
}