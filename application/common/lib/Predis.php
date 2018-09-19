<?php
namespace app\common\lib;

class Predis{

    /**
     * @var null|\Redis
     */
    public $redis = null;

    /**
     * @var null
     */
    private static $_instance = null;

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    /**
     * @return Predis|null
     */
    public static function getInstance(){
        if( empty(self::$_instance) ){
            self::$_instance = new self();
        }
        return self::$_instance ;
    }

    /**
     * Predis constructor.
     * @throws \Exception
     */
    private function __construct()
    {
        $this->redis = new \Redis();
        $result = $this->redis->connect(config('redis.host'),config('redis.port'),config('redis.time_out'));
        if (!$result)
            throw new \Exception('redis connect error!');
        $result = $this->redis->auth(config('redis.auth'));
        if (!$result)
            throw new \Exception('redis password error!');
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!method_exists($this->redis,$method)) {
            trigger_error("Call to undefined method " . __CLASS__ . "::{$method}() ", E_USER_ERROR);
        }
        return call_user_func_array(array($this->redis, $method), $args);
    }



}