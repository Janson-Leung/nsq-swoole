<?php
/**
 * Author: Janson
 * Create: 2017-05-29
 */
namespace Asan\Nsq;

use Asan\Nsq\Contracts\MonitorInterface;
use Asan\Nsq\Monitor\Producer;

class Client {
    const PUB_ONE = 1;
    const PUB_TWO = 2;
    const PUB_QUORUM = 5;

    /**
     * Connect/read/write timeout - in seconds
     *
     * @var float
     */
    private $timeout;

    /**
     * Swoole client setting
     *
     * @var array
     */
    private $setting;

    /**
     * Producer monitor pool
     *
     * @var array
     */
    private $producerPool = [];

    /**
     * Publish success criteria (how many nodes need to respond)
     *
     * @var int
     */
    private $pubSuccessCount;

    /**
     * Client constructor.
     *
     * @param null  $lookup
     * @param int   $timeout
     * @param array $setting
     */
    public function __construct($lookup = null, $timeout = 3, $setting = []) {

        $this->timeout = $timeout;
        $this->setting = $setting;
        $this->pubSuccessCount = 1;
    }

    /**
     * Subscribe to topic/channel
     *
     * @param string $topic
     * @param string $channel
     * @param array $callback
     */
    public function subscribe($topic, $channel, $callback) {

    }

    /**
     * Define nsqd hosts to publish to
     *
     * We'll remember these hosts for any subsequent publish() call, so you
     * only need to call this once to publish
     *
     * @param array $hosts keys: host、port、timeout、setting
     * @param int $cl Consistency level - basically how many `nsqd`
     *      nodes we need to respond to consider a publish successful
     *      The default value is nsqphp::PUB_ONE
     *
     * @throws \InvalidArgumentException If bad CL provided
     * @throws \InvalidArgumentException If we cannot achieve the desired CL
     *      (eg: if you ask for PUB_TWO but only supply one node)
     *
     * @return $this
     */
    public function publishTo(array $hosts, $cl = null) {
        foreach ($hosts as $item) {
            $host = isset($item['host']) ? $item['host'] : 'localhost';
            $port = isset($item['port']) ? $item['port'] : 4150;

            $this->producerPool[] = new Producer($host, $port, $this->timeout, $this->setting);
        }

        // work out success count
        if ($cl === null) {
            $cl = self::PUB_ONE;
        }

        $poolSize = count($this->producerPool);

        switch ($cl) {
            case self::PUB_ONE:
            case self::PUB_TWO:
                $this->pubSuccessCount = $cl;
                break;

            case self::PUB_QUORUM:
                $this->pubSuccessCount = ceil($poolSize / 2) + 1;
                break;

            default:
                throw new \InvalidArgumentException('Invalid consistency level');
                break;
        }

        if ($this->pubSuccessCount > $poolSize) {
            throw new \InvalidArgumentException(
                sprintf('Cannot achieve desired consistency level with %s nodes', $poolSize)
            );
        }

        return $this;
    }
}
