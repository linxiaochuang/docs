# MVC Application

All the hard work behind orchestrating the operation of MVC in ManaPHP is normally done by `ManaPHP\\Mvc\\Application`.
This component encapsulates all the complex operations required in the background, instantiating every component needed and integrating it with the project, to allow the MVC pattern to operate as desired.

## Application
```php
namespace Application {

    use ManaPHP\Mvc\Router;
    use ManaPHP\Mvc\Router\Group;

    class Application extends \ManaPHP\Mvc\Application
    {

        protected function registerServices()
        {
            $self = $this;

            $this->_dependencyInjector->setShared('configure', new Configure());

            $this->_dependencyInjector->setShared('router', function () {
                return (new Router())->mount(new Group(), 'Home', '/');
            });

            $this->_dependencyInjector->setShared('authorization', new Authorization());
        }

        public function main()
        {
            date_default_timezone_set('PRC');

            $this->debugger->start();

            $this->registerServices();

            //   $this->useImplicitView(false);

            $this->registerModules(['Home']);

            return $this->handle()->getContent();
        }

    }
}
```
## Modules

Application uses the same document root for more than one module. In this case the following file structure can be used:

```bash
    manaphp_app/
        Application/
            Home/
                Controllers/
                Models/
                Views/
                Widgets/
                Module.php
            Application.php
            Authorization.php
            Configure.php
            Exception.php
        Public/
            css/
            img/
            js/
            index.php
```

Each module has its own MVC structure. A `Module.php` is present to configure specific settings of each module like autoloaders or custom services:

```php
    <?php

    namespace Application\Backend;

    use ManaPHP\DiInterface;
    use ManaPHP\Mvc\ModuleInterface;

    class Module implements ModuleInterface
    {
        /**
         * Register a specific autoloader for the module
         */
        public function registerAutoloaders(DiInterface $di = null)
        {
        }

        /**
         * Register specific services for the module
         */
        public function registerServices(DiInterface $di)
        {
        }
    }
```

When `ManaPHP\\Mvc\\Application` have modules registered, always is necessary that every matched route returns a valid module.
Each registered module has an associated class offering functions to set the module itself up. Each module class definition must implement two
methods: registerAutoloaders() and registerServices(), they will be called by `ManaPHP\\Mvc\\Application` according to the module to be executed.

## Application Events

`ManaPHP\\Mvc\\Application` is able to send events. Events are triggered using the type "application". The following events are supported:

|Event Name           |Triggered                                                     |
|---------------------|:-------------------------------------------------------------|
| boot                | Executed when the application handles its first request      |
| beforeStartModule   | Before initialize a module, only when modules are registered |
| afterStartModule    | After initialize a module, only when modules are registered  |


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

