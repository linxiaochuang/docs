# using Debugger
ManaPHP provides a debugger component that allows the developer to easily find errors produced in an application
created with the framework.

To enable it, add the following to your bootstrap:

```php
    <?php

    $debug = new \ManaPHP\Debugger();
    $debug->start();
```