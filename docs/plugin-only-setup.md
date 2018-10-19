Execute this script inside docker container:

To install composer
```bash
#!/usr/bin/env bash

curl -sS https://getcomposer.org/installer | php -- --filename=composer

./compose install
```

To install wp-router alone (if you do not install all other plugins).
```
#!/usr/bin/env bash

apt update && apt install unzip

# https://wordpress.org/plugins/wp-router/
curl -O https://downloads.wordpress.org/plugin/wp-router.zip && unzip wp-router.zip && mv wp-router ../ && rm wp-router.zip

```


Activating debug in `wp-config.php`:
```php
define('WP_DEBUG', true);

// Enable Debug logging to the /wp-content/debug.log file
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

```
