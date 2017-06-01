<?php
/**
 * Client.php
 *
 * @author Janson
 * @create 2017-06-01
 */
require __DIR__ . '/../autoload.php';

$client = new Asan\Nsq\Client();
$client->publishTo([
    ['host' => '192.168.1.50', 'port' => 4150],
    ['host' => '192.168.1.51', 'port' => 4150]
], 1);

$client->publish('test', 'From nsq swoole client', 0);
