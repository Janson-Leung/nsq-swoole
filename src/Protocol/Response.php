<?php
/**
 * Author: Janson
 * Create: 2017-05-30
 */
namespace Asan\Nsq\Protocol;

use Asan\Nsq\Contracts\MonitorInterface;
use Asan\Nsq\Exception\FrameException;

class Response {
    /**
     * Frame types
     */
    const FRAME_TYPE_RESPONSE = 0;
    const FRAME_TYPE_ERROR = 1;
    const FRAME_TYPE_MESSAGE = 2;

    /**
     * Heartbeat response content
     */
    const HEARTBEAT = '_heartbeat_';

    /**
     * OK response content
     */
    const OK = 'OK';

    /**
     * Read frame
     *
     * @throws FrameException
     * @return array With keys: type, data
     */
    public static function readFrame(MonitorInterface $monitor) {
        $frame = [
            'size' => self::readInt($monitor),
            'type' => self::readInt($monitor)
        ];

        switch ($frame['type']) {
            case self::FRAME_TYPE_RESPONSE:
                $frame['response'] = self::readString($monitor, $frame['size']-4);
                break;

            case self::FRAME_TYPE_ERROR:
                $frame['error'] = self::readString($monitor, $frame['size']-4);
                break;

            case self::FRAME_TYPE_MESSAGE:
                $frame['ts'] = self::readLong($monitor);
                $frame['attempts'] = self::readShort($monitor);
                $frame['id'] = self::readString($monitor, 16);
                $frame['payload'] = self::readString($monitor, $frame['size']-30);
                break;

            default:
                throw new FrameException(self::readString($monitor, $frame['size']-4));
                break;
        }

        return $frame;
    }

    /**
     * Test if frame is a message frame
     *
     * @param array $frame
     * @return boolean
     */
    public static function isMessage(array $frame) {
        return isset($frame['type'], $frame['payload']) && $frame['type'] === self::FRAME_TYPE_MESSAGE;
    }

    /**
     * Test if frame is heartbeat
     *
     * @param array $frame
     *
     * @return boolean
     */
    public static function isHeartbeat(array $frame) {
        return isset($frame['type'], $frame['response']) && $frame['type'] === self::FRAME_TYPE_RESPONSE
            && $frame['response'] === self::HEARTBEAT;
    }

    /**
     * Test if frame is OK
     *
     * @param array $frame
     * @return boolean
     */
    public static function isOk(array $frame) {
        return isset($frame['type'], $frame['response']) && $frame['type'] === self::FRAME_TYPE_RESPONSE
            && $frame['response'] === self::OK;
    }

    /**
     * Read and unpack short integer (2 bytes) from connection
     *
     * @param MonitorInterface $monitor
     * @return int
     */
    private static function readShort(MonitorInterface $monitor) {
        list(,$res) = unpack('n', $monitor->read(2));

        return $res;
    }

    /**
     * Read and unpack integer (4 bytes)
     *
     * @param MonitorInterface $monitor
     * @return int
     */
    private static function readInt(MonitorInterface $monitor) {
        list(,$size) = unpack('N', $monitor->read(4));
        if ((PHP_INT_SIZE !== 4)) {
            $size = sprintf("%u", $size);
        }

        return (int)$size;
    }

    /**
     * Read and unpack long (8 bytes)
     *
     * @param MonitorInterface $monitor
     * @return string
     */
    private static function readLong(MonitorInterface $monitor) {
        $hi = unpack('N', $monitor->read(4));
        $lo = unpack('N', $monitor->read(4));

        // workaround signed/unsigned braindamage in php
        $hi = sprintf("%u", $hi[1]);
        $lo = sprintf("%u", $lo[1]);

        return bcadd(bcmul($hi, "4294967296" ), $lo);
    }

    /**
     * Read and unpack string; reading $size bytes
     *
     * @param MonitorInterface $monitor
     * @param int $size
     * @return string
     */
    private static function readString(MonitorInterface $monitor, $size) {
        $temp = unpack("c{$size}chars", $monitor->read($size));

        $out = "";
        foreach($temp as $v) {
            if ($v > 0) {
                $out .= chr($v);
            }
        }

        return $out;
    }
}
