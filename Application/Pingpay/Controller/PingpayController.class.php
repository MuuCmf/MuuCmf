<?php
/**
ping++管理
 */

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;


class PingPayController extends AdminController
{
    protected $pingpayModel;
    protected $pingpayOrderModel;

    function _initialize()
    {
        $this->pingpayModel = D('Pingpay/Pingpay');
        $this->pingpayOrderModel = D('Pingpay/PingpayOrder');
        parent::_initialize();
    }
    function is_HTTPS(){  
        if(!isset($_SERVER['HTTPS']))  return 'http://'; 
        if($_SERVER['HTTPS'] === 1){  //Apache  
            return 'https://';
        }elseif($_SERVER['HTTPS'] === 'on'){ //IIS  
            return 'https://';
        }elseif($_SERVER['SERVER_PORT'] == 443){ //其他  
            return 'https://';
        }
        return 'http://';
    }
    public function config()
    {
        $admin_config = new AdminConfigBuilder();
        $data = $admin_config->handleConfig();
        $data['PINGPAY_CONFIG_WEBHOOKS'] = $this->is_HTTPS().$_SERVER['SERVER_NAME'].'/pingpay/webhooks';

        $score_list = D('Ucenter/Score')->getTypeList(array('status' => 1));
        $score_type=array();
        foreach($score_list as $val){
            $score_type=array_merge($score_type,array('score'.$val['id']=>$val['title']));
        }
        //dump($score_type);exit;

        $admin_config
            ->title('Ping++支付中心基本设置')
            //ping++配置
            ->keyText('PINGPAY_CONFIG_APIKEY','api_key','登录(https://dashboard.pingxx.com)->点击管理平台右上角公司名称->开发信息-> Secret Key')
            ->keyText('PINGPAY_CONFIG_APPID','app_id','登录(https://dashboard.pingxx.com)->点击你创建的应用->应用首页->应用 ID(App ID)')
            ->keyTextArea('PINGPAY_CONFIG_PUBLICKEY','ping++公钥','')
            ->keyText('PINGPAY_CONFIG_PUBLISHABLEKEY','Publishable Key','Ping++ 应用内快捷支付 Key')
            ->keyTextArea('PINGPAY_CONFIG_PRIVATEKEY','RSA 商户私钥','如：your_rsa_private_key.pem')
            ->keyReadOnlyText('PINGPAY_CONFIG_WEBHOOKS','webhooks回调地址')
            //充值设置
            ->keyRadio('PINGPAY_CONFIG_OPEN','是否开通充值功能','',array('1'=>'是','0'=>'否'))
            ->keyText('PINGPAY_CONFIG_RESULTURL','充值成功后的返回地址','支付成功页返回地址（通常设置为订单列表）')
            ->keyCheckBox('PINGPAY_CONFIG_SCORE','允许充值的积分类型','',$score_type)
            ->keyText('PINGPAY_CONFIG_MINMONEY','最小充值金额','请填写允许在线充值的最小额度，单位（元）,默认或0为不限制')
            ->keyText('PINGPAY_CONFIG_ORDERPX','商户订单前缀','数字或字符')
            //提现设置
            ->keyRadio('PINGPAY_CONFIG_TOPEN','是否开通提现功能','',array('1'=>'是','0'=>'否'))
            ->keyText('PINGPAY_CONFIG_TMINMONEY','最小提现金额','请填写允许提现的最小额度，单位（元）,默认或0为不限制')


            ->group('ping++ 接口设置','PINGPAY_CONFIG_APIKEY,PINGPAY_CONFIG_APPID,PINGPAY_CONFIG_PUBLICKEY,PINGPAY_CONFIG_PUBLISHABLEKEY,PINGPAY_CONFIG_PRIVATEKEY,PINGPAY_CONFIG_WEBHOOKS')
            ->group('充值设置','PINGPAY_CONFIG_OPEN,PINGPAY_CONFIG_RESULTURL,PINGPAY_CONFIG_SCORE,PINGPAY_CONFIG_MINMONEY,PINGPAY_CONFIG_ORDERPX')
            ->group('提现设置','PINGPAY_CONFIG_TOPEN,PINGPAY_CONFIG_TMINMONEY')
            ->group('第三方模块支付配置','')

            ->buttonSubmit('', '保存')
            ->data($data);
        $admin_config->display();
    }

    //支付渠道参数设置
    public function channelConfig()
    {
        //配置文件路径
        $path = APP_PATH  . 'Pingpay/Conf/channel.php';
        $channel = load_config($path);
        foreach($channel as &$val){
            foreach($val['extra'] as $k=>$value){
                $val['extrastr'].=$k.':'.$value.'<br/>';
            }
        }
        
        //dump($channel);exit;
        $builder=new AdminListBuilder();
        $builder->title('支付渠道参数')
                ->keyText('name','channel')
                ->keyText('title','支付渠道')
                ->keyText('icon','图标')
                ->keyText('extrastr','extra')
                ->data($channel)
                ->display();
    }

    public function index($page=1,$r=20,$uid='',$order_no='')
    {
        if ($uid != '') {
            $map['uid'] = $uid;
        }
        if ($order_no != '') {
            $map['order_no'] = array('like', '%' . $order_no . '%');
        }
        list($list,$totalCount) = $this->pingpayOrderModel->getListByPage($map,$page,'created desc','*',$r);
        foreach($list as &$val){
            if($val['paid']==1){
                $val['paid']='已付款';
            }else{
                $val['paid']='未付款';
            }
            $val['amount']='￥'.sprintf("%.2f",$val['amount']/100);
        }
        unset($val);
        

        $builder=new AdminListBuilder();
        $builder->title('订单列表')
        ->data($list)
        ->keyId('id')
        ->keyUid('uid')
        ->keyText('ch_id','PingId')
        ->keyText('order_no','商户订单号')
        ->keyText('subject','商品名')
        ->keyText('amount','金额(单位：元)')
        ->keyText('channel','支付渠道')
        ->keyText('paid','状态')
        ->keyCreateTime('created','订单创建时间')
        ->keyCreateTime('time_paid','订单支付时间')
        ->setSearchPostUrl(U('Admin/Pingpay/index'))->search('UID','uid')->search('订单号', 'order_no');
        $builder->pagination($totalCount,$r);
        $builder->display();
    }
    
}
