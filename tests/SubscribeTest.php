<?php
/**
 * Subscribe test
 *
 * @author Janson
 * @create 2017-06-01
 */
require __DIR__ . '/../autoload.php';

$lookup = new Asan\Nsq\Lookup\Lookupd([
    ['host' => '192.168.1.50', 'port' => 4161],
    ['host' => '192.168.1.51', 'port' => 4161]
]);

$client = new Asan\Nsq\Client;

$client->subscribe($lookup, 'test', 'web', function($moniter, $msg) {
    echo sprintf("READ\t%s\t%s\n", $msg->getId(), $msg->getPayload());
});
