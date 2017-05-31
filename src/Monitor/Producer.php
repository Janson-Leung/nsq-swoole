<?php
/**
 * Author: Janson
 * Create: 2017-05-30
 */
namespace Asan\Nsq\Monitor;

use Asan\Nsq\Exception\ConnectionException;
use Asan\Nsq\Exception\SocketException;
use Asan\Nsq\Monitor\AbstractMonitor;
use Asan\Nsq\Protocol\Command;

class Producer extends AbstractMonitor {
    /**
     * Read from the socket exactly $len bytes
     *
     * @param int $len How many bytes to read
     * @return string
     */
    public function read($len) {
        if ($this->rBuffer === null) {
            $this->readAll();
        }

        // Just return full buff
        if ($len >= strlen($this->rBuffer)) {
            $out = $this->rBuffer;
            $this->rBuffer = null;

            return $out;
        }

        $out = substr($this->rBuffer, 0, $len);
        $this->rBuffer = substr($this->rBuffer, $len);

        return $out;
    }

    /**
     * Read all from the socket
     *
     * @return string
     */
    public function readAll() {
        $data = $this->getMonitor()->recv();

        if ($data === false) {
            throw new SocketException('Failed to read from ' . $this->getDomain());
        } elseif ($data == '') {
            throw new SocketException('Read 0 bytes from ' . $this->getDomain());
        }

        return $data;
    }

    /**
     * Write to the socket.
     *
     * @param string $buf
     */
    public function write($buf) {
        if ($this->getMonitor()->send($buf) === false) {
            throw new SocketException('Failed to write ' . strlen($buf) . ' bytes to ' . $this->getDomain());
        }
    }

    /**
     * Reconnect the socket.
     *
     * @return \swoole_client
     */
    public function reconnect() {
        if ($this->monitor) {
            $this->monitor->close();
        }

        return $this->getMonitor();
    }

    /**
     * Get swoole client
     *
     * @return \swoole_client
     * @throws ConnectionException
     */
    public function getMonitor() {
        if ($this->monitor === null) {
            $this->monitor = new \swoole_client(SWOOLE_TCP);

            $this->monitor->set($this->setting);
        }

        if (!$this->monitor->isConnected()) {
            if (!$this->monitor->connect($this->host, $this->port, $this->timeout)) {
                throw new ConnectionException('Failed to connect to ' . $this->getDomain());
            }

            $this->monitor->send(Command::magic());
        }

        return $this->monitor;
    }
}
