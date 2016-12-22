<?php

namespace Wshop\Controller;

use Think\Controller;
use Com\TPWechat;
use Com\WechatAuth;

class CouponController extends BaseController {
	protected $coupon_model;
	protected $coupon_logic;
	protected $user_coupon_model;


	function _initialize()
	{
		parent::_initialize();
		$this->coupon_model       = D('Wshop/WshopCoupon');
		$this->user_coupon_model  = D('Wshop/WshopUserCoupon');
		$this->coupon_logic       = D('Wshop/WshopCoupon', 'Logic');

		//分类
		$items = $this->product_cats_model->select();
		$cats = array();
		foreach ($items as $v) {
		    $cats[$v['id']] = $v;
		    $cats[$v['id']]['link'] = U('Wshop/index/cats',array('id'=>$v['id']));
		    $cats[$v['id']]['items'] = array();//items存放当前节点的所有子节点。
		    if($v['parent_id'] != 0) {
		    	$cats[$v['parent_id']]['items'][$v['id']] = &$cats[$v['id']];
		   	}
		 }
		unset($v);
		foreach ($cats as $k=>$v) {
		    if($v['parent_id'] != 0) {
		        unset($cats[$k]);
		    } 
		}
		$this->assign('cats',$cats);
		//商城菜单
		$menu = array(
			array('title'=>'购物车','link'=>U('Wshop/cart/index'),'tab'=>'cart'),
			array('title'=>'我的','link'=>U('Wshop/user/index'),'tab'=>'user')
		);
		$this->assign('menu',$menu);

	}

	/*
	 * 获取用户的优惠券
	 */
	public function user_coupon()
	{
		$this->init_user();
		$type = I('type', '', 'text');
		$option['page']    = I('page', '', 'intval');
		$option['r']       = I('r', '', 'intval');
		$option['user_id'] = $this->user_id;
		//可用的
		$option['available'] = I('available', 'true', 'bool');
		$GLOBALS['_TMP']['paid_fee'] = I('paid_fee','','intval'); //
		$user_coupons = $this->user_coupon_model->get_user_coupon_list($option);
		$this->assign('user_coupons', $user_coupons);
		$this->assign('type', $type);
		$this->display();
	}

	/*
	 * 可领优惠券列表
	 */
	public function coupons()
	{
		isset($_REQUEST['available']) && $option['available'] = I('available', 'true', 'bool');
		$coupon = $this->coupon_model->get_coupon_lsit($option);
		$this->assign('coupon', $coupon);
		$this->display();
	}

	/*
	 * 领取优惠券
	 */
	public function get_coupon()
	{
		$this->init_user();
		$coupon_id = I('coupon_id', '', 'intval');
		if (
			empty($coupon_id)
			|| !($coupon = $this->coupon_model->get_coupon_by_id($coupon_id))
		)
		{
			$this->error('优惠券不存在');//id 解密对不上
		}

		$ret = $this->coupon_logic->add_a_coupon_to_user($coupon['id'], $this->uid);

		if ($ret)
		{
			$this->success('领取成功');
		}
		else
		{
			$this->error('领取失败，' . $this->coupon_logic->error_str);
		}
	}
}