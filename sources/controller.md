# Using Controllers

The controllers provide a number of methods that are called actions. Actions are methods on a controller that handle requests. By default all
public methods ended with **Action** suffix on a controller map to actions and are accessible by a URL.
Actions are responsible for interpreting the request and creating the response.
Usually responses are in the form of a rendered view, but there are other ways to create responses as well.

For instance, when you access a URL like this: http://localhost/post/show/2016/the-post-title ManaPHP by default will decompose each
part like this:

|Name                   |Value           |
|-----------------------|:---------------|
| **Controller**        | post           |
| **Action**            | show           |
| **Parameter**         | 2016           |
| **Parameter**         | the-post-title |

In this case, the PostController will handle this request. Controllers must have the suffix `Controller` while actions the suffix `Action`. A sample of a controller is as follows:

```php

    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function showAction()
        {

        }
    }
```

A controller can optionally extend `ManaPHP\\Mvc\\Controller`. By doing this, the controller can have easy access to
the application services.

You can get an arbitrary parameter from its name in the following way:

```php

    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function showAction()
        {
            $year      = $this->dispatcher->getParam(0);
            $postTitle = $this->dispatcher->getParam(1);
        }
    }
```

## Dispatch Loop

The dispatch loop will be executed within the Dispatcher until there are no actions left to be executed. In the previous example only one
action was executed. Now we'll see how "forward" can provide a more complex flow of operation in the dispatch loop, by forwarding
execution to a different controller/action.

```php

    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function showAction($year, $postTitle)
        {
            $this->flash->error("You don't have permission to access this area");

            // Forward flow to another action
            $this->dispatcher->forward('user/signin');
        }
    }
```

If users don't have permissions to access a certain action then will be forwarded to the User controller, signin action.

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class UserController extends Controller
    {
        public function indexAction()
        {

        }

        public function signinAction()
        {

        }
    }
```
There is no limit on the "forwards" you can have in your application, so long as they do not result in circular references, at which point
your application will halt. If there are no other actions to be dispatched by the dispatch loop, the dispatcher will automatically invoke
the view layer of the MVC that is managed by `ManaPHP\\Mvc\\View`.

## Initializing Controllers

`ManaPHP\\Mvc\\Controller` offers the initialize method, which is executed first, before any
action is executed on a controller. The use of the `__construct` method is not recommended.

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public $settings;

        public function initialize()
        {
            $this->settings = array(
                "mySetting" => "value"
            );
        }

        public function saveAction()
        {
            if ($this->settings["mySetting"] == "value") {
                // ...
            }
        }
    }
```



> Method `initialize` is only called if the event `beforeExecuteRoute` is executed with success. This avoid that application logic in the initializer can be executed without authorization.

If you want to execute some initialization logic just after build the controller object you can implement the
method `onConstruct`:

```php

    <?php

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function onConstruct()
        {
            // ...
        }
    }
```

>    Be aware that method `onConstruct` is executed even if the action to be executed not exists
    in the controller or the user does not have access to it (according to custom control access
    provided by developer).

## Injecting Services

If a controller extends `ManaPHP\\Mvc\\Controller` then it has easy access to the service
container in application. For example, if we have registered a service like this:

```php
    <?php

    use ManaPHP\Di;

    $di = new Di();

    $di->setShared('storage', function () {
        return new Storage('/some/directory');
    });
```
Then, we can access to that service in several ways:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class FileController extends Controller
    {
        public function saveAction()
        {
            // Injecting the service by just accessing the property with the same name
            $this->storage->save('/some/file');

            // Accessing the service from the DI
            $this->di->get('storage')->save('/some/file');
        }
    }
```

If you're using ManaPHP as a full-stack framework, you can read the services provided by [default di](di.html) in the framework.

## Request
The `request` service contains an instance of `ManaPHP\\Http\\Request`.

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function saveAction()
        {
            // Check if request has made with POST
            if ($this->request->isPost()) {
                // Access POST data
                $customerName = $this->request->getPost("name");
                $customerBorn = $this->request->getPost("born");
            }
        }
    }
```

Learn more about the HTTP environment in their dedicated articles [request](request.html).

## Response

The `response` contains a `ManaPHP\\Http\\Response` representing what is going to be sent back to the client.
The response object is not usually used directly, but is built up before the execution of the action, sometimes - like in
an afterDispatch event - it can be useful to access the response directly:

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function notFoundAction()
        {
            // Send a HTTP 404 response header
            $this->response->setStatusCode(404, "Not Found");
        }
    }
```

Learn more about the HTTP environment in their dedicated articles [response](response.html).

## Session

Session helps us maintain persistent data between requests. You could access a `ManaPHP\\Session\\Bag`
from any controller to encapsulate data that need to be persistent.

```php

    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class UserController extends Controller
    {
        public function indexAction()
        {
            $this->persistent->set('name', 'Michael');
        }

        public function welcomeAction()
        {
            echo "Welcome, ", $this->persistent->get('name');
        }
    }
```

## Using Services as Controllers

Services may act as controllers, controllers classes are always requested from the services container. Accordingly,
any other class registered with its name can easily replace a controller:

```php
    <?php

    // Register a controller as a service
    $di->set('IndexController', function () {
        $component = new Component();
        return $component;
    });

    // Register a namespace-used controller as a service
    $di->set('Application\Home\Controllers\IndexController', function () {
        $component = new Component();
        return $component;
    });
```

## Creating a Base Controller

Some application features like access control lists, translation, cache, and template engines are often common to many
controllers. In cases like these the creation of a "base controller" is encouraged to ensure your code stays [DRY]. A base
controller is simply a class that extends the `ManaPHP\\Mvc\\Controller` and encapsulates
the common functionality that all controllers must have. In turn, your controllers extend the "base controller" and have
access to the common functionality.

This class could be located anywhere, but for organizational conventions we recommend it to be in the controllers folder,
e.g. Application/Home/Controllers/ControllerBase.php.

The implementation of common components (actions, methods, properties etc.) resides in this file:

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class ControllerBase extends Controller
    {
        /**
         * This action is available for multiple controllers
         */
        public function someAction()
        {

        }
    }
```

Any other controller now inherits from `ControllerBase`, automatically gaining access to the common components (discussed above):

```php

    <?php

    namespace Application\Home\Controllers;

    class UserController extends ControllerBase
    {

    }
```

## Events in Controllers

Controllers automatically act as listeners for [dispatcher](dispatching.html) events, implementing methods with those event names allow
you to implement hook points before/after the actions are executed:

```php

    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function beforeExecuteRoute($dispatcher)
        {
            // This is executed before every found action
            if ($dispatcher->getActionName() == 'save') {

                $this->flash->error("You don't have permission to save posts");

                $this->dispatcher->forward('home/index);
                return false;
            }
        }

        public function afterExecuteRoute($dispatcher)
        {
            // Executed after every found action
        }
    }
```

[DRY]: https://en.wikipedia.org/wiki/Don%27t_repeat_yourself
