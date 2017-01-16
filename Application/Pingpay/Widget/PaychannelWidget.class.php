<?php

namespace Pingpay\Widget;
use Think\Controller;

/**
 * 支付驱动选择列表widget
 */

class PaychannelWidget extends Controller{
	
	public function render(){
		$payChannel = D('Pingpay/Pingpay')->channel();
		if(empty($payChannel)){
			echo "支付驱动调用错误！";exit;
		}
        $this->assign('payChannel',$payChannel);
        $this->display(T('Application://Pingpay@Widget/paychannel'));
	}
}