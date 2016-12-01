<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Expand\Widget;
use Think\Controller;

class WebhookWidget extends Controller{

	/**
	 * webhook支付成功后的处理
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function charge($data)
    {
        $map['order_no']=$data['order_no'];
        $order=D('Expand/ExpandRecords')->getRecordData($map);
        if($order['paid']!=1){//未支付状态就执行
            $exdata['id']=$order['id'];
            $exdata['paid']=1;

            $res=D('Expand/ExpandRecords')->editRecordData($exdata);
            if(!$res){
                echo '数据写入失败';
                http_response_code(500);
            }else{
            	//支付成功后的积分处理
            	if(is_numeric($order['payment'])){
            		$type = $order['payment'];
            	}else{
            		$type = 3;
            	}
            	$scoreType = D('Ucenter/Score')->getType(array('id'=>$type));//根据ID获取积分类型详细
            	$duid=query_user(array('nickname'),$order['uid']); //购买用户的昵称
            	$fuid=query_user(array('nickname'),$order['add_uid']); //发布用户的昵称
            	$amount = $order['amount']/100;

            	$remark = $fuid['nickname'].'发布的应用被'.$duid['nickname'].'购买【'.$scoreType['title'].'：+'.$amount.$scoreType['unit'].'】';
        		D('Ucenter/Score')->setUserScore($order['uid'],$amount,$type,'inc','Expand',0,$remark);//增加积分
            }
        }else{
        	echo '数据有误或已处理';
            http_response_code(500);
        }
    }
}