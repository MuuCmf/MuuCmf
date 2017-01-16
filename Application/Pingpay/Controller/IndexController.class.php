<?php

namespace Pingpay\Controller;

use Think\Controller;

class IndexController extends BaseController
{
    protected $pingpayModel;
    protected $pingpayOrderModel;

    function _initialize()
    {
        $this->pingpayModel = D('Pingpay/Pingpay');
        $this->pingpayOrderModel = D('Pingpay/PingpayOrder');
        //需要登录
        if (!is_login()) {
            $this->error(L('_ERROR_NEED_LOGIN_'));
        }
        parent::_initialize();
        
    }
    //钱包首页
    public function index()
    {
        $uid=is_login();
        $score_list = D('Ucenter/Score')->getTypeList(array('status' => 1));
            $score_key = array();
            foreach ($score_list as $vf) {
                $score_key[] = 'score' . $vf['id'];
            }
            $score_data = D('Member')->where(array('uid' => $uid))->field(implode(',', $score_key))->find();

            foreach($score_list as &$val){
                $val['num']=$score_data['score'.$val['id']];
            }
            unset($val);
        $col_num = 12/count($score_list);
        //dump($score_list);exit;
        $this->assign('col_num',$col_num);
        $this->assign('score',$score_list);
        $this->display();
    }
    /**
     * 充值页
     * @return [type] [description]
     */
    public function recharge()
    {   
        if(!$this->open_recharge){
            $this->error('系统未开通充值功能');
        }

        if(IS_POST){
            $order_px = modC('PINGPAY_CONFIG_ORDERPX','','Pingpay');//订单前缀，webhooks将根据订单前缀判断订单类型
            // 支付参数
            $data['order_no'] = $order_px.substr(md5(time()), 0, 12);//商户订单号,推荐使用 8-20 位，要求数字或字母，不允许其他字符
            $data['uid'] = $uid = is_login();
            $data['subject'] = I('post.subject','','op_t');
            $data['body'] = I('post.body','','op_t');
            $data['channel'] = I('post.channel','','text'); //订单支付渠道
            $data['amount'] = I('post.amount','','floatval'); //订单金额，单位：元
            $data['amount'] = sprintf("%.2f",$data['amount'])*100;//将金额单位转成分
            $data['quantity'] =I('post.quantity',0,'intval');//将金额单位转成分
            $data['metadata'] = I('post.metadata','','text');//订单元数据，要求JSON字符串
            $data['description'] = I('post.description','','text');//订单附加说明
            $data['client_ip'] = $_SERVER['REMOTE_ADDR']; // 发起支付请求客户端的 IP 地址，格式为 IPV4，如: 127.0.0.1
            //判断充值数与价格是否匹配
            $score_id = $data['metadata']['score_id'];
            $exchange = $this->pingpayModel->getScoreExchangebyid($score_id);
            $trueAmount = $data['quantity']/$exchange;
            //dump($trueAmount);exit;
            //真实应付价格与输入的价格比较，不相等就抛出错误
            if(intval($trueAmount*100)!=intval($data['amount'])){
                $this->error('少年~输入的数值错误');
            }
            $data['metadata'] = serialize($data['metadata']);

            $result_url=think_encrypt(modC('PINGPAY_CONFIG_RESULTURL','','Pingpay'));//支付成功后跳转回的地址
            
            if (!$this->pingpayOrderModel->create($data)){//验证表单
                $this->error('操作失败！'.$this->pingpayOrderModel->getError());
            }else{
                 $res = $this->pingpayOrderModel->editData($data);
                if($res){
                    $recordData = $this->pingpayOrderModel->getDataById($res);
                    $this->success('操作成功，即将进入在线支付页面',U('Pingpay/index/pubpingpay',array('app'=>'Pingpay','table'=>'PingpayOrder','order_no'=>$recordData['order_no'],'result_url'=>$result_url)));
                }else{
                    $this->error('订单写入时出错');
                }
            }
        }else{
            //允许充值的最小额度
            $min_money = modC('PINGPAY_CONFIG_MINMONEY','','Pingpay');
            //允许充值的积分类型
            $able_score=modC('PINGPAY_CONFIG_SCORE','','Pingpay');
            $able_score = explode(',',$able_score);
            $score_ids = array();
            foreach($able_score as $val){
                $score_ids[] = substr($val,-1);
            }
            $map['id'] = array('in',$score_ids);
            $map['status'] = 1;
            $score_list = D('Ucenter/Score')->getTypeList($map);
            //写入积分兑换比例
            foreach($score_list as &$val){
                $val['exchange'] = $this->pingpayModel->getScoreExchangebyid($val['id']);
            }
            unset($val);
            //获取支付方式
            //$payChannel = D('Pingpay')->channel();
            $this->assign('min_money',$min_money);
            $this->assign('score',$score_list);
            $this->display();
        }
    }
    /**
     * 在线支付确认页面
     * @return [type] [description]
     */
    public function payMent()
    {
        $app = I('app','','text');
        $table = I('table','','text');
        $order_no = I('order_no','','text');
        $chid = I('data','','text');
        $result_url = I('result_url','','text');
        //获取订单信息
        $map['order_no'] = $order_no;
        $order = D($app.'/'.$table)->where($map)->find();
        // 设置RSA私钥
        \Pingpp\Pingpp::setPrivateKeyPath($this->rsa_key);

        \Pingpp\Pingpp::setApiKey($this->api_key);
        $ch = \Pingpp\Charge::retrieve($chid);
        //扫码支付判断
        //$qrcode_url = '';
        if($ch['credential']['wx_pub_qr']){
            $credential = think_encrypt($ch['credential']['wx_pub_qr']);
        }
        if($ch['credential']['alipay_qr']){
            $credential = think_encrypt($ch['credential']['alipay_qr']);
        }
        

        if($ch['credential']['wx_pub_qr'] || $ch['credential']['alipay_qr']){
             $this->redirect('pingpay/index/paybyqrcode',array('app'=>$app,'table'=>$table,'order_no'=>$ch['order_no'],'data'=>$credential,'result_url'=>$result_url),0, '页面跳转中...');
        }
        //非扫码支付处理
        $channel = $this->pingpayModel->getPaychannelInfo($ch['channel']);//获取支付渠道的详细配置
        $ch_y = sprintf("%.2f",$ch['amount']/100);

        //$ch=json_decode($ch, true);
        $this->assign('order',$order);
        $this->assign('channel',$channel);//支付渠道
        $this->assign('ch_y',$ch_y);//金额转换成元
        $this->assign('ch',$ch);
        $this->display();
    }
    /**
     * 扫码支付页面
     * @param  string $app    应用
     * @param  string $model  订单数据表
     * @param  [type] $order_no 商家订单号
     * @param  [type] $data     加密的二维码url参数
     * @return [type]           [description]
     */
    public function payByQrcode($data)
    {
        $app = I('app','','text');
        $table = I('table','','text');
        $order_no = I('order_no','','text');
        $result_url = I('result_url','','text');
        $map['order_no']=$order_no;
        $order = M($table)->where($map)->find();
        if($order){

        }else{
            $this->error('参数错误');
        }
        $this->assign('result_url',$result_url);
        $this->assign('app',$app);
        $this->assign('table',$table);
        $this->assign('order_no',$order_no);
        //$this->assign('order',$order);
        $this->assign('data',$data);
        $this->display();
    }
    /**
     * 生成扫码支付二维码
     * @return [type] [description]
     */
    public function qrcode($data){
        $data = think_decrypt($data);
        $data = urldecode($data);
        $qrcode = qrcode($data,false,$picPath=false,$logo=false,$size='9',$level='L',$padding=2,$saveandprint=false);
    }
    /**
     * AJAS轮询支付状态
     * @param  string $app    应用
     * @param  string $model  订单数据表
     * @param  string $status 字段
     * @return [type]         [description]
     */
    public function payStatus($app,$table,$order_no,$status='paid')
    {
        $app = I('app','','text');
        $table = I('table','','text');
        $order_no = I('order_no','','text');

        $map['order_no'] = $order_no;
        $order = D($app.'/'.$table)->where($map)->find();

        if($order[$status]){
            $result['info']='已支付';
        }else{
            $result['info']='未支付';
        }
            $result['app']=$app;
            $result['table']=$table;
            $result['paid']=$order['paid'];
            $result['order_no']=$order['order_no'];
        $this->ajaxReturn($result);
    }
    /**
    *跨模块的通用支付方法
    **/
    public function pubPingpay()
    {
        //获取订单数据
        $app = I('app','','text');
        $table = I('table','','text');
        $order_no = I('order_no','','text');
        $result_url = I('result_url','','text');//支付成功后跳转回的地址

        $map['order_no'] = $order_no;
        $order = D($app.'/'.$table)->where($map)->find();
        //dump($order);exit;
        if($order){
            $arr['success_url']=$_SERVER['HTTP_HOST'].U('Pingpay/Index/succee',array('app'=>$app,'table'=>$table,'order_no'=>$order_no,'result_url'=>$result_url));//支付成功后的回调地址
            $arr['product_id']=$order['order_no'];//商品订单的id
            //获取特定渠道的额外参数
            $channel = $order['channel']?strtolower($order['channel']):strtolower($order['paychannel']);
            $extra = $this->extra($channel,$arr);
            // 设置RSA私钥
            \Pingpp\Pingpp::setPrivateKeyPath($this->rsa_key);

            //发起支付 设置 API Key
            \Pingpp\Pingpp::setApiKey($this->api_key);
            // 支付参数
            $data['order_no'] = $order['order_no'];//商户订单号,推荐使用 8-20 位，要求数字或字母，不允许其他字符
            $data['app'] = array('id'=>$this->app_id); //app [ id ]
            $data['channel'] = $channel;// 支付使用的第三方支付渠道取值，请参考：https://www.pingxx.com/api#api-c-new
            $data['amount'] = $order['amount']; //订单总金额, 人民币单位：分（如订单总金额为 1 元，此处请填 100）
            $data['client_ip'] = $_SERVER['REMOTE_ADDR']; // 发起支付请求客户端的 IP 地址，格式为 IPV4，如: 127.0.0.1
            $data['currency'] = 'cny'; //三位 ISO 货币代码，目前仅支持人民币  cny 。
            $data['subject'] = $order['subject']; //商品名称
            $data['body'] = $order['body']; //商品描述
            $data['extra'] = $extra;//特定渠道发起交易时需要的额外参数，以及部分渠道支付成功返回的额外参数
            $data['time_expire'] = '';//订单失效时间，用 Unix 时间戳表示。默认1天
            $data['metadata'] = unserialize($order['metadata']); //使用键值对的形式来构建自己的 metadata，例如 metadata[color] = red，
            $data['description'] = $order['description']; //订单附加说明，最多 255 个 Unicode 字符。

            try {
                $ch = \Pingpp\Charge::create($data);
                if($ch){
                    //ping++订单创建成功后将ping订单号写入数据库
                    //$edata['id'] = $order['id'];
                    //$res = $this->D($app.'/'.$table)->editData($edata);
                    //echo $ch;
                    $this->redirect('pingpay/index/payMent',array('app'=>$app,'table'=>$table,'order_no'=>$order_no,'data'=>$ch['id'],'result_url'=>$result_url), 0, '页面跳转中...');
                }else{
                    $check['error']['message'] = '支付参数有错误';
                    $this->ajaxReturn($check);
                }
            } catch (\Pingpp\Error\Base $e) {
                // 捕获报错信息
                if ($e->getHttpStatus() != NULL) {
                    header('Status: ' . $e->getHttpStatus());
                    echo $e->getHttpBody();
                } else {
                    echo $e->getMessage();
                }
            }

        }else{
            $this->error('订单数据获取错误');
        }
    }
    /**
     * 通用的支付成功页
     * @param  [type] $url 跳转的result_url
     * @return [type]      [description]
     */
    public function succee(){
        $app = I('app','','text');
        $table = I('table','','text');
        $order_no = I('order_no','','text');
        $result_url = I('result_url','','text');
        $result_url = think_decrypt($result_url);//成功后的会跳地址

        $map['order_no'] = $order_no;
        $order = D($app.'/'.$table)->where($map)->find();

        $this->assign('result_url',$result_url);
        $this->assign('order',$order);
        $this->display();
    }

    /**
    * 提现申请页
    **/
    public function withdraw()
    {
        if(!$this->open_withdraw){
            $this->error('系统未开通提现功能');
        }
        $this->display();
    }

    /**
     * 获取积分兑换比例
     */
    public function score_exchange(){
        $id = I('get.id',0,'intval');
        $map['id'] = $id;
        $map['status'] = 1;
        $score_list = D('Ucenter/Score')->getType($map);
        //写入积分兑换比例
        $score_list['exchange'] = $this->pingpayModel->getScoreExchangebyid($id);
        
        unset($val);
        //组装JSON返回数据
        if(isset($score_list)){
            $result['status']=1;
            $result['info'] = 'success';
            $result['data'] = $score_list;
        }else{
            $result['status']=0;
            $result['info'] = 'error';
        }
        $this->ajaxReturn($result,'JSON');
    }

    private function extra($channelName,$arr)
    {   
        $path = APP_PATH  . 'Pingpay/Conf/channel.php';
        $channel = load_config($path);
        //$extra = $channel[$channelName]['extra'];
        $extra = $this->_extra($channelName,$arr);
        return $extra;
    }
    private function _extra($channelName,$arr)//自定义extra的值
    {
        switch ($channelName)
        {
            case 'alipay':
              $extra = array(
                    //'extern_token'=>'',//开放平台返回的包含账户信息的 token（授权令牌，商户在一定时间内对支付宝某些服务的访问权限）。通过授权登录后获取的  alipay_open_id ，作为该参数的  value ，登录授权账户即会为支付账户，32 位字符串。
                    //'rn_check'=>'F',//是否发起实名校验，T 代表发起实名校验；F 代表不发起实名校验。
                    //'buyer_account'=>''//支付完成将额外返回付款用户的支付宝账号。
                );
            break;
            case 'alipay_wap':
              $extra= array(
                    'success_url'=>$arr['success_url'],//支付成功的回调地址。
                    'cancel_url'=>$arr['cancel_url'],//支付取消的回调地址， app_pay 为true时，该字段无效。
                    'app_pay'=>'true',//是否使用支付宝客户端支付，该参数为true时，调用客户端支付。
                    //'buyer_account'=>'',//支付完成将额外返回付款用户的支付宝账号。
                );
            break;
            case 'alipay_pc_direct':
            $extra = array(
                    'success_url'=>$arr['success_url'],//支付成功的回调地址。
                    'enable_anti_phishing_key'=>'',//是否开启防钓鱼网站的验证参数（如果已申请开通防钓鱼时间戳验证，则此字段必填)
                    'exter_invoke_ip'=>$_SERVER['REMOTE_ADDR'],//客户端 IP ，用户在创建交易时，该用户当前所使用机器的IP（如果商户申请后台开通防钓鱼IP地址检查选项，此字段必填，校验用）
                );
            break;
            case 'alipay_qr':
            $extra = array(
                    
                );
            break;
            case 'wx':
            $extra=array(
                    'limit_pay'=>'no_credit',//指定支付方式，指定不能使用信用卡支付可设置为  no_credit 
                    'goods_tag'=>$arr['goods_tag'],//商品标记，代金券或立减优惠功能的参数。
                    'open_id'=>$arr['open_id'],//用户在商户  appid 下的唯一标识
                    //'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type
                );
            break;
            case 'wx_pub':
            $extra=array(
                    'limit_pay'=>'no_credit',//指定支付方式，指定不能使用信用卡支付可设置为  no_credit 。
                    'product_id'=>$arr['product_id'],//商品 ID，1-32 位字符串。此 id 为二维码中包含的商品 ID，商户自行维护。
                    'goods_tag'=>$arr['goods_tag'],//商品标记，代金券或立减优惠功能的参数。
                    //'open_id'=>'',//支付完成后额外返回付款用户的微信  open_id 。
                    //'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type 。
                );
            break;
            case 'wx_pub_qr':
            $extra=array(
                    'limit_pay'=>'no_credit',//指定支付方式，指定不能使用信用卡支付可设置为  no_credit 。
                    'product_id'=>$arr['product_id'],//商品 ID，1-32 位字符串。此 id 为二维码中包含的商品 ID，商户自行维护。
                    'goods_tag'=>$arr['goods_tag'],//商品标记，代金券或立减优惠功能的参数。
                    //'open_id'=>'',//支付完成后额外返回付款用户的微信  open_id 。
                    //'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type 。
                );
            break;
            case 'wx_wap':
            $extra=array(
                    'result_url'=>$arr['success_url'],//支付完成的回调地址。
                    'goods_tag'=>$arr['goods_tag'],//商品标记，代金券或立减优惠功能的参数。
                    //'open_id'=>'',//支付完成后额外返回付款用户的微信  open_id 。
                    //'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type 。
                );
            break;
            case 'upacp_wap':
            $extra=array(
                    'result_url'=>$arr['success_url'],//支付完成的回调地址。
                );
            break;
            case 'upacp_pc':
            $extra=array(
                    'result_url'=>$arr['success_url'],//支付完成的回调地址。
                );
            break;
        }
        return $extra;
    }

}