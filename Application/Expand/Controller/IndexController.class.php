<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Expand\Controller;

use Think\Controller;

class IndexController extends Controller
{
    protected $expandModel;
    protected $expandCategoryModel;
    protected $expandRecordsModel;
    protected $expandVersionModel;

    function _initialize()
    {
        $this->expandModel = D('Expand/Expand');
        $this->expandCategoryModel = D('Expand/ExpandCategory');
        $this->expandRecordsModel = D('Expand/ExpandRecords');
        $this->expandVersionModel = D('Expand/ExpandVersion');

        $tree = $this->expandCategoryModel->getTree(0,true,array('status' => 1));
        $menu_list['menu'][] = array('tab' => 'category_' . 0, 'title' => '全部应用', 'href' => U('Index/index'));
        foreach ($tree as $category) {
            $menu = array('tab' => 'category_' . $category['id'], 'title' => $category['title'], 'href' => U('Index/index', array('category' => $category['id'])));
            $menu_list['menu'][] = $menu;//分类菜单数组
        }

        $show_edit=S('SHOW_EDIT_BUTTON');
        if($show_edit===false){
            $map['can_post']=1;
            $map['status']=1;
            $show_edit=$this->expandCategoryModel->where($map)->count();
            S('SHOW_EDIT_BUTTON',$show_edit);
        }
        $menu_list['user']=array();//用户菜单数组
        if(is_login()){
            $menu_list['user'][]=array('tab' => 'myBoughtExpand', 'title' => '<i class="icon-th-list"></i> 我购买的应用', 'href' =>U('Expand/My/bought'));
            $menu_list['user'][]=array('tab' => 'myExpand', 'title' => '<i class="icon icon-upload"></i> 开发者中心', 'href' =>U('Expand/Dev/devCenter'));
        }

        $menu_list['user'][]=array('tab' => 'developer', 'title' => '<i class="icon-credit"></i> 开发者认证', 'href' =>is_login()?U('Expand/Dev/devAuth'):"javascript:toast.error('登录后才能操作')");
        $this->assign('sub_menu', $menu_list);

    } 
    //扩展首页
    public function index($page=1,$r=20)
    {
        
        /* 分类信息 */
        $category = I('category',0,'intval');
        if($category){
            //$this->_category($category);
            $cates=$this->expandCategoryModel->getCategoryList(array('pid'=>$category));
            if(count($cates)){
                $cates=array_column($cates,'id');
                $cates=array_merge(array($category),$cates);
                $map['category']=array('in',$cates);
            }else{
                $map['category']=$category;
            }
        }
        $map['status']=1;
        /* 获取当前分类下列表 */
        list($list,$totalCount) = $this->expandModel->getListByPage($map,$page,'sort desc,update_time desc','*',$r);
        foreach($list as &$val){
            $val['price'] = '￥'.sprintf("%.2f",$val['price']/100);//将金额单位转成元
            $val['user']=query_user(array('space_url','avatar32','nickname'),$val['uid']);

            $map['expand_id'] = $val['id'];
            $version = $this->expandVersionModel->getList($map,'id desc',1,'*');
            if($version){
                //$val['file_id'] = $version[0]['download_file'];
                $val['downBtn'] = $this->_downBtn ($val['id'],$version[0]['download_file']);
            }
        }
        unset($val);
        //dump($list);exit;

        /* 模板赋值并渲染模板 */
        $this->setTitle('应用商店');
        $this->assign('list', $list);
        $this->assign('category', $category);
        $this->assign('totalCount',$totalCount);
        $this->display();
    }

    public function info()
    {
        $aId=I('id',0,'intval');
        /* 标识正确性检测 */
        if (!($aId && is_numeric($aId))) {
            $this->error('应用ID错误！');
        }
        $info = $this->expandModel->getData($aId);
        //开始获取该应用版本库列表
        $info['price'] = '￥'.sprintf("%.2f",$info['price']/100);//将金额单位转成元
        $map['expand_id'] = $aId;
        $map['status'] = 1;
        $page = 1;
        $r = 10;
        list($versionList,$vtotalCount) = $this->expandVersionModel->getListByPage($map,$page,'id desc','*',$r);
        if($versionList){
            $info['file_id'] = $versionList[0]['download_file'];
            $info['version'] = $versionList[0]['version'];
            $info['update_log'] = $versionList[0]['update_log'];
            $info['update_time'] = $versionList[0]['update_time'];
            $info['downBtn'] = $this->_downBtn($aId,$info['file_id']);

            foreach($versionList as &$val){
                $val['price'] = '￥'.sprintf("%.2f",$val['price']/100);//将金额单位转成元
                $val['downBtn'] = $this->_downBtn($aId,$val['download_file']);
            }
            unset($val);
        }
        
        //获取用户信息
        $author=query_user(array('uid','space_url','nickname','avatar32','avatar64','signature'),$info['uid']);
        //获取用户发布扩展数量
        $author['expand_count']=$this->expandModel->where(array('uid'=>$info['uid']))->count();
        //用户所有扩展访问量
        $author['expand_total_view']=$this->_totalView($info['uid']);
        //关键字转化成数组
        $keywords = explode(',',$info['keywords']);

        $info['cover'] = explode(',',$info['cover']);

        /* 更新浏览数 */
        $map = array('id' => $aId);
        $this->expandModel->where($map)->setInc('view');
        /* 模板赋值并渲染模板 */
        $this->setTitle($info['title']);
        $this->setDescription('{$info.description|text}-{$MODULE_ALIAS}');
        $this->assign('author',$author);
        $this->assign('info', $info);
        $this->assign('keywords',$keywords);
        $this->assign('revisions', $versionList);
        $this->assign('vtotalCount',$vtotalCount);
        $this->display();
    }
    /*
    **应用下载并记录下载数量
    */
    public function downExpand()
    {
        $aid=I('id',0,'intval');
        $file_id = I('file_id',0,'intval');
        $uid=is_login();
        $map['uid']=$uid;
        $map['expand_id']=$aid;
        $map['paid']=1;
        $resRecord = $this->expandRecordsModel->getRecordData($map);//获取购买应用记录
        unset($map);
        if($resRecord){
            if(!$file_id){
                $file_id = $file_id;
            }else{
                $map['expand_id'] = $aid;
                $map['status'] = 1;
                $result = $this->expandVersionModel->getList($map,'id desc',5,'*');
                $file_id = $result[0]['download_file'];
            }
            //$file_url = getFileById($file_id);
            $downexpand= M('file')->find($file_id);
            $file_name = $downexpand['name'];//原始名
            $file_ext = $downexpand['ext'];//扩展名
            $file_url = $downexpand['savepath'].$downexpand['savename'];//文件路径
            if($result){
                //更新下载次数
                $map = array('id' => $aid);
                $this->expandModel->where($map)->setInc('download_num'); 
                $this->_download($file_url,$file_name,$file_ext);
            }
        }else{
            $this->error('少年！酱紫是不可以的...');
        }
    }
    /**
     * 下载
     * @param $get_url
     * @param $file_name
     */
    private function _download($get_url, $file_name, $file_ext = 'zip')
    {
        header('Content-Type: application/'.$file_ext);
        header('Content-Disposition: attachment; filename='.$file_name);
        header('Content-Length:'.filesize($get_url));

        readfile($get_url);
        exit;
    }
    /**
    * 应用购买页
    **/
    public function buy()
    {
        if(!is_login()){
            $this->error("需要登陆");
        }
        
        if(IS_POST){
            //写入购买记录
            $order_px = modC('EXPAND_CONFIG_ORDERPX','','Expand');//订单前缀，webhooks将根据订单前缀判断订单类型
            // 支付参数
            $data['order_no'] = $order_px.substr(md5(time()), 0, 12);//商户订单号,推荐使用 8-20 位，要求数字或字母，不允许其他字符
            $data['uid'] = $uid = is_login();
            $data['expand_id'] = I('post.expand_id',0,'intval');
            $data['payment'] = I('post.payment','','op_t');
            if(!is_numeric($data['payment'])){
                $data['paychannel'] = I('post.paychannel','','text');
            }
            $data['amount'] = I('post.amount',0,'intval');
            $data['amount'] = sprintf("%01.2f", $data['amount']*100);//将金额单位转成分
            $result = $this->expandModel->getData($data['expand_id']);//获取应用详细
            $data['subject'] = $result['title'];//商品名称
            $data['body'] = $result['description'];//商品描述
            $data['add_uid'] = $result['uid'];//应用发布者id
            $data['metadata'] = serialize(array('module'=>'Expand'));//订单元数据，要求JSON字符串
            $data['client_ip'] = $_SERVER['REMOTE_ADDR']; //下单的ip地址
            //积分支付，判断积分是否够用
            if(is_numeric($data['payment'])){
                $score_type = D('Ucenter/Score')->getType(array('id'=>$data['payment']));
                $score = D('Ucenter/Score')->getUserScore($uid, $data['payment']);
                if($score<$data['amount']/100){
                    $this->error('少年~'.$score_type['title'].'不够用啦！');
                }
            }
            //验证及写入数据
            if (!$this->expandRecordsModel->create($data)){//验证表单
                $this->error('操作失败！'.$this->expandRecordsModel->getError());
            }else{
                $res=$this->expandRecordsModel->addRecordData($data);//写入购买记录
                if($res){
                $recordData = $this->expandRecordsModel->getDataById($res);
                    //判断支付类型
                    if(is_numeric($recordData['payment'])){
                        $this->success('操作成功,即将进入支付页面',U('index/pay',array('id'=>$recordData['id'],'order_no'=>$recordData['order_no'])));
                    }else{
                        $result_url=think_encrypt(modC('EXPAND_CONFIG_RESULTURL','','Expand'));//支付成功后跳转回的地址
                        $this->success('操作成功，即将进入在线支付页面',U('Pingpay/index/pubpingpay',array('app'=>'Expand','table'=>'ExpandRecords','order_no'=>$recordData['order_no'],'result_url'=>$result_url)));
                    }
                }else{
                    $this->error('写入数据错误');
                }
            }  
        }else{
            $aId=I('id',0,'intval');
            $uid = is_login();
            /* 标识正确性检测 */
            $result = $this->expandModel->getData($aId);
            if(empty($result)){
                $this->error('应用ID错误!sorry');
            }
            $result['price'] = sprintf("%01.2f", $result['price']/100);
            //允许购买的积分类型
            $able_score=modC('EXPAND_CONFIG_SCORE','','Expand');
            $able_score = explode(',',$able_score);
            $score_ids = array();
            foreach($able_score as $val){
                $score_ids[] = substr($val,-1);
            }
            unset($val);
            $map['id'] = array('in',$score_ids);
            $map['status'] = 1;
            $score_list = D('Ucenter/Score')->getTypeList($map);
            //获取用户积分数量
            //$score = getUserScore($uid,$val['id']);
            foreach($score_list as &$val){
                $val['num'] = D('Ucenter/Score')->getUserScore($uid,$val['id']);
            }
            
            //是否启用在线支付
            $onlinePay=modC('EXPAND_CONFIG_ONLINEPAY','','Expand');
            //获取在线支付渠道
            $payChannel = D('Pingpay/Pingpay')->channel();

            //dump($score_list);exit;
            $this->assign('ableScore',$score_list);
            $this->assign('onlinePay',$onlinePay);
            $this->assign('payChannel',$payChannel);
            $this->assign('result',$result);
            $this->display();
        }
    }
    /**
    *   积分购买应用的订单支付页
    */
    public function pay()
    {
        if(!is_login()){
            $this->error("需要登陆");
        }

        $aId=I('id',0,'intval');//获取订单ID
        $aOrder_no=I('order_no','','text');//获取订单ID
        $recordData = $this->expandRecordsModel->getDataById($aId);
        if($aOrder_no!=$recordData['order_no']){
            $this->error("参数错误");
        }

        //支付处理
        if($recordData['payment']!='pingpay' || is_numeric($recordData['payment'])){//判断是否在线支付
            //积分购买处理
            $score = $recordData['amount']/100;//积分数量
            $add_uid = $recordData['add_uid'];//发布者的UID
            $scoreModel = D('Ucenter/Score');

            $scoreID = $map['id'] = $recordData['payment'];
            $scoreType = D('Ucenter/Score')->getType($map);//根据ID获取积分类型详细

            $duid=query_user(array('nickname'),is_login()); //购买用户的昵称
            $fuid=query_user(array('nickname'),$add_uid); //发布用户的昵称

            $remark = $duid['nickname'].'购买了'.$fuid['nickname'].'发布的应用扣除【'.$scoreType['title'].'：-'.$score.$scoreType['unit'].'】';
            $res = $scoreModel->setUserScore(is_login(),$score,$scoreID,'dec','expand',0,$remark);//购买用户扣除积分
            if(!$res){
                $this->error('扣除积分错误！');
            }
            $remark = $fuid['nickname'].'发布的应用被'.$duid['nickname'].'购买【'.$scoreType['title'].'：+'.$score.$scoreType['unit'].'】';
            $res = $scoreModel->setUserScore($add_uid, $score,$scoreID,'inc','expand',0,$remark);//发布应用用户增加积分
            if(!$res){
                $this->error('写入积分错误！');
            }
            $data['id']=$aId;
            $data['paid']=1;
            $res=$this->expandRecordsModel->editRecordData($data);//写入购买记录
            $this->success('成功购买应用');
        }
    }
    /*
    *应用商店帮助文档
    */
    public function help()
    {
        $info = modC('EXPAND_SHOW_HELP', '', 'Expand');

        $this->assign('help_info',$info);
        $this->display();
    }
    /**
     * 通用下载应用按钮参数
     * @param  [type] $id      [应用ID]
     * @param  [type] $file_id [下载文件ID]
     * @param  [type] $price   [下载所需积分]
     * @return [type]          [Array]
     */
    public function _downBtn ($id,$file_id)
    {
        $downBtn['title'] = '下载应用';
        
        $map['uid'] = is_login();
        $map['expand_id'] = $id;
        $map['paid'] = 1;
        $result = $this->expandRecordsModel->getRecordData($map);//获取购买应用记录
            if($result) {
                $$downBtn['enable_down'] = 1;
                $downBtn['down_url'] = U('index/downexpand',array('id'=>$id,'file_id'=>$file_id));
                $downBtn['btn'] = '<a type="button" class="btn btn-block btn-lg btn-warning" href="'.$downBtn['down_url'].'" target= _blank>已购 下载应用</a>';
            }else {
                $downBtn['url'] = U('index/buy',array('id'=>$id,'file_id'=>$file_id));
                $$downBtn['enable_down'] = 0;
                $downBtn['btn'] = '<button type="button" class="btn btn-block btn-lg btn-warning" data-title="'.$downBtn['title'].'" data-url="'.$downBtn['url'].'" data-toggle="modal">下载应用</button>';
            }
            if(!is_login()){
                $downBtn['btn'] = '<button type="button" class="btn btn-block btn-lg btn-warning" onclick="javascript:toast.error(\'登录后才能操作\')">下载应用</button>';
            }
        return $downBtn;
    }
    private function _category($id=0)
    {
        $now_category=$this->expandCategoryModel->getTree($id,'id,title,pid,sort');
        $this->assign('now_category',$now_category);
        return $now_category;
    }
    protected function _needLogin()
    {
        if(!is_login()){
            $this->error('请先登录！');
        }
    }
    //获取用户发布内容的总阅读量
    private function _totalView($uid=0)
    {
        $res=$this->expandModel->where(array('uid'=>$uid))->select();
        $total=0;
        foreach($res as $value){ 
            $total=$total+$value['view'];
        }
        unset($value);
        return $total;
    }

}