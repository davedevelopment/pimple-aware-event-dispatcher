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

use Silex\Application;

$app = new Application;

// override the dispatcher
$app['dispatcher_class'] = "PimpleAwareEventDispatcher\PimpleAwareEventDispatcher";
$app['dispatcher']->extend('dispatcher', function($dispatcher) use ($app) {
    $dispatcher->setContainer($app);
    return $dispatcher;
});

// define our application services
$app['some.service'] = $app->share(function() use ($app) {
    // let's assume this takes a bit of doing and/or is dependant on several other
    // services
    sleep(1);
    return new SomeService;
});

// add a listener, that will lazily fetch the service when needed
$app['dispatcher']->addListenerService("some.event", array("some.service", "serviceMethod"));

```

