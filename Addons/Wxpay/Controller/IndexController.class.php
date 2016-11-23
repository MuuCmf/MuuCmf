<?php

namespace Addons\Wxpay\Controller;
use Home\Controller\AddonsController;
use Common\Model\UcuserModel;
use Com\Wxpay\example\JsApiPay;
use Com\Wxpay\lib\WxPayApi;
use Com\Wxpay\lib\WxPayConfig;
use Com\Wxpay\lib\WxPayUnifiedOrder;
use Addons\Wxpay\Controller\PayNotifyCallBackController;
use Com\TPWechat;


class IndexController extends AddonsController{

    public $options;    //使用微信支付的Controller最好有一个统一的微信支付配置参数
    public $wxpaycfg;
    public function index($mp_id = 0){
        $params['mp_id'] = get_mpid();   //系统中公众号ID
        $this->assign ( 'mp_id', $params['mp_id'] );
        $this->display ( );
    }

    /**
     *
     * jsApi微信支付示例
     * 注意：
     * 1、微信支付授权目录配置如下  http://test.uctoo.com/addon/Wxpay/Index/jsApiPay/mp_id/
     * 2、支付页面地址需带mp_id参数
     * 3、管理后台-基础设置-公众号管理，微信支付必须配置的参数都需填写正确
     * @param array $mp_id 公众号在系统中的ID
     * @return 将微信支付需要的参数写入支付页面，显示支付页面
     */

    public function jsApiPay($mp_id = 0){
        empty ( $mp_id ) && $mp_id = get_mpid ();
        $params['mp_id'] = $mp_id;   //系统中公众号ID
        $this->assign ( 'mp_id', $params['mp_id'] );

        $order_total_price = I('order_total_price', 1, 'intval');
        $addon = I('addon', '', 'op_t');

        $couponArray = array();
        for($i = 0; $i <= 32; $i++) {         //收集优惠券code参数，用于后续orderpaided核销优惠券
            $coupon_id_[$i] = I('coupon_id_'.$i, '', 'op_t');
            $coupon_fee_[$i] = I('coupon_fee_'.$i, '', 'op_t');
            $couponArray["coupon_id_".$i] = I('coupon_id_'.$i, '', 'op_t');
            $couponArray["coupon_fee_".$i] = I('coupon_fee_'.$i, '', 'op_t');
        }
        $couponArray["coupon_fee"] = I('coupon_fee', '', 'op_t');
        $couponArray["coupon_count"] = I('coupon_count', '', 'op_t');
        $couponArrayF = array_filter($couponArray);
        $coupon = json_encode($couponArrayF);
        $couponJson = I('consumeJson', '', 'op_t');
        $selected_couponsum = I('selected_couponsum', '', 'op_t');


        $mid = get_ucuser_mid();                         //获取粉丝用户mid，一个神奇的函数，没初始化过就初始化一个粉丝
        if($mid === false){
            $this->error('只可在微信中访问');
        }
        $user = get_mid_ucuser($mid);                    //获取本地存储公众号粉丝用户信息
        $this->assign('user', $user);

        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $surl = get_shareurl();
        if(!empty($surl)){
            $this->assign ( 'share_url', $surl );
        }

        //odata通用订单数据,订单数据可以从订单页面提交过来
        $odata['mid'] = $mid;
        $odata['mp_id'] = $params['mp_id'];                    // 当前公众号在系统中ID
        $odata['order_id'] = "time".date("YmdHis");   //
        $odata['order_status'] = 1;                            //不带该字段-全部状态, 2-待发货, 3-已发货, 5-已完成, 8-维权中
        $odata['order_total_price'] = $order_total_price ? $order_total_price-$selected_couponsum*100 : 1;                      //订单总价，单位：分
        $odata['buyer_openid'] = $user['openid'];
        $odata['buyer_nick'] = $user['nickname'];
        $odata['receiver_mobile'] = $user['mobile'];
        $odata['product_id'] = 1;
        $odata['product_name'] = "UCToo";
        $odata['product_price'] = $order_total_price ? $order_total_price : 1;                          //商品价格，单位：分
        $odata['product_sku'] = "UCToo_Wxpay";
        $odata['product_count'] = 1;
        $odata['module'] = MODULE_NAME;
        $odata['addon'] = $addon ? $addon : "donate";
        $odata['model'] = "order";
        $odata['aim_id'] = 1;
        $odata['coupon'] = $couponJson;
        $order = D("Order"); // 实例化order对象
        $order->create($odata); // 生成数据对象
        $result = $order->add(); // 写入数据
        if($result){
            // 如果主键是自动增长型 成功后返回值就是最新插入的值

        }

        //获取公众号信息，jsApiPay初始化参数
        $info = get_mpid_appinfo ( $odata['mp_id'] );
	    $cfg = array(
		    'APPID'     => $info['appid'],
		    'MCHID'     => $info['mchid'],
		    'KEY'       => $info['mchkey'],
		    'APPSECRET' => $info['secret'],
		    'NOTIFY_URL' => $info['notify_url'],
	    );
	    WxPayConfig::setConfig($cfg);
        //①、初始化JsApiPay
        $tools = new JsApiPay();

        //②、统一下单
        $input = new WxPayUnifiedOrder();           //这里带参数初始化了WxPayDataBase
      //  $input->SetAppid($info['appid']);//公众账号ID
      //  $input->SetMch_id($info['mchid']);//商户号
        $input->SetBody($odata['product_name']);
        $input->SetAttach($odata['product_sku']);
        $input->SetOut_trade_no($odata['order_id']);
        $input->SetTotal_fee($odata['order_total_price']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
       // $input->SetGoods_tag("WXG");                      //商品标记，代金券或立减优惠功能的参数
      //  $input->SetNotify_url($info['notify_url']);       //http://test.uctoo.com/index.php/UShop/Index/notify
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($user['openid']);
        $order = WxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($order);
//获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
        /**
         * 注意：
         * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
         * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
         * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
         */
        $this->assign ( 'order_total_price', $odata['order_total_price'] );
        $this->assign ( 'order', $odata );
        $this->assign ( 'jsApiParameters', $jsApiParameters );
        $this->assign ( 'editAddress', $editAddress );

		$this->display ( );
	}

    //支付完成接收支付服务器返回通知，PayNotifyCallBackController继承WxPayNotify处理定制业务逻辑
    public function notify(){

        $rsv_data = $GLOBALS ['HTTP_RAW_POST_DATA'];
        $result = xmlToArray($rsv_data);

        $map["appid"] = $result["appid"];
        $map["mchid"] = $result["mch_id"];

        $info = M ( 'member_public' )->where ( $map )->find ();
       //获取公众号信息，jsApiPay初始化参数
	    $cfg = array(
		    'APPID'     => $info['appid'],
		    'MCHID'     => $info['mchid'],
		    'KEY'       => $info['mchkey'],
		    'APPSECRET' => $info['secret'],
		    'NOTIFY_URL' => $info['notify_url'],
	    );
	    WxPayConfig::setConfig($cfg);

        //发送模板消息
        $param['mp_id'] = $info['id'];
        $param['template_id'] = "diW6jm5hBwemeoDF0FZdU2agSZ9kydje22YJIC0gVMo";
        $param['touser'] = $result["openid"];
        $param['product_name'] = $result['transaction_id'];
        hook('TplMsg',$param);   //把消息分发到addons/TplMsg/TplMsg的方法中,发送模板信息

        //回复公众平台支付结果
        $notify = new PayNotifyCallBackController();    //
        $notify->Handle(false);

        //处理业务逻辑

    }

    //支付成功JS回调显示支付成功页
    public function orderpaid(){

        $map['order_id'] = I('order_id');
        $order = M('order')-> where($map)->find();
        $this->assign ( 'order', $order );
        //显示支付成功结果页
        $this->display ();
    }

    //UCToo开源捐赠
    public function donate(){
        empty ( $mp_id ) && $mp_id = get_mpid ();
        $params['mp_id'] = $mp_id;   //系统中公众号ID
        $this->assign ( 'mp_id', $params['mp_id'] );

        $jsApiPay_url = addons_url('Wxpay://Index/jsApiPay', array('mp_id' => get_mpid()));
        $this->assign ( 'jsApiPay_url', $jsApiPay_url);

        $mid = get_ucuser_mid();                         //获取粉丝用户mid，一个神奇的函数，没初始化过就初始化一个粉丝
        if($mid === false){
            $this->error('只可在微信中访问');
        }
        $user = get_mid_ucuser($mid);                    //获取本地存储公众号粉丝用户信息
        $this->assign('user', $user);

        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $surl = get_shareurl();
        if(!empty($surl)){
            $this->assign ( 'share_url', $surl );
        }

        $home_url = addons_url('Ucuser://Ucuser/index', array('mp_id' => get_mpid()));
        $this->assign ( 'home_url', $home_url );

        $appinfo = get_mpid_appinfo ( $params ['mp_id'] );   //获取公众号信息
        $this->assign ( 'appinfo', $appinfo );

        $options['appid'] = $appinfo['appid'];    //初始化options信息
        $options['appsecret'] = $appinfo['secret'];
        $options['encodingaeskey'] = $appinfo['encodingaeskey'];
        $weObj = new TPWechat($options);

        $auth = $weObj->checkAuth();
        $js_ticket = $weObj->getJsTicket();
        if (!$js_ticket) {
            $this->error('获取js_ticket失败！错误码：'.$weObj->errCode.' 错误原因：'.ErrCode::getErrText($weObj->errCode));
        }
        $js_sign = $weObj->getJsSign($url);

        $this->assign ( 'js_sign', $js_sign );

        //分享数据定义
        $sharedata['title']= $user['nickname']."的爱心捐赠";
        $sharedata['desc']= "捐赠UCToo 互联网+ 开源开发框架！";
        $sharedata['link'] = addons_url('Wxpay://Index/donate', array('mp_id' => get_mpid()));
        $sharedata['imgUrl'] = $user['headimgurl'];
        $this->assign ( 'sharedata', $sharedata );

        $map['addon'] = $mp_id;
        $map['addon'] = 'donate';
        $map['trans_id'] = array('neq','');
        $donate_list = M('order')->where($map)->order('order_create_time desc')->limit(100)->select();
        $this->assign ( 'donate_list', $donate_list );

        $donate_sum = M('order')->field('mid,buyer_nick,sum(order_total_price) as total_price')->where($map)->order('sum(order_total_price) desc')->group('mid')->select();
        $this->assign ( 'donate_sum', $donate_sum );

        //微信卡券相关示例
        $cardlist = $weObj->getCardIdList();
        $cards = array();

        foreach ($cardlist['card_id_list'] as $k => $vo) {
            $cards[$k] = $weObj->getCardInfo("$vo");
        }
        if($user['login']){     //区分登录会员和未登录会员的不同产品定价
            $wxpay_sum = 1;
        }else{
            $wxpay_sum = 1;
        }

        $usercards = $weObj->getUserCardList($user['openid'],'');
        $codes = array();
        $usercardsinfo = array();
        $couponsum = 0;
        $selected_couponsum = 0;         //选中的优惠券总金额
        $canReduce = 300;               //此订单可享受的优惠上限

        $consume = array();                   //待核销的优惠券列表
        foreach ($usercards['card_list'] as $k => $vo) {
            $codes[$k] = $weObj->checkCardCode($vo['code']);
            if($codes[$k]['user_card_status'] == 'NORMAL' && $codes[$k]['can_consume'] == true){   //正常状态且可以核销的卡券
                $goodcodes[$k] = $codes[$k];
                $usercardsinfo[$k] = $weObj->getCardInfo($vo['card_id']);
                $couponsum += $usercardsinfo[$k]['card']['cash']['reduce_cost'];
                if($canReduce >= $usercardsinfo[$k]['card']['cash']['reduce_cost']){     // 可享受的优惠上限大于此张优惠券的面值
                    if($canReduce > $selected_couponsum){                                       // 存在优惠券边界值超出可享受的优惠上限的bug
                        $selected_couponsum += $usercardsinfo[$k]['card']['cash']['reduce_cost'];
                        $consume[$k] = $usercards['card_list'][$k];
                    }
                }
            }
        }

        $wxpay_sum += $selected_couponsum;

        $couponsum = $couponsum/100;  //单位元
        $canReduce = $canReduce/100;  //单位元
        $tips = "微信支付立减".$canReduce."元";

        if($canReduce > $couponsum){  //优惠券总额小于可享受的优惠上限
            $tips .= "您已领取的优惠券小于可享受的最高优惠，您可领取更多优惠券或使用现有优惠券继续支付";
        }else{                       //优惠券总额大于可享受的优惠上限，只能享受优惠上限的额度 $selected_couponsum 已算出

        }
        $this->assign ( 'selected_couponsum',$selected_couponsum/100 );
        $this->assign ( 'couponsum',$couponsum );
        $this->assign ( 'consume',$consume );
        $this->assign ( 'consumeJson',json_encode($consume) );
        $this->assign ( 'tips',$tips );
        $this->assign ( 'wxpay_sum',$wxpay_sum );


        $card_sign = $weObj->getCardSign('CASH');
        $this->assign ( 'card_sign', $card_sign );
        $cardExt = $weObj->getCardSign('','pWhGnjq33WL4mFZuNcl-uKZe1lao');
        $this->assign ( 'cardExt_timestamp', $cardExt['timestamp'] );
        $this->assign ( 'cardExt_nonceStr', $cardExt['nonceStr'] );
        $this->assign ( 'cardExt_cardSign', $cardExt['cardSign'] );

        $this->assign ( 'usercards', $usercards );

        $this->display();
    }

}