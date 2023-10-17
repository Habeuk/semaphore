<?php

namespace Drupal\semaphore\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *        The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
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
    \Stephane888\Debug\debugLog::kintDebugDrupal($request, $request->getMethod() . '--onKernelRequest', true, 'logs/request');
  }
  
  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *        Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    // $this->messenger->addStatus(__FUNCTION__);
    // \Stephane888\Debug\debugLog::kintDebugDrupal($event, 'onKernelResponse',
    // true, 'logs/reponse');
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
