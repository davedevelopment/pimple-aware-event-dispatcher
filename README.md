Pimple Aware Event Dispatcher
=============================

Installation
------------

```
composer.phar require "davedevelopment/pimple-aware-event-dispatcher:*@dev"
```

Usage
-----

To use in a [Silex](http://silex.sensiolabs.org) application:

``` php
<?php

use PimpleAwareEventDispatcher\PimpleAwareEventDispatcher;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;

$app = new Application;

// define the dispatcher
$app['event_dispatcher'] = function () use ($app) {
    $dispatcher = new EventDispatcher();
    return new PimpleAwareEventDispatcher($dispatcher, $app);
};

// define our application services
$app['some.service'] = function() use ($app) {
    // let's assume this takes a bit of doing and/or is dependant on several other
    // services
    sleep(1);
    return new SomeService;
};

// add a listener, that will lazily fetch the service when needed
$app['event_dispatcher']->addListenerService(
    "some.event",
    array("some.service", "serviceMethod")
);
```

