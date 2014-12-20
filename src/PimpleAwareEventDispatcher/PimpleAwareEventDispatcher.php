<?php

/*
 * This file is part of PimpleAwareEventDispatcher
 *
 * (c) Dave Marshall <dave@atstsolutions.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PimpleAwareEventDispatcher;

use Pimple\Container;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Wraps listeners in closures to allow lazy loading of services
 *
 * @author Dave Marshall <dave@atstsolutions.co.uk>
 */
class PimpleAwareEventDispatcher implements EventDispatcherInterface
{
    protected $eventDispatcher;

    protected $container;

    protected $listenerIds = array();

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param Container                   $container
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, Container $container = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container !== null ? $container : new Container();
    }

    /**
     * Set container
     *
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Adds a service as event listener
     *
     * @param string $eventName Event for which the listener is added
     * @param array $callback The service ID of the listener service & the method
     * name that has to be called
     * @param integer $priority The higher this value, the earlier an event listener
     * will be triggered in the chain.
     * Defaults to 0.
     */
    public function addListenerService($eventName, $callback, $priority = 0)
    {
        if (!is_array($callback) || 2 !== count($callback)) {
            throw new \InvalidArgumentException('Expected an array("service", "method") argument');
        }

        $serviceId = $callback[0];
        $method = $callback[1];
        $container = $this->container;
        $closure = function (Event $e) use ($container, $serviceId, $method) {
            call_user_func(array($container[$serviceId], $method), $e);
        };

        $this->listenerIds[$eventName][] = array($callback, $closure);
        $this->eventDispatcher->addListener($eventName, $closure, $priority);
    }

    public function removeListener($eventName, $listener)
    {
        foreach ($this->listenerIds[$eventName] as $i => $parts) {
            list($callback, $closure) = $parts;
            if ($listener == $callback) {
                $listener = $closure;
                break;
            }
        }

        $this->eventDispatcher->removeListener($eventName, $listener);
    }

    /**
     * Adds a service as event subscriber
     *
     * @param string $serviceId The service ID of the subscriber service
     * @param string $class The service's class name (which must implement EventSubscriberInterface)
     */
    public function addSubscriberService($serviceId, $class)
    {
        $rfc = new \ReflectionClass($class);
        if (!$rfc->implementsInterface('Symfony\Component\EventDispatcher\EventSubscriberInterface')) {
            throw new \InvalidArgumentException(
                "$class must implement Symfony\Component\EventDispatcher\EventSubscriberInterface"
            );
        }

        foreach ($class::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListenerService($eventName, array($serviceId, $params), 0);
            } elseif (is_string($params[0])) {
                $this->addListenerService($eventName, array($serviceId, $params[0]), isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListenerService($eventName, array($serviceId, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    public function removeSubscriberService($serviceId, $class)
    {
        $rfc = new \ReflectionClass($class);
        if (!$rfc->implementsInterface('Symfony\Component\EventDispatcher\EventSubscriberInterface')) {
            throw new \InvalidArgumentException(
                "$class must implement Symfony\Component\EventDispatcher\EventSubscriberInterface"
            );
        }

        foreach ($class::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->removeListener($eventName, array($serviceId, $params));
            } elseif (is_string($params[0])) {
                $this->removeListener($eventName, array($serviceId, $params[0]));
            } else {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, array($serviceId, $listener[0]));
                }
            }
        }
    }

    /**
     * {@inheritdocs}
     */
    public function dispatch($eventName, Event $event = null)
    {
        return $this->eventDispatcher->dispatch($eventName, $event);
    }

    /**
     * {@inheritdocs}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        return $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritdocs}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->eventDispatcher->addSubscriber($subscriber);
    }

    /**
     * {@inheritdocs}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->eventDispatcher->removeSubscriber($subscriber);
    }

    /**
     * {@inheritdocs}
     */
    public function getListeners($eventName = null)
    {
        return $this->eventDispatcher->getListeners($eventName);
    }

    /**
     * {@inheritdocs}
     */
    public function hasListeners($eventName = null)
    {
        return $this->eventDispatcher->hasListeners($eventName);
    }
}
