<?php

namespace Wshop\Controller;

use Think\Controller;
use Com\TPWechat;
use Com\WechatAuth;
class CartController extends BaseController {

	protected $cart_model;
	function _initialize()
	{
		parent::_initialize();
		$this->cart_model = D('Wshop/WshopCart');

	}

	public function index()
	{
		parent::init_user();
		$cart = $this->cart_model->get_shop_cart_by_user_id($this->uid);
		
		$this->assign('cart', $cart);
		$this->display();
	}

	public function add_to_cart()
	{
		parent::init_user();
		if (!($shop_cart = $this->cart_model->create())){
			$this->error($this->cart_model->getError());
		}
		$shop_cart['user_id'] = $this->uid;
		$ret = $this->cart_model->add_shop_cart($shop_cart);
		if ($ret){
			$this->success('成功');
		}else{
			$this->error('');
		}
	}
	public function delete_cart()
	{
		parent::init_user();
		$ids = I('ids', '');
		$ret = $this->cart_model->delete_shop_cart($ids, $this->user_id);
		if ($ret){
			$this->success('成功');
		}else{
			$this->error();
		}
	}

}