<?php

namespace Wshop\Controller;

use Think\Controller;
use Com\TPWechat;
use Com\WechatAuth;
class UserController extends BaseController {
	protected $product_model;
	protected $cart_model;
	protected $order_model;
	protected $order_logic;
	protected $user_address_model;

function _initialize()
	{
		parent::_initialize();
		$this->product_model      = D('Wshop/WshopProduct');
		$this->cart_model         = D('Wshop/WshopCart');
		$this->order_model        = D('Wshop/WshopOrder');
		$this->order_logic        = D('Wshop/WshopOrder', 'Logic');
		$this->user_address_model = D('Wshop/WshopUserAddress');

	}

	public function index()
	{
		$this->init_user();
		$su = query_user(array('avatar32', 'nickname', 'mobile'), $this->uid);
		$map['user_id'] = $this->uid;
		$map['status'] = 1;
		$order_count_group_by_status = $this->order_model->where($map)->getfield('status,count(1) as count');
		$this->assign('su', $su);
		$this->assign('order_count_group_by_status', $order_count_group_by_status);
		$this->display();
	}

}