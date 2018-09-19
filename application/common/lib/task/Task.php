<?php

namespace app\common\lib\task;
use app\common\lib\Util;
use app\common\lib\Predis;
use app\common\lib\Sms;

class Task{
    /**
     * 通过task机制发送赛况数据给客户端
     * @param $data
     */
    public function pushLive($data,$serv){
        $clients = Predis::getInstance()->sMembers(config('redis.live_redis_key'));
        if (!$clients){
            return ;
        }
        foreach($clients as $fd)
        {
            $serv->push( $fd,Util::show( config('code.success'),'OK',$data ) );
        }
        //存入redis队列
        $chat_key = 'chart:1';
        $info_key = 'info:1';
        Predis::getInstance()->lPush($chat_key,json_encode($data));
        Predis::getInstance()->lPush($info_key,json_encode($data));
    }

    /**
     * 发送短信验证码
     */
    public function sendCode($data,$serv)
    {
        $key    = 'code:phone:'. $data['phone_num'] .':login_code';
        $code   =  Predis::getInstance()->get($key);
        if ($code){
            return;
        }
        $code   = rand(1000,9999);
        $to     = $data['phone_num'];
        $datas  = [$code,'3'];
        $tempId = 1;
        $result =  (new Sms())->sendTemplateSMS($to,$datas,$tempId);
        if (isset( $result['code'] ) && $result['code'] == 1 ){
            Predis::getInstance()->set($key,$code,180);
        }
    }
}
