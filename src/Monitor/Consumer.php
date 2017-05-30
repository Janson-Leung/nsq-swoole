<?php
/**
 * Author: Janson
 * Create: 2017-05-30
 */
namespace Asan\Nsq\Monitor;

use Asan\Nsq\Exception\ConnectionException;

class Consumer extends AbstractMonitor {
    /**
     * Reconnect the socket.
     *
     * @return Resource The socket, after reconnecting
     */
    public function reconnect() {
        $this->monitor->connect($this->host, $this->port, $this->timeout);
    }

    /**
     * Get swoole async client
     *
     * @return \swoole_client
     */
    public function getMonitor() {
        if ($this->monitor === null) {
            $this->monitor = new \swoole_client(SWOOLE_TCP, SWOOLE_ASYNC);

            $this->monitor->set($this->setting);

            $this->monitor->on('receive', [$this, 'monitorOnReceive']);
            $this->monitor->on('error', function() {
                throw new ConnectionException('Failed to connect to ' . $this->getDomain());
            });
            $this->monitor->on('close', function() {

            });

            $this->monitor->connect($this->host, $this->port, $this->timeout);
        }

        return $this->monitor;
    }

    public function monitorOnReceive(\swoole_client $client, string $data) {

    }
}
