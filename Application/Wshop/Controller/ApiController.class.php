<?php

namespace Wshop\Controller;

use Think\Controller;

class ApiController extends Controller {
	protected $product_model;
	protected $cart_model;
	protected $order_model;
	protected $order_logic;
	protected $user_address_model;
	protected $delivery_model;
	protected $user_coupon;
	protected $coupon_logic;

	function _initialize()
	{
		$this->product_model      = D('Wshop/WshopProduct');
		$this->cart_model         = D('Wshop/WshopCart');
		$this->order_model        = D('Wshop/WshopOrder');
		$this->order_logic        = D('Wshop/WshopOrder', 'Logic');
		$this->user_address_model = D('Wshop/WshopUserAddress');
		$this->delivery_model     = D('Wshop/WshopDelivery');
		$this->user_coupon        = D('Wshop/WshopUserCoupon');
		$this->coupon_logic       = D('Wshop/WshopCoupon', 'Logic');
	}

	/**
	 * 校验微信接口配置信息
	 */
	public function api()
	{
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()){
			echo $echoStr;
			exit;
		}
	}
	private function checkSignature()
	{
		// you must define TOKEN by yourself

		$token = $this->weinfo['token'];
		if (!$token) {
			throw new Exception('TOKEN is not defined!');
		}
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 运费模板json接口
	 * @param int $id 运费模板ID
	 * @param int $areaid 地区ID代码 依赖ChinaCity插件
	 * @return [json] [根据模板ID返回模板详细JSON字符串]
	 */
	public function delivery(){
		$id = I('get.id',0,'intval');
		$areaid = I('get.areaid',0,'intval');
		$delivery = $this->delivery_model->get_delivery_by_id($id);
		//指定地区运费
		if($delivery['valuation']==1){
			$custom = $delivery['rule']['express']['custom'];
			foreach($custom as $val){
				foreach($val['area'] as $v){
					//$area = in_array($areaid,$v);
					//echo $v['id'];
					if($areaid == $v['id']){
						$areaid = $v['id'];
						$areaname = $v['name'];
						$cost = $val['cost'];
					}else{
						$cost = $delivery['rule']['express']['normal'];
					}
				}
			}
		}else{//固定运费
			$cost = $delivery['rule']['express']['cost'];
		}
		//组装DATA数据
		$data['id']=$delivery['id'];
		$data['title']=$delivery['title'];
		$data['valuation']=$delivery['valuation'];
		$data['cost']=$cost;
		//组装JSON返回数据
		if($delivery){
			$result['status']=1;
			$result['info'] = 'success';
			$result['data'] = $data;
		}else{
			$result['status']=0;
			$result['info'] = 'error';
		}
		$this->ajaxReturn($result,'JSON');
	}
}