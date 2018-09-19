<?php
namespace app\index\controller;
use app\common\lib\Util;
use app\common\lib\Predis;
class Chart
{
    public function index()
    {
        $uid     = request()->post('uid');
        $token   = request()->post('token');
        $game_id = request()->post('game_id');
        $content = request()->post('content');

        //判断uid 和 token 是否正确
        if ( empty($uid) || empty($token)  ){
            return Util::show(config('code.error'),'请先登陆');
        }

        $info = $_POST['ws_server']->user->get('user:'.$uid);
        if(empty($info) || !isset($info['token']) || $token != $info['token']  ){
            //删除本地的cookie
            $_POST['cookie']['user']['value']  = '';
            $_POST['cookie']['user']['expire'] = time() - 3600;
            return Util::show(config('code.error'),'请先登陆');
        }

        //比赛id判断
        if ( empty( $game_id ) ){
            return Util::show(config('code.error'),'ERROR');
        }
        //内容判断
        if ( empty( $content ) ){
            return Util::show(config('code.error'),'ERROR');
        }
        
        $data = [
            'content'     => $content,
            'from_type'   => 'user',
        ];

        foreach( $_POST['ws_server']->ports[1]->connections as $fd ){
            $data['user'] = '手机用户'.substr_replace($info['phone_num'], '****', 3, 4);
            $_POST['ws_server']->push($fd,json_encode($data));
        }
        //存入redis队列
        $chat_key = 'chart:1';
        Predis::getInstance()->lPush($chat_key,json_encode($data));
        return Util::show(config('code.success'),'SUCCESS');
    }

    /**
     * 获得最新的十条聊天信息
     */
    public function get(){
        $game_id     = request()->get('game_id');
        $key         = 'chart:'.$game_id;
        $data        = Predis::getInstance()->lRange($key,0,9);
        return Util::show(config('code.success'),'SUCCESS',$data);
    }



}
