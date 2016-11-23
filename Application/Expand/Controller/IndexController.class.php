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
            $val['user']=query_user(array('space_url','avatar32','nickname'),$val['uid']);

            $map['expand_id'] = $val['id'];
            $version = $this->expandVersionModel->getList($map,'id desc',1,'*');
            if($version){
                $val['file_id'] = $version[0]['download_file'];
                $val['downBtn'] = $this->_downBtn ($val['id'],$version[0]['download_file'],$val['price']);
            }
            
            if(is_login()){
            $map['uid'] = is_login();
            $result = $this->expandRecordsModel->where($map)->find();
                if($result) {
                    $val['enable_down'] = 1;
                }else {
                    $val['enable_down'] = 0;
                }
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
            $info['downBtn'] = $this->_downBtn($aId,$info['file_id'],$info['price']);
            foreach($versionList as &$val){
                $val['downBtn'] = $this->_downBtn($aId,$val['download_file'],$info['price']);
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

        if(is_login()){
            $map['uid'] = is_login();
            $map['expand_id'] = $info['id'];
            $result = $this->expandRecordsModel->where($map)->find();
                if($result) {
                    $info['enable_down'] = 1;
                }else {
                    $info['enable_down'] = 0;
                }
        }
        //dump($versionList);exit;


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

    public function buyExpand()
    {
        $aid=I('id',0,'intval');
        $file_id=I('file_id',0,'intval');
        $aprice=I('price',0,'intval');
        $result = $this->expandModel->getData($aid);
        if(empty($result)){
            $this->error('少年！发生参数错误了。sorry');exit;
        }
        if(IS_POST && is_login()){
            //获取用户积分
            $scoreModel = D('Ucenter/Score');
            $score = $scoreModel->getUserScore(is_login(), 1);
            //判断用户积分是否够用
            if($score<$aprice){//用户积分小于应用价格提示错误
                $this->error('少年！积分也太少了...不够用啦');
            }else{

                $expand_id = $aid;
                $uid = is_login();
                $resRecord = $this->expandRecordsModel->getRecordData($uid,$expand_id);//获取购买应用记录
                if($resRecord){
                    if($result){
                        $this->success('已购应用，即将开始下载...',U('index/downexpand',array('id'=>$aid,'file_id'=>$file_id)));
                    }else{
                        $this->error('操作失败！'.$this->expandModel->getError());
                    }
                }else{
                    $now_time=date('Y-m-d H:i:s',time()); //格式化当前时间
                    $score = $result['price'];

                    $uid = is_login();
                    $duid=query_user(array('nickname'),$uid); //下载用户的昵称
                    $remark = $duid['nickname'].'在'.$now_time.'下载应用【积分：-'.$score.'分】';
                    $scoreModel->setUserScore($uid, $score,1,'dec','expand',0,$remark);//购买用户扣除积分

                    $add_uid = $result['uid'];
                    $fuid=query_user(array('nickname'),$add_uid); //发布用户的昵称
                    $remark = $fuid['nickname'].'发布的应用在'.$now_time.'被'.$duid['nickname'].'下载【积分：+'.$score.'分】';
                    $scoreModel->setUserScore($add_uid, $score,1,'inc','expand',0,$remark);//发布应用用户增加积分

                    //写入购买记录
                    $data['uid'] = $uid;
                    $data['add_uid'] = $add_uid;
                    $data['expand_id'] = $aid;
                    $data['score'] = $score;
                    $res=$this->expandRecordsModel->addRecordData($data);//写入购买记录

                    if(!empty($res)){
                        $this->success('购买成功！-'.$aprice.'积分，即将开始下载...',U('index/downexpand',array('id'=>$aid,'file_id'=>$file_id)));
                    }else{
                        $this->error('操作失败！'.$this->expandModel->getError());
                    }
                }
            }
            
        }else{
            if(is_login()){
                $scoreModel = D('Ucenter/Score');
                $score = $scoreModel->getUserScore(is_login(), 1);
                //判断用户积分是否够用
                if($score<$aprice){//用户积分小于应用价格提示错误
                    $score = 0; //用于前端判断
                    $info = "少年！积分也太少了...不够用啦>>><a class='fangfa' href=''>如何赚积分</a>";
                }else{
                    $expand_id = $aid;
                    $uid = is_login();
                    $resRecord = $this->expandRecordsModel->getRecordData($uid,$expand_id);//获取购买应用记录
                    if($resRecord){
                        $info = "已购应用，确认后开始下载...";
                    }else{
                        if($result['price']==0){
                            $info = "免费应用，确认后开始下载...";
                        }else{
                            $info = "您的操作将扣除".$aprice."积分，是否确认？";
                        } 
                    }
                    $score = 1; //用于前端判断
                }
            }else{
                $score = 0; //用于前端判断
                $info = "需要登录后才能继续操作！";
            }
            $this->assign('score',$score);
            $this->assign('info',$info);
            $this->assign('price',$aprice);
            $this->assign('id',$aid);
            $this->display();
        }
    }

    /*
    **应用下载并记录下载数量
    */
    public function downExpand()
    {
        $aid=I('id',0,'intval');
        $file_id = I('file_id',0,'intval');
        $uid=is_login();
        $resRecord = $this->expandRecordsModel->getRecordData($uid,$aid);//获取购买应用记录
        if($resRecord){
            if($file_id){
                $file_id = $file_id;
            }else{
                $map['expand_id'] = $aid;
                $map['status'] = 1;
                $result = $this->expandVersionModel->getList($map,'id desc',5,'*');
                $file_id = $result[0]['download_file'];
            }
            //$result = $this->expandModel->getData($aid);
            

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
            $data['uid'] = is_login();
            
            $data['expand_id'] = I('post.expand_id',0,'intval');
            $data['payment'] = I('post.payment','','');
            $result = $this->expandModel->getData($data['expand_id']);//获取应用详细
            $data['add_uid'] = $result['uid'];
            //dump($data);exit;

            $res=$this->expandRecordsModel->addRecordData($data);//写入购买记录
            if($res){
                $this->success('操作成功');
            }else{
                $this->error('操作错误');
            }
        }else{
            $aId=I('id',0,'intval');
            /* 标识正确性检测 */
            $result = $this->expandModel->getData($aId);
            if(empty($result)){
                $this->error('应用ID错误!sorry');
            }
            //获取支付方式
            $payChannel = D('Pingpay/Pingpay')->channel();

            //dump($result);exit;
            $this->assign('payChannel',$payChannel);
            $this->assign('result',$result);
            $this->display();
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
    public function _downBtn ($id,$file_id,$price)
    {
        $downBtn['url'] = U('index/buyexpand',array('id'=>$id,'price'=>$price,'file_id'=>$file_id));
        $downBtn['title'] = '下载应用';

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