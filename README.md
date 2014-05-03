DigiDoc demo site
=================

todo: finish the readme

## Usage

1.  Install dependencies using [Composer](https://getcomposer.org/)

    ```
    # skip this, if you already have composer
    $ curl -sS https://getcomposer.org/installer | php

    $ php composer.phar install
    ```

2.  Start the [PHP built-in server](http://www.php.net/manual/en/features.commandline.webserver.php)

    ```
    $ php -S localhost:8080
    ```

3.  Start up [stunnel](http://stunnel.org/) (signing won't work without TLS enabled)

    ```
     $ stunnel -d 4443 -f -r 8080 -p /path/to/pemfile.pem -P /tmp/stunnel.pid
    ```

4.  Navigate to `https://localhost:443/app.php`

You can obviously skip steps 2 and 3 in favor of an actual web server.

