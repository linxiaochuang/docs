# Improving Performance with Cache
ManaPHP provides the `ManaPHP\\Cache` class allowing faster access to frequently used or already processed data.

## When to implement cache?
Although this component is very fast, implementing it in cases that are not needed could lead to a loss of performance rather than gain.
We recommend you check this cases before using a cache:

* You are making complex calculations that every time return the same result (changing infrequently)
* You are using a lot of helpers and the output generated is almost always the same
* You are accessing database data constantly and these data rarely change

>>  Even after implementing the cache, you should check the hit ratio of your cache over a period of time. This can easily
    be done, especially in the case of Memcache or Apc, with the relevant tools that the backends provide.

## Caching Behavior
The caching process is divided into 2 parts:

* **Frontend**: This part is responsible for providing consistency user interface and performing additional transformations to the data before storing and after retrieving them from the adapter.
* **Backend**: This part is responsible for communicating, writing/reading the data required by the interface.

## Cache Usage Example
One of the caching adapters is 'File'. The only key area for this adapter is the location of where the cache files will be stored.
This is controlled by the cacheDir option which *must* have a backslash at the end of it.

```php

    <?php

    use ManaPHP\Cache\Adapter\File;

    // Set the cache file directory
    $cache = new BackFile(["cacheDir" => "../app/cache"]);

    // Try to get cached records
    $cacheKey = 'robots_order_id';
    $robots   = $cache->get($cacheKey);
    if ($robots === false) {

        // $robots is null because of cache expiration or data does not exist
        // Make the database call and populate the variable
        $robots = Robots::find(
            array(
                "order" => "id"
            )
        );

        // Store it in the cache
        $cache->save($cacheKey, $robots);
    }

    // Use $robots :)
    foreach ($robots as $robot) {
       echo $robot->name, "\n";
    }
```

## Retrieving Items From The Cache
The elements added to the cache are uniquely identified by a key. To retrieve data from the cache, we just have to call it using the unique key. If the key does
not exist, the get method will return `false`.

The `get` method on the `cache` is used to retrieve items from the cache.  
```php
    <?php

    // Retrieve products by key "myProducts"
    $products = $cache->get("myProducts");
    
```
## Deleting Items From The Cache
There are times where you will need to forcibly invalidate a cache entry (due to an update in the cached data).
The only requirement is to know the key that the data have been stored with.

```php
    <?php

    // Delete an item with a specific key
    $cache->delete("key");
```

## Checking For Item Existence
It is possible to check if a cache already exists with a given key:

```php
    <?php

    if ($cache->exists("key")) {
        echo $cache->get("key");
    } else {
        echo "Cache does not exists!";
    }
```

## Storing Items In The Cache
You may use the `set` method on the Cache to store items in the cache. When you place an item in the cache,
you will need to specify the number of seconds for which the value should be cached.

```php
    <?php
    $cache->set('key','value',$seconds);
```