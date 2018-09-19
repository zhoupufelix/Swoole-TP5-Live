<?php
date_default_timezone_set('Asia/Shanghai');

class Http{

    CONST PORT = 8813;
    CONST HOST = '0.0.0.0';
    CONST ENABLE_STATIC_HANDLER = TRUE;
    CONST DAEMONIZE = 0;
    CONST DOCUMENT_ROOT = '/var/www/swoole/public/static';
    CONST WORKER_NUM = 5;

    private $http = null;

    public function __construct()
    {
        $this->http = new swoole_http_server(self::HOST,self::PORT);
        $this->http->set(
            [
                'enable_static_handler'=>self::ENABLE_STATIC_HANDLER,
                'document_root'        =>self::DOCUMENT_ROOT,
                'daemonize'            =>self::DAEMONIZE,
                'worker_num'           =>self::WORKER_NUM,
            ]
        );
        $this->http->on('request',[$this,'onRequest']);
        $this->http->on('WorkerStart',[$this,'onWorkerStart']);
        $this->http->start();
    }

    /**
     * php
     * @param $request
     * @param $response
     */
    public function onRequest($request,$response)
    {
        $_SERVER = [];
        if ( isset($request->server) ){
            foreach( $request->server as $k=>$v ){
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        $_GET = [];
        if ( isset($request->header) ){
            foreach( $request->header as $k=>$v ){
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        if ( isset($request->get) ){
            foreach( $request->get as $k=>$v ){
                $_GET[$k] = $v;
            }
        }
        $_POST = [];
        if ( isset($request->post) ){
            foreach( $request->post as $k=>$v ){
                $_POST[$k] = $v;
            }
        }

        ob_start();
        // 执行应用并响应
        try{
            think\Container::get('app', [APP_PATH])
                ->run()
                ->send();
        }catch(\Exception $e){

        }
        $content = ob_get_contents();
        ob_end_clean();
        $response->end($content);
    }

    public function onWorkerStart($server,$worker_id){
        // 定义应用目录
        defined('APP_PATH') or define('APP_PATH', __DIR__ . '/../application/');
        // 加载框架引导文件
        require __DIR__ . '/../thinkphp/base.php';
    }

};

new Http();
