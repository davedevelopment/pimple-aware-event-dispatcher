<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PimpleAwareEventDispatcher\Test;

use Pimple;
use PimpleAwareEventDispatcher\PimpleAwareEventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * container test cases.
 *
 * @author Dave Marshall <dave.marshall@atstsolutions.co.uk>
 */
class PimpleAwareEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->container = new Pimple;
        $this->container['foo.service'] = $this->container->share(function() {
            return new FooService;
        });
        $dispatcher = new EventDispatcher;
        $this->dispatcher = new PimpleAwareEventDispatcher($dispatcher, $this->container);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddListenerServiceThrowsIfCallbackNotArray()
    {
        $this->dispatcher->addListenerService('foo', 'onBar');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddListenerServiceThrowsIfCallbackWrongSize()
    {
        $this->dispatcher->addListenerService('foo', array('onBar'));
    }

    public function testAddListenerService()
    {
        $this->dispatcher->addListenerService('foo', array('foo.service', 'onFoo'), 5);
        $this->dispatcher->addListenerService('foo', array('foo.service', 'onBar1'));
        $this->dispatcher->dispatch('foo', new Event());
        $this->assertEquals("foobar1", $this->container['foo.service']->string);
    }

    public function testRemoveListener()
    {
        $this->dispatcher->addListenerService('foo', array('foo.service', 'onFoo'), 5);
        $this->dispatcher->addListenerService('foo', array('foo.service', 'onBar1'));
        $this->dispatcher->removeListener('foo', array('foo.service', 'onFoo'));
        $this->dispatcher->dispatch('foo', new Event());
        $this->assertEquals("bar1", $this->container['foo.service']->string);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddSubscriberThrowsIfClassNotImplementEventSubscriberInterface()
    {
        $this->dispatcher->addSubscriberService('foo.service', 'stdClass');
    }


    public function testAddSubscriberService()
    {
        $this->dispatcher->addSubscriberService('foo.service', 'PimpleAwareEventDispatcher\Test\FooService');
        $this->dispatcher->dispatch('foo', new Event());
        $this->assertEquals("foo", $this->container['foo.service']->string);
        $this->dispatcher->dispatch('bar', new Event());
        $this->assertEquals("foobar2bar1", $this->container['foo.service']->string);
        $this->dispatcher->dispatch('buzz', new Event());
        $this->assertEquals("foobar2bar1buzz", $this->container['foo.service']->string);
    }

}

class FooService implements EventSubscriberInterface
{
    public $string = '';

    public function onFoo(Event $e)
    {
        $this->string.= 'foo';
    }

    public function onBar1(Event $e)
    {
        $this->string.= 'bar1';
    }

    public function onBar2(Event $e)
    {
        $this->string.= 'bar2';
    }

    public function onBuzz(Event $e)
    {
        $this->string.= 'buzz';
    }

    public static function getSubscribedEvents()
    {
        return array(
            'foo' => 'onFoo',
            'bar' => array(
                array('onBar1'),
                array('onBar2', 10),
            ),
            'buzz' => array('onBuzz', 5),
        );
    }
}
