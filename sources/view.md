# Using Views

Views represent the user interface of your application. Views are often HTML files with embedded PHP code that perform tasks
related solely to the presentation of the data. Views handle the job of providing data to the web browser or other tool that
is used to make requests from your application.

`ManaPHP\\Mvc\\View` is responsible for the managing the view layer of your MVC application.

## Integrating Views with Controllers

ManaPHP automatically passes the execution to the view component as soon as a particular controller has completed its cycle. The view component
will look in the views folder for a folder named as the same name of the last controller executed and then for a file named as the last action
executed. For instance, if a request is made to the URL *http://127.0.0.1/blog/post/show/301*, ManaPHP will parse the URL as follows:

| Server Address    | 127.0.0.1 |
|-------------------|:----------|
| Module            | blog      |
| Controller        | post      |
| Action            | show      |
| Parameter         | 301       |

The dispatcher will look for a "PostController" and its action "showAction". A simple controller file for this example:

```php
    <?php

    namespace Application\Blog\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function showAction($postId)
        {
            // Pass the $postId parameter to the view
            $this->view->setVar('postId',$postId);
        }
    }
```

The `setVar` allows us to create view variables on demand so that they can be used in the view template. The example above demonstrates
how to pass the `$postId` parameter to the respective view template.

## Hierarchical Rendering
`ManaPHP\\Mvc\\View` supports a hierarchy of files. This hierarchy allows for common layout points (commonly used views), as well as controller named folders defining respective view templates.

This component uses by default PHP itself as the template engine, therefore views should have the `.phtml` extension.
If the views directory is  `@app/Blog/Views` then view component will find automatically for these 2 view files.

| Name              | File                                 | Description                                                                                                                                                                                                              |
|-------------------|:-------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Action View       | `@app/Blog/Views/Post/Show.phtml`    | This is the view related to the action. It only will be shown when the "show" action was executed.                                                                                                                       |
| Controller Layout | `@app/Blog/Views/Layouts/Post.phtml` | This is the view related to the controller. It only will be shown for every action executed within the controller "Post". All the code implemented in the layout will be reused for all the actions in this controller. |

You are required to implement all of the files mentioned above. they will be processed as follows:

```php
        <!-- Application/Blog/Views/Post/Show.phtml -->

        <h3>This is show view!</h3>

        <p>I have received the parameter <?php echo $postId; ?></p>
```

```php
    <!-- Application/Blog/Views/Layouts/Post.phtml -->

    <html>
        <head>
            <title>Example</title>
        </head>
        <body>

        <h2>This is the "post" controller layout!</h2>

        <?php echo $view->getContent(); ?>

        </body>
    </html>
```

Note the lines where the method `$view->getContent()` was called. This method instructs `ManaPHP\\Mvc\\View`
on where to inject the contents of the previous view executed in the hierarchy. For the example above, the output will be:

.. figure:: ../_static/img/views-1.png
   :align: center

The generated HTML by the request will be:

```php
    <!-- Application/Blog/Views/Layouts/Post.phtml -->

    <html>
        <head>
            <title>Example</title>
        </head>
        <body>

        <h2>This is the "posts" controller layout!</h2>

        <!-- Application/Blog/Views/Post/Show.phtml -->

        <h3>This is show view!</h3>

        <p>I have received the parameter 101</p>

        </body>
    </html>
```

## Picking Views

As mentioned above, when `ManaPHP\\Mvc\\View` is managed by `ManaPHP\\Mvc\\Application`
the view rendered is the one related with the last controller and action executed. You could override this by using the `ManaPHP\Mvc\View::pick()` method:

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class ProductController extends Controller
    {
        public function listAction()
        {
            // Pick "@app/Home/Product/Search" as view to render
            $this->view->pick("product/search");

            // Pick "@app/Home/Product/List" as view to render
            $this->view->pick('list');
        }
    }
```

## Disabling the view
If your controller doesn't produce any output in the view (or not even have one) you may disable the view component avoiding unnecessary processing:

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class SessionController extends Controller
    {
        public function closeAction()
        {
            // Close session
            // ...

            // A HTTP Redirect
            $this->response->redirect('index/index');

            // Return false to avoid rendering
            return false;
        }
    }
```

You can return a 'response' object to avoid disable the view manually:

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class SessionController extends Controller
    {
        public function closeAction()
        {
            // Close session
            // ...

            // A HTTP Redirect
            return $this->response->redirect('index/index');
        }
    }
```

## Using Partials

Partial templates are another way of breaking the rendering process into simpler more manageable chunks that can be reused by different
parts of the application. With a partial, you can move the code for rendering a particular piece of a response to its own file.

One way to use partials is to treat them as the equivalent of subroutines: as a way to move details out of a view so that your code can be more easily understood.
For example, you might have a view that looks like this:

```php
    <div class="top"><?php $view->partial("Shared/AdBanner"); ?></div>

    <div class="content">
        <h1>Robots</h1>

        <p>Check out our specials for robots:</p>
        ...
    </div>

    <div class="footer"><?php $view->partial("Shared/Footer"); ?></div>
```

Method `partial()` does accept a second parameter as an array of variables/parameters that only will exists in the scope of the partial:

```php

    <?php $view->partial("Shared/AdBanner", array('id' => $site->id, 'size' => 'big')); ?>
```

## Transfer values from the controller to views
`ManaPHP\\Mvc\\View` is available in each controller using the view component (`$this->view`). You can
use that object to set variables directly to the view from a controller action by using the `setVar()` method.

```php
    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function showAction($postId)
        {
            // Pass all the posts to the views
            $this->view->setVar('posts', Posts::find());

            $post=Post::findFirst((int)$postId);
            // Passing more than one variable at the same time
            $this->view->setVars([
                        'title'   => $post->title,
                        'content' => $post->content
                    ]);
        }
    }
```

A variable with the name of the first parameter of `setVar()` will be created in the view, ready to be used. The variable can be of any type,
from a simple string, integer etc. variable to a more complex structure such as array, collection etc.

```php
    <div class="post">
    <?php

        foreach ($posts as $post) {
            echo "<h1>", $post->title, "</h1>";
        }

    ?>
    </div>
```

## Using models in the view layer
[Models](model.html) are always available at the view layer. The `ManaPHP\\Loader` will instantiate them at
runtime automatically:

```php
    <div class="categories">
    <?php

        foreach (Category::find(['status' => 1]) as $category) {
            echo "<span class='category'>", $category->name, '</span>';
        }

    ?>
    </div>
```

Although you may perform model manipulation operations such as create() or update() in the view layer, it is not recommended since it is not possible to forward the execution flow to another controller in the case of an error or an exception.

## Template Engines
Template Engines help designers to create views without the use of a complicated syntax. view uses `renderer` service to render template,
so please refer to [renderer](renderer.html).

## Injecting services in View
Every view executed is included inside a `ManaPHP\\Component` instance, providing easy access to the application's service container.

The following example shows how to write a jQuery [ajax request](http://api.jquery.com/jQuery.ajax/) using a URL with the framework conventions.
The service "url" (usually `ManaPHP\\Mvc\\Url`) is injected in the view by accessing a property with the same name:

```php
    <script type="text/javascript">

    $.ajax({
        url: "<?=$view->url->get("cities/get"); ?>"
    })
    .done(function () {
        alert("Done!");
    });

    </script>
```

## View Events
`ManaPHP\\Mvc\\View` is able to send events if it is present. Events are triggered using the type "view".
 Some events when returning boolean false could stop the active operation. The following events are supported:

| Event Name           | Triggered                                                  | Can stop operation? |
|----------------------|:-----------------------------------------------------------|:--------------------|
| beforeRender         | Triggered before starting the render process               | No                 |
| afterRender          | Triggered after completing the render process              | No                  |

The following example demonstrates how to attach listeners to this component:

```php
    <?php

    use ManaPHP\Mvc\View;

    $di->setShared('view', function () {

        $view = new View();
        $view->setViewsDir("../app/views/");

        // Attach a listener for type "view"
        $view->attachEvent("view", function ($event, $view) {
            echo $event->getType(), PHP_EOL;
        });

        return $view;
    });
```

The following example shows how to create a plugin that clean/repair the HTML produced by the render process using [Tidy](http://www.php.net/manual/en/book.tidy.php):

```php
    <?php

    class TidyPlugin
    {
        public function afterRender($event, $view)
        {
            $tidyConfig = array(
                'clean'          => true,
                'output-xhtml'   => true,
                'show-body-only' => true,
                'wrap'           => 0
            );

            $tidy = tidy_parse_string($view->getContent(), $tidyConfig, 'UTF8');
            $tidy->cleanRepair();

            $view->setContent((string) $tidy);
        }
    }

    // Attach the plugin as a listener
    $view->attachEvent("view:afterRender", new TidyPlugin());
```