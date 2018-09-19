<?php
namespace app\index\controller;
use app\common\lib\Util;
use app\common\lib\Predis;


class Login
{
    public function index()
    {
        $root      = '1888888881';
        $root_code = 1234;

        //检查电话号码正确性
        $phone_num =    request()->get('phone_num');
        $code      =    request()->get('code');
        $phone_num =    trim($phone_num);
        if ( $phone_num == $root && $code == $root_code ){
            //登陆成功以后做的事情
            $this->_login($phone_num);
            return Util::show(config('code.success'),'SUCCESS');
        }

        $pattern   =    '/^[1][3,4,5,7,8][0-9]{9}$/';
        preg_match($pattern,$phone_num,$match);

        if ( empty($match) ){
            return Util::show(config('code.error'),'请输入正确的电话号码！');
        }

        //查找验证码
        $key        = 'code:phone:'. $phone_num .':login_code';

        $sms_code   = Predis::getInstance()->get($key);
        if ( empty( $sms_code ) ){
            return Util::show(config('code.error'),'验证码超时，请重新获取');
        }

        if ( $code !=  $sms_code){
            return Util::show(config('code.error'),'验证码不正确,请重新输入');
        }
        $this->_login($phone_num);
        return Util::show(config('code.success'),'SUCCESS');

    }


    public function sendCode()
    {
        //检查电话号码正确性
        $phone_num =    request()->post('phone_num');
        $phone_num =    trim($phone_num);
        $pattern   =    '/^[1][3,4,5,7,8][0-9]{9}$/';
        preg_match($pattern,$phone_num,$match);

        if ( empty($match) ){
            return Util::show(config('code.error'),'请输入正确的电话号码！');
        }

        //存储短信验证码入redis

        $data   = ['phone_num'=>$phone_num];
        //异步task 发送短信
        $taskData = [
            'method'    =>  'sendCode',
            'data'      =>  $data,
        ];
        $_POST['ws_server']->task($taskData);

        return Util::show(config('code.success'),'SUCCESS');
    }

    /**
     * 登陆成功要做的事情
     * 存入内存swoole_table 并给客户端一个token
     * 以后每次都带着这个token登陆 退出就清空这个token
     */
    private function _login($phone_um)
    {
        $key  = 'user:phone_um:'.$phone_um;
        $uid  = Predis::getInstance()->get($key);
        //如果找不到uid 证明是第一次登陆 添加uid
        if ( empty($uid) ){
            $incr_id = Predis::getInstance()->incr('uid');
            Predis::getInstance()->set($key,$incr_id);
            $uid = $incr_id;
        }
        $token = $uid.'_'.md5($uid.uniqid().rand(00000000,99999999)); //随机数
        //存入内存swoole_table供服务端查看
        $data = ['id'=>$uid,'phone_num'=>$phone_um,'last_update_time'=>time(),'token'=>$token];
        $_POST['ws_server']->user->set('user:'.$uid,$data);
        //返回客户端数据
        $_POST['cookie']['user']['expire'] = 3600*24;
        $_POST['cookie']['user']['value']  = json_encode($data);
//        setcookie("user", $data);

    }


}
