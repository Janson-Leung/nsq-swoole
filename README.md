# NSQ-SWOOLE

PHP Swoole client for [NSQ](https://github.com/bitly/nsq).

### Requirements

  - PHP 5.4 or higher
  - Swoole 1.8.6 or higher

### Installation

    composer require janson-leung/nsq-swoole


### Testing it out

Publish some messages:

    php tests/PublishTest.php

Subscribe the topic:

    php test/SubscribeTest.php

### Publishing

The client supports publishing to N `nsqd` servers. which must be specified 
explicitly by hostname. And supports publishing multiple messages.

```php
$client = new Asan\Nsq\Client;

$client->publishTo([
    ['host' => 'localhost', 'port' => 4150]
])->publish('test', 'single message');

//multiple messages
$client->publish('test', ['message one', 'message two']);

//HA publishing:
$client->publishTo([
    ['host' => 'nsq1', 'port' => 4150],
    ['host' => 'nsq2', /*'port' => 4150*/]
], Asan\Nsq\Client::PUB_QUORUM)->publish('test', 'HA publishing message');
```

### Subscribing

The client supports subscribing from N `nsqd` servers, each of which will be
auto-discovered from one or more `nslookupd` servers. The way this works is
that `nslookupd` is able to provide a list of auto-discovered nodes hosting
messages for a given topic.

```php
$lookup = new Asan\Nsq\Lookup\Lookupd([
    ['host' => 'nsq1', 'port' => 4161],
    ['host' => 'nsq2', /*'port' => 4161*/]
]);

$client = new Asan\Nsq\Client;

$client->subscribe($lookup, 'test', 'web', function($moniter, $msg) {
    echo sprintf("READ\t%s\t%s\n", $msg->getId(), $msg->getPayload());
});
```

