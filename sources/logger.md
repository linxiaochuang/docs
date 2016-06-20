# Logging

`ManaPHP\\Logger` is a component whose purpose is to provide logging services for applications.
It offers logging to different backends using different adapters.
It also offers transaction logging, configuration options, different formats and filters.
You can use the `ManaPHP\\Logger` for every logging need your application has, from debugging processes to tracing application flow.

## Adapters
This component makes use of adapters to store the logged messages. The use of adapters allows for a common logging interface
which provides the ability to easily switch backends if necessary. The adapters supported are:

| Adapter | Description               | API                                        |
|---------|:--------------------------|:-------------------------------------------|
| File    | Logs to a plain text file | `ManaPHP\\Logger\\Adapter\\File`      |
| Stream  | Logs to a PHP Streams     | `ManaPHP\\Logger\\Adapter\\Stream`    |
| Syslog  | Logs to the system logger | `ManaPHP\\Logger\\Adapter\\Syslog`    |
| Firephp | Logs to the FirePHP       | `ManaPHP\\Logger\\Adapter\\FirePHP`   |

## Creating a Log
The example below shows how to create a log and add messages to it:

```php
    <?php

    use ManaPHP\Logger;
    use ManaPHP\Logger\Adapter\File as FileAdapter;

    $logger = new FileAdapter("app/logs/test.log");

    // These are the different log levels available:
    $logger->fatal("This is a fatal message");
    $logger->error("This is an error message");
    $logger->warning("This is a warning message");
    $logger->info("This is an info message");
    $logger->debug("This is a debug message");
```

The log generated is below:

```bash
    [Tue, 28 Jul 15 22:09:02 -0500][FATAL] This is an fatal message
    [Tue, 28 Jul 15 22:09:02 -0500][ERROR] This is error message
    [Tue, 28 Jul 15 22:09:02 -0500][WARNING] This is a warning message
    [Tue, 28 Jul 15 22:09:02 -0500][INFO] This is an info message
    [Tue, 28 Jul 15 22:09:02 -0500][DEBUG] This is a message
```

You can also set a log level using the `setLevel()` method. This method takes a Logger constant and will only save log messages that are as important or more important than the constant:

```php
    use ManaPHP\Logger;
    use ManaPHP\Logger\Adapter\File as FileAdapter;

    $logger = new FileAdapter("app/logs/test.log");

    $logger->setLevel(Logger::LEVEL_ERROR);
```

In the example above, only error and fatal messages will get saved to the log. By default, everything is saved.

## Logging to Multiple Handlers
`ManaPHP\\Logger` can send messages to multiple handlers with a just single call:

```php
    <?php

    use ManaPHP\Logger;
    use ManaPHP\Logger\Multiple as MultipleStream;
    use ManaPHP\Logger\Adapter\File as FileAdapter;
    use ManaPHP\Logger\Adapter\Stream as StreamAdapter;

    $logger = new MultipleStream();

    $logger->push(new FileAdapter('test.log'));
    $logger->push(new StreamAdapter('php://stdout'));

    $logger->log("This is a message");
    $logger->log("This is an error", Logger::ERROR);
    $logger->error("This is another error");
```

The messages are sent to the handlers in the order they were registered.

## Message Formatting
This component makes use of 'formatters' to format messages before sending them to the backend. The formatters available are:

| Adapter | Description                                              | API                                          |
|---------|:---------------------------------------------------------|:---------------------------------------------|
| Line    | Formats the messages using a one-line string             | `ManaPHP\\Logger\\Formatter\\Line`      |
| Firephp | Formats the messages so that they can be sent to FirePHP | `ManaPHP\\Logger\\Formatter\\Firephp`   |
| Json    | Prepares a message to be encoded with JSON               | `ManaPHP\\Logger\\Formatter\\Json`      |
| Syslog  | Prepares a message to be sent to syslog                  | `ManaPHP\\Logger\\Formatter\\Syslog`    |

## Line Formatter

Formats the messages using a one-line string. The default logging format is:

```bash

    [%date%][%type%] %message%
```

You can change the default format using `setFormat()`, this allows you to change the format of the logged
messages by defining your own. The log format variables allowed are:

| Variable  | Description                              |
|-----------|:-----------------------------------------|
| %message% | The message itself expected to be logged |
| %date%    | Date the message was added               |
| %type%    | Uppercase string with message type       |

The example below shows how to change the log format:

```php
    <?php

    use ManaPHP\Logger\Formatter\Line as LineFormatter;

    // Changing the logger format
    $formatter = new LineFormatter("%date% - %message%");
    $logger->setFormatter($formatter);
```

## Implementing your own formatters

The `ManaPHP\\Logger\\FormatterInterface` interface must be implemented in order to create your own logger formatter or extend the existing ones.

## Adapters

The following examples show the basic use of each adapter:

## Stream Logger

The stream logger writes messages to a valid registered stream in PHP. A list of streams is available `here <http://php.net/manual/en/wrappers.php>`_:

```php
    <?php

    use ManaPHP\Logger\Adapter\Stream as StreamAdapter;

    // Opens a stream using zlib compression
    $logger = new StreamAdapter("compress.zlib://week.log.gz");

    // Writes the logs to stderr
    $logger = new StreamAdapter("php://stderr");
```
## File Logger

This logger uses plain files to log any kind of data. By default all logger files are opened using
append mode which opens the files for writing only; placing the file pointer at the end of the file.
If the file does not exist, an attempt will be made to create it. You can change this mode by passing additional options to the constructor:

```php
    <?php

    use ManaPHP\Logger\Adapter\File as FileAdapter;

    // Create the file logger in 'w' mode
    $logger = new FileAdapter(
        "app/logs/test.log",
        array(
            'mode' => 'w'
        )
    );
```

## Syslog Logger
This logger sends messages to the system logger. The syslog behavior may vary from one operating system to another.

```php
    <?php

    use ManaPHP\Logger\Adapter\Syslog as SyslogAdapter;

    // Basic Usage
    $logger = new SyslogAdapter(null);

    // Setting ident/mode/facility
    $logger = new SyslogAdapter(
        "ident-name",
        array(
            'option'   => LOG_NDELAY,
            'facility' => LOG_MAIL
        )
    );
```

## FirePHP Logger

This logger sends messages in HTTP response headers that are displayed by `FirePHP <http://www.firephp.org/>`_,
a `Firebug <http://getfirebug.com/>`_ extension for Firefox.

```php
    <?php

    use ManaPHP\Logger;
    use ManaPHP\Logger\Adapter\Firephp as Firephp;

    $logger = new Firephp("");
    $logger->log("This is a message");
    $logger->log("This is an error", Logger::ERROR);
    $logger->error("This is another error");
```

## Implementing your own adapters
The `ManaPHP\\Logger\\AdapterInterface` interface must be implemented in order to
create your own logger adapters or extend the existing ones.
