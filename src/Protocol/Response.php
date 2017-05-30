<?php
/**
 * Author: Janson
 * Create: 2017-05-30
 */
namespace Asan\Nsq\Protocol;

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

    public function readFrame($data) {
        if (strlen($data) < 8) {
            throw new FrameException('Error reading message frame');
        }

        $size = $this->readSize(substr($data, 0, 4));
        $type = $this->readType(substr($data, 4, 4));


        return $frame;
    }

    public function isResponse() {

    }

    public function isMessage() {

    }

    public function isHeartbeat() {

    }

    public function isOK() {

    }

    /**
     * Read and unpack short integer (2 bytes) from connection
     *
     * @param string $section
     * @return int
     */
    private function readShort($section) {
        list(,$res) = unpack('n', $section);

        return $res;
    }

    /**
     * Read and unpack integer (4 bytes)
     *
     * @param string $section
     * @return int
     */
    private function readInt($section) {
        list(,$size) = unpack('N', $section);
        if ((PHP_INT_SIZE !== 4)) {
            $size = sprintf("%u", $size);
        }

        return (int)$size;
    }

    /**
     * Read and unpack long (8 bytes) from connection
     *
     * @param ConnectionInterface $connection
     *
     * @return string We return as string so it works on 32 bit arch
     */
    private function readLong(ConnectionInterface $connection) {
        $hi = unpack('N', $connection->read(4));
        $lo = unpack('N', $connection->read(4));

        // workaround signed/unsigned braindamage in php
        $hi = sprintf("%u", $hi[1]);
        $lo = sprintf("%u", $lo[1]);

        return bcadd(bcmul($hi, "4294967296" ), $lo);
    }

    /**
     * Read and unpack string; reading $size bytes
     *
     * @param ConnectionInterface $connection
     * @param integer $size
     *
     * @return string
     */
    private function readString(ConnectionInterface $connection, $size) {
        $temp = unpack("c{$size}chars", $connection->read($size));
        $out = "";
        foreach($temp as $v) {
            if ($v > 0) {
                $out .= chr($v);
            }
        }
        return $out;
    }
}
