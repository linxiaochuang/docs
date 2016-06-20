# Routing
The router component allows you to define routes that are mapped to controllers or handlers that should receive the request. 
A router simply parses a URI to determine this information.

## Defining Routes
`ManaPHP\\Mvc\\Router` provides advanced routing capabilities. In MVC mode,
you can define routes and map them to /modules/controllers/actions that you require. A route is defined as follows:

```php
    <?php
    
    // Create a route group, which contains routes
    $group = new \ManaPHP\Mvc\Router\Group();

    // Add a route to the group
    $group->add("/admin/users/my-profile", array(
        "controller" => "user",
        "action" => "profile"
      ));

    // Add another route to the group
    $group->add("/admin/users/change-password", "user::changePassword");

    // Create the router
    $router = new \ManaPHP\Mvc\Router();

    // Mount a group to the route for default module
    $router->mount($group);

    $router->handle();
```

The first parameter of the `add()` method is the pattern you want to match and, optionally, the second parameter is a set of paths.
In this case, if the URI is /admin/users/my-profile, then the "user" controller with its action "profile" will be executed. 
It's important to remember that the router does not execute the controller and action, 
it only collects this information to inform the correct component (ie. `ManaPHP\\Mvc\\Dispatcher`) that this is the controller/action it should execute.

An application can have many paths and defining routes one by one can be a cumbersome task. In these cases we can create more flexible routes:

```php
    <?php

    // Create a route group, which contains routes
    $group = new \ManaPHP\Mvc\Router\Group();

    // Create the router
    $router = new \ManaPHP\Mvc\Router();

    // Mount a group to the route for default module
    $router->mount($group);

    // Define a route
    $group->add("/admin/:controller/a/:action/:params", array(
        "controller" => 1,
        "action" => 2,
        "params" => 3
      ));
```

In the example above, we're using wildcards to make a route valid for many URIs. 
For example, by accessing the following URL (/admin/user/a/delete/dave/301) would produce:

| Controller | user          |
|------------|:--------------|
| Action     | delete        |
| Parameter  | dave          |
| Parameter  | 301           |

The `add()` method receives a pattern that can optionally have predefined placeholders and regular expression modifiers. 
The regular expression syntax used is the same as the [`PCRE regular expressions`](http://www.php.net/manual/en/book.pcre.php). 
Note that, it is not necessary to add regular expression delimiters. 

> All route patterns are case-insensitive and All the routing patterns must start with a forward slash character (/).

The second parameter defines how the matched parts should bind to the controller/action/parameters. 
Matching parts are placeholders or sub-patterns delimited by parentheses (round brackets). 
In the example given above, the first sub-pattern matched (:controller) is the controller part of the route, the second the action and so on.

These placeholders help writing regular expressions that are more readable for developers and easier to understand. 
The following placeholders are supported:

| Placeholder          | Regular Expression          | Usage                                                                                      |
|----------------------|:----------------------------|:-------------------------------------------------------------------------------------------|
| `/:module`     | `/([a-z0-9_-]+)`      | Matches a valid module name with alpha-numeric characters only                                         |
| `/:controller` | `/([a-z0-9_-]+)`      | Matches a valid controller name with alpha-numeric characters only                                     |
| `/:action`     | `/([a-z0-9_-]+)`      | Matches a valid action name with alpha-numeric characters only                                         |
| `/:params`     | `(/.*)*`              | Matches a list of optional words separated by slashes. Only use this placeholder at the end of a route |
| `/:int`        | `/([0-9]+)`           | Matches an integer parameter                                                                           |
    
Controller names are camelized, this means that characters (`-`) and (`_`) are removed and the next character is uppercased. 
For instance, blog_comment is converted to BlogComment.

Since you can add many routes as you need using the `add()` method, the order in which routes are added indicate their relevance, 
latest routes added have more relevance than first added. **Internally, all defined routes
are traversed in reverse order until `ManaPHP\\Mvc\\Router` finds the one that matches the given URI and processes it, while ignoring the rest.**

## Parameters with Names
The example below demonstrates how to define names to route parameters:

```php
    <?php

    $group->add(
        "/news/{year:[0-9]{4}}/{month:[0-9]{2}}/{day:[0-9]{2}}/:params",
        array(
            "controller" => "post",
            "action"     => "show",
        )
    );
```
In the above example, the route doesn't define a "controller" or "action" part. These parts are replaced
with fixed values ("post" and "show"). The user will not know the controller that is really dispatched
by the request. Inside the controller, those named parameters can be accessed as follows:

```php
    <?php

    class PostController extends ManaPHP\Mvc\Controller
    {
        public function indexAction()
        {

        }

        public function showAction()
        {
            // Get "year" parameter
            $year = $this->dispatcher->getParam("year");

            // Get "month" parameter
            $month = $this->dispatcher->getParam("month");

            // Get "day" parameter
            $day = $this->dispatcher->getParam("day");

            // ...
        }
    }
```

Note that the values of the parameters are obtained from the dispatcher. 
This happens because it is the component that finally interacts with the drivers of your application. 
Moreover, there is also another example to create named parameters as part of the pattern:

```php
    <?php

    $group->add(
        "/documentation/{chapter}/{name}.{type:[a-z]+}",
        array(
            "controller" => "documentation",
            "action"     => "show"
        )
    );
```
You can access their values in the same way as before:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class DocumentationController extends Controller
    {
        public function showAction()
        {
            // Get "name" parameter
            $name = $this->dispatcher->getParam("name");

            // Get "type" parameter
            $type = $this->dispatcher->getParam("type");

            // ...
        }
    }
```

## Short Syntax
If you don't like using an array to define the route paths, an alternative syntax is also available.
The following examples produce the same result:

```php
    <?php

    // Short form
    $group->add("/posts/{year:[0-9]+}/{title:[a-z\-]+}", "Post::show");

    // Array form
    $group->add(
        "/posts/{year:[0-9]+}/{title:[a-z\-]+}",
        array(
           "controller" => "post",
           "action"     => "show",
        )
    );
```

The following short syntax are supported:

| pattern                    | sample            |
|----------------------------|:------------------|
| module::controller::action | admin::user::list |
| controller::action         | user::list        |
| controller                 | user::index       |

## Mixing Array and Short Syntax
Array and short syntax can be mixed to define a route, in this case note that named parameters automatically
are added to the route paths according to the position on which they were defined:

```php
    <?php

    // First position must be skipped because it is used for
    // the named parameter 'country'
    $group->add('/news/{country:[a-z]{2}}/([a-z+])/([a-z\-+])',
        array(
            'section' => 2, // Positions start with 2, because 'country' occupies a position
            'article' => 3
        )
    );
```

## HTTP Method Restrictions
When you add a route using simply `add()`, the route will be enabled for any HTTP method. 
Sometimes we can restrict a route to a specific method, this is especially useful when creating RESTful applications:

```php
    <?php

    // This route only will be matched if the HTTP method is GET
    $group->addGet("/products/{product_id:\d+}", "Product::edit");

    // This route only will be matched if the HTTP method is POST
    $group->addPost("/products/{product_id:\d+}", "ProductController::updateAction");

    // This route will be matched if the HTTP method is POST or PUT
    $group->add("/products/{product_id:\d+}", "Product::update",["POST", "PUT"]);
```
## Groups of Router
The router is composed of routes group. 
After adding routes to the group, if you want the group to become effective, you need mount which to the router.

you can mount the routes group to domain only, path only or domain and path.

$group->mount($group,'blog','blog.manaphp.com'); means mount the blog module to blog.manaphp.com
$group->mount($group,'blog') or $group->mount($group,'blog','/blog'); means mount the blog module to /blog path.
$group->mount($group,'blog,'www.manaphp.com/blog'); means mount the blog module to www.manaphp.com/blog.

```php
    <?php

    $router = new \ManaPHP\Mvc\Router();

    $blog = new \ManaPHP\Mvc\Router\Group();

    // Add a route to the group: controller='blog',action='save'
    $blog->addPost('/blogs','blog::save')
    );

    // Add another route to the group: controller='blog',action='edit'
    $blog->addPost('/blog/{blog_id}','blog::edit'
    );

    // Add another route with short path: controller='blog',action='index'
    $blog->addGet('/blogs','blog');

    // Add the group which bind to blog module to the router
    $router->mount($blog,'blog');
```

You can move groups of routes to separate files in order to improve the organization and code reusing in the application:

```php
    <?php
    namespace Applcation\Blog;
    
    class RouteGroup extends ManaPHP\Mvc\Router\Group
    {
        public function __construct()
        {
            parent::__construct(true);

            // Add a route to the group: controller='blog',action='save'
            $this->addPost('/blogs','blog::save')
            );

            // Add another route to the group: controller='blog',action='edit'
            $this->addPost('/blogs/{blog_id}','blog::edit'
            );

            // Add another route with short path: controller='blog',action='index'
            $this->addGet('/blogs','blog');
        }
    }
```

Then mount the group in the router:

```php
    <?php

    // Add the group to the router
    $router->mount(new \Applcation\Blog\RouteGroup(),'blog');
```

## Matching Routes
A valid URI must be passed to the Router so that it can process it and find a matching route.
By default, the routing URI is taken from the `$_GET['_url']` variable that is created by the rewrite engine module. 
A couple of rewrite rules that work very well with ManaPHP are:

```apache

    RewriteEngine On
    RewriteCond   %{REQUEST_FILENAME} !-d
    RewriteCond   %{REQUEST_FILENAME} !-f
    RewriteRule   ^((?s).*)$ index.php?_url=/$1 [QSA,L]
```

In this configuration, any requests to files or folders that don't exist will be sent to index.php.

The following example shows how to use this component in stand-alone mode:

```php
    <?php

    // Creating a router
    $router = new \ManaPHP\Mvc\Router();

    $group =new \ManaPHP\Mvc\Router\Group();
    // Define routes group here if any
    // ...
    $router->mount($group);

    // Taking URI from $_GET["_url"]
    $router->handle();

    // Or Setting the URI value directly
    $router->handle("/employees/edit/17");

    // Getting the processed controller
    echo $router->getControllerName();

    // Getting the processed action
    echo $router->getActionName();
```

## Naming Routes

Each route that is added to the router is stored internally as a `ManaPHP\\Mvc\\Router\\Route` object.
That class encapsulates all the details of each route. For instance, we can give a name to a path to identify it uniquely in our application.
This is especially useful if you want to create URLs from it.

```php
    <?php

    $route = $router->add("/posts/{year}/{title}", "Posts::show");

    $route->setName("show-posts");

    // Or just

    $router->add("/posts/{year}/{title}", "Posts::show")->setName("show-posts");
```

Then, using for example the component `ManaPHP\\Mvc\\Url` we can build routes from its name:

```php

    <?php

    // Returns /posts/2012/ManaPHP-1-0-released
    echo $url->get(
        array(
            "for"   => "show-posts",
            "year"  => "2012",
            "title" => "ManaPHP-1-0-released"
        )
    );
```

## Usage Examples

The following are examples of custom routes:

```php
    <?php

    // Matches "/system/admin/a/edit/7001"
    $group->add(
        "/system/:controller/a/:action/:params",
        array(
            "controller" => 1,
            "action"     => 2,
            "params"     => 3
        )
    );

    // Matches "/es/news"
    $group->add(
        "/([a-z]{2})/:controller",
        array(
            "controller" => 2,
            "action"     => "index",
            "language"   => 1
        )
    );

    // Matches "/es/news"
    $group->add(
        "/{language:[a-z]{2}}/:controller",
        array(
            "controller" => 2,
            "action"     => "index"
        )
    );

    // Matches "/admin/posts/edit/100"
    $group->add(
        "/admin/:controller/:action/:int",
        array(
            "controller" => 1,
            "action"     => 2,
            "id"         => 3
        )
    );

    // Matches "/posts/2015/02/some-cool-content"
    $group->add(
        "/posts/([0-9]{4})/([0-9]{2})/([a-z\-]+)",
        array(
            "controller" => "posts",
            "action"     => "show",
            "year"       => 1,
            "month"      => 2,
            "title"      => 4
        )
    );

    // Matches "/manual/en/translate.adapter.html"
    $group->add(
        "/manual/([a-z]{2})/([a-z\.]+)\.html",
        array(
            "controller" => "manual",
            "action"     => "show",
            "language"   => 1,
            "file"       => 2
        )
    );

    // Matches /feed/fr/le-robots-hot-news.atom
    $group->add(
        "/feed/{lang:[a-z]+}/{blog:[a-z\-]+}\.{type:[a-z\-]+}",
        "Feed::get"
    );

    // Matches /api/v1/users/peter.json
    $group->add(
        '/api/(v1|v2)/{method:[a-z]+}/{param:[a-z]+}\.(json|xml)',
        array(
            'controller' => 'api',
            'version'    => 1,
            'format'     => 4
        )
    );
```

>    Beware of characters allowed in regular expression for controllers and namespaces. 
    As these become class names and in turn they're passed through the file system could be used by attackers to
    read unauthorized files. A safe regular expression is: `/([a-z0-9_-]+)`

## Default Behavior
`ManaPHP\\Mvc\\Router\\Group` has a default behavior that provides a very simple routing that always expects a URI that matches the following pattern: `/:controller/:action/:params`
For example, for a URL like this *http://www.manaphp.com/documentation/show/about.html*, this router will translate it as follows:

|part        | value         |
|:-----------|:--------------|
| Controller | documentation |
| Action     | show          |
| Parameter  | about.html    |

If you don't want the router to have this behavior, you must create the route group passing `false` as the first parameter:

```php

    <?php

    // Create the route group without default routes
    $router = new \ManaPHP\Mvc\Router\Group(false);
```

## Dealing with extra/trailing slashes
Sometimes a route could be accessed with extra/trailing slashes.
Those extra slashes would lead to produce a not-found status in the dispatcher.
You can set up the router to automatically remove the slashes from the end of handled route:

```php

    <?php

    $router = new \ManaPHP\Mvc\Router();

    // Remove trailing slashes automatically
    $router->removeExtraSlashes(true);
```

Or, you can modify specific routes to optionally accept trailing slashes:

```php
    <?php

    // The [/]{0,1} allows this route to have optionally have a trailing slash
    $group->add(
        '/{language:[a-z]{2}}/:controller[/]{0,1}',
        array(
            'controller' => 2,
            'action'     => 'index'
        )
    );
```
## URI Sources
By default the URI information is obtained from the `$_GET['_url']` variable, this is passed by the Rewrite-Engine to ManaPHP, 
Or you can manually pass a URI to the `handle()` method:

```php
    <?php

    $router->handle('/some/route/to/handle');
```

## Testing your routes

Since this component has no dependencies, you can create a file as shown below to test your routes:

```php
    <?php

    // These routes simulate real URIs
    $testRoutes = array(
        '/',
        '/index',
        '/index/index',
        '/index/test',
        '/products',
        '/products/index/',
        '/products/show/101',
    );

    $router = new ManaPHP\Mvc\Router\Router();
    $routeGroup=new ManaPHP\Mvc\Router\Group();
    $router->mount($routeGroup,'/','Home');
    // Add here your custom routes
    // ...

    // Testing each route
    foreach ($testRoutes as $testRoute) {

        // Handle the route
        $router->handle($testRoute);

        echo 'Testing ', $testRoute, '<br>';

        // Check if some route was matched
        if ($router->wasMatched()) {
            echo 'Controller: ', $router->getControllerName(), '<br>';
            echo 'Action: ', $router->getActionName(), '<br>';
        } else {
            echo 'The route was not matched by any route<br>';
        }

        echo '<br>';
    }
```

## Implementing your own Router
The `ManaPHP\\Mvc\\RouterInterface` interface must be implemented to create your own router replacing the one provided by ManaPHP.