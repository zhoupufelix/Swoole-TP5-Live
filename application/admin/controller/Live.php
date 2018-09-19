<?php
namespace app\admin\controller;
use app\common\lib\Util;
use app\common\lib\Predis;
use app\common\lib\StatisticClient;

class Live
{
    public function push()
    {
        StatisticClient::tick("Live", 'push');
        //使用swoole的TCP连接迭代器进行推送
//        foreach($_POST['ws_server']->connections as $fd)
//        {
//            $_POST['ws_server']->push($fd,'hello world');
//        }
        //此处应用TP5的方法
        if (empty($_GET)){
            StatisticClient::report('Live', 'push', false, 1001, 'bad');
            return Util::show(config('code.error'),'ERROR');
        }
        //此处查询数据库获取信息
        $teams = [
            1=>[
                'name'=>'马刺',
                'logo' =>'./imgs/team1.png',
            ],
            4=>[
                'name'=>'火箭',
                'logo' =>'./imgs/team2.png',
            ],
        ];
        $data = [
            'type'      => intval($_GET['type']),
            'title'     => !empty($teams[$_GET['team_id']]['name'])?$teams[$_GET['team_id']]['name']:'直播员',
            'logo'      => !empty($teams[$_GET['team_id']]['logo'])?$teams[$_GET['team_id']]['logo']:'',
            'content'   => !empty($_GET['content'])?$_GET['content']:'',
            'image'     => !empty($_GET['image'])?$_GET['image']:[],
            'time'      => !empty($_GET['time'])?$_GET['time']:[],
            'from_type' => 'admin',
        ];
        //获取连接的用户 赛况的基本信息入库 数据组织好 push到直播页面
        $taskData = [
            'method'    =>  'pushLive',
            'data'      =>  $data,
        ];
        $_POST['ws_server']->task($taskData);
        // 上报结果
        StatisticClient::report('Live', 'push', true, config('code.success'), 'OK');
        return Util::show( config('code.success'),'OK');
    }


    public function get(){
        $game_id     = request()->get('game_id');
        $key         = 'info:'.$game_id;
        $data        = Predis::getInstance()->lRange($key,0,9);
        return Util::show(config('code.success'),'SUCCESS',$data);
    }

}
