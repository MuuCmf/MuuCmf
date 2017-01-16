<?php

namespace Pingpay\Widget;
use Think\Controller;

/**
 * 自定义页面内容widget
 */

class WebhookWidget extends Controller{
	
	public function charge($data){
		$id = $data['id'];
        $moduleName = $data['metadata']['module'];//支付的模块
		$score_id = $data['metadata']['score_id'];//积分类型
		$amount = $data['amount'];//支付的金额

            $map['order_no']=$data['order_no'];
            $order=D('Pingpay/PingpayOrder')->getData($map); 
            if($order && $order['paid']!=1){
                $editdata['id']=$order['id'];
                $editdata['paid']=1;
                $editdata['ch_id']=$id;
                $editdata['time_paid']=time();
                $res = D('Pingpay/PingpayOrder')->editData($editdata);
                    if($res){
                        $amount = $amount/100;
                        $type['id'] = $score_id;
        				$scoreType = D('Ucenter/Score')->getType($type);//根据ID获取积分类型详细
                        $remark = '在线充值'.$scoreType['title'].'：+'.$order['quantity'].$scoreType['unit'];
        				$ress = D('Ucenter/Score')->setUserScore($order['uid'],$order['quantity'],$score_id,'inc','Pingpay',0,$remark);//增加积分
        				if($ress){
        					echo '积分调整成功';
        					http_response_code(200); // PHP 5.4 or greater
        				}
                    }else{
                        echo '数据写入失败';
                        http_response_code(500);
                    }
            }else{
            	echo '数据有误或已处理';
                http_response_code(500);
            }

	}
	
	
}