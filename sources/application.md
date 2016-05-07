---
title: application
---

# MVC Applications

All the hard work behind orchestrating the operation of MVC in ManaPHP is normally done by `ManaPHP\\Mvc\\Application`.
This component encapsulates all the complex operations required in the background, instantiating every component needed and integrating it with the project, to allow the MVC pattern to operate as desired.

## Multiple Module

A multi-module application uses the same document root for more than one module. In this case the following file structure can be used:

```bash
  Application/
    Frontend/
       Controllers/
       Models/
       Views/
       Module.php
    Backend/
       Controllers/
       Models/
       Views/
       Module.php
  Public/
    css/
    img/
    js/
```

Each directory in Application/ have its own MVC structure. A Module.php is present to configure specific settings of each module like autoloaders or custom services:

```php
    <?php

    namespace Multiple\Application\Backend;

    use ManaPHP\Loader;
    use ManaPHP\Mvc\View;
    use ManaPHP\DiInterface;
    use ManaPHP\Mvc\Dispatcher;
    use ManaPHP\Mvc\ModuleDefinitionInterface;

    class Module implements ModuleInterface
    {
        /**
         * Register a specific autoloader for the module
         */
        public function registerAutoloaders(DiInterface $di = null)
        {
            $loader = new Loader();

            $loader->registerNamespaces(
                array(
                    'Multiple\Application' => dirname(__DIR__),
                )
            );

            $loader->register();
        }

        /**
         * Register specific services for the module
         */
        public function registerServices(DiInterface $di)
        {
            // Registering a dispatcher
            $di->set('dispatcher', function () {
                $dispatcher = new Dispatcher();
                $dispatcher->setRootNamespace("Multiple\Application");
                return $dispatcher;
            });

            // Registering the view component
            $di->set('view', function () {
                $view = new View();
                $view->setAppDir(dirname(__DIR__));
                return $view;
            });
        }
    }
```

A special bootstrap file is required to load a multi-module MVC architecture:

```php

    <?php

    use ManaPHP\Mvc\Router;
    use ManaPHP\Mvc\Application;
    use ManaPHP\Di\FactoryDefault;

    $di = new FactoryDefault();

    // Specify routes for modules
    // More information how to set the router up https://docs.manaphp.com/en/latest/reference/routing.html
    $di->set('router', function () {

        $router = new Router();

        $router->mount(new Group(),'Frontend','/frontend');
        $router->mount(new Group(),'Backend','/backend');
        return $router;
    });

    try {

        // Create an application
        $application = new Application($di);

        // Register the installed modules
        $application->registerModules(
            array(
                'Frontend',
                'Backend',
            )
        );

        // Handle the request
        echo $application->handle()->getContent();

    } catch (\Exception $e) {
        echo $e->getMessage();
    }
```

When :doc:`ManaPHP\\Mvc\\Application` have modules registered, always is necessary that every matched route returns a valid module.
Each registered module has an associated class offering functions to set the module itself up. Each module class definition must implement two
methods: registerAutoloaders() and registerServices(), they will be called by `ManaPHP\\Mvc\\Application` according to the module to be executed.

Understanding the default behavior
----------------------------------
If you've been following the `tutorial <tutorial>` or have generated the code using `ManaPHP Devtools <tools>`,
you may recognize the following bootstrap file:

```php
<?php
use ManaPHP\Mvc\Application;

try {

    // Register autoloaders
    // ...

    // Register services
    // ...

    // Handle the request
    $application = new Application($di);

    echo $application->handle()->getContent();

} catch (\Exception $e) {
    echo "Exception: ", $e->getMessage();
}
```
The core of all the work of the controller occurs when handle() is invoked:

```php
<?php
echo $application->handle()->getContent();
```

## Application Events

`ManaPHP\\Mvc\\Application` is able to send events. Events are triggered using the type "application". The following events are supported:

|Event Name           |Triggered                                                     |
|---------------------|:-------------------------------------------------------------|
| boot                | Executed when the application handles its first request      |
| beforeStartModule   | Before initialize a module, only when modules are registered |
| afterStartModule    | After initialize a module, only when modules are registered  |
| beforeHandleRequest | Before execute the dispatch loop                             |
| afterHandleRequest  | After execute the dispatch loop                              |


The following example demonstrates how to attach listeners to this component:

```php

    <?php

    $application->attachEvent(
        "application",
        function ($event, $application) {
            // ...
        }
    );
```
External Resources
------------------
* `MVC examples on Github <https://github.com/manaphp/mvc>`_
