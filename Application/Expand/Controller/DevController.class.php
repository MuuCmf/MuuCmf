<?php
namespace Expand\Controller;

use Think\Controller;

class DevController extends IndexController
{
    protected $expandModel;
    protected $expandCategoryModel;
    protected $expandRecordsModel;
    protected $expandVersionModel;
    protected $expandDevauthModel;
    function _initialize(){
        parent::_needLogin();
        $this->expandModel = D('Expand/Expand');
        $this->expandCategoryModel = D('Expand/ExpandCategory');
        $this->expandRecordsModel = D('Expand/ExpandRecords');
        $this->expandVersionModel = D('Expand/ExpandVersion');
        $this->expandDevauthModel = D('Expand/ExpandDevauth');

        //开发者中心菜单数组
        $devMenuList=array();
        if(is_login()){
            $devMenuList[]=array('tab' => 'myExpand', 'title' => '<i class="icon-th-list"></i> 我发布的应用', 'href' =>U('Expand/Dev/launch'));
            $devMenuList[]=array('tab' => 'myBoughtExpand', 'title' => '<i class="icon-th-list"></i> 我购买的应用', 'href' =>U('Expand/My/bought'));
            $devMenuList[]=array('tab' => 'devauth', 'title' => '<i class="icon-credit"></i> 开发者认证', 'href' =>U('Expand/Dev/devAuth'));
        }
        $this->assign('devMenu',$devMenuList);
    }

    /**
     * 开发者认证
     * @return [type] [description]
     */
    public function devAuth()
    {
        if(IS_POST){

            $data['uid'] = is_login();
            $data['tname'] = I('post.tname','','op_t');//真实姓名
            $data['snum'] = I('post.snum','','op_t');//身份证号码
            $data['spic'] = I('post.spic',0,'intval');//身份证图片
            $data['description'] = I('post.description','','op_h');//自我介绍
            $data['status'] = 2;//默认待审核状态
            if (!$this->expandDevauthModel->create($data)){//验证表单
                $this->error('操作失败！'.$this->expandDevauthModel->getError());
            }else{
                $res = $this->expandDevauthModel->editData($data);
                if($res){
                    $this->success('认证资料提交成功，请等待审核...',U('Dev/devauth'));
                }else{
                    $this->error('操作失败！'.$this->expandDevauthModel->getError());
                }  
            }
        }else{
            $res = $this->expandDevauthModel->getDataByUid(is_login());
            $repost = I('repost','','op_t');
            $status = $res['status'];
            if($res&&!$repost){
                //已经提交过审核资料';
                $this->assign('data',$res);
                $template = 'devauthing';

            }else{
                //提交资料
                $this->assign('data',$res);
                $template = 'devauth';
            }

            $devag = modC('EXPAND_SHOW_DEVAG');//开发者协议

            $this->assign('devag',$devag);
            $this->setTitle('开发者认证');
            $this->display($template);
        }
    }
    /**
     * 开发者管理中心
     * @return [type] [description]
     */
    public function devCenter()
    {
        $this->redirect('Expand/Dev/launch');
        $this->setTitle('开发者中心');
        $this->display();
    }

    /**
     * 我发布的应用列表
     * @param int $page
     * @param int $r
     */
    public function launch($page=1,$r=20)
    {
        $category = I('category',0,'intval');
        if($category){
            $cates=$this->expandCategoryModel->getCategoryList(array('pid'=>$category));
            if(count($cates)){
                $cates=array_column($cates,'id');
                $cates=array_merge(array($category),$cates);
                $map['category']=array('in',$cates);
            }else{
                $map['category']=$category;
            }
        }
        $map['uid'] = is_login();
        list($list,$totalCount)=$this->expandModel->getListByPage($map,$page,'sort desc,update_time desc','*',$r);
        foreach($list as &$val){
            $val['price'] = sprintf("%.2f",$val['price']/100);//将金额单位转成元
            $val['user']=query_user(array('space_url','avatar32','nickname'),$val['uid']);
        }
        unset($val);

        $this->setTitle('我发布的应用');
        $this->assign('list', $list);
        $this->assign('totalCount',$totalCount);
        $this->display();
    }

    /**
     * 发布、编辑应用
     * @return [type] [description]
     */
    public function edit()
    {
        $aId=I('id',0,'intval');
        $title=$aId?"编辑":"发布";
        $button=$aId?"编辑":"下一步";
        $this->checkAuth('Expand/Dev/edit',-1,'您无应用发布权限。');
        if(IS_POST){
            $data['id'] = $aId;
            $data['uid'] = is_login();
            $data['title'] = I('post.title','','op_t');
            $data['icon']=I('post.icon',0,'intval');
            $data['category']=I('post.category',0,'intval');
            $data['description']=I('post.description','','op_t');
            $data['content']=I('post.content','','op_h');
            $data['price']=I('post.price',0,'intval');
            $data['price']=$data['price']*100;

            if (!$this->expandModel->create($data)){//验证表单
                $this->error('操作失败！'.$this->expandModel->getError());
            }else{
                $res = $this->expandModel->editData($data);
                if($res){
                    if($aId){
                        $this->success('应用信息编辑成功！',U('Dev/launch'));
                    }else{
                        $this->success('应用信息发布成功，进入下一步...',U('Dev/editversion',array('expand_id'=>$res)));
                    }
                }else{
                    $this->error('操作失败！'.$this->expandModel->getError());
                }  
            }
        }else{
            $map['status'] = 1;
            $category=$this->expandCategoryModel->getCategoryList($map);
            $data = $this->expandModel->getData($aId);
            $data['price']=$data['price']/100;
            $this->setTitle($title.'应用');
            $this->assign('button',$button);
            $this->assign('category',$category);
            $this->assign('data',$data);
            $this->display();
        }
    }
    /**
     * 历史版本库管理
     * @return [type] [description]
     */
    public function versionList($page=1,$r=20)
    {
        $aId=I('id',0,'intval');
        $expand = $this->expandModel->getData($aId);

        $map['expand_id'] = $aId;
        $map['status'] = array('in',array(1,2,0));
        list($list,$totalCount)=$this->expandVersionModel->getListByPage($map,$page,'id desc','*',$r);
        foreach($list as &$val){
                if($val['status']==1){
                    $val['audit_status']='<span style="color: green;">审核通过</span>';
                }elseif($val['status']==2){
                    $val['audit_status']='<span style="color:#4D9EFF;">等待审核</span>';
                }elseif($val['status']==0){
                    $val['audit_status']='<span style="color: #b5b5b5;">审核失败</span>';
                }
        }
        unset($val);

        $this->setTitle('版本库');
        $this->assign('expand',$expand);
        $this->assign('list',$list);
        $this->assign('totalCount',$totalCount);
        $this->display();
    }
    /**
     * 发布、编辑应用的版本
     * @return [type] [description]
     */
    public function editVersion()
    {
        $aId = I('get.id',0,'intval');//版本ID
        $expandId = I('get.expand_id',0,'intval');
        $title=$aId?"编辑":"发布";
        
        if(IS_POST){
            $data['id']=$aId;
            $data['expand_id'] = I('post.expand_id',0,'intval');
            $data['version'] = I('post.version','','op_t');
            $data['update_log'] = I('post.update_log','','op_h');
            $data['download_file'] = I('post.download_file',0,'intval');
            $data['status'] = 2;

            if (!$this->expandVersionModel->create($data)){//验证表单
                $this->error('操作失败！'.$this->expandVersionModel->getError());
            }else{
                $res = $this->expandVersionModel->editData($data);
                if($res){
                    $this->success('操作成功！',U('Dev/versionList',array('id'=>$data['expand_id'])));
                }else{
                    $this->error('操作失败！'.$this->expandModel->getError());
                }  
            }
        }else{
            if(!$expandId){
                $this->error('参数错误');
            }
            $versionData = $this->expandVersionModel->getData($aId);
            $expandData = $this->expandModel->getData($expandId);
            $data = $versionData;

            $this->setTitle($title.'版本');
            $this->assign('expandData',$expandData);
            $this->assign('data',$data);
            $this->display();
        }
    }
    /**
    * 通过版本id移除一个版本
    */
    public function delVersion()
    {
        $this->checkAuth('Expand/Dev/edit',-1);
        if(IS_POST){
            $aId=I('post.id',0,'intval');
            $map['id']=$aId;
            $res = $this->expandVersionModel->where($map)->find();
            if($res){
                $expandMap['id']=$res['expand_id'];
                $expandMap['uid']=is_login();
                $expandRes = $this->expandModel->where($expandMap)->find();
                if($expandRes){
                    $res=$this->expandVersionModel->setDel($aId);
                    if($res){
                        $this->success('移除成功',U('Expand/Dev/versionlist',array('id'=>$expandRes['id'])));
                    }else{
                        $this->error('操作失败');
                    }
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('参数错误');
            }
        }
    }


}