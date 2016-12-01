<?php

namespace Pingpay\Controller;

use Think\Controller;

class OrderController extends BaseController
{
    protected $pingpayModel;
    protected $orderModel;

    public function _initialize()
    {
        $this->pingpayModel = D('Pingpay/Pingpay');
        $this->orderModel = D('Pingpay/PingpayOrder');
        //需要登录
        if (!is_login()) {
            $this->error(L('_ERROR_NEED_LOGIN_'));
        }
        parent::_initialize();
        //需要登录
    }
    
    
    public function index($page=1,$r=20)
    {
        list($list,$totalCount) = $this->orderModel->getListByPage($map,$page,'created desc','*',$r);

        //dump($list);exit;
        foreach($list as &$val){
            $val['amount'] = sprintf("%01.2f", $val['amount']/100);//将金额单位分转成元
        }
        unset($val);
        
        $result_url=think_encrypt(modC('PINGPAY_CONFIG_RESULTURL','','Pingpay'));//支付成功后跳转回的地址

        $this->assign('result_url',$result_url);
        $this->assign('data',$list);
        $this->assign('totalCount',$totalCount);
        $this->display();
    }


    public function detail()
    {
        $id = I('id','','op_t');
        $data = $this->orderModel->getDataById($id);
        if($data){
            //$metadata = unserialize($data['metadata']);
            //将金额单位分转成元
            $data['amount'] = sprintf("%01.2f", $data['amount']/100);
            //支付方式详细配置
            $channel = $data['channel'];
            $channel = $this->pingpayModel->getPaychannelInfo($channel);
        }else{
            $this->error('错误的参数');
        }
        
        //dump($metadata);exit;
        $this->assign('data',$data);
        $this->assign('channel',$channel);
        $this->display();
    }
    
}