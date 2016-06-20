# Using Aliases
Aliases are used to represent file paths or URLs so that you don't have to hard-code absolute paths or URLs in your project. 
An alias *must* start with the `@` character to be differentiated from normal file paths and URLs.
 
ManaPHP has many pre-defined aliases already available. For example, the alias @manaphp represents the installation path of the ManaPHP framework.

## Defining Aliases
You can define an alias for a file path or URL by calling `set`:
```php
    <?php
    
    // an alias of a file path
    $alias->set('@foo','/path/to/foo');
    
    // an alias of a URL
    $alias->set('@bar','http://www.example.com');
```
> *Note*: The file path or URL being aliased may not necessarily refer to an existing file or resource.

You can define an alias using another alias:
```php
    <?php
    
    $alias->set('@foobar','@foo/bar');
```

Aliases are usually defined during the bootstrapping stage. For example, you may call $alias->set() in the `registerServices` of Application:

```php
    <?php
    
     protected function registerServices()
     {
        $this->alias->set('@foo','path/to/foo');
        $this->alias->set('@bar','http://www.example.com');
     }
```
## Resolving Aliases
You can call `$alias->resolve($path)` to resolve an alias into the file path or URL it represents.
```php
    <?php
    
    echo $alias->resolve('@foo');               // displays: /path/to/foo
    echo $alias->resolve('@bar');               // displays: http://www.example.com
    echo $alias->resolve('@foo/bar/file.php');  // displays: /path/to/foo/bar/file.php
```
The path/URL represented by an alias is determined by replacing the alias part with its corresponding path/URL in the alias.

> *Note:* The $alias->resolve() method does not check whether the resulting path/URL refers to an existing file or resource.

## Using Aliases
Aliases are recognized in many places in ManaPHP without needing to call `$alias->resolve()` to convert them into paths or URLs. 
For example, ManaPHP\Cache\Adapter\File::$_cacheDir can accept both a file path and an alias representing a file path, thanks to the `@` prefix which allows it to differentiate a file path from an alias.

```php
    <?php
    
    $cache=new \ManaPHP\Cache\Adapter\File(['cacheDir'=>'@data/Cache']);
```
Please pay attention to the API documentation to see if a property or method parameter supports aliases.

## Predefined Aliases

ManaPHP predefines a set of aliases to easily reference commonly used file paths and URLs:
    * `@manaphp`,  the directory where framework file is located.
    * `@app`, the base path of the current running application.
    * `@data`, the data path of the current running application, Defaults to @app/../Data.