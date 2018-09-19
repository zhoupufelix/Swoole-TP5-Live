<?php
namespace app\common\lib;

class Util{
    /**
     * API输出格式
     * @param $status
     * @param string $message
     * @param array $data
     */
    public static function show($status,$message='',$data=[]){
        $result = [
            'status'    =>  $status,
            'message'   =>  $message,
            'data'      =>  $data,
        ];
        return json_encode($result);
    }
}