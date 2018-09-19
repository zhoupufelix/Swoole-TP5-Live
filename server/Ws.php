<?php
class Ws{
    CONST ENABLE_STATIC_HANDLER = TRUE;
    CONST DAEMONIZE = 0;
    CONST DOCUMENT_ROOT = '/var/www/swoole/public/static';
    CONST WORKER_NUM = 5;
    CONST TASK_WORKER_NUM = 2;
    //host
    CONST HOST = '0.0.0.0';
    //port
    CONST PORT = 8818;
    CONST CHART_PORT = 8819;

    //websocket实例
    private $ws = null;

    public function __construct()
    {
        $this->ws = new \swoole_websocket_server(self::HOST, self::PORT);
        $this->ws->on('start',[$this,'onStart']);
        $this->ws->on('WorkerStart',[$this,'onWorkerStart']);
        //监听打开事件
        $this->ws->on('open',[$this,'onOpen']);
        //监听消息推送事件
        $this->ws->on('message',[$this,'onMessage']);
        //异步task事件
        $this->ws->on('task',[$this,'onTask']);

        $this->ws->listen(self::HOST, self::CHART_PORT,SWOOLE_SOCK_TCP);

        $this->ws->set([
            'worker_num'           =>   self::WORKER_NUM,
            'task_worker_num'      =>   self::TASK_WORKER_NUM,
            'enable_static_handler'=>   self::ENABLE_STATIC_HANDLER,
            'document_root'        =>   self::DOCUMENT_ROOT,
            'daemonize'            =>   self::DAEMONIZE,
        ]);
        $this->ws->on('request',[$this,'onRequest']);
        //Task Finish
        $this->ws->on('finish',[$this,'onFinish']);
        //监听客户端关闭事件
        $this->ws->on('close',[$this,'onClose']);
        //开启服务
        $this->ws->start();
    }

    public function onStart(){
        swoole_set_proccess_name("live_master");
    }


    public function onWorkerStart($server,$worker_id){
        // 定义应用目录
        define('APP_PATH', __DIR__ . '/../../../application/');
        // 加载框架引导文件
        require __DIR__ . '/../thinkphp/start.php';
        //如果redis 集合中有数据 则清空集合数据
        $members = \app\common\lib\Predis::getInstance()->sMembers(config('redis.live_redis_key'));
        if( $members ){
            \app\common\lib\Predis::getInstance()->del(config('redis.live_redis_key'));
        }
    }

    /**
     * 监听打开事件的回调函数
     * @param swoole_websocket_server $server websocket 服务器
     * @param $request 请求的对象 http服务器中的request对象
     */
    public function onOpen(\swoole_websocket_server $server, $request)
    {
        \app\common\lib\Predis::getInstance()->sAdd(config('redis.live_redis_key'), $request->fd);
        echo "server: handshake success with fd{$request->fd}\n";
//        if($request->fd == 1) {
//            swoole_timer_tick(2000, function ($time_id) {A
//                echo "2s:time_id:{$time_id}\n";
//            });
//        }
    }


    /**
     * @param swoole_websocket_server $server websocket服务器
     * @param $frame 客户端对象
     */
    public function onMessage(\swoole_websocket_server $server, $frame)
    {
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        //假设有10s的逻辑，类似成功连接发送邮件
//        $data = [
//                    'task'=>'1',
//                    'fd'  => $frame->fd,
//        ];
//        $server->task($data);
//        swoole_timer_after(5000,function() use($server,$frame) {
//            echo "5s after\n";
//            $server->push($frame->fd,'server-time-after:'.date('Y-m-d H:i:s'));
//
//        });
//        $message = ';';
//        //将内容推送到客户端
//        $server->push($frame->fd, "push message success".$message);
    }


    public function onTask($serv, $task_id,$worker_id, $data)
    {
        $obj = new app\common\lib\task\Task;

        $method = $data['method'];
        $flag   = $obj->$method($data['data'],$serv);

//        print_r($data);
//        //模拟耗时场景
//        sleep(10);
//        return "onTask finish";//告诉worker
        return $flag;
    }

    public function onFinish($serv, $task_id, $data)
    {
        echo "task_id:{$task_id}\n";
        echo "finish-data-success{$data}\n";
    }

    /**
     * 关闭事件的回调函数
     * @param $ser
     * @param $fd
     */
    public function onClose($ser, $fd)
    {
        \app\common\lib\Predis::getInstance()->sRem(config('redis.live_redis_key'), $fd);
        echo "client {$fd} closed\n";
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
        if ( isset($request->header) ){
            foreach( $request->header as $k=>$v ){
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        $_GET = [];
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
        //保存server对象 方便以后使用异步任务等内容
        $_POST['ws_server'] = $this->ws;

        $_FILES = [];
        if ( isset($request->files) ) {
            foreach ($request->files as $k => $v) {
                $_FILES[$k] = $v;
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




}


new Ws();

