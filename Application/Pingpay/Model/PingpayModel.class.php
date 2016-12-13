<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Pingpay\Model;
use Think\Model;
use Think\Page;


class PingpayModel extends Model{
    protected $_validate = array(
        array('title', '1,100', '标题长度不合法', self::EXISTS_VALIDATE, 'length'),
        array('explain', '1,40000', '内容长度不合法', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
        array('status', '1', self::MODEL_INSERT),
        array('uid', 'is_login',3, 'function'),
    );
    /**
    * 判断支付方式列表
    */
    public function channel(){
        $path = APP_PATH  . 'Pingpay/Conf/channel.php';
        $channel = load_config($path);

        $payChannel = array();

        if(is_mobile()){
            foreach($channel as $k=>$val){
                if(strpos($k,'wap') && $val['status']){
                    $payChannel[] = $val;
                }
            }
        }elseif(isWeixinBrowser()){
            if($k=='wx_wap' && $val['status']){
                $payChannel[] = $val;
            }
        }else{
            foreach($channel as $k=>$val){
                if(strpos($k,'pc') && $val['status']){
                    $payChannel[] = $val;
                }
                if($k=='wx_pub_qr' && $val['status']){
                    $payChannel[] = $val;
                }
                if($k=='alipay_qr' && $val['status']){
                    $payChannel[] = $val;
                }
            }
        }
        return $payChannel;
    }

/**
 * 根据支付channel获取支付详细配置
 * @param  [type] $channel [description]
 * @return [type]          [description]
 */
    public function getPaychannelInfo($channel)
    {
        $path = APP_PATH  . 'Pingpay/Conf/channel.php';
        $config = load_config($path);
        return $config[$channel];
    }

}
