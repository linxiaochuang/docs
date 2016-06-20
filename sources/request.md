# Request Environment
Every HTTP request contains additional information regarding the request such as header data, files, variables, etc. 
A web based application needs to parse that information so as to provide the correct
response back to the requester. `ManaPHP\\Http\\Request` encapsulates the
information of the request, allowing you to access it in an object-oriented way.

```php
    <?php

    use ManaPHP\Http\Request;

    // Getting a request instance
    $request = new Request();

    // Check whether the request was made with method POST
    if ($request->isPost()) {
        // Check whether the request was made with Ajax
        if ($request->isAjax()) {
            echo "Request was made using POST and AJAX";
        }
    }
```

## Getting Values
PHP automatically fills the superglobal arrays `$_GET` and `$_POST`. These arrays
contain the values present in forms submitted or the parameters sent via the URL. The variables in the arrays are
never sanitized and can contain illegal characters or even malicious code, which can lead to [SQL injection] or
[Cross Site Scripting (XSS)] attacks.

`ManaPHP\\Http\\Request` allows you to access the values stored in the `$_REQUEST`,
`$_GET` and `$_POST` arrays and sanitize or filter them with the 'filter' service, (by default
`ManaPHP\\Filter <filter>`). The following examples offer the same behavior:

```php
    <?php

    use ManaPHP\Filter;

    // Manually applying the filter
    $filter = new Filter();
    $email  = $filter->sanitize($_POST["user_email"], "email");

    // Manually applying the filter to the value
    $filter = new Filter();
    $email  = $filter->sanitize($request->getPost("user_email"), "email");

    // Automatically applying the filter
    $email = $request->getPost("user_email", "email");

    // Setting a default value if the param is null
    $email = $request->getPost("user_email", "email", "some@example.com");

    // Setting a default value if the param is null without filtering
    $email = $request->getPost("user_email", null, "some@example.com");
```

## Accessing the Request from Controllers
The most common place to access the request environment is in an action of a controller. To access the
`ManaPHP\\Http\\Request` object from a controller you will need to use
the `$this->request` public property of the controller:

```php
    <?php

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

## Uploading Files
Another common task is file uploading. `ManaPHP\\Http\\Request` offers an object-oriented way to achieve this task:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function uploadAction()
        {
            // Check if the user has uploaded files
            if ($this->request->hasFiles()) {

                // Print the real file names and sizes
                foreach ($this->request->getFiles() as $file) {

                    // Print file details
                    echo $file->getName(), " ", $file->getSize(), "\n";

                    // Move the file into the application
                    $file->moveTo('files/' . $file->getName());
                }
            }
        }
    }
```

Each object returned by `ManaPHP\Http\Request::getFiles()` is an instance of the
`ManaPHP\\Http\\Request\\File` class. Using the `$_FILES` superglobal array offers the same behavior.
`ManaPHP\\Http\\Request\\File` encapsulates only the information related to each file uploaded with the request.

## Working with Headers
As mentioned above, request headers contain useful information that allow us to send the proper response back to
the user. The following examples show usages of that information:

```php
    <?php

    if ($request->isAjax()) {
        echo "The request was made with Ajax";
    }

    // Get the servers's IP address. ie. 192.168.0.100
    $ipAddress   = $request->getServerAddress();

    // Get the client's IP address ie. 201.245.53.51
    $ipAddress   = $request->getClientAddress();

    // Get the User Agent (HTTP_USER_AGENT)
    $userAgent   = $request->getUserAgent();
```

[SQL injection]: http://en.wikipedia.org/wiki/SQL_injection
[Cross Site Scripting (XSS)]: http://en.wikipedia.org/wiki/Cross-site_scripting
