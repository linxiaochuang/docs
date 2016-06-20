# installing ManaPHP
You can install ManaPHP in two ways, using the Composer package manager or by downloading an archive file.
The former is the preferred way, as it allows you to update ManaPHP by simply running a single command.

Standard installations of ManaPHP result in both the framework and a project template being downloaded and installed.
A project template is a working ManaPHP project implementing some basic features. Its code is organized in a recommended way. Therefore, it can serve as a good starting point for your projects.

## Installing via Composer

If you do not already have Composer installed, you may do so by following the instructions at [getcomposer.org](https://getcomposer.org/download/). On Linux and Mac OS X, you'll run the following commands:

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

On Windows, you'll download and run [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe).

Please refer to the [Composer Documentation](https://getcomposer.org/doc/) if you encounter any problems or want to learn more about Composer usage.

If you had Composer already installed before, make sure you use an up to date version. You can update Composer by running `composer self-update`.

With Composer installed, you can install ManaPHP by running the following commands under a Web-accessible folder:

```bash
composer global require "fxp/composer-asset-plugin:~1.1.1"
composer create-project --prefer-dist manaphp/manaphp manaphp_app
```

The first command installs the [composer asset plugin](https://github.com/francoispluchino/composer-asset-plugin/) which allows managing [bower](http://bower.io/) and [npm](https://www.npmjs.com/) package dependencies through Composer.
You only need to run this command once for all.
The second command installs ManaPHP in a directory named `manaphp_app`. You can choose a different directory name if you want.

>During the installation Composer may ask for your Github login credentials. This is normal because Composer needs to get enough API rate-limit to retrieve the dependent package information from Github. For more details, please refer to the [Composer documentation](https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens).

 If you want to install the latest development version of ManaPHP, you may use the following command instead, which adds a stability option:

```bash
 composer create-project --prefer-dist --stability=dev manaphp/manaphp manaphp_app
```

## Installing from an Archive File

Installing ManaPHP from an archive file involves three steps:

   1. Download the archive file from [github.com](https://github.com/manaphp/manaphp).
   2. Unpack the downloaded file to a Web-accessible folder.
   3. Modify the `Application/Configure.php` file by entering a secret key for the `$this->crypt->key` configuration item.

## Configuration for Apache
[Apache] is a popular and well known web server available on many platforms.

These notes are primarily focused on the configuration of the mod_rewrite module allowing to use friendly URLs and the
`router component <routing>`.

### Directory under the main Document Root

This being the most common case, the application is installed in any directory under the document root.

In this case, we use two `.htaccess` files, the first one to hide the application code by forwarding all requests
to the application's document root (Public/).

```apache
    # manaphp_app/.htaccess

    <IfModule mod_rewrite.c>
        RewriteEngine on
        RewriteRule  ^$ Public/    [L]
        RewriteRule  ((?s).*) Public/$1 [L]
    </IfModule>
```

Now a second `.htaccess` file is located in the `Public/` directory, this re-writes all the URIs to the `Public/index.php` file:

```apache
    # manaphp_app/Public/.htaccess

    AddDefaultCharset UTF-8

    <IfModule mod_rewrite.c>
        SetEnv MANAPHP_REWRITE_ON ON
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !(.css|.js|.gif|.png|.jpg|.jpeg|.ttf|.woff|.ico)$
        RewriteRule ^(.*)$ index.php?_url=/$1 [QSA,L]
    </IfModule>
```

If you do not want to use `.htaccess` files you can move these configurations to the apache's main configuration file:

```apache
    <IfModule mod_rewrite.c>

        <Directory "/var/www/manaphp_app">
            RewriteEngine on
            RewriteRule  ^$ Public/    [L]
            RewriteRule  ((?s).*) Public/$1 [L]
        </Directory>

        <Directory "/var/www/manaphp_app/Public">
            AddDefaultCharset UTF-8

            SetEnv MANAPHP_REWRITE_ON ON
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !(.css|.js|.gif|.png|.jpg|.jpeg|.ttf|.woff|.ico)$
            RewriteRule ^(.*)$ index.php?_url=/$1 [QSA,L]
        </Directory>

    </IfModule>
```
### Virtual Hosts
And this second configuration allows you to install a ManaPHP application in a virtual host:

```apache
    <VirtualHost *:80>

        ServerAdmin admin@example.host
        DocumentRoot "/var/vhosts/manaphp_app/Public"
        DirectoryIndex index.php
        ServerName localhost.host
        ServerAlias www.example.host

        <Directory "/var/vhosts/manaphp_app/Public">
            Options All
            AllowOverride All
            Allow from all
        </Directory>

    </VirtualHost>
```
## Configuration for Nginx

[Nginx] is a free, open-source, high-performance HTTP server and reverse proxy, as well as an IMAP/POP3 proxy server. Unlike traditional servers, [Nginx] doesn't rely on threads to handle requests. Instead it uses a much more scalable event-driven (asynchronous) architecture. This architecture uses small, but more importantly, predictable amounts of memory under load.

The [PHP-FPM] (FastCGI Process Manager) is usually used to allow [Nginx] to process PHP files. Nowadays, [PHP-FPM] is bundled with any Unix PHP distribution. [ManaPHP] + [Nginx] + [PHP-FPM] provides a powerful set of tools that offer maximum performance for your PHP applications.

The following are potential configurations you can use to setup nginx with ManaPHP:

### Basic configuration
Using `$_GET['_url']` as source of URIs:

```nginx
    server {
        listen      80;
        server_name localhost.dev;
        root        /var/www/ManaPHP/Public;
        index       index.php index.html index.htm;

        location / {
            try_files $uri $uri/ /index.php?_url=$uri&$args;
        }

        location ~ \.php {
            fastcgi_pass  unix:/run/php-fpm/php-fpm.sock;
            fastcgi_index /index.php;

            include fastcgi_params;
            fastcgi_split_path_info       ^(.+\.php)(/.+)$;
            fastcgi_param PATH_INFO       $fastcgi_path_info;
            fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }

        location ~ /\.ht {
            deny all;
        }
    }
```

Using `$_SERVER['PATH_INFO']` as source of URIs:

```nginx
    server {
        listen      80;
        server_name localhost.dev;
        root        /var/www/ManaPHP/Public;
        index       index.php index.html index.htm;

        location / {
            try_files $uri $uri/ /index.php;
        }

        location ~ \.php$ {
            try_files     $uri =404;

            fastcgi_pass  127.0.0.1:9000;
            fastcgi_index /index.php;

            include fastcgi_params;
            fastcgi_split_path_info       ^(.+\.php)(/.+)$;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }

        location ~ /\.ht {
            deny all;
        }
    }
```

### Dedicated Instance
```nginx

    server {
        listen      80;
        server_name localhost.dev;
        root        /srv/www/htdocs/ManaPHP-website/public;
        index       index.php index.html index.htm;
        charset     utf-8;

        #access_log /var/log/nginx/$host.access.log main;

        location / {
            try_files $uri $uri/ /index.php?_url=$uri&$args;
        }

        location ~ \.php {
            # try_files   $uri =404;

            fastcgi_pass  127.0.0.1:9000;
            fastcgi_index /index.php;

            include fastcgi_params;
            fastcgi_split_path_info       ^(.+\.php)(/.+)$;
            fastcgi_param PATH_INFO       $fastcgi_path_info;
            fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }

        location ~ /\.ht {
            deny all;
        }
    }
```
### Configuration by Host
And this second configuration allow you to have different configurations by host:

```nginx

    server {
        listen      80;
        server_name localhost.dev;
        root        /var/www/$host/public;
        index       index.php index.html index.htm;

        access_log  /var/log/nginx/$host-access.log;
        error_log   /var/log/nginx/$host-error.log error;

        location / {
            try_files $uri $uri/ /index.php?_url=$uri&$args;
        }

        location ~ \.php {
            # try_files   $uri =404;

            fastcgi_pass  127.0.0.1:9000;
            fastcgi_index /index.php;

            include fastcgi_params;
            fastcgi_split_path_info       ^(.+\.php)(/.+)$;
            fastcgi_param PATH_INFO       $fastcgi_path_info;
            fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }

        location ~ /\.ht {
            deny all;
        }
    }
```

## Configuration for PHP Built-in webserver

As of PHP 5.4.0, you can use PHP's on [built-in] web server for development.

To start the server type:

```bash
    php -S localhost:8000 -t /Public
```

If you want to rewrite the URIs to the index.php file use the following router file (.htrouter.php):

```php
    <?php
    if (!file_exists(__DIR__ . '/' . $_SERVER['REQUEST_URI'])) {
        $_GET['_url'] = $_SERVER['REQUEST_URI'];
    }
    return false;
```
and then start the server from the base project directory with:

```php
    php -S localhost:8000 -t /public .htrouter.php
```
Then point your browser to http://localhost:8000/ to check if everything is working.

[Apache]: http://httpd.apache.org/
[Nginx]: http://wiki.nginx.org/Main
[PHP-FPM]: http://php-fpm.org/
[ManaPHP]: http://github.com/manaphp
[built-in]: http://php.net/manual/en/features.commandline.webserver.php