<?php


namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;
use Common\Model\ContentHandlerModel;


class WshopController extends AdminController
{
    protected $product_cats_model;
    protected $product_model;
    protected $order_model;
    protected $delivery_model;
    protected $message_model;
    protected $coupon_model;
    protected $user_coupon_model;
    protected $address_model;
    protected $product_comment_model;
	protected $order_logic;
	protected $coupon_logic;

    function _initialize()
    {
        $this->product_cats_model = D('Wshop/WshopProductCats');
	    $this->product_model = D('Wshop/WshopProduct');
	    $this->order_model = D('Wshop/WshopOrder');
	    $this->delivery_model = D('Wshop/WshopDelivery');
	    $this->message_model = D('Wshop/WshopMessage');
	    $this->coupon_model = D('Wshop/WshopCoupon');
	    $this->user_coupon_model = D('Wshop/WshopUserCoupon');
	    $this->order_logic = D('Wshop/WshopOrder','Logic');
	    $this->coupon_logic = D('Wshop/WshopCoupon','Logic');
	    $this->address_model = D('Wshop/WshopUserAddress');
	    $this->product_comment_model = D('Wshop/WshopProductComment');
        parent::_initialize();
    }


	public function index()
	{
		if(!modC('WSHOP_SHOW_TITLE', '', 'Wshop'))
		{
			//未配置商城跳转
			redirect(U('Wshop/config'));
		}
		else
		{
			redirect(U('Wshop/product'));
		}
	}

	public function config()
	{
		$wxType = array(
			'1'=>'普通订阅号',
			'2'=>'认证订阅号/普通服务号',
			'3'=>'认证服务号'
		);
		$apiUrl = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?s=/wshop/api';

		$builder = new AdminConfigBuilder();
		$data = $builder->handleConfig();
		$builder->title('商城基本设置')
			->data($data)
			->keyText('WSHOP_SHOW_TITLE', '商城名称', '在首页的商场名称')->keyDefault('WSHOP_SHOW_TITLE','MuuCmf轻量级商场解决方案')
			->keySingleImage('WSHOP_SHOW_LOGO','商场logo')
			->keyEditor('WSHOP_SHOW_DESC', '商城简介','','all',array('width' => '700px', 'height' => '400px'))
			->keyBool('WSHOP_SHOW_STATUS', '商城状态','默认正常')
			->keySelect('WSHOP_WX_TYPE','类型','',$wxType)
			->keyText('WSHOP_WX_APPID', 'appID', '微信公众号的appID')
			->keyText('WSHOP_WX_APPSECRET', 'appsecret', '微信公众号的appsecret')
			->keyReadOnly('WSHOP_WX_OAUTH','Oauth2.0')->keyDefault('WSHOP_WX_OAUTH','在微信公众号请求用户网页授权之前，开发者需要先到公众平台网站的【开发者中心】网页服务中配置授权回调域名。查看详情')
			->keyReadOnlyText('WSHOP_WX_URL','接口地址','设置“公众平台接口”配置信息中的接口地址')->keyDefault('WSHOP_WX_URL',$apiUrl)
			->keyText('WSHOP_WX_TOKEN', 'Token', '微信公众号的Token')
			->keyText('WSHOP_WX_ENCODINGAESKEY', 'EncodingAESKey', '微信公众号的EncodingAESKey')



			->group('商城基本配置', 'WSHOP_SHOW_TITLE,WSHOP_SHOW_LOGO,WSHOP_SHOW_DESC,WSHOP_SHOP_STATUS')
			->group('微信配置','WSHOP_WX_TYPE,WSHOP_WX_APPID,WSHOP_WX_APPSECRET,WSHOP_WX_OAUTH,WSHOP_WX_URL,WSHOP_WX_TOKEN,WSHOP_WX_ENCODINGAESKEY')
			->buttonSubmit('', '保存')
			->display();
	}
	/*
	 * 商品分类
	 */
	public function product_cats($action='',$page=1,$r=10)
	{

		switch($action)
		{
			case 'add':
				if(IS_POST)
				{
//					var_dump(__file__.' line:'.__line__,$_REQUEST);exit;
					$product_cats = $this->product_cats_model->create();
					if (!$product_cats){

						$this->error($this->product_cats_model->getError());
					}
					if(!empty($product_cats['parent_id'] )
						&& (
							($product_cats['parent_id'] ==$product_cats['id']) ||
							(($sun_id = $this->product_cats_model->get_all_cat_id_by_pid($product_cats['id']))
							&& (in_array($product_cats['parent_id'],$sun_id))))
					)
					{
						$this->error('不要选择自己分类或自己的子分类');
					}
					$ret = $this->product_cats_model->add_or_edit_product_cats($product_cats);
					if ($ret){

						$this->success('操作成功。', U('wshop/product_cats',array('parent_id'=>I('parent_id',0))));
					}else{
						$this->error('操作失败。');
					}
				}else{
					$builder       = new AdminConfigBuilder();
					$id = I('id');
					if(!empty($id)){
						$product_cats = $this->product_cats_model->get_product_cat_by_id($id);
					}else{
						$product_cats = array();
					}

					$select = $this->product_cats_model->get_produnct_cat_config_select();
//					var_dump(__file__.' line:'.__line__,$select);exit;
					$builder->title('新增/修改商品分类')

						->keyId()
						->keyText('title', '分类名称')
						->keyText('title_en', '分类名称英文')
						->keySingleImage('image','图片')
						->keySelect('parent_id','上级分类','',$select)
						->keyText('sort', '排序')
						->keyRadio('stauts','状态','',array('0'=>'正常','1'=>'隐藏'))
						->keyCreateTime()
						->data($product_cats)
						->buttonSubmit(U('wshop/product_cats',array('action'=>'add')))
						->buttonBack()
						->display();
				}
				break;
			case 'delete':
				$ids = I('ids');
				$ret = $this->product_cats_model->delete_product_cats($ids);
				if ($ret)
				{

					$this->success('操作成功。', U('shop/product_cats'));
				}
				else
				{
					$this->error('操作失败。');
				}
				break;
			default:

				$option['parent_id'] = I('parent_id',0,'intval');
				if(!empty($option['parent_id']))
				{
					$parent_cat  = $this->product_cats_model->get_product_cat_by_id($option['parent_id']);
				}
				if(I('all')) $option = array();
				$option['page'] = $page;
				$option['r']  =  $r;
				$cats = $this->product_cats_model->get_product_cats($option);
				$totalCount = $cats['count'];
//				var_dump(__file__.' line:'.__line__,$parent_cat);exit;
				$select = $this->product_cats_model->get_produnct_cat_list_select();
				$builder = new AdminListBuilder();
				$builder
					->title((empty($parent_cat)?'顶级的':$parent_cat['title'].' 的子').'商品分类')
					->setSelectPostUrl(U('wshop/product_cats'))
					->select('分类查看', 'parent_id', 'select', '', '', '', $select)
//					->buttonNew(U('shop/product_cats',array('all'=>1)),'全部分类')
					->buttonNew(U('wshop/product_cats',array('parent_id'=>(empty($parent_cat['parent_id'])?0:$parent_cat['parent_id']))),'上级分类')
					->buttonnew(U('wshop/product_cats',array('action'=>'add','parent_id'=>$option['parent_id'])),'新增分类')
					->ajaxButton(U('wshop/product_cats',array('action'=>'delete')),'','删除')
//					->keyText('id','id')
					->keyText('title','标题')
					->keyText('title_en','英文标题')
					->keyImage('image','图片')
					->keyText('sort','排序')
					->keyTime('create_time','创建时间')
					->keyStatus('status','状态')
					->keyDoAction('admin/wshop/product_cats/action/add/id/###','编辑')
					->keyDoAction('admin/wshop/product_cats/parent_id/###','查看下属分类')
					->data($cats['list'])
					->pagination($totalCount, $r)
					->display();
		}
	}

	/*
	 * 商品相关
	 */
	public function product($action = '')
	{
		switch($action)
		{
			case 'add':
				if(IS_POST)
				{
					$product = $this->product_model->create();
					if (!$product){
						$this->error($this->product_model->getError());
					}
					$product['price'] = sprintf("%.2f",$product['price']*100);
					$product['ori_price'] = sprintf("%.2f",$product['ori_price']*100);

					$ret = $this->product_model->add_or_edit_product($product);
					if ($ret){
						$this->success('操作成功。', U('wshop/product'));
					}else{
						$this->error('操作失败。');
					}
				}else{
					$builder       = new AdminConfigBuilder();
					$id = I('id');
					if(!empty($id)){
						$product = $this->product_model->get_product_by_id($id);
						$product['price'] = sprintf("%.2f",$product['price']/100);
						$product['ori_price'] = sprintf("%.2f",$product['ori_price']/100);
					}else{
						$product = array();
					}

					$select = $this->product_cats_model->get_produnct_cat_config_select('选择分类');
					if(count($select)==1){
						$this->error('先添加一个商品分类吧',U('wshop/product_cats',array('action'=>'add')),2);
					}
					$delivery_select = $this->delivery_model->getfield('id,title');
					
					$info_array = array(
					//'不货到付款','不包邮','不开发票','不保修','不退换货','不是新品',
					                    '6'=>'热销','7'=>'推荐');
					//注释的暂不支持
					$builder->title('新增/修改商品')
						->keyId()
						->keyText('title', '商品名称')
						->keyTextArea('description','简单描述')
						->keySingleImage('main_img','商品主图')
						->keyMultiImage('images','商品图片,分号分开多张图片')
						->keySelect('cat_id','商品分类','',$select)
						->keyInteger('price', '价格/元','交易价格')
						->keyInteger('ori_price', '原价/元','显示被划掉价格')
						->keyInteger('quantity', '库存')
						->keyText('product_code', '商家编码,可用于搜索')
						->keyCheckBox('info','其他配置','',$info_array)
						->keyInteger('back_point', '购买返还积分')
//						->keyInteger('point_price', '积分换购所需分数')
//						->keyInteger('buy_limit', '限购数,0不限购')
//						->keyText('sku_table','商品sku')
//						->keytext('location','货物所在地址')
						->keySelect('delivery_id','运费模板, 可先保存后再修改运费模板,避免丢失已编辑信息','<a target="_blank" href="index.php?s=/admin/wshop/delivery">点击添加运费模板</a>',$delivery_select)
						->keyText('sort', '排序')
						->keyRadio('status','状态','',array('1'=>'正常','0'=>'下架'))
						->keyEditor('content', '商品详情','','all')

						//->keyCreateTime()
//						->keytime('modify_time','编辑时间')
						->data($product)
						->buttonSubmit(U('wshop/product',array('action'=>'add')))
						->buttonBack()
						->display();
				}
				break;
			case 'delete':
				$ids = I('ids');
				$ret = $this->product_model->delete_product($ids);
				if ($ret){
					$this->success('操作成功。', U('wshop/product'));
				}else{
					$this->error('操作失败。');
				}
				break;
			case 'cell_record':
				$option['product_id'] = I('product_id',0);
				$option['user_id'] = I('user_id',0);
//				$option['min_time'] = I('min_time',0);
				$option['page'] = I('page',1);
				$option['r'] = I('r',10);
				$product_sell_model = D('wshop/ShopProductSell');
				$product_sell_record = $product_sell_model->get_sell_record($option);
				$totalCount = $product_sell_record['count'];
				$builder = new AdminListBuilder();
				$builder
					->title('商品成交记录')
					->keyText('product_id','商品id')
					->keyText('order_id','订单id')
					->keyText('user_id','用户id')
					->keyText('paid_price','下单价格/（分）')
					->keyText('quantity','下单数目')
					->keyTime('create_time','创建时间')
					->data($product_sell_record['list'])
					->pagination($totalCount, $option['r'])
					->display();
				break;
			case 'delete_sku_table':
				if(IS_POST)
				{
					$product['id'] = I('id','','intval');
					empty($product['id']) && $this->error('缺少商品id');
					$product['sku_table'] = '';
					$ret = $this->product_model->add_or_edit_product($product);
					if ($ret)
					{
						$this->success('操作成功。',U('wshop/product',array('action'=>'sku_table','id'=>$product['id'])),1);
					}
					else
					{
						$this->error('操作失败。');
					}
				}
				break;
			case 'sku_table':
				if(IS_POST)
				{
					$product['id'] = I('id',0,'intval');
					empty($product['id']) && $this->error('缺少商品id');
					$table = I('table','','text');
					$info = I('info','','text');

					$product['sku_table'] = array('table'=>$table,'info'=>$info);
					$product['sku_table'] = json_encode($product['sku_table']);
					$ret = $this->product_model->add_or_edit_product($product);
					if ($ret){
						$this->success('操作成功。');
					}else{
						$this->error('操作失败。');
					}
				}
				else
				{
					$id = I('id',0,'intval');
					if(empty($id) || !($product = $this->product_model->get_product_by_id($id)))
					{
						$this->error('请选择一个商品','',2);
					}
					$this->assign('product', $product);
	                $this->display('Wshop@Admin/sku_table');
				}
				
				break;
			case 'exi':
				if(IS_POST)
				{
					//没写完
					var_dump(__file__.' line:'.__line__,$_REQUEST);exit;
					$product = array();
					$ret = $this->product_model->add_or_edit_product($product);
					if($ret){
						$this->success('操作成功',U('wshop/product'));
					}else{
						$this->error('操作失败');
					}

				}else{
					$porduct_extra_info_model = D('Wshop/WshopProductExtraInfo');

					$id = I('id');
					if(empty($id)
						||!($product = $this->product_model->get_product_by_id($id)))
					{
						$this->error('请选择一个商品','',2);
					}
					$exi = $porduct_extra_info_model->get_product_extra_info($id);
					$this->assign('exi', $exi);
					$this->display('Wshop@Admin/exi');
				}
				break;
			default:

				$option['page'] = I('page',1);
				$option['r'] = I('r',10);
				$option['cat_id'] = I('cat_id');
				$count = I('count');
				if(empty($option['cat_id'])) unset($option['cat_id']);
				$product = $this->product_model->get_product_list($option);
				foreach($product['list'] as &$val){
					$val['price']='￥'.sprintf("%.2f",$val['price']/100);
				}
				unset($val);
				$totalCount = $product['count'];
				
				$select = $this->product_cats_model->get_produnct_cat_list_select('全部分类');
				$select2 = $this->product_cats_model->get_produnct_cat_config_select('全部分类');
				$builder = new AdminListBuilder();
				$builder
					->title('商品管理')
					->setSelectPostUrl(U('wshop/product'))
					->select('分类查看', 'cat_id', 'select', '', '', '', $select)
					->select('显示模式', 'count', 'select', '', '', '', array(array('id'=>0,'value'=>'正常'),array('id'=>1,'value'=>'统计信息')))
					->buttonnew(U('wshop/product',array('action'=>'add')),'新增商品')
					->ajaxButton(U('wshop/product',array('action'=>'delete')),'','删除')
					->keyText('id','商品id')
					->keyText('title','商品名');
				if(!$count){
					$builder->keyMap('cat_id','所属分类',$select2)
						->keyText('price','价格/（元）')
						->keyText('quantity','库存')
						->keyImage('main_img','图片')
						->keyTime('create_time','创建时间')
						//->keyTime('modify_time','编辑时间')
						->keyText('sort','排序')
						->keyMap('status','状态',array('0'=>'正常','1'=>'下架'));
				}else{
					$builder
						->keyText('like_cnt','点赞数')
//						->keyText('fav_cnt','收藏数')
						->keyText('comment_cnt','评论数')
						->keyText('click_cnt','点击数')
						->keyText('sell_cnt','总销量')
						->keyText('score_cnt','评分次数')
						->keyText('score_total','总评分');
				}

				$builder->keyDoAction('admin/wshop/product/action/add/id/###','基本')
					->keyDoAction('admin/wshop/product/action/sku_table/id/###','规格')
//					->keyDoAction('admin/shop/product/action/exi/id/###','商品参数')
					->data($product['list'])
					->pagination($totalCount, $option['r'])
					->display();
			break;
		}
	}

	/*
	 *  订单相关
	 */
	public function order($action= '')
	{
		switch($action)
		{
			case 'delete':
				$ids = I('ids');
				$ret = $this->order_logic->delete_order($ids);
				if($ret)
				{
					$this->success('删除成功');
				}
				else
				{
					$this->error('删除失败，'.$this->order_logic->error_str,'',3);
				}
			break;
			case 'order_delivery':
				if(IS_POST)
				{
					$id = I('id');
					empty($id) && $this->error('信息错误',1);
					$courier_no = I('courier_no');
					$courier_name = I('courier_name');
					$courier_phone = I('courier_phone','','intval');
					$delivery_info = array(
						'courier_no'=>$courier_no,
						'courier_name'=>$courier_name,
						'courier_phone'=>$courier_phone,
					);
					$order['delivery_info'] = json_encode($delivery_info);
					$order['id'] = $id;
					$ret = $this->order_model->add_or_edit_order($order);
					if($ret)
					{
						$this->success('操作成功');
					}
					else{
						$this->error('操作失败','',3);
					}
				}
				else{
					$id = I('id');
					$order = $this->order_model->get_order_by_id($id);
					$delivery_info = json_decode($order['delivery_info'],true);
					//				var_dump(__file__.' line:'.__line__,$order);exit;
					$delivery_info['id'] = $order['id'];
					$order['send_time'] = (empty($order['send_time'])?'未发货':date('Y-m-d H:i:s',$order['send_time']));
					$order['recv_time'] = (empty($order['recv_time'])?'未收货':date('Y-m-d H:i:s',$order['recv_time']));

					$delivery_info['send_time'] = $order['send_time'];
					$delivery_info['recv_time'] = $order['recv_time'];
					$builder       = new AdminConfigBuilder();
					$builder
						->title('发货信息')
						->suggest('发货信息')
						->keyReadOnly('id','订单id')
						->keyText('courier_no','快递单号')
						->keyText('courier_name','快递员姓名')
						->keyText('courier_phone','快递员电话')
						->keyText('send_time','发货时间')
						->keyText('recv_time','收货时间')
						->buttonSubmit(U('wshop/order',array('action'=>'order_delivery')),'修改')
						->buttonBack()
						->data($delivery_info)
						->display();
				}
				break;
			case 'order_address':
				$id = I('id');
				$order = $this->order_model->get_order_by_id($id);
				$address = is_array($order['address'])?$order['address']:json_decode($order['address'],true);
				$info  = is_array($order['info'])?$order['info']:json_decode($order['info'],true);

				foreach($info as $ik=>$iv)
				{
					$infos['info_'.$ik] = $iv;
				}

				$builder       = new AdminConfigBuilder();
				$builder
					->title('地址等信息')
					->keyReadOnly('id','订单id')
					->keyJoin('user_id','用户','uid','nickname','member','/admin/user/index')
					->keyText('name','姓名')
					->keyText('phone','手机')
					->keyMultiInput('province|city|town','地址','省|市|区',array(
						array('type'=>'text','style'=>'width:95px;margin-right:5px'),
						array('type'=>'text','style'=>'width:95px;margin-right:5px'),
						array('type'=>'text','style'=>'width:95px;margin-right:5px'),
					))
					->keyText('address','详细地址')
					->keyText('info_remark','备注')
					->keyText('info_fapiao','发票抬头');
				//其他信息 滚出
				foreach($infos as $ik=>$iv)
				{
					if(in_array($ik,array('info_remark','info_fapiao')))
						continue;
					$builder->keyText($ik,$ik);
				}
				$address = is_array($address)?$address:array();
				$builder
					->buttonBack()
					->data(array_merge($address,$infos))
					->display();
				break;
			case 'order_detail':
				$id = I('id');
				$order = $this->order_model->get_order_by_id($id);
				$order['create_time'] =(empty($order['create_time'])?'':date('Y-m-d H:i:s',$order['create_time']));
				$order['paid_time'] =(empty($order['paid_time'])?'未支付':date('Y-m-d H:i:s',$order['paid_time']));
				$order['send_time'] = (empty($order['send_time'])?'未发货':date('Y-m-d H:i:s',$order['send_time']));
				$order['recv_time'] = (empty($order['recv_time'])?'未收货':date('Y-m-d H:i:s',$order['recv_time']));
				$builder       = new AdminConfigBuilder();
//				var_dump(__file__.' line:'.__line__,$order );exit;
				$builder
					->title('订单详情')
					->keyReadOnly('id','订单id')
//					->keytext('')
//					->keyText('use_point','使用积分')
//					->keyText('back_point','返回积分')
					->keytext('create_time','创建时间')
					;
//				$product_input_list = array(
//					'title'=>array('name'=>'商品名','type'=>'keytext'),
//					'quantity'=>array('name'=>'数量','type'=>'keytext'),
//					'paid_price'=>array('name'=>'价格','type'=>'keytext'),
//					'sku_id'=>array('name'=>'其他信息','type'=>'keytext'),
//					'main_img'=>array('name'=>'商品主图','type'=>'keySingleImage'));
				$product_input_list = array(
					'title'=>array('name'=>'商品名','type'=>'text'),
					'quantity'=>array('name'=>'数量','type'=>'text'),
					'paid_price'=>array('name'=>'价格/分','type'=>'text'),
					'sku_id'=>array('name'=>'其他信息','type'=>'text'),
//					'main_img'=>array('name'=>'商品主图','type'=>'SingleImage')
				);
				if(!empty($order['products']))
				{
					foreach($order['products'] as $pk=> $product)
					{
						$MultiInput_name='|';
						foreach($product_input_list as $k=>$kv)
						{
							$name = 'porduct'.$pk.$k;
							if($k == 'sku_id'){
								if($product['sku_id'] = explode(';',$product['sku_id']))
								{
									unset($product['sku_id'][0]);
									$order[$name] =(empty($product['sku_id'])?'无':implode(',',$product['sku_id']));
								}
							}else{
								$order[$name] = $product[$k];
							}
							$order[$name.'title'] = $kv['name'];
//							$builder->$kv['type']($name,$kv['name']);
							$MultiInput_name .= $name.'title'.'|'.$name.'|';
							$MultiInput_array[] =array('type'=>$kv['type'],'style'=>'width:95px;margin-right:5px') ;
							$MultiInput_array[] =array('type'=>$kv['type'],'style'=>'width:295px;margin-right:5px') ;
						}
						$builder->keyMultiInput(trim($MultiInput_name,'|'),'商品['.($pk+1).']信息','',$MultiInput_array);

					}
				}
//				var_dump(__file__.' line:'.__line__,$order);exit;
				$builder
					->keytext('paid_time','支付时间')
					->keyMultiInput('paid_fee|discount_fee|delivery_fee','支付信息(单位：分)','支付金额|优惠金额|运费',array(
						array('type'=>'text','style'=>'width:95px;margin-right:5px'),
						array('type'=>'text','style'=>'width:95px;margin-right:5px'),
						array('type'=>'text','style'=>'width:95px;margin-right:5px'),
					))
					->keyText('send_time','发货时间')
					->keyText('recv_time','收货时间')
					->buttonBack()
					->data($order)
					->display();
			break;
			case 'edit_order_modal':
				if(IS_POST)
				{
					$order_id = I('order_id','','intval');
					$status = I('status','','intval');
					$order = $this->order_model->get_order_by_id($order_id);
					if(empty($order_id) || empty($status) || !($order))
					{
						$this->error('参数错误');
					}
					else
					{
						switch ($status)
						{
							case '1':
								//取消订单
								$ret = $this->order_logic->cancal_order($order);
								if($ret)
								{
									$this->success('操作成功');
								}
								else
								{
									$this->error('操作失败,'.$this->order_logic->error_str);
								}
								break;
							case '2':
								//发货
								$courier_no = I('courier_no');
								$courier_name = I('courier_name');
								$courier_phone = I('courier_phone','','intval');
								$delivery_info = array(
									'courier_no'=>$courier_no,
									'courier_name'=>$courier_name,
									'courier_phone'=>$courier_phone,
								);
								$ret = $this->order_logic->send_good($order,$delivery_info);
								if($ret)
								{
									$this->success('操作成功');
								}
								else
								{
									$this->error('操作失败,'.$this->order_logic->error_str);
								}
								break;
							case '3':
								//确认收货
								$ret = $this->order_logic->recv_goods($order);
								if($ret)
								{
									$this->success('操作成功');
								}
								else
								{
									$this->error('操作失败,'.$this->order_logic->error_str);
								}
								break;
							case '8':
								//拒绝退款
								$refund_reason = I('refund_reason','');
								$this->error('暂不支持该操作,'.$this->order_logic->error_str);
								break;
							case '10':
								//删除订单
								$ret = $this->order_logic->delete_order($order['id']);
								if($ret)
								{
									$this->success('操作成功');
								}
								else
								{
									$this->error('操作失败,'.$this->order_logic->error_str);
								}
								break;
						}

					}
				}
				else{
					$id = I('id');                        //获取点击的ids
					$order = $this->order_model->get_order_by_id($id);
					$this->assign('order', $order);
					$this->display('Wshop@Admin/edit_order_modal');
				}


				break;
			default:
				$option['page'] = I('page',1);
				$option['r'] = I('r',20);
				$option['user_id'] = I('user_id');
				$option['status'] = I('status');
				$option['key'] = I('key');
				$option['ids'] = I('id');
				empty($option['ids']) || $option['ids'] = array($option['ids']);
				$option['show_type'] = I('show_type','','intval');
				$order = $this->order_model->get_order_list($option);

				foreach($order['list'] as &$val){
					$val['paid_fee']='¥ '.sprintf("%01.2f", $val['paid_fee']/100);
				}
				//dump($order);exit;

				$status_select = $this->order_model->get_order_status_config_select();
				$status_select2 = $this->order_model->get_order_status_list_select();
				$show_type_array = array(array('id'=>0,'value'=>'订单信息'),array('id'=>1,'value'=>'订单状态'));
				$totalCount = $order['count'];
				$builder = new AdminListBuilder();
				$builder
					->title('订单管理')
					->setSearchPostUrl(U('wshop/order'))
					->search('', 'id', 'text', '订单id', '', '', '')
					->search('', 'key', 'text', '商品名', '', '', '')
					->select('订单状态：', 'status', 'select', '', '', '', $status_select2)
					->select('显示模式:', 'show_type', 'select', '', '', '', $show_type_array)
					->buttonNew(U('wshop/order'), '全部订单')
					->keyText('id','订单id')
					->keyJoin('user_id','用户','uid','nickname','member','/admin/user/index');
//					->ajaxButton(U('shop/order',array('action'=>'delete')),'','删除')
				$option['show_type'] && $builder
					->keyTime('create_time','下单时间')
					->keyTime('paid_time','支付时间')
					->keyTime('send_time','发货时间')
					->keyTime('recv_time','收货时间');

				$option['show_type'] || $builder
					->keyMap('status','订单状态',$status_select)
					->keyText('paid_fee','总价/元')
					->keyText('discount_fee','已优惠的价格')
					->keyText('delivery_fee','邮费')
					->keyText('product_cnt','商品种数')
					->keyText('product_quantity','商品总数');

				$builder->keyDoAction('admin/wshop/order/action/order_detail/id/###','订单详情')
					->keyDoAction('admin/wshop/order/action/order_address/id/###','地址等信息')
					->keyDoAction('admin/wshop/order/action/order_delivery/id/###','发货信息')
					->keyDoActionModalPopup('admin/wshop/order/action/edit_order_modal/id/###','订单操作');
				$builder
					->data($order['list'])
					->pagination($totalCount, $option['r'])
					->display();
			break;
		}

	}

	/*
	 * 运费模板
	 */
	public function delivery($action = '')
	{
		//计件数据结构
		$de_array = array(
			"express"=>array(
				"name"=>"普通快递",
				"normal"=>array(
						"start"=>1,
						"start_fee"=>10,
						"add"=>1,
						"add_fee"=>8
						),
				"custom"=>array(
					array(
						"area"=>array(
								1000,
								2000
								),
						"cost"=>array(
								"start"=>1,
								"start_fee"=>10,
								"add"=>1,
								"add_fee"=>8
								)
					),
					array(
						"area"=>array(
							1000,
							2000
								),
						"cost"=>array(
								"start"=>1,
								"start_fee"=>10,
								"add"=>1,
								"add_fee"=>8
								)
					)
				)
			)
		);
		//固定运费数据结构
		$normal_array = array(
						"express"=>array(
								"name"=>"普通快递",
								"cost"=>10	
						)
		);
		//$json = json_encode($de_array);
		//echo $json;exit;
		switch($action)
		{
			case 'add':
				if(IS_POST)
				{
					$delivery = $this->delivery_model->create();
					if (!$delivery){

						$this->error($this->delivery_model->getError());
					}
					isset($rule) && $delivery['rule'] =json_encode($rule);
					$ret = $this->delivery_model->add_or_edit_delivery($delivery);
					if ($ret){
						$this->success('操作成功。', U('wshop/delivery'),1);
					}else{
						$this->error('操作失败。');
					}
				}else{
					$id = I('get.id',0,'intval');
					if(!empty($id)){
						$delivery = $this->delivery_model->get_delivery_by_id($id);
					}else{
						$delivery = array();
					}
					//获取中国省份列表
					$district = $this->District(1);

					//dump($district);exit;
					$this->setTitle('运费模板编辑');
					$this->assign('district',$district);
					$this->assign('delivery',$delivery);
					$this->display('Wshop@Admin/adddelivery');exit;
				}
				break;
			case 'delete':
				$ids = I('ids');
				$ret = $this->delivery_model->delete_delivery($ids);
				if ($ret)
				{

					$this->success('操作成功。', U('wshop/delivery'));
				}
				else
				{
					$this->error('操作失败。');
				}
				break;
			default:
				$option['page'] = I('page',1);
				$option['r'] = I('r',10);
				$delivery = $this->delivery_model->get_delivery_list($option);
				$totalCount = $delivery['count'];

				$builder = new AdminListBuilder();
				$builder
					->title('运费模板管理')
					->buttonnew(U('Wshop/Delivery',array('action'=>'add')),'新增运费模板')
					->ajaxButton(U('Wshop/Delivery',array('action'=>'delete')),'','删除')
					->keyText('id','id')
					->keyText('title','标题')
					->keyText('brief','模板说明')
//					->keyMap('valuation','计费方式',array())
					->keyTime('create_time','创建时间')
					->keyDoAction('admin/wshop/delivery/action/add/id/###','编辑')
					->data($delivery['list'])
					->pagination($totalCount, $option['r'])
					->display();
				break;
		}
	}
	/*
	 * 优惠券
	 */
	public function coupon($action = '')
	{
		switch($action)
		{
			case 'add':
				if(IS_POST){
					$coupon = $this->coupon_model->create();
					if(!$coupon){
						$this->error($this->coupon_model->getError());
					}
					empty($_REQUEST['max_cnt_enable']) || $rule['max_cnt'] =I('max_cnt',0,'intval');
					empty($_REQUEST['max_cnt_day_enable']) || $rule['max_cnt_day'] =I('max_cnt_day',0,'intval');
					empty($_REQUEST['min_price_enable']) || $rule['min_price'] =I('min_price',0,'intval');
					if(empty($_REQUEST['discount'])){
						$this->error('请设置优惠金额');
					}else{
						$rule['discount'] =I('discount',0,'intval');
					}
					empty($rule) || $coupon['rule'] = json_encode($rule);

					$ret = $this->coupon_model->add_or_edit_coupon($coupon);
					if ($ret){
						$this->success('操作成功。', U('wshop/coupon'));
					}else{
						$this->error('操作失败。');
					}
				}else{
					$id = I('id');
					if(!empty($id)){
						$coupon = $this->coupon_model->get_coupon_by_id($id);
						if(!empty($coupon['rule'])){
							$coupon['rule']['max_cnt_enable'] = (empty($coupon['rule']['max_cnt'])?0:1);
							$coupon['rule']['max_cnt_day_enable'] = (empty($coupon['rule']['max_cnt_day'])?0:1);
							$coupon['rule']['min_price_enable'] = (empty($coupon['rule']['min_price'])?0:1);
							$coupon = array_merge($coupon,$coupon['rule']);
						}
					}else{
						$coupon =array();
					}
					
					$builder       = new AdminConfigBuilder();
					$builder->title('优惠券详情')
						->keyId()
						->keytext('title','优惠券名称')
						->keySingleImage('img','优惠券图片')
						->keyInteger('publish_cnt','总发放数量')
						->keyInteger('discount','优惠金额','单位：分')
						->keySelect('duration','有效期','',array('0'=>'永久有效','86400'=>'一天内有效','604800'=>'一周内有效','2592000'=>'一月内有效'))
						->keyMultiInput('max_cnt_enable|max_cnt','领取限制','每个用户最多允许领取多少张',array(array('type'=>'select','opt'=>array('不限制','限制'),'style'=>'width:95px;margin-right:5px'),array('type'=>'text','style'=>'width:95px;margin-right:5px')))
						->keyMultiInput('max_cnt_day_enable|max_cnt_day','领取限制','每个用户每天最多允许领取多少张',array(array('type'=>'select','opt'=>array('不限制','限制'),'style'=>'width:95px;margin-right:5px'),array('type'=>'text','style'=>'width:95px;margin-right:5px')))
						->keyMultiInput('min_price_enable|min_price','使用限制','最低可以使用的价格（单位：分），即满多少可用',array(array('type'=>'select','opt'=>array('不限制','限制'),'style'=>'width:95px;margin-right:5px'),array('type'=>'text','style'=>'width:95px;margin-right:5px')))
						->keySelect('valuation','类型','',array('现金券','折扣券'))
						->keyEditor('brief','优惠券说明')
						->keyCreateTime()
						->data($coupon)
						->buttonSubmit(U('wshop/coupon',array('action'=>'add')))
						->buttonBack()
						->display();
				}
				break;
			case 'delete':
				$ids= I('ids');
				$ret = $this->coupon_model->delete_coupon($ids);
				if ($ret)
				{
					$this->success('操作成功。', U('wshop/coupon'));
				}
				else
				{
					$this->error('操作失败。');
				}
				break;
			case 'couponlink':
				$id = I('id');
				$id = $this->coupon_model->encrypt_id($id);
				redirect(U('Udriver/index/get_coupon',array('id'=>$id)));//优惠券id 加密 跳转 具体链接 依业务需求修改
				break;
			default:
				$option['page'] = I('page',1);
				$option['r'] = I('r',10);
				$option['id'] = I('id');
				$coupon = $this->coupon_model->get_coupon_lsit($option);
//				empty($coupon['list'])
//					||
//				array_walk($coupon['list'],
//					function(&$a){
////						$a['link'] = think_encrypt($a['id'],'Coupon',0);
//						$a['link'] = \Think\Crypt\Driver\Des::encrypt($a['id'],md5('Coupon'),0);
//
//						$a['link'] = urlencode(base64_encode($a['link']));
////						var_dump(__file__.' line:'.__line__,$a['link']);exit;
//					});
				$totalCount = $coupon['count'];
				$builder = new AdminListBuilder();
				$builder
					->title('优惠券')
					->buttonnew(U('wshop/coupon',array('action'=>'add')),'新增优惠券')
					->ajaxButton(U('wshop/coupon',array('action'=>'delete')),'','删除')
					->keyText('id','优惠券id')
					->keyText('title','优惠券名称')
					->keyImage('img','优惠券图片')
					->keyMap('valuation','类型',array('现金券','折扣券'))
					->keyTruncText('brief','优惠券说明','25')
					->keyText('used_cnt','已发放数量')
					->keyText('publish_cnt','总发放数量')
					->keyTime('create_time','创建时间')
					->keyLinkByFlag('','领取链接','/wshop/coupon/get_coupon/coupon_id/###','id')
					->keyMap('duration','有效期',array('0'=>'永久有效','86400'=>'一天内有效','604800'=>'一周内有效','2592000'=>'一月内有效'))
					->keyDoAction('admin/wshop/coupon/action/add/id/###','查看和编辑')
					->data($coupon['list'])
					->pagination($totalCount, $option['r'])
					->display();
				break;
		}
	}

	/*
	 * 优惠券领取情况
	 */
	public function user_coupon($action = '')
	{
		switch($action)
		{

			case 'add':
				//派优惠券
				if(IS_POST){
					$coupon_id       = I('coupon_id', '', 'intval');
					$uid     = I('uid', '', 'trim');
					if(empty($coupon_id) || !($coupon = $this->coupon_model->get_coupon_by_id($coupon_id)))
						$this->error('请选择一个优惠券');
					if(empty($uid)) $this->error('请选择一个用户');
					$ret =$this->coupon_logic->add_a_coupon_to_user($coupon_id,$uid);
					if($ret){
						$this->success('操作成功。', U('wshop/user_coupon'));
					}else{
						$this->error('操作失败。'.$this->coupon_logic->error_str);
					}
				}else{
					$all_coupon_select = $this->coupon_model->getfield('id,title');
					if(empty($all_coupon_select)){
						redirect(U('wshop/coupon',array('action'=>'add')));
					}
					$builder = new AdminConfigBuilder();
					$builder
						->title('手动发放优惠券')
						->keySelect('coupon_id','优惠券','要发放的优惠券',$all_coupon_select)
						->keyInteger('uid','用户id','')

						->buttonSubmit(U('wshop/user_coupon',array('action'=>'add')))
						->buttonBack()
						->display();
				}
				break;
			case 'delete':
				$ids= I('ids');
				$ret = $this->user_coupon_model->delete_user_coupon($ids);
				if ($ret)
				{
					$this->success('操作成功。', U('wshop/user_coupon'));
				}
				else
				{
					$this->error('操作失败。');
				}
				break;
			default:
				$option['id'] = I('id');
				$option['page'] = I('page',1);
				$option['r'] = I('r',10);
				$user_coupon = $this->user_coupon_model->get_user_coupon_list($option);

				empty($user_coupon['list']) ||
				array_walk($user_coupon['list'],
					function(&$a){
						$a['coupon_title'] = (empty($a['info']['title'])?'':$a['info']['title']);
						$a['coupon_img'] = (empty($a['info']['img'])?'':$a['info']['img']);
						$a['coupon_valuation'] = (empty($a['info']['valuation'])?'':$a['info']['valuation']);
						$a['coupon_discount'] = (empty($a['info']['rule']['discount'])?'':$a['info']['rule']['discount']);
						$a['coupon_min_price'] = (empty($a['info']['rule']['min_price'])?'':$a['info']['rule']['min_price']);
					});
				$totalCount = $user_coupon['count'];

				$builder = new AdminListBuilder();
				$builder
					->title('已领取优惠券')
					->buttonnew(U('wshop/user_coupon',array('action'=>'add')),'派发优惠券')
					->ajaxButton(U('wshop/user_coupon',array('action'=>'delete')),'','删除')
					->keyId()
					->keyUid('user_id')
					->keyLinkByFlag('coupon_title','优惠券','admin/wshop/coupon/id/###','coupon_id')
					->keyImage('coupon_img','优惠券图片')
					->keytext('coupon_discount','折扣,单位:分')
					->keytext('coupon_min_price','满多少可用,单位:分')
					->keyTime('create_time','发放时间')
					->keyTime('expire_time','到期时间')
					->keyLinkByFlag('order_id','订单号（无）','admin/shop/order/key/###','order_id')
					->keyMap('status','状态',array('0'=>'未使用','1'=>'已使用','2'=>'已过期'))

					->data($user_coupon['list'])
					->pagination($totalCount, $option['r'])
					->display();
				break;
		}
	}


	/*
	 *商品评论
	 */
	public function product_comment($action ='')
	{
		switch($action)
		{
			case 'edit_status':
				if(IS_POST)
				{
					$ids  =  I('ids');
					$status  =  I('get.status','','/[012]/');
					if(empty($ids) || empty($status))
					{
						$this->error('参数错误');
					}
					$ret = $this->product_comment_model->edit_status_product_comment($ids,$status);
					if($ret)
					{
						$this->success('操作成功');
					}
					else
					{
						$this->error('操作失败');
					}
				}
				break;
			case 'show_pic':
				$id = I('id','','intval');
				$ret = $this->product_comment_model->find($id);
				$this->assign('product_comment',$ret);
//				var_dump(__file__.' line:'.__line__,$ret);exit;
				$this->display('Wshop@Admin/show_pic');
				break;
			default:
				$option['page'] = I('page','1','intval');
				$option['r'] = I('r','10','intval');
				$product_comment  = $this->product_comment_model->get_product_comment_list($option);
				$builder = new AdminListBuilder();
				$builder
					->title('商品评论管理')
					->ajaxButton(U('shop/product_comment',array('action'=>'edit_status','status'=>1)),'','审核通过')
					->ajaxButton(U('shop/product_comment',array('action'=>'edit_status','status'=>2)),'','审核不通过')
					->keyId()
					->keyJoin('product_id','商品','id','title','shop_product','/admin/shop/product')
					->keyJoin('order_id','订单','id','id','shop_order','/admin/shop/order')
					->keyJoin('user_id','用户','uid','nickname','member','/admin/user/index')
					->keyText('score','星数')
					->keyText('brief','评论内容')
					->keyTime('create_time','评论时间')
					->keyMap('status','状态',array('0'=>'未审核','1'=>'已通过','2'=>'未通过'))
//					->keyDoActionModalPopup('admin/shop/product_comment/action/show_pic/id/###','查看评论图片','操作')
					->data($product_comment['list'])
					->pagination($product_comment['count'], $option['r'])
					->display();
				break;
		}

	}
	/*
	获取中国省份、城市
	 */
	private function District($level=1){
			$map['level'] = $level;
			$map['upid'] = 0;
			$list = D('Addons://ChinaCity/District')->_list($map);
			return $list;
	}

}
