<?php
/**
 * Author: Janson
 * Create: 2017-05-30
 */
namespace Asan\Nsq\Monitor;

use Asan\Nsq\Exception\ConnectionException;
use Asan\Nsq\Exception\FrameException;
use Asan\Nsq\Exception\SocketException;
use Asan\Nsq\Protocol\Command;
use Asan\Nsq\Protocol\Message;
use Asan\Nsq\Protocol\Response;

class Consumer extends AbstractMonitor {
    /**
     * Subscribe topic
     *
     * @var string
     */
    protected $topic;

    /**
     * Subscribe channel
     *
     * @var string
     */
    protected $channel;

    /**
     * Subscribe callback
     *
     * @var callable
     */
    protected $callback;

    /**
     * @param string $topic
     * @param string $channel
     * @param callable $callback
     */
    public function initSubscribe($topic, $channel, $callback) {
        $this->topic = $topic;
        $this->channel = $channel;
        $this->callback = $callback;
    }

    /**
     * Read from the socket exactly $len bytes
     *
     * @param int $len How many bytes to read
     * @return string
     */
    public function read($len) {
        if ($this->rBuffer === null) {
            throw new SocketException('Read 0 bytes from ' . $this->getDomain());
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
     * Get swoole async client
     *
     * @return \swoole_client
     */
    public function getMonitor() {
        if ($this->monitor === null) {
            $this->monitor = new \swoole_client(SWOOLE_TCP, SWOOLE_ASYNC);

            $this->monitor->set($this->setting);

            $this->monitor->on('connect', [$this, 'monitorOnConnect']);
            $this->monitor->on('receive', [$this, 'monitorOnReceive']);
            $this->monitor->on('error', [$this, 'monitorOnError']);
            $this->monitor->on('close', [$this, 'monitorOnClose']);

            $this->monitor->connect($this->host, $this->port, $this->timeout);
        }

        return $this->monitor;
    }

    /**
     * Connected
     *
     * @param \swoole_client $monitor
     */
    public function monitorOnConnect(\swoole_client $monitor) {
        $monitor->send(Command::magic());

        //subscribe
        if (!isset($this->topic) || !isset($this->channel)) {
            throw new \InvalidArgumentException('Cannot subscribe without topic or channel');
        }

        $monitor->send(Command::sub($this->topic, $this->channel));
        $monitor->send(Command::rdy(1));
    }

    /**
     * Dispatch callback for async sub loop
     *
     * @param \swoole_client $monitor
     * @param string         $data
     */
    public function dispatchMessage(\swoole_client $monitor, string $data) {
        $this->rBuffer = $data;

        $frame = Response::readFrame($this);

        // intercept errors/responses
        if (Response::isHeartbeat($frame)) {
            $monitor->send(Command::nop());
        } elseif (Response::isMessage($frame)) {
            $msg = new Message($frame);

            if (!isset($this->callback) || !is_callable($this->callback)) {
                throw new \BadMethodCallException('Subscribe callback is not callable');
            }

            call_user_func($this->callback, $monitor, $msg);

            // mark as done; get next on the way
            $monitor->send(Command::fin($msg->getId()));
            $monitor->send(Command::rdy(1));

        } elseif (Response::isOk($frame)) {
            //ignore
        } else {
            throw new FrameException('Error/unexpected frame received: ' . json_encode($frame));
        }
    }

    /**
     * Connect failed
     *
     * @throws ConnectionException
     */
    public function monitorOnError() {
        throw new ConnectionException('Failed to connect to ' . $this->getDomain());
    }

    /**
     * Connect closed
     *
     * @param \swoole_client $monitor
     */
    public function monitorOnClose(\swoole_client $monitor) {
        //reconnect
        $monitor->connect($this->host, $this->port, $this->timeout);
    }
}
