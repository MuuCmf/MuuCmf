<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;
use Common\Model\ContentHandlerModel;


class ExpandController extends AdminController
{
    protected $expandModel;
    protected $expandCategoryModel;
    protected $expandRecordsModel;
    protected $expandVersionModel;
    protected $expandDevauthModel;

    function _initialize()
    {
        parent::_initialize();
        $this->expandModel = D('Expand/Expand');
        $this->expandCategoryModel = D('Expand/ExpandCategory');
        $this->expandRecordsModel = D('Expand/ExpandRecords');
        $this->expandVersionModel = D('Expand/ExpandVersion'); //系统在线升级模型
        $this->expandDevauthModel = D('Expand/ExpandDevauth');
    }


    public function config()
    {
        $score_list = D('Ucenter/Score')->getTypeList(array('status' => 1));
        $score_type=array();
        foreach($score_list as $val){
            $score_type=array_merge($score_type,array('score'.$val['id']=>$val['title']));
        }
        $builder=new AdminConfigBuilder();
                $data=$builder->handleConfig();
                $default_position=<<<str
1:推荐
str;

        $builder->title('应用商店设置')
                ->data($data);

        $builder->keyText('EXPAND_CONFIG_ORDERPX', '订单号前缀', '')
                ->keyCheckBox('EXPAND_CONFIG_SCORE','允许支付的积分类型','',$score_type)
                ->keySelect('EXPAND_CONFIG_ONLINEPAY','是否开通在线支付','',array(0=>'否',1=>'是'))
                ->keyText('EXPAND_CONFIG_RESULTURL', '支付成功后的返回地址', '支付成功页返回地址（通常设置为订单列表）')
                ->keyTextArea('EXPAND_SHOW_POSITION','展示位配置')
                ->keyDefault('EXPAND_SHOW_POSITION',$default_position)
                ->keyText('EXPAND_SHOW_TITLE', '标题名称', '在首页展示块的标题')
                ->keyDefault('EXPAND_SHOW_TITLE','热门应用')
                ->keyText('EXPAND_SHOW_DESCRIPTION', '简短描述', '精简的描述模块内容')
                ->keyText('EXPAND_SHOW_COUNT', '显示应用的个数', '只有在网站首页模块中启用了应用模块之后才会显示')
                ->keyDefault('EXPAND_SHOW_COUNT',4)
                ->keyRadio('EXPAND_SHOW_TYPE', '应用的筛选范围', '', array('1' => '后台推荐', '0' => '全部'))
                ->keyDefault('EXPAND_SHOW_TYPE',0)
                ->keyRadio('EXPAND_SHOW_ORDER_FIELD', '排序值', '展示模块的数据排序方式', array('view' => '阅读数', 'download_num' => '下载数','create_time' => '发表时间', 'update_time' => '更新时间'))
                ->keyDefault('EXPAND_SHOW_ORDER_FIELD','view')
                ->keyRadio('EXPAND_SHOW_ORDER_TYPE', '排序方式', '展示模块的数据排序方式', array('desc' => '倒序，从大到小', 'asc' => '正序，从小到大'))
                ->keyDefault('EXPAND_SHOW_ORDER_TYPE','desc')
                ->keyText('EXPAND_SHOW_CACHE_TIME', '缓存时间', '默认600秒，以秒为单位')
                ->keyDefault('EXPAND_SHOW_CACHE_TIME','600')
                ->keyEditor('EXPAND_SHOW_HELP', '应用商店的使用帮助文档','','all',array('width' => '100%', 'height' => '400px'))
                ->keyEditor('EXPAND_SHOW_DEVAG', '开发者认证协议','','all',array('width' => '100%', 'height' => '400px'))

                ->group('基本配置', 'EXPAND_CONFIG_SCORE,EXPAND_CONFIG_ORDERPX,EXPAND_CONFIG_ONLINEPAY,EXPAND_CONFIG_RESULTURL,EXPAND_SHOW_POSITION')
                ->group('首页展示配置', 'EXPAND_SHOW_COUNT,EXPAND_SHOW_TITLE,EXPAND_SHOW_DESCRIPTION,EXPAND_SHOW_TYPE,EXPAND_SHOW_ORDER_TYPE,EXPAND_SHOW_ORDER_FIELD,EXPAND_SHOW_CACHE_TIME')
                ->group('帮助文档', 'EXPAND_SHOW_HELP')
                ->group('开发者协议', 'EXPAND_SHOW_DEVAG')
                ->groupLocalComment('本地评论配置','index')
                ->buttonSubmit()
                ->buttonBack()
                ->display();
    }

    /*
    **扩展分类管理
    */
    public function expandCategory() 
    {
        //显示页面
        $builder = new AdminTreeListBuilder();
        $tree = $this->expandCategoryModel->getTree(0, 'id,title,sort,pid,status');
        $builder->title('应用分类管理')
                ->suggest('禁用、删除分类时会将分类下的内容转移到默认分类下')
                ->buttonNew(U('Expand/addExpandCategory'))
                ->setModel('ExpandCategory') //自定义下链接的命名
                ->data($tree)
                ->display();
    }
    /*
    **编辑、增加分类
    */
    public function addExpandCategory()
    {   
        $id=I('id',0,'intval');
        $title=$id?"编辑":"新增";
        if (IS_POST) {
            if ($this->expandCategoryModel->editData()) {
                S('SHOW_EDIT_BUTTON',null);
                $this->success($title.'成功。', U('Expand/expandCategory'));
            } else {
                $this->error($title.'失败!'.$this->expandCategoryModel->getError());
            }
        } else {
            $builder = new AdminConfigBuilder();

            if ($id != 0) {
                $data = $this->expandCategoryModel->find($id);
            } else {
                $father_category_pid=$this->expandCategoryModel->where(array('id'=>$pid))->getField('pid');
                if($father_category_pid!=0){
                    $this->error('分类不能超过二级！');
                }
            }
            if($pid!=0){
                $categorys = $this->expandCategoryModel->where(array('pid'=>0,'status'=>array('egt',0)))->select();
            }
            $opt = array();
            foreach ($categorys as $category) {
                $opt[$category['id']] = $category['title'];
            }
            $builder->title($title.'分类')
                ->data($data)
                ->keyId()->keyText('title', '标题')
                ->keySelect('pid', '父分类', '选择父级分类', array('0' => '顶级分类') + $opt)->keyDefault('pid',$pid)
                ->keyRadio('can_post','前台是否可发布','',array(0=>'否',1=>'是'))->keyDefault('can_post',1)
                ->keyRadio('need_audit','前台发布是否需要审核','',array(0=>'否',1=>'是'))->keyDefault('need_audit',1)
                ->keyInteger('sort','排序')->keyDefault('sort',0)
                ->keyStatus()->keyDefault('status',1)
                ->buttonSubmit(U('Expand/addExpandCategory'))->buttonBack()
                ->display();
        }
    }
    /**
     * 设置分类状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     */
    public function setExpandCategoryStatus($ids, $status)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        if(in_array(1,$ids)){
            $this->error('id为 1 的分类是基础分类，不能被禁用、删除！');
        }
        if($status==0||$status==-1){
            $map['category']=array('in',$ids);
            $this->expandModel->where($map)->setField('category',1);
        }
        $builder = new AdminListBuilder();
        $builder->doSetStatus('expandCategory', $ids, $status);
    }
    /*
    **扩展列表
    */
    public function index($page=1,$r=20)
    {
        $aCate=I('cate',0,'intval');
        if($aCate){
            $cates=$this->expandCategoryModel->getCategoryList(array('pid'=>$aCate));
            if(count($cates)){
                $cates=array_column($cates,'id');
                $cates=array_merge(array($aCate),$cates);
                $map['category']=array('in',$cates);
            }else{
                $map['category']=$aCate;
            }
        }

        $aPos=I('pos',0,'intval');
        /* 设置推荐位 */
        if($aPos>0){
            $map[] = "position & {$aPos} = {$aPos}";
        }

        $map['status']=1;

        $positions=$this->_getPositions(1);

        list($list,$totalCount)=$this->expandModel->getListByPage($map,$page,'update_time desc','*',$r);
        $category=$this->expandCategoryModel->getCategoryList(array('status'=>array('egt',0)),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['price'] = '￥'.sprintf("%.2f",$val['price']/100);//将金额单位转成元
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
            $val['icon']=getThumbImageById($val['icon'],60,60);
        }
        unset($val);

        $optCategory=$category;
        foreach($optCategory as &$val){
            $val['value']=$val['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();
        $builder->title('应用列表')
            ->data($list)
            ->setSelectPostUrl(U('Admin/Expand/index'))
            ->select('','cate','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$optCategory))
            ->select('推荐位：','pos','select','','','',array_merge(array(array('id'=>0,'value'=>'全部(含未推荐)')),$positions))

            ->buttonNew(U('Expand/editExpand'))
            ->buttonModalPopup(U('Expand/doExpandAudit'),null,'审核不通过',array('data-title'=>'设置审核失败原因','target-form'=>'ids'))
            ->keyId()
            ->keyUid()
            ->keyImage('icon','图标')
            ->keyText('title','标题')
            ->keyText('category','分类')
            ->keyText('price','价格（元）')
            ->keyText('download_num','下载次数')
            ->keyText('sort','排序')
            ->keyLink('revisions','版本库','Expand/revisions?id=###')
            ->keyStatus()
            ->keyCreateTime()
            ->keyUpdateTime()
            ->keyDoActionEdit('Expand/editExpand?id=###');

        $builder->ajaxButton(U('Expand/setExpandDel'),'','回收站');
        $builder->pagination($totalCount,$r)->display();
    }

  //待审核列表
    public function expandAudit($page=1,$r=20)
    {
        $aAudit=I('get.audit',0,'intval');
        switch($aAudit){
            case 1:
                $map['status']=0;
                break;
            case 2:
                $map['status']=2;
                break;
            default:
                $map['status']=array('in','0,2');
        }
        list($list,$totalCount)=$this->expandModel->getListByPage($map,$page,'update_time desc','*',$r);
        $cates=array_column($list,'category');
        $category=$this->expandCategoryModel->getCategoryList(array('id'=>array('in',$cates),'status'=>1),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();

        $builder->title('待审核列表')
                ->data($list)
                ->setStatusUrl(U('Expand/setExpandStatus'))
                ->buttonEnable(null,'审核通过')
                ->buttonModalPopup(U('Expand/expandDoAudit'),null,'审核不通过',array('data-title'=>'设置审核失败原因','target-form'=>'ids'))
                ->ajaxButton(U('Expand/setExpandDel'),'','回收站')
                ->setSelectPostUrl(U('Admin/Expand/expandAudit'))
                ->select('','audit','select','','','',array(array('id'=>0,'value'=>'全部待审核'),array('id'=>1,'value'=>'禁用'),array('id'=>2,'value'=>'未审核')))
                ->keyId()
                ->keyUid()
                ->keyText('title','标题')
                ->keyText('category','分类')
                ->keyText('price','价格')
                ->keyText('download_num','下载次数')
                ->keyStatus()
                ->keyCreateTime()
                ->keyUpdateTime();
        if($aAudit==1){
        $builder->keyText('reason','审核失败原因');
        }
        $builder->keyDoActionEdit('Expand/editExpand?id=###')
                ->pagination($totalCount,$r)
                ->display();
    }

    //回收站列表
    public function expandRecycle($page=1,$r=20)
    {

        $map['status']=-1;

        list($list,$totalCount)=$this->expandModel->getListByPage($map,$page,'update_time desc','*',$r);
        $cates=array_column($list,'category');
        $category=$this->expandCategoryModel->getCategoryList(array('id'=>array('in',$cates),'status'=>1),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();

        $builder->title('回收站列表')
            ->data($list)
            ->setStatusUrl(U('Expand/setExpandStatus'))
            ->buttonEnable(null,'审核通过')
            ->setSelectPostUrl(U('Admin/Expand/expandRecycle'))

            ->keyId()
            ->keyUid()
            ->keyText('title','标题')
            ->keyText('category','分类')
            ->keyText('price','价格')
            ->keyText('download_num','下载次数')
            ->keyText('sort','排序')
            ->keyStatus()
            ->keyCreateTime()
            ->keyUpdateTime();

        $builder->keyDoActionEdit('Expand/editExpand?id=###')
            ->buttonModalPopup(U('Expand/setExpandTrueDel'),'','彻底删除',array('data-title'=>'是否彻底删除','target-form'=>'ids'))
            ->pagination($totalCount,$r)
            ->display();
    }

    /**
     * 审核失败原因设置
     */
    public function doExpandAudit()
    {
        if(IS_POST){
            $ids=I('post.ids','','text');
            $ids=explode(',',$ids);
            $reason=I('post.reason','','text');
            $res=$this->expandModel->where(array('id'=>array('in',$ids)))->setField(array('reason'=>$reason,'status'=>-1));
            if($res){
                $result['status']=1;
                $result['url']=U('Admin/Expand/expandAudit');
                //发送消息
                $messageModel=D('Common/Message');
                foreach($ids as $val){
                    $expand=$this->expandModel->getData($val);
                    $tip = '你发布的【'.$expand['title'].'】审核失败，失败原因：'.$reason;
                    $messageModel->sendMessage($expand['uid'], '审核失败！',$tip,  'Expand/Expand/detail',array('id'=>$val), is_login(), 2);
                }
                //发送消息 end
            }else{
                $result['status']=0;
                $result['info']='操作失败！';
            }
            $this->ajaxReturn($result);
        }else{
            $ids=I('ids');
            $ids=implode(',',$ids);
            $this->assign('ids',$ids);
            $this->display(T('Expand@Admin/expandaudit'));
        }
    }

    public function setExpandStatus($ids,$status=1)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $builder = new AdminListBuilder();
        S('expand_home_data',null);
        //发送消息
        $messageModel=D('Common/Message');
        foreach($ids as $val){
            $expand=$this->expandModel->getData($val);
            $tip = '你的投稿【'.$expand['title'].'】审核通过。';
            $messageModel->sendMessage($expand['uid'],'审核通过！', $tip,  'Expand/index/detail',array('id'=>$val), is_login(), 2);
        }
        //发送消息 end
        $builder->doSetStatus('Expand', $ids, $status);
    }

    public function editExpand()
    {
        $aId=I('id',0,'intval');
        $title=$aId?"编辑":"新增";
        if(IS_POST){

            $aId&&$data['id']=$aId;
            $data['uid']=I('post.uid',get_uid(),'intval');
            $data['title']=I('post.title','','op_t');
            $data['keywords']=I('post.keywords','','op_t');
            $data['content']=I('post.content','','op_h');
            $data['category']=I('post.category',0,'intval');
            $data['description']=I('post.description','','op_t');
            $data['icon']=I('post.icon',0,'intval');
            $data['cover']=I('post.cover','','op_h');
            $data['price']=I('post.price',0,'intval');
            $data['view']=I('post.view',0,'intval');
            $data['comment']=I('post.comment',0,'intval');
            $data['collection']=I('post.collection',0,'intval');
            $data['sort']=I('post.sort',0,'intval');
            $data['status']=I('post.status',1,'intval');
            $data['position']=0;
            //版本库数据


            //获取推荐位
            $position=I('post.position','','op_t');
            $position=explode(',',$position);
            foreach($position as $val){
                $data['position']+=intval($val);
            }
            $this->_checkOk($data);
            $result=$this->expandModel->editData($data);

            if($result){
                S('expand_home_data',null);
                $aId=$aId?$aId:$result;
                $this->success($title.'成功！',U('Expand/editExpand',array('id'=>$aId)));
            }else{
                $this->error($title.'失败！',$this->expandModel->getError());
            }
        }else{
            $position_options=$this->_getPositions();
            if($aId){
                $data=$this->expandModel->find($aId);
                $position=array();
                foreach($position_options as $key=>$val){
                    if($key&$data['position']){
                        $position[]=$key;
                    }
                }
                $data['position']=implode(',',$position);
            }
            $category=$this->expandCategoryModel->getCategoryList(array('status'=>array('egt',0)),1);
            $options=array();
            foreach($category as $val){
                $options[$val['id']]=$val['title'];
            }
            $builder=new AdminConfigBuilder();
            $builder->title($title.'扩展')
                ->data($data)
                ->keyId()
                ->keyReadOnly('uid','发布者')->keyDefault('uid',get_uid())
                ->keyText('title','标题')
                ->keyText('keywords','关键字','多个关键字用（,）分隔')
                ->keyEditor('content','详细描述','','all',array('width' => '100%', 'height' => '400px'))
                ->keySelect('category','应用分类','',$options)

                ->keyTextArea('description','应用简介')
                ->keySingleImage('icon','图标','官方建议400x400正方形图片')
                ->keyMultiImage('cover','预览图','支持多图')
                ->keyInteger('price','价格','需要多少积分可下载')
                ->keyInteger('view','阅读量')
                ->keyDefault('view',0)
                ->keyInteger('comment','评论数')
                ->keyDefault('comment',0)
                ->keyInteger('collection','收藏量')
                ->keyDefault('collection',0)
                ->keyInteger('sort','排序')->keyDefault('sort',0)
                ->keyCheckBox('position','推荐位','多个推荐，则将其推荐值相加',$position_options)
                ->keyStatus()->keyDefault('status',1)

                ->group('基础','id,uid,title,keywords,icon,cover,price,content,category')
                ->group('扩展','description,view,comment,sort,position,status')

                ->buttonSubmit()->buttonBack()
                ->display();
        }
    }

    public function setExpandDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $res=$this->expandModel->setDel($ids);
        if($res){
            S('expand_home_data',null);
            $this->success('操作成功！',U('Expand/index'));
        }else{
            $this->error('操作失败！'.$this->expandModel->getError());
        }
    }
    //真实删除
    public function setExpandTrueDel($ids)
    {
    if(IS_POST){
        $ids=I('post.ids','','text');
        $ids=explode(',',$ids);
        //!is_array($ids)&&$ids=explode(',',$ids);
        $res=$this->expandModel->setTrueDel($ids);
        if($res){
            S('expand_home_data',null);
            $this->success('彻底删除成功！',U('Expand/expandRecycle'));
        }else{
            $this->error('操作失败！'.$this->expandModel->getError());
        }
    }else{
        $ids=I('ids');
            $ids=implode(',',$ids);
            $this->assign('ids',$ids);
            $this->display(T('Expand@Admin/expandtruedel'));
        }
    }
    /**
     * 应用版本库列表管理
     * @param  integer $page [description]
     * @param  integer $r    [description]
     * @return [type]        [description]
     */
    public function revisions($page=1,$r=20)
    {
        $expand_id = I('id',0,'intval');
        if($expand_id){
            $map['expand_id'] = $expand_id;
        }
        $aStatus=I('get.status',0,'intval');
        switch($aStatus){
            case 1:
                $map['status']=1;
                break;
            case 2:
                $map['status']=2;
                break;
            case -2:
                $map['status']=0;//禁用
                break;
            case -1:
                $map['status']=-1;
                break;
            default:
                $map['status']=array('in','0,1,-1,2');
        }
        list($list,$totalCount)=$this->expandVersionModel->getListByPage($map,$page,'update_time desc','*',$r);
        foreach($list as &$val){
            $expand = $this->expandModel->getData($val['expand_id']);
            $val['expand_title']='['.$val['expand_id'].'] '.$expand['title'];
        }

        $builder=new AdminListBuilder();
        $builder->title('版本库管理')
                ->data($list)
                

            ->buttonNew(U('Expand/editRevisions?expand_id='.$expand_id))
            ->setStatusUrl(U('Expand/setRevisionsStatus'))
            ->buttonEnable(null,'审核通过')
            ->buttonModalPopup(U('Expand/doRevisionsAudit'),null,'审核不通过',array('data-title'=>'设置审核失败原因','target-form'=>'ids'))
            ->setSelectPostUrl(U('Expand/revisions'))
            ->select('','status','select','','','',array(array('id'=>'0','value'=>'全部'),array('id'=>1,'value'=>'启用'),array('id'=>2,'value'=>'未审核'),array('id'=>-1,'value'=>'回收站'),array('id'=>-2,'value'=>'禁用')))
                ->keyId()
                ->keyText('expand_title','应用ID&标题')
                ->keyText('version','版本号')
                ->keyText('update_log','更新日志')
                ->keyStatus()
                ->pagination($totalCount,$r)
                ->keyDoActionEdit('Expand/editRevisions?id=###')
                ->display();
    }
    /**
     * 应用版本库编辑、新增数据
     * @return [type] [description]
     */
    public function editRevisions()
    {
        $aId=I('id',0,'intval');
        $expandId=I('expand_id',0,'intval');
        $title=$aId?"编辑版本库":"新增版本库";
        if(IS_POST){

            $aId&&$data['id']=$aId;
            $expandId&&$data['expand_id']=$expandId;
            //版本库数据
            $data['version']=I('post.version','','op_t');
            $data['expand_id']=$expandId;
            $data['update_log']=I('post.update_log','','op_t');
            $data['download_file']=I('post.download_file','','op_t');
            $data['status'] = I('status',0,'intval');

            $versionResult=$this->expandVersionModel->editData($data);
            if($versionResult){
                $aId=$aId?$aId:$versionResult;
                $this->success($title.'成功！',U('Expand/editRevisions',array('id'=>$aId)));
            }else{
                $this->error($title.'失败！',$this->expandVersionModel->getError());
            }
        }else{
            if($aId){
                $data=$this->expandVersionModel->find($aId);
                $expand = $this->expandModel->getData($data['expand_id']);
                $data['expand_title'] = $expand['title'];
            }
            if($expandId){
                $expand = $this->expandModel->getData($expandId);
                $data['expand_title'] = $expand['title'];
                $data['expand_id'] = $expand['id'];
            }
                
            unset($v);
            //dump($data);exit;

            $builder=new AdminConfigBuilder();
            $builder->title($title.'-'.$data['expand_title'])
                ->data($data)
                ->keyId()
                ->keyId('expand_id','应用ID')
                ->keyReadOnly('expand_title','应用名称')
                ->keyText('version','版本号')
                ->keyEditor('update_log','更新日志','','',array('width' => '600px', 'height' => '300px'))
                ->keySingleFile('download_file','上传文件')
                ->keyStatus()->keyDefault('status',1)

                ->buttonSubmit()->buttonBack()
                ->display();
        }
    }
    /**
     * 版本库审核通过
     * @param [type]  $ids    [description]
     * @param integer $status [description]
     */
    public function setRevisionsStatus($ids,$status=1)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $builder = new AdminListBuilder();
        //发送消息

        foreach($ids as $val){
            $expand=$this->expandModel->getData($val);
            $tip = '你的提交的【'.$expand['title'].$val['version'].'】版本审核通过。';
            send_message($expand['uid'], '版本审核失败！',$tip,  'Expand/My/revision',array('id'=>$val['expand_id']), is_login(), 'Expand','');
        }
        //发送消息 end
        $builder->doSetStatus('ExpandVersion', $ids, $status);
    }
    /**
     * 版本库审核失败原因
     * @return [type] [description]
     */
    public function doRevisionsAudit()
    {
        if(IS_POST){
            $ids=I('post.ids','','text');
            $ids=explode(',',$ids);
            $reason=I('post.reason','','text');
            $res=$this->expandVersionModel->where(array('id'=>array('in',$ids)))->setField(array('reason'=>$reason,'status'=>0));
            if($res){
                $revision=$this->expandVersionModel->where(array('id'=>array('in',$ids)))->select();
                //发送消息
                foreach($revision as $val){
                    $expand=$this->expandModel->getData($val['expand_id']);
                    $tip = '你发布的【'.$expand['title'].$val['version'].'】版本审核失败，失败原因：'.$reason;

                    /**
                    * @param $to_uids 接收消息的用户们
                    * @param string $title 消息标题
                    * @param string $content 消息内容
                    * @param string $url 消息指向的路径，U函数的第一个参数
                    * @param array $url_args 消息链接的参数，U函数的第二个参数
                    * @param int $from_uid 发送消息的用户
                    * @param string $type 消息类型标识，对应各模块message_config.php中设置的消息类型
                    * @param string $tpl 消息模板标识，对应各模块message_config.php中设置的消息模板
                    **/
                    send_message($expand['uid'], '版本审核失败！',$tip,  'Expand/My/revision',array('id'=>$val['expand_id']), is_login(), 'Expand','');
                }
                unset($val);
                //发送消息 end
                $result['status']=1;
                $result['info']='操作成功！';
                $result['url']=U('Admin/Expand/revisions');
            }else{
                $result['status']=0;
                $result['info']='操作失败！';
            }
            $this->ajaxReturn($result);
        }else{
            $ids=I('ids');
            $ids=implode(',',$ids);
            $this->assign('ids',$ids);
            $this->display(T('Expand@Admin/expandaudit'));
        }
    }
    public function revisionsRecycle()
    {
        $expand_id = I('id',0,'intval');
        if($expand_id){
            $map['expand_id'] = $expand_id;
        }
        $aStatus=I('get.status',0,'intval');
        $map['status']=-1;
        list($list,$totalCount)=$this->expandVersionModel->getListByPage($map,$page,'update_time desc','*',$r);
        foreach($list as &$val){
            $expand = $this->expandModel->getData($val['expand_id']);
            $val['expand_title']='['.$val['expand_id'].'] '.$expand['title'];
        }

        $builder=new AdminListBuilder();
        $builder->title('版本库回收站')
                ->data($list)
            ->setStatusUrl(U('Expand/setRevisionsStatus'))
            ->buttonEnable(null,'审核通过')
                ->keyId()
                ->keyText('expand_title','应用ID&标题')
                ->keyText('version','版本号')
                ->keyText('update_log','更新日志')
                ->keyStatus()
                ->pagination($totalCount,$r)
                ->keyDoActionEdit('Expand/editRevisions?id=###')
                ->display();
    }



    private function _checkOk($data=array()){
        if(!mb_strlen($data['title'],'utf-8')){
            $this->error('标题不能为空！');
        }
        if(mb_strlen($data['content'],'utf-8')<20){
            $this->error('内容不能少于20个字！');
        }
        return true;
    }

    private function _getPositions($type=0)
    {
        $default_position=<<<str
1:推荐
str;
        $positons=modC('EXPAND_SHOW_POSITION',$default_position,'Expand');
        $positons = str_replace("\r", '', $positons);
        $positons = explode("\n", $positons);
        $result=array();
        if($type){
            foreach ($positons as $v) {
                $temp = explode(':', $v);
                $result[] = array('id'=>$temp[0],'value'=>$temp[1]);
            }
        }else{
            foreach ($positons as $v) {
                $temp = explode(':', $v);
                $result[$temp[0]] = $temp[1];
            }
        }

        return $result;
    }

    /*
    **购买应用记录
    */
    public function expandRecord($page=1,$r=20)
    {

        $map['status']=1;
        list($list,$totalCount)=$this->expandRecordsModel->getListByPage($map,$page,'create_time desc','*',$r);
        unset($map);
        foreach($list as &$val){
            $val['amount'] = '￥'.sprintf("%01.2f", $val['amount']/100);//将金额单位分转成元
            $val['expand']=$this->expandRecordsModel->query_expand(array('id','title','description'),$val['expand_id']);
            $val['title'] = $val['expand']['title'];
            if($val['paid']==1){
                $val['paid'] = '已付款';
            }else{
                $val['paid'] = '未付款';
            }
            if($val['payment']!='pingpay'){
                $map['id'] = $val['payment'];
                $score = D('Ucenter/Score')->getType($map);
                $val['payment']=$score['title'];
            }else{
                $val['payment']='在线支付';
            }
        }
        unset($val);
        //dump($list);exit;
        $builder=new AdminListBuilder();
        $builder->title('购买应用列表')
            ->data($list)
            ->setSelectPostUrl(U('Admin/Expand/expandRecord'))

            ->keyId()
            ->keyText('order_no','订单号')
            ->keyUid()
            ->keyUid('add_uid','发布者')
            ->keyText('title','标题')
            ->keyText('amount','金额')
            ->keyText('payment','支付方式')
            ->keyText('paid','状态')
            ->keyCreateTime()
            ->keyTime('pay_time','付款时间');

        $builder->pagination($totalCount,$r)
        ->display();
    }
    /**
     * 开发者列表
     * @param  integer $page [description]
     * @param  integer $r    [description]
     * @return [type]        [description]
     */
    public function devauthList($page=1,$r=20)
    {
        $aAudit=I('get.audit',0,'intval');
        switch($aAudit){
            case 1:
                $map['status']=1;
                break;
            case 2:
                $map['status']=2;
                break;
            case -1:
                $map['status']=-1;
                break;
            default:
                $map['status']=array('in','0,1,2');
        }
        list($list,$toatalCount)=$this->expandDevauthModel->getListByPage($map,$page,'create_time desc','*',$r);
        foreach($list as &$v){
            $v['id']=$v['uid'];
        }
        unset($v);

        $builder=new AdminListBuilder();
        $builder->title('开发者列表')
            ->data($list)
            ->setStatusUrl(U('Expand/setDevauthStatus'))
            ->buttonEnable(null,'审核通过')
            ->buttonModalPopup(U('Expand/devauthDoAudit'),null,'审核不通过',array('data-title'=>'设置审核失败原因','target-form'=>'ids'))
            ->buttonDelete(null,'回收站')
            ->setSelectPostUrl(U('Admin/Expand/devauthList'))
            ->select('','audit','select','','','',array(array('id'=>0,'value'=>'全部'),array('id'=>1,'value'=>'已审核'),array('id'=>2,'value'=>'待审核'),array('id'=>-1,'value'=>'已删除')))
            ->keyUid()
            ->keyText('tname','真实姓名')
            ->keyText('snum','身份证号码')
            ->keyText('description','自我介绍')
            ->keyStatus()
            ->keyCreateTime();

        $builder->pagination($totalCount,$r)
        ->display();
    }

    public function setDevauthStatus($ids,$status)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $AuthGroup = D('AuthGroup');
        if($status==1){
            $tip = '开发者认证审核通过。';
            //发送消息
            foreach($ids as $val){
            send_message($val, '开发者审核通知！',$tip,  'Expand/Dev/devauth','', is_login(), 'Expand','');
            if (!$AuthGroup->addToGroup($val, 3)) {
                $this->error($AuthGroup->getError());
            }
            }
        };
        if($stauts==-1){
            $tip = '开发者权限已禁用。';
            //发送消息
            foreach($ids as $val){
            send_message($val, '开发者审核通知！',$tip,  'Expand/Dev/devauth','', is_login(), 'Expand','');
            if (!$AuthGroup->addToGroup($val, 1)) {
                $this->error($AuthGroup->getError());
            }
            }
        }
        
        $this->doDevauthSetStatus($ids, $status);
    }
    public function devauthDoAudit()
    {
        if(IS_POST){
            $ids=I('post.ids','','text');
            $ids=explode(',',$ids);
            $reason=I('post.reason','','text');
            $res=$this->expandDevauthModel->where(array('uid'=>array('in',$ids)))->setField(array('reason'=>$reason,'status'=>0));
            if($res){
                $AuthGroup = D('AuthGroup');
                //发送消息
                foreach($ids as $val){
                    $tip = '开发者认证审核失败，失败原因：'.$reason;
                    /**
                    * @param $to_uids 接收消息的用户们
                    * @param string $title 消息标题
                    * @param string $content 消息内容
                    * @param string $url 消息指向的路径，U函数的第一个参数
                    * @param array $url_args 消息链接的参数，U函数的第二个参数
                    * @param int $from_uid 发送消息的用户
                    * @param string $type 消息类型标识，对应各模块message_config.php中设置的消息类型
                    * @param string $tpl 消息模板标识，对应各模块message_config.php中设置的消息模板
                    **/
                    send_message($val, '开发者认证审核通知',$tip,'Expand/Dev/devauth','', is_login(), 'Expand','');
                    if (!$AuthGroup->removeFromGroup($val, 3)) {
                        $this->error($AuthGroup->getError());
                    }
                }
                unset($val);
                //发送消息 end
                $result['status']=1;
                $result['info']='操作成功！';
                $result['url']=U('Admin/Expand/devauthlist');
            }else{
                $result['status']=0;
                $result['info']='操作失败！';
            }
            $this->ajaxReturn($result);
        }else{
            $ids=I('ids');
            $ids=implode(',',$ids);
            $this->assign('ids',$ids);
            $this->display(T('Expand@Admin/devauthaudit'));
        }
    }

    private function doDevauthSetStatus($ids, $status = 1)
    {
        $id = array_unique((array)$ids);
        $rs = M('ExpandDevauth')->where(array('uid' => array('in', $id)))->save(array('status' => $status));
        if ($rs === false) {
            $this->error(L('_ERROR_SETTING_') . L('_PERIOD_'));
        }
        $this->success(L('_SUCCESS_SETTING_'), $_SERVER['HTTP_REFERER']);
    }
}
