<?php
namespace app\common\lib;
use app\common\lib\ronglian\Rest;
/*
 *  Copyright (c) 2014 The CCP project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a Beijing Speedtong Information Technology Co.,Ltd license
 *  that can be found in the LICENSE file in the root of the web site.
 *
 *   http://www.yuntongxun.com
 *
 *  An additional intellectual property rights grant can be found
 *  in the file PATENTS.  All contributing project authors may
 *  be found in the AUTHORS file in the root of the source tree.
 */
class Sms{
    private $accountSid='';
    private $accountToken='';
    private $appId = '';
    private $serverIP = '';
    private $serverPort = '8883';
    private $softVersion = '2013-12-26';

    public function __construct(){
        $this->accountSid   = config('ronglian.accountSid');
        $this->accountToken = config('ronglian.accountToken');
        $this->appId        = config('ronglian.appId');
        $this->serverIP     = config('ronglian.serverIP');
    }

    /**
     * **************************************举例说明***********************************************************************
     * 假设您用测试Demo的APP ID，则需使用默认模板ID 1，发送手机号是13800000000，传入参数为6532和5，则调用方式为
     * result = sendTemplateSMS("13800000000" ,array('6532','5'),"1");
     * 则13800000000手机号收到的短信内容是：【云通讯】您使用的是云通讯短信模板，您的验证码是6532，请于5分钟内正确输入
     * ********************************************************************************************************************
     * @param $to 手机号码
     * @param $datas 替换内容数组
     * @param $tempId 模板id
     * @return array
     *
     */
    public function sendTemplateSMS($to,$datas,$tempId){
        $return = ['code'=>1,'info'=>[]];
        // 初始化REST SDK
        $rest = new Rest($this->serverIP,$this->serverPort,$this->softVersion);
        $rest->setAccount($this->accountSid,$this->accountToken);
        $rest->setAppId($this->appId);

        // 发送模板短信
        $result = $rest->sendTemplateSMS($to,$datas,$tempId);
        if($result == NULL ) {
            $return['code'] = 0;
            $return['info']['msg'] = 'error';
        }
        if($result->statusCode!=0) {
            $return['code'] = 0;
            $return['info']['statusCode'] = $result->statusCode;
            $return['info']['statusMsg'] = $result->statusMsg;
            //TODO 添加错误处理逻辑
        }else{
            $return['code'] = 1;
            $return['info']['dateCreated'] = $result->dateCreated;
            $return['info']['smsMessageSid'] = $result->smsMessageSid;
            //TODO 添加成功处理逻辑
        }
        return $return;
    }


}

?>

