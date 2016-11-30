<?php

namespace Pingpay\Controller;

use Think\Controller;

class WebhooksController extends BaseController
{

    protected $orderModel;
    public function _initialize()
    {
        $this->orderModel = D('Pingpay/PingpayOrder');
        parent::_initialize();
    }
    
    
    public function index()
    {
        $raw_data = file_get_contents('php://input');

        $headers = \Pingpp\Util\Util::getRequestHeaders();
        // 签名在头部信息的 x-pingplusplus-signature 字段
        $signature = isset($headers['X-Pingplusplus-Signature']) ? $headers['X-Pingplusplus-Signature'] : NULL;

        $result = $this->verify_signature($raw_data, $signature);
        if ($result === 1) {
            // 验证通过
            //echo 'verification success';
        } elseif ($result === 0) {
            http_response_code(400);
            echo 'verification failed';
            exit;
        } else {
            http_response_code(400);
            echo 'verification error';
            exit;
        }

        $event = json_decode($raw_data, true);
        //支付成功后处理
        if ($event['type'] == 'charge.succeeded') {
            $data = $event['data']['object'];
            $this->chargeSucceeded($data);
        }
        //退款成功后处理
        if ($event['type'] == 'refund.succeeded') {
            $refund = $event['data']['object'];
            // ...
            http_response_code(200); // PHP 5.4 or greater
        } 
    }

    private function verify_signature($raw_data, $signature) 
    {

        $pub_key_contents = $this->public_key;
        // php 5.4.8 以上，第四个参数可用常量 OPENSSL_ALGO_SHA256
        return openssl_verify($raw_data, base64_decode($signature), $pub_key_contents, 'sha256');
    }
    /**
     * 订单支付成功事件后续处理
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function chargeSucceeded($data)
    {   
        //获取元数据的第一个参数module.判断调用支付的模块
        $id = $data['id'];
        $metadata = $data['metadata'];
        $module = $metadata['module'];//支付的模块
        $amount = $data['amount'];//支付的金额
        
        if(!$module){
            echo '无模块数据';
            http_response_code(500);
        }
        
        if($module=='Pingpay'){
            $score_id = $data['metadata']['score_id'];//积分类型
            $map['order_no']=$data['order_no'];
            $order=D('Pingpay/PingpayOrder')->getData($map); 
            if($order && $order['paid']!=1){
                $editdata['id']=$order['id'];
                $editdata['paid']=1;
                $editdata['ch_id']=$id;
                $editdata['time_paid']=time();
                $res = D('Pingpay/PingpayOrder')->editData($editdata);
                    if($res){
                        $this->scoreAdd($id,$score_id,$amount);//积分写入用户表中
                        http_response_code(200); // PHP 5.4 or greater
                    }else{
                        echo '数据写入失败';
                        http_response_code(500);
                    }
            }
        }
        if($module='Expand'){
            $map['order_no']=$data['order_no'];
            $order=D('Expand/ExpandRecords')->getRecordData($map);
            if($order['paid']!=1){//未支付状态就执行
                $exdata['id']=$order['id'];
                $exdata['paid']=1;

                $res=D('Expand/ExpandRecords')->editRecordData($exdata);
                if(!$res){
                    echo '数据写入失败';
                    http_response_code(500);
                }
            }
        }
    }

    /**
     * 积分充值订单处理
     * @param  [type] $order_no [description]
     * @return [type]           [description]
     */
    private function scoreOrder($data)
    {

        $order['id']=$data['id'];
        $order['paid']=$data['paid']?1:0;
        $order['created']=$data['created'];
        $order['refunded']=$data['refunded']?1:0;
        $order['channel']=$data['channel'];
        $order['order_no']=$data['order_no'];
        $order['client_ip']=$data['client_ip'];
        $order['amount']=$data['amount'];
        $order['amount_settle']=$data['amount_settle'];
        $order['time_paid']=$data['time_paid'];
        $order['time_expire']=$data['time_expire'];
        $order['time_settle']=$data['time_settle'];
        $order['refunds']=serialize($data['refunds']);
        $order['amount_refunded']=$data['amount_refunded'];
        //判断是否有此订单
        $order_data = $this->orderModel->getDataById($order['id']);
        if($order_data){
            //执行数据写入
            $res = $this->orderModel->editData($order);
        }
        return $res;
    }
    /**
     * 订单成功后增加积分
     * @param  [type] $id [ping++订单id]
     * @param  [type] $score_id [积分类型id]
     * @return [type] $amount   [支付金额]
     */
    private function scoreAdd($id,$score_id,$amount)
    {
        $map['ch_id']=$id;
        $res = $this->orderModel->getData($map);

        $type['id'] = $score_id;
        $scoreType = D('Ucenter/Score')->getType($type);//根据ID获取积分类型详细
        $score_num = $amount/100;

        $scoreModel = D('Ucenter/Score');
        $remark = '在线充值'.$scoreType['title'].'：+'.$score_num.$scoreType['unit'];
            $res = $scoreModel->setUserScore($res['uid'], $score_num,$score_id,'inc','Pingpay',0,$remark);//增加积分
        return $res;
    }
}