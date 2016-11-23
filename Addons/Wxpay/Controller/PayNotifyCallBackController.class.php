<?php
namespace Addons\Wxpay\Controller;

use Com\Wxpay\lib\WxPayApi;
use Com\Wxpay\lib\WxPayConfig;
use Com\Wxpay\lib\WxPayException;
use Com\Wxpay\lib\WxPayNotify;
use Com\Wxpay\lib\WxPayOrderQuery;
use Ucuser\Model\UcuserScoreModel;
use Com\TPWechat;

class PayNotifyCallBackController extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);

		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理方法，成功的时候返回true，失败返回false，处理商城订单
	public function NotifyProcess($data, &$msg)
	{

		$notfiyOutput = array();
		
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}

        //以上的代码都是相同的，以下代码写定制业务逻辑，这里应该写通用订单处理逻辑
        $transaction = M('transaction'); // 保存微信支付订单流水
        $transaction->data($data)->add();

        $omap["order_id"] = $map["out_trade_no"] = $data["out_trade_no"];
        $data["paySta"] = 1;
        M('shop_order')-> where($map)->setField($data); //商城订单支付状态置为1
        M('order')-> where($omap)->setField("trans_id",$data["transaction_id"]); //支付流水号写入订单
        $ucuser = get_ucuser_by_openid($data['openid']);
        D('Ucuser/UcuserScore')->setUserScore($ucuser['mid'],$data['total_fee'],4,'inc'); //加余额

        //核销订单中使用的优惠券，核销逻辑并未提供完整演示，聪明的开发者请自己写 ：）
        $order = M('order')-> where($omap)->find(); //通用订单
        if(empty($order['coupon'])){               //订单没有使用优惠券
            return true;
        }
        $coupon = json_decode($order['coupon']);

        $appinfo = get_mpid_appinfo ( $order ['mp_id'] );   //获取公众号信息

        $options['appid'] = $appinfo['appid'];    //初始化options信息
        $options['appsecret'] = $appinfo['secret'];
        $options['encodingaeskey'] = $appinfo['encodingaeskey'];
        $weObj = new TPWechat($options);
        $res = $weObj->consumeCardCode();
		return true;
	}
}

