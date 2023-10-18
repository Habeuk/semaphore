<?php

namespace Drupal\semaphore\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\ProxyClass\Lock\DatabaseLockBackend;
use Drupal\Component\Utility\Timer;

/**
 * semaphore event subscriber.
 */
class SemaphoreSubscriber implements EventSubscriberInterface {
  
  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   *
   * @var string
   */
  private const LOCKKEY = "post-execution";
  /**
   *
   * @var integer
   */
  private static $sem_id = Null;
  /**
   * help to debug
   *
   * @var array
   */
  private static $logs = [];
  
  /**
   * Request headers
   */
  private static $headers = [];
  
  /**
   *
   * @var DatabaseLockBackend
   */
  protected $DatabaseLockBackend;
  
  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *        The messenger.
   */
  public function __construct(MessengerInterface $messenger, DatabaseLockBackend $lock) {
    $this->messenger = $messenger;
    $this->DatabaseLockBackend = $lock;
  }
  
  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *        Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    // $this->messenger->addStatus(__FUNCTION__);
    $request = $event->getRequest();
    self::$headers = $request->headers->all();
    if ($request->getMethod() == Request::METHOD_POST || !empty(self::$headers['x-semaphore'])) {
      \Stephane888\Debug\debugLog::$max_depth = 10;
      $route = str_replace("/", "|", trim($request->getPathInfo(), "/"));
      Timer::start($route);
      $ip = $request->getClientIp();
      $sem_key = ftok(__FILE__, 'S');
      self::$sem_id = sem_get($sem_key, 1);
      
      self::$logs[$route] = [
        'request' => [
          'sem_id' => self::$sem_id,
          'time_init' => Timer::read($route)
        ],
        'ip' => $ip
      ];
      /**
       * si la clée existe ce code va etre stopé jusqu& ce que la clé soit
       * disponible.
       * Si le sémaphore est disponible, la fonction retournera true et le
       * processus pourra accéder à la ressource partagée. Si le sémaphore n'est
       * pas disponible, la fonction se bloquera jusqu'à ce que le sémaphore
       * devienne disponible.
       */
      if (sem_acquire(self::$sem_id)) {
        self::$logs[$route]['request']['time_begin_run'] = Timer::read($route);
      }
      // \Stephane888\Debug\debugLog::kintDebugDrupal(self::$logs, $route .
      // '--', true, 'logs/request');
    }
  }
  
  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *        Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $request = $event->getRequest();
    if ($request->getMethod() == Request::METHOD_POST || !empty(self::$headers['x-semaphore'])) {
      $route = str_replace("/", "|", trim($request->getPathInfo(), "/"));
      $semStatus = sem_release(self::$sem_id);
      self::$logs[$route]['reponse'] = [
        'sem_id' => self::$sem_id,
        'semaphore_is_release' => $semStatus,
        'time_begin_end' => Timer::stop($route),
        'headers' => $request->headers->all()
      ];
      // \Stephane888\Debug\debugLog::kintDebugDrupal(self::$logs, $route .
      // '--', true, 'logs/reponse');
    }
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => [
        'onKernelRequest'
      ],
      KernelEvents::RESPONSE => [
        'onKernelResponse'
      ]
    ];
  }
  
}
