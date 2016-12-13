<?php
namespace Pingpay\Controller;

use Think\Controller;

class BaseController extends Controller
{
    protected $api_key;
    protected $app_id;
    protected $public_key;
    protected $rsa_key;
    protected $open_recharge;
    protected $open_withdraw;
    public function _initialize()
    {
        //引入类库
        import('Pingpay.PingSDK.init',APP_PATH,'.php');
        $this->api_key=modC('PINGPAY_CONFIG_APIKEY','','Pingpay');
        $this->app_id=modC('PINGPAY_CONFIG_APPID','','Pingpay');
        $this->public_key=modC('PINGPAY_CONFIG_PUBLICKEY','','Pingpay');
        $this->rsa_key=modC('PINGPAY_CONFIG_PRIVATEKEY','','Pingpay');

        $this->open_recharge=modC('PINGPAY_CONFIG_OPEN','','Pingpay');//是否开通充值
        $this->open_withdraw=modC('PINGPAY_CONFIG_TOPEN','','Pingpay');//是否开通提现
        $userMenuList=array();
        if(is_login()){
            $userMenuList[]=array('tab'=>'recharge', 'icon'=>'<i class="icon-yen"></i>', 'title'=>'账号充值', 'href'=>U('Index/recharge'));
            $userMenuList[]=array('tab'=>'order', 'icon'=>'<i class="icon-columns"></i>', 'title'=>'订单列表', 'href'=>U('Order/index'));
            if($this->open_withdraw){
                $userMenuList[]=array('tab'=>'withdraw', 'icon'=>'<i class="icon-credit"></i>', 'title'=>'申请提现', 'href'=>U('Index/withdraw'));
                $userMenuList[]=array('tab'=>'withdrawlist', 'icon'=>'<i class="icon-calculator"></i>', 'title'=>'提现记录', 'href'=>U(''));
            }
            
            $userMenuList[]=array('tab'=>'devauth', 'icon'=>'<i class="icon-paste"></i>', 'title' =>'转入余额', 'href'=>U(''));
        }
        $this->assign('userMenu',$userMenuList);
    }
}