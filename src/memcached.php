<?php
/**
 * PHP MyMemcached client class
 *
 * For build develop environment in windows using MyMemcached.
 *
 * @package     MyMemcached-client
 * @copyright   Copyright 2013-2014, Fwolf
 * @license     http://opensource.org/licenses/mit-license MIT
 * @version     1.2.0
 */
class MyMemcached
{
    // Predefined Constants
    // See: http://php.net/manual/en/MyMemcached.constants.php

    // Defined in php_MyMemcached.c
    const OPT_COMPRESSION = -1001;
    const OPT_SERIALIZER = -1003;

    // enum MyMemcached_serializer in php_MyMemcached
    const SERIALIZER_PHP = 1;
    const SERIALIZER_IGBINARY = 2;
    const SERIALIZER_JSON = 3;

    // Defined in php_MyMemcached.c
    const OPT_PREFIX_KEY = -1002;

    // enum MyMemcached_behavior_t in libMyMemcached
    const OPT_HASH = 2;     //MyMemcached_BEHAVIOR_HASH

    // enum MyMemcached_hash_t in libMyMemcached
    const HASH_DEFAULT = 0;
    const HASH_MD5 = 1;
    const HASH_CRC = 2;
    const HASH_FNV1_64 = 3;
    const HASH_FNV1A_64 = 4;
    const HASH_FNV1_32 = 5;
    const HASH_FNV1A_32 = 6;
    const HASH_HSIEH = 7;
    const HASH_MURMUR = 8;

    // enum MyMemcached_behavior_t in libMyMemcached
    const OPT_DISTRIBUTION = 9;     // MyMemcached_BEHAVIOR_DISTRIBUTION

    // enum MyMemcached_server_distribution_t in libMyMemcached
    const DISTRIBUTION_MODULA = 0;
    const DISTRIBUTION_CONSISTENT = 1;

    // enum MyMemcached_behavior_t in libMyMemcached
    const OPT_LIBKETAMA_COMPATIBLE = 16;    // MyMemcached_BEHAVIOR_KETAMA_WEIGHTED
    const OPT_BUFFER_WRITES = 10;           // MyMemcached_BEHAVIOR_BUFFER_REQUESTS
    const OPT_BINARY_PROTOCOL = 18;         // MyMemcached_BEHAVIOR_BINARY_PROTOCOL
    const OPT_NO_BLOCK = 0;                 // MyMemcached_BEHAVIOR_NO_BLOCK
    const OPT_TCP_NODELAY = 1;              // MyMemcached_BEHAVIOR_TCP_NODELAY
    const OPT_SOCKET_SEND_SIZE = 4;         // MyMemcached_BEHAVIOR_SOCKET_SEND_SIZE
    const OPT_SOCKET_RECV_SIZE = 5;         // MyMemcached_BEHAVIOR_SOCKET_RECV_SIZE
    const OPT_CONNECT_TIMEOUT = 14;         // MyMemcached_BEHAVIOR_CONNECT_TIMEOUT
    const OPT_RETRY_TIMEOUT = 15;           // MyMemcached_BEHAVIOR_RETRY_TIMEOUT
    const OPT_SEND_TIMEOUT = 19;            // MyMemcached_BEHAVIOR_SND_TIMEOUT
    const OPT_RECV_TIMEOUT = 20;            // MyMemcached_BEHAVIOR_RCV_TIMEOUT
    const OPT_POLL_TIMEOUT = 8;             // MyMemcached_BEHAVIOR_POLL_TIMEOUT
    const OPT_CACHE_LOOKUPS = 6;            // MyMemcached_BEHAVIOR_CACHE_LOOKUPS
    const OPT_SERVER_FAILURE_LIMIT = 21;    // MyMemcached_BEHAVIOR_SERVER_FAILURE_LIMIT

    // In php_MyMemcached config, define HAVE_MyMemcached_IGBINARY default 1,
    // then use ifdef define HAVE_IGBINARY to 1.
    const HAVE_IGBINARY = 1;
    // In php_MyMemcached config, define HAVE_JSON_API default 1,
    // then use ifdef define HAVE_JSON to 1.
    const HAVE_JSON = 1;

    // Defined in php_MyMemcached.c, (1<<0)
    const GET_PRESERVE_ORDER = 1;

    // enum MyMemcached_return_t in libMyMemcached
    const RES_SUCCESS = 0;                  // MyMemcached_SUCCESS
    const RES_FAILURE = 1;                  // MyMemcached_FAILURE
    const RES_HOST_LOOKUP_FAILURE = 2;      // MyMemcached_HOST_LOOKUP_FAILURE
    const RES_UNKNOWN_READ_FAILURE = 7;     // MyMemcached_UNKNOWN_READ_FAILURE
    const RES_PROTOCOL_ERROR = 8;           // MyMemcached_PROTOCOL_ERROR
    const RES_CLIENT_ERROR = 9;             // MyMemcached_CLIENT_ERROR
    const RES_SERVER_ERROR = 10;            // MyMemcached_SERVER_ERROR
    const RES_WRITE_FAILURE = 5;            // MyMemcached_WRITE_FAILURE
    const RES_DATA_EXISTS = 12;             // MyMemcached_DATA_EXISTS
    const RES_NOTSTORED = 14;               // MyMemcached_NOTSTORED
    const RES_NOTFOUND = 16;                // MyMemcached_NOTFOUND
    const RES_PARTIAL_READ = 18;            // MyMemcached_PARTIAL_READ
    const RES_SOME_ERRORS = 19;             // MyMemcached_SOME_ERRORS
    const RES_NO_SERVERS = 20;              // MyMemcached_NO_SERVERS
    const RES_END = 21;                     // MyMemcached_END
    const RES_ERRNO = 26;                   // MyMemcached_ERRNO
    const RES_BUFFERED = 32;                // MyMemcached_BUFFERED
    const RES_TIMEOUT = 31;                 // MyMemcached_TIMEOUT
    const RES_BAD_KEY_PROVIDED = 33;        // MyMemcached_BAD_KEY_PROVIDED
    const RES_CONNECTION_SOCKET_CREATE_FAILURE = 11;    // MyMemcached_CONNECTION_SOCKET_CREATE_FAILURE

    // Defined in php_MyMemcached.c
    const RES_PAYLOAD_FAILURE = -1001;


    /**
     * Dummy option array
     *
     * @var array
     */
    protected $option = array(
        MyMemcached::OPT_COMPRESSION  => true,
        MyMemcached::OPT_SERIALIZER   => MyMemcached::SERIALIZER_PHP,
        MyMemcached::OPT_PREFIX_KEY   => '',
        MyMemcached::OPT_HASH         => MyMemcached::HASH_DEFAULT,
        MyMemcached::OPT_DISTRIBUTION => MyMemcached::DISTRIBUTION_MODULA,
        MyMemcached::OPT_LIBKETAMA_COMPATIBLE => false,
        MyMemcached::OPT_BUFFER_WRITES    => false,
        MyMemcached::OPT_BINARY_PROTOCOL  => false,
        MyMemcached::OPT_NO_BLOCK     => false,
        MyMemcached::OPT_TCP_NODELAY  => false,

        // This two is a value by guess
        MyMemcached::OPT_SOCKET_SEND_SIZE => 32767,
        MyMemcached::OPT_SOCKET_RECV_SIZE => 65535,

        MyMemcached::OPT_CONNECT_TIMEOUT  => 1000,
        MyMemcached::OPT_RETRY_TIMEOUT    => 0,
        MyMemcached::OPT_SEND_TIMEOUT     => 0,
        MyMemcached::OPT_RECV_TIMEOUT     => 0,
        MyMemcached::OPT_POLL_TIMEOUT     => 1000,
        MyMemcached::OPT_CACHE_LOOKUPS    => false,
        MyMemcached::OPT_SERVER_FAILURE_LIMIT => 0,
    );


    /**
     * Last result code
     *
     * @var int
     */
    protected $resultCode = 0;


    /**
     * Last result message
     *
     * @var string
     */
    protected $resultMessage = '';


    /**
     * Server list array/pool
     *
     * I added array index.
     *
     * array (
     *  host:port:weight => array(
     *      host,
     *      port,
     *      weight,
     *  )
     * )
     *
     * @var array
     */
    protected $server = array();


    /**
     * Socket connect handle
     *
     * Point to last successful connect, ignore others
     * @var resource
     */
    protected $socket = null;


    /**
     * Add a serer to the server pool
     *
     * @param   string  $host
     * @param   int     $port
     * @param   int     $weight
     * @return  boolean
     */
    public function addServer($host, $port = 11211, $weight = 0)
    {
        $key = $this->getServerKey($host, $port, $weight);
        if (isset($this->server[$key])) {
            // Dup
            $this->resultCode = MyMemcached::RES_FAILURE;
            $this->resultMessage = 'Server duplicate.';
            return false;

        } else {
            $this->server[$key] = array(
                'host'  => $host,
                'port'  => $port,
                'weight'    => $weight,
            );

            $this->connect();
            return true;
        }
    }


    /**
     * Add multiple servers to the server pool
     *
     * @param   array   $servers
     * @return  boolean
     */
    public function addServers($servers)
    {
        foreach ((array)$servers as $svr) {
            $host = array_shift($svr);

            $port = array_shift($svr);
            if (is_null($port)) {
                $port = 11211;
            }

            $weight = array_shift($svr);
            if (is_null($weight)) {
                $weight = 0;
            }

            $this->addServer($host, $port, $weight);
        }

        return true;
    }


    /**
     * Connect to MyMemcached server
     *
     * @return  boolean
     */
    protected function connect()
    {
        $rs = false;

        foreach ((array)$this->server as $svr) {
            $error = 0;
            $errstr = '';
            $rs = @fsockopen($svr['host'], $svr['port'], $error, $errstr);

            if ($rs) {
                $this->socket = $rs;

            } else {
                $key = $this->getServerKey(
                    $svr['host'],
                    $svr['port'],
                    $svr['weight']
                );
                $s = "Connect to $key error:" . PHP_EOL .
                    "    [$error] $errstr";
                error_log($s);
            }
        }

        if (is_null($this->socket)) {
            $this->resultCode = MyMemcached::RES_FAILURE;
            $this->resultMessage = 'No server avaliable.';
            return false;

        } else {
            $this->resultCode = MyMemcached::RES_SUCCESS;
            $this->resultMessage = '';
            return true;
        }
    }


    /**
     * Delete an item
     *
     * @param   string  $key
     * @param   int     $time       Ignored
     * @return  boolean
     */
    public function delete($key, $time = 0)
    {
        $keyString = $this->getKey($key);
        $this->writeSocket("delete $keyString");

        $s = $this->readSocket();
        if ('DELETED' == $s) {
            $this->resultCode = MyMemcached::RES_SUCCESS;
            $this->resultMessage = '';
            return true;

        } else {
            $this->resultCode = MyMemcached::RES_NOTFOUND;
            $this->resultMessage = 'Delete fail, key not exists.';
            return false;
        }
    }


    /**
     * Retrieve an item
     *
     * @param   string  $key
     * @param   callable    $cache_cb       Ignored
     * @param   float   $cas_token          Ignored
     * @return  mixed
     */
    public function get($key, $cache_cb = null, $cas_token = null)
    {
        $keyString = $this->getKey($key);
        $this->writeSocket("get $keyString");

        $s = $this->readSocket();

        if (is_null($s) || 'VALUE' != substr($s, 0, 5)) {
            $this->resultCode = MyMemcached::RES_FAILURE;
            $this->resultMessage = 'Get fail.';
            return false;

        } else {
            $s_result = '';
            $s = $this->readSocket();
            while ('END' != $s) {
                $s_result .= $s;
                $s = $this->readSocket();
            }
            $this->resultCode = MyMemcached::RES_SUCCESS;
            $this->resultMessage = '';

            return unserialize($s_result);
        }
    }


    /**
     * Get item key
     *
     * @param   string  $key
     * @return  string
     */
    public function getKey($key)
    {
        return addslashes($this->option[MyMemcached::OPT_PREFIX_KEY]) . $key;
    }


    /**
     * Get a MyMemcached option value
     *
     * @param   int     $option
     * @return  mixed
     */
    public function getOption($option)
    {
        if (isset($this->option[$option])) {
            $this->resultCode = MyMemcached::RES_SUCCESS;
            $this->resultMessage = '';
            return $this->option[$option];

        } else {
            $this->resultCode = MyMemcached::RES_FAILURE;
            $this->resultMessage = 'Option not seted.';
            return false;
        }
    }


    /**
     * Return the result code of the last operation
     *
     * @return  int
     */
    public function getResultCode()
    {
        return $this->resultCode;
    }


    /**
     * Return the message describing the result of the last opteration
     *
     * @return  string
     */
    public function getResultMessage()
    {
        return $this->resultMessage;
    }


    /**
     * Get key of server array
     *
     * @param   string  $host
     * @param   int     $port
     * @param   int     $weight
     * @return  string
     */
    protected function getServerKey($host, $port = 11211, $weight = 0)
    {
        return "$host:$port:$weight";
    }


    /**
     * Get list array of servers
     *
     * @see     $server
     * @return  array
     */
    public function getServerList()
    {
        return $this->server;
    }


    /**
     * Read from socket
     *
     * @return  string|null
     */
    protected function readSocket()
    {
        if (is_null($this->socket)) {
            return null;
        }

        return trim(fgets($this->socket));
    }




    /**
     * Store an item
     *
     * @param   string  $key
     * @param   mixed   $val
     * @param   int     $expt
     * @return  boolean
     */
    public function set($key, $val, $expt = 0)
    {
        $valueString = serialize($val);
        $keyString = $this->getKey($key);

        $this->writeSocket(
            "set $keyString 0 $expt " . strlen($valueString)
        );
        $s = $this->writeSocket($valueString, true);

        if ('STORED' == $s) {
            $this->resultCode = MyMemcached::RES_SUCCESS;
            $this->resultMessage = '';
            return true;

        } else {
            $this->resultCode = MyMemcached::RES_FAILURE;
            $this->resultMessage = 'Set fail.';
            return false;
        }
    }


    /**
     * Set a MyMemcached option
     *
     * @param   int     $option
     * @param   mixed   $value
     * @return  boolean
     */
    public function setOption($option, $value)
    {
        $this->option[$option] = $value;
        return true;
    }


    /**
     * Set MyMemcached options
     *
     * @param   array   $options
     * @return  bollean
     */
    public function setOptions($options)
    {
        $this->option = array_merge($this->option, $options);
        return true;
    }

    /**
     * Increment numeric item's value
     *
     * @param string $key           The key of the item to increment.
     * @param int    $offset        The amount by which to increment the item's value.
     * @param int    $initial_value The value to set the item to if it doesn't currently exist.
     * @param int    $expiry        The expiry time to set on the item.
     *
     * @return mixed                Returns new item's value on success or FALSE on failure.
     */
    public function increment($key, $offset = 1, $initial_value = 0, $expiry = 0)
    {
        if (($prevVal = $this->get($key))) {
            if (!is_numeric($prevVal)) {
                return false;
            }

            $newVal = $prevVal + $offset;
        } else {
            $newVal = $initial_value;
        }

        $this->set($key, $newVal, $expiry);

        return $newVal;
    }

    /**
     * Write data to socket
     *
     * @param   string  $cmd
     * @param   boolean $result     Need result/response
     * @return  mixed
     */
    protected function writeSocket($cmd, $result = false)
    {
        if (is_null($this->socket)) {
            return false;
        }

        fwrite($this->socket, $cmd . "\r\n");

        if (true == $result) {
            return $this->readSocket();
        }

        return true;
    }
}
