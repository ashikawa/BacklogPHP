BacklogPHP
---

[Backlog API V2](http://developer.nulab-inc.com/ja/docs/backlog/api/2/) PHP Client.

# Setup

```sh
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

# How to use

## API Client

```php
<?php
$backlog = new \Backlog\Client();
$backlog->setBaseUri($baseUri)
    ->setApiKey($token);

// GET /api/v2/projects
$response = $backlog->projects->get();

var_dump($response->getBody());
```

[more sample](https://github.com/ashikawa/BacklogPHP/blob/master/sample.php)

## OAuth2

1. Create new application in [Backlog Developper](https://www.backlog.jp/developer/applications/).
2. set ENVs and run server.

for example

```sh
env BACKLOG_BASE_URI=https://exmple.backlog.jp/ \
    BACKLOG_CLIENT_ID=XXXXXXXXXXX \
    BACKLOG_CLIENT_SECRET=XXXXXXXXXXX \
    php -S 0.0.0.0:8000 -t public/
```


# Test

```sh
php composer.phar test
```

# CI Build Status

[![Build Status](https://travis-ci.org/m-s-modified/BacklogPHP.svg?branch=master)](https://travis-ci.org/m-s-modified/BacklogPHP)

# Deploy

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy?template=https://github.com/m-s-modified/BacklogPHP/tree/heroku)
