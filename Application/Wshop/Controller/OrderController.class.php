<?php

namespace Wshop\Controller;

use Think\Controller;
use Com\TPWechat;
use Com\WechatAuth;
class OrderController extends BaseController {
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

	public function makeorder()
	{

		$this->init_user();
		if (IS_POST){
			//todo 也可以通过购物车id来取, 下单完成后可以清除下购物车
			if (isset($_REQUEST['products']) && !($products = I('post.products')) )
			{
				$this->error('商品参数错误');
			}
			//购物车 cart_id = array(1,2,3)
			if (isset($_REQUEST['cart_id']) && (!($cart_id = I('cart_id',''))
				|| !($GLOBALS['_TMP']['cart_id'] = $cart_id)
				|| !($products = $this->cart_model->get_shop_cart_by_ids($cart_id, $this->user_id)))
			)
			{
				$this->error('购物车数据有误');
			}

			foreach ($products as $k => $p)
			{
				if (!is_string($p['sku_id']) || !is_numeric($p['quantity']))
				{
					$this->error('参数错误');
				}
				$products[$k] = array(
					'sku_id'   => $products[$k]['sku_id'],
					'quantity' => $products[$k]['quantity']);
			}
			$order = array(
				'user_id'  => $this->user_id,
				'products' => $products,
			);

			//收货地址, 虚拟物品不要收货地址
			if (isset($_REQUEST['address_id'])){
				if (!($aid = I('address_id', false, 'intval')) || !($address = $this->user_address_model->get_user_address_by_id($aid))){
					$this->error('地址参数错误');
				}
			}else{
				isset($_REQUEST['name']) && $address['name'] = I('name','','text');
				isset($_REQUEST['phone']) && $address['phone']  = preg_match('/^([0-9\-\+]{3,16})$/',I('phone', '', 'text'),$ret)?'':$ret[0];
				isset($_REQUEST['province']) && $address['province'] = I('province','','text');
				isset($_REQUEST['city']) && $address['city'] = I('city','','text');
				isset($_REQUEST['town']) && $address['town'] = I('town','','text');
				//如果这里要5级分类,用冒号分开多级 如 ${town}:车公庙:金地花园:48栋301
				isset($_REQUEST['address']) && $address['address'] = I('address','','text');
			}
			//运送方式 express, ems, mail, self, virtual
			isset($_REQUEST['delivery']) && $address['delivery'] = I('delivery','','text');
			isset($address) && $order['address'] = $address;
//			//支付方式
//			if (!isset($_REQUEST['pay_type']) ||
//				!($order['pay_type'] = I('pay_type', ShopOrderModel::PAY_TYPE_WEIXINPAY,'intval'))
//			)
//			{
//				$this->error('选择支付方式');
//			}
			//使用优惠劵
			isset($_REQUEST['coupon_id']) && $order['coupon_id'] = I('coupon_id', '', 'intval');
			//留言 发票 提货时间 等其他信息
			$order['info'] = I('info', '', 'text');
			//增加下单后的钩子
			\Think\Hook::add('AfterMakeOrder', '\Wshop\Logic\WshopOrderLogic');
			$ret = $this->order_logic->make_order($order);
			if ($ret){
				$this->success($ret);
			}else{
				$this->error('下单失败.' . $this->order_logic->error_str);
			}
		}else{
			//$this->assign('su', $su);
			if(!($id = I('id','','intval')) || !($product = $this->product_model->get_product_by_id($id))) {
				$product = array();
			}
			$quantity =I('quantity','1','intval');
			if(!($coupon_id = I('cookie.coupon_id','','intval')) || !($coupon = $this->user_coupon_model->get_user_coupon_by_id($coupon_id))) {
				$coupon = array();
			}
			$sku = I('sku','','text');
			if( !empty($sku) && !($product['sku_table']['info'][$sku])){
				$product['price'] = $product['sku_table']['info'][$sku]['price'];
			}else{
				$sku = '';
			}

			$cart = $this->cart_model->get_shop_cart_by_user_id($this->user_id);
			$cart_id[0] = '';

			if (isset($_REQUEST['cart_id'])
				&& ( !($cart_id = I('cart_id','','text'))
					|| !(preg_match('/^\d+(,\d+)*$/',$cart_id))
					|| !($cart_id = explode(',',$cart_id))
					|| !($cart_list_products = $this->cart_model->get_shop_cart_by_ids($cart_id, $this->user_id)))
			) {
				redirect(U('wshop/index/user'));
			}

			$address[0] = $this->user_address_model->get_last_user_address_by_user_id($this->user_id);

			$this->assign('quantity', $quantity);
			$this->assign('product', $product);
			$this->assign('coupon', $coupon);
			$this->assign('sku', $sku);
			$this->assign('cart_id', $cart_id);
			$this->assign('cart', $cart);
			$this->assign('cart_list_products', $cart_list_products);
			$this->assign('address',$address);
			$this->display();
		}
	}

	
	public function orders($page=1,$r=10)
	{
		$this->init_user();
		$option['status'] = 1;
		$option['page'] = $page;
		$option['r'] = $r;
		$option['user_id'] = $this->user_id;
		if(IS_POST)
		{
			$order_list = $this->order_model->get_order_list($option);
			$order_list['list'] = empty($order_list['list'])?array(): $order_list['list'];
			array_walk($order_list['list'],function(&$a)
			{
				empty($a['products']) ||
				array_walk($a['products'],function(&$b)
				{
					$b['main_img'] = (empty($b['main_img'])?'':pic($b['main_img']));
				});
			});
			$this->success($order_list);
		}else{
			$this->assign('option', $option);
			$this->display();
		}
	}

	/*
	 * 取消订单
	 */
	public function cancel_order()
	{
		$this->init_user();
		if (IS_POST)
		{
			if (!($order_id = I('id', false, 'intval'))
				|| !($order = $this->order_model->get_order_by_id($order_id))
				|| !($order['user_id'] == $this->user_id)
			){
				$this->error('参数错误');
			}
			$ret = $this->order_logic->cancal_order($order);
			if ($ret)
			{
				$this->success('成功取消订单');
			}
			else
			{
				$this->error('取消失败,' . $this->order_logic->error_str);
			}
		}
		else
		{
			$this->error('提交方式不合法');
		}
	}

	/*
	 * 确认收货
	 */
	public function do_receipt()
	{
		$this->init_user();
		if (IS_POST)
		{
			if (!($order_id = I('id', false, 'intval'))
				|| !($order = $this->order_model->get_order_by_id($order_id))
				|| !($order['user_id'] == $this->user_id)
			)
			{
				$this->error('参数错误');
			}
			$ret = $this->order_logic->recv_goods($order);
			if ($ret)
			{
				$this->success('操作成功');
			}
			else
			{
				$this->error('操作失败,' . $this->order_logic->error_str);
			}

		}
		else
		{
			$this->error('提交方式不合法');
		}
	}

	/*
	 * 订单评论
	 */
	public function comment()
	{
		$this->init_user();
		if(IS_POST)
		{
			$product_comments = I('product_comment');
			foreach($product_comments as &$product_comment)
			{
				$product_comment['user_id'] = $this->user_id;
				$product_comment['product_id'] = explode(';',$product_comment['product_id'])[0];
				if(!($product_comment =  $this->product_comment_model->create($product_comment)))
				{
					$this->error($this->product_comment_model->geterror());
				}


			}
			$ret = $this->order_logic->add_product_comment($product_comments);
			if(!$ret )
			{
				$this->error('评论失败，'.$this->order_logic->error_str);
			}
			if($ret )
			{
				$this->success('评论成功');
			}
		}
		else
		{
			$id = I('id','','intval');
			$order = $this->order_model->get_order_by_id($id);
			$this->assign('order', $order);
			$this->assign('products', $order['products']);
			$this->display();
		}

	}


	/*
	 * 订单详情
	 */
	public function orderdetail()
	{
		$id = I('id','','intval');
		$order = $this->order_model->get_order_by_id($id);
//		var_dump(__file__.' line:'.__line__,$order);exit;
		$this->assign('order', $order);
		$this->display();
	}

	public function test_pay($id='')
	{
		if(APP_DEBUG)
		{
			$order_model = D('Wshop/WshopOrder');
			$order_logic = D('Wshop/WshopOrder','Logic');
			$shop_order = $order_model->where('id ="'.$id.'"')->find();
			empty($shop_order) && $this->error('订单号错误');
			$shop_order['paid_time'] = time();
			$shop_order['pay_type'] = 9;
			$shop_order['pay_info'] =   array(
				'info' => 'this is test pay',
			);
			$shop_order['pay_info'] = json_encode($shop_order['pay_info']);
			$ret = $order_logic->pay_order($shop_order);//支付订单
			echo $ret?'成功':'失败,'.$order_logic->error_str;exit;
		}


	}

	public function commentlist()
	{
		$option['product_id']= I('product_id','','intval');
		$option['page'] = I('page','1','intval');
		if (IS_POST)
		{
//			$option['status'] = 1;//只取审核通过的
			$ret = $this->product_comment_model->get_product_comment_list($option);
			if($ret)
			{
				$this->success($ret);
			}
			else
			{
				$this->error();
			}

		}
		else{
			$this->assign('product_id', $option['product_id']);
			$this->display();
		}


	}

}