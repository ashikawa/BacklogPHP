BacklogPHP
---

[Backlog API V2](http://developer.nulab-inc.com/ja/docs/backlog/api/2/) PHP Client.

# Setup

```sh
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

# How to use

```php
<?php
$backlog = new \Backlog\Client();
$backlog->setSpace($space)
    ->setToken($token);

$response = $backlog->projects->get();

var_dump($response->getBody());
```

# Test

```sh
php composer.phar php-cs-fixer
php composer.phar phpunit
```
