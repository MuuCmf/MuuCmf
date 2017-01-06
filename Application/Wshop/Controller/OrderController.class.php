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
	protected $delivery_model;
	protected $user_coupon;
	protected $coupon_logic;

function _initialize()
	{
		parent::_initialize();
		$this->product_model      = D('Wshop/WshopProduct');
		$this->cart_model         = D('Wshop/WshopCart');
		$this->order_model        = D('Wshop/WshopOrder');
		$this->order_logic        = D('Wshop/WshopOrder', 'Logic');
		$this->user_address_model = D('Wshop/WshopUserAddress');
		$this->delivery_model     = D('Wshop/WshopDelivery');
		$this->user_coupon        = D('Wshop/WshopUserCoupon');
		$this->coupon_logic       = D('Wshop/WshopCoupon', 'Logic');
	}

	public function makeorder()
	{
		$this->init_user();
		if (IS_POST){
			$products = I('post.products');
			$cart_id = I('cart_id','','text');
			//todo 也可以通过购物车id来取, 下单完成后可以清除下购物车
			//购物车 cart_id = array(1,2,3)
			if ($cart_id){
				$products = $this->cart_model->get_shop_cart_by_ids($cart_id, $this->uid);
			}

			foreach ($products as $k => $p)
			{
				if (!is_string($p['sku_id']) || !is_numeric($p['quantity'])){
					$this->error('参数错误');
				}
				$products[$k] = array(
					'sku_id'   => $products[$k]['sku_id'],
					'quantity' => $products[$k]['quantity']);
			}
			$order['user_id'] = $this->uid;
			$order['order_no'] = date('YmdHis').rand(100000, 999999);//自动生成商家唯一订单号
			$order['products'] = $products;

			//收货地址, 虚拟物品不要收货地址
			if (isset($_REQUEST['address_id'])){
				if (!($aid = I('address_id', false, 'intval')) || !($address = $this->user_address_model->get_user_address_by_id($aid))){
					$this->error('地址参数错误');
				}
			}else{
				$address['name'] = I('name','','text');
				$address['phone']  = preg_match('/^([0-9\-\+]{3,16})$/',I('phone', '', 'text'),$ret)?'':$ret[0];
				$address['province'] = I('province','','text');
				$address['city'] = I('city','','text');
				$address['district'] = I('district','','text');
				$address['address'] = I('address','','text');
			}
			//运送方式 express, ems, mail, self, virtual
			$address['delivery'] = I('delivery','','text');

			$order['address'] = $address;
			//邮费价格
			$order['delivery_fee'] = I('delivery_fee','','intval');
			//使用优惠劵
			$order['coupon_id'] = I('coupon_id', '', 'intval');
			//留言 发票 提货时间 等其他信息
			$order['info'] = I('info', '', 'text');
			//dump($order);exit;
			//增加下单后的钩子
			\Think\Hook::add('AfterMakeOrder', '\Wshop\Logic\WshopOrderLogic');
			$ret = $this->order_logic->make_order($order);
			if ($ret){
				$this->success('下单成功');
			}else{
				$this->error('下单失败.' . $this->order_logic->error_str);
			}
		}else{
			//购物车提交
			$cart_id = I('cart_id','','text');
			//直接购买
			$id = I('id','','intval');
			$quantity =I('quantity',0,'intval');
			$sku = I('sku','','text');
			//初始化总价格为0
			$real_price = 0;
			//购物车提交
			if($cart_id){
				$cart_list_products = $this->cart_model->get_shop_cart_by_ids($cart_id,$this->uid);
				if(empty($cart_list_products)){
					$this->error('参数错误');
				}
				//初始化运费模板ID为0
				$tmp_price = 0;
				foreach($cart_list_products as &$val){
		            $val['product']['price'] = sprintf("%01.2f", $val['product']['price']/100);//将金额单位分转成元
		            $val['product']['ori_price'] = sprintf("%01.2f", $val['product']['ori_price']/100);
		            $val['total_price'] = $val['product']['price']*$val['quantity'];
		            $val['total_price'] = sprintf("%01.2f", $val['total_price']);
		            //购物车商品总价格
		            $real_price+=$val['total_price'];
		            //购物车商品总数量
		            $real_quantity+=$val['quantity'];
		            //运费模板id选择价格最贵的商品
		            if($val['product']['price']>=$tmp_price){
		            	$tmp_price = $val['product']['price'];
		            	$delivery_id = $val['product']['delivery_id'];
		            }
		        }
		        unset($val);
		        unset($tmp_price);
		        $this->assign('cart_id',$cart_id);
			}
			//直接购买
			if($id && $quantity){
				//商品信息
				//购买数量
				//购买规格
				$product = $this->product_model->get_product_by_id($id);
				if(empty($product)){
					$this->error('参数错误');
				}
				if($product['quantity']<$quantity){
					$this->error('没有这么多~亲！');
				}
				if($product['sku_table']['info'][$sku]){
					$product['price'] = $product['sku_table']['info'][$sku]['price'];
				}
				$product['price'] = sprintf("%01.2f", $product['price']/100);//将金额单位分转成元
				$product['total_price'] = $product['price']*$quantity;
				$product['total_price'] = sprintf("%01.2f", $product['total_price']);
				$product['sku'] = $sku;
				$product['sku_quantity'] = $quantity;
			}
		    //获取可用优惠卷列表
		    $option['user_id'] = $this->uid; 
		    $option['available'] = 1;
		    $enable_coupon = $this->user_coupon->get_user_coupon_list($option);
		    foreach($enable_coupon['list'] as &$val){
		            $val['info']['rule']['min_price'] = sprintf("%01.2f", $val['info']['rule']['min_price']/100);//将金额单位分转成元
		            $val['info']['rule']['discount'] = sprintf("%01.2f", $val['info']['rule']['discount']/100);
		    }
		    unset($val);
		    //获取收货地址列表
			list($listAddress,$totalCount) = $this->user_address_model->get_user_address_list($this->uid);
			foreach($listAddress as &$val){
		            $val['province'] = D('district')->where(array('id' => $val['province']))->getField('name');
		            $val['city'] = D('district')->where(array('id' => $val['city']))->getField('name');
		            $val['district'] = D('district')->where(array('id' => $val['district']))->getField('name');
			}
			unset($val);
			//$delivery_id值为空即包邮
			if(!empty($product)){
		        $real_price =  $product['total_price'];//获取商品总价格
		        $real_quantity = $quantity; //获取商品总数量
		        $delivery_id = $product['delivery_id'];//获取配送方式及运费ID
		        $way = 'product';
		    }
		    if(!empty($cart_list_products)){
		        $real_price =  $real_price;//获取商品总价格
		        $real_quantity = $real_quantity;//获取商品总数量
		        $delivery_id = $delivery_id;//获取配送方式及运费ID
		        $way = 'cart';
		    }
		    
			$this->assign('delivery_id', $delivery_id);
			$this->assign('product', $product);
			$this->assign('cart_list_products', $cart_list_products);
			$this->assign('real_price',$real_price);
			$this->assign('real_quantity', $real_quantity);
			$this->assign('enable_coupon',$enable_coupon);
			$this->assign('listAddress',$listAddress);
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
			if ($ret){
				$this->success('成功取消订单');
			}else{
				$this->error('取消失败,' . $this->order_logic->error_str);
			}
		}else{
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
			){
				$this->error('参数错误');
			}
			$ret = $this->order_logic->recv_goods($order);
			if ($ret){
				$this->success('操作成功');
			}else{
				$this->error('操作失败,' . $this->order_logic->error_str);
			}

		}else{
			$this->error('提交方式不合法');
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