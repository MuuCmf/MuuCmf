<?php
/**
 * Date: 16-4-26
 * Time: 上午10:21
 * @author 大蒙
 */

namespace Admin\Controller;


use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;
use Common\Model\ContentHandlerModel;

class PortfolioController extends AdminController{

    protected $portfolioModel;
    protected $portfolioDetailModel;
    protected $portfolioCategoryModel;

    function _initialize()
    {
        parent::_initialize();
        $this->portfolioModel = D('Portfolio/Portfolio');
        $this->portfolioDetailModel = D('Portfolio/PortfolioDetail');
        $this->portfolioCategoryModel = D('Portfolio/PortfolioCategory');
    }

    public function portfolioCategory()
    {
        //显示页面
        $builder = new AdminTreeListBuilder();

        $tree = $this->portfolioCategoryModel->getTree(0, 'id,title,sort,pid,status');

        $builder->title('作品集分类管理')
            ->suggest('禁用、删除分类时会将分类下的文章转移到默认分类下')
            ->buttonNew(U('Portfolio/add'))
            ->data($tree)
            ->display();
    }

    /**分类添加
     * @param int $id
     * @param int $pid
     */
    public function add($id = 0, $pid = 0)
    {
        $title=$id?"编辑":"新增";
        if (IS_POST) {
            if ($this->portfolioCategoryModel->editData()) {
                S('SHOW_EDIT_BUTTON',null);
                $this->success($title.'成功。', U('Portfolio/portfolioCategory'));
            } else {
                $this->error($title.'失败!'.$this->portfolioCategoryModel->getError());
            }
        } else {
            $builder = new AdminConfigBuilder();

            if ($id != 0) {
                $data = $this->portfolioCategoryModel->find($id);
            } else {
                $father_category_pid=$this->portfolioCategoryModel->where(array('id'=>$pid))->getField('pid');
                if($father_category_pid!=0){
                    $this->error('分类不能超过二级！');
                }
            }
            if($pid!=0){
                $categorys = $this->portfolioCategoryModel->where(array('pid'=>0,'status'=>array('egt',0)))->select();
            }
            $opt = array();
            foreach ($categorys as $category) {
                $opt[$category['id']] = $category['title'];
            }
            $builder->title($title.'分类')
                ->data($data)
                ->keyId()
                ->keyText('title', '标题')
                ->keySelect('pid', '父分类', '选择父级分类', array('0' => '顶级分类') + $opt)
                ->keyDefault('pid',$pid)
                ->keyRadio('can_post','前台是否可发布','',array(0=>'否',1=>'是'))
                ->keyDefault('can_post',1)
                ->keyRadio('need_audit','前台发布是否需要审核','',array(0=>'否',1=>'是'))
                ->keyDefault('need_audit',1)
                ->keyInteger('sort','排序')->keyDefault('sort',0)
                ->keyStatus()
                ->keyDefault('status',1)
                ->buttonSubmit(U('Portfolio/add'))
                ->buttonBack()
                ->display();
        }

    }

    /**
     * 设置分类状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     */
    public function setStatus($ids, $status)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        if(in_array(1,$ids)){
            $this->error('id为 1 的分类是网站基础分类，不能被禁用、删除！');
        }
        if($status==0||$status==-1){
            $map['category']=array('in',$ids);
            $this->portfolioModel->where($map)->setField('category',1);
        }
        $builder = new AdminListBuilder();
        $builder->doSetStatus('portfolioCategory', $ids, $status);
    }
//分类管理end

    public function config()
    {
        $builder=new AdminConfigBuilder();
        $data=$builder->handleConfig();
        $default_position=<<<str
1:推荐
str;

        $builder->title('作品集基础设置')
            ->data($data);

        $builder->keyTextArea('PORTFOLIO_SHOW_POSITION','展示位配置')
            ->keyDefault('PORTFOLIO_SHOW_POSITION',$default_position)
            ->keyText('PORTFOLIO_SHOW_TITLE', '标题名称', '在首页展示块的标题')
            ->keyDefault('PORTFOLIO_SHOW_TITLE','热门作品')
            ->keyText('PORTFOLIO_SHOW_DESCRIPTION', '简短描述', '精简的描述模块内容')
            ->keyText('PORTFOLIO_SHOW_COUNT', '显示作品的个数', '只有在网站首页模块中启用了作品模块之后才会显示')
            ->keyDefault('PORTFOLIO_SHOW_COUNT',4)
            ->keyRadio('PORTFOLIO_SHOW_TYPE', '作品的筛选范围', '', array('1' => '后台推荐', '0' => '全部'))
            ->keyDefault('PORTFOLIO_SHOW_TYPE',0)
            ->keyRadio('PORTFOLIO_SHOW_ORDER_FIELD', '排序值', '展示模块的数据排序方式', array('view' => '阅读数', 'create_time' => '发表时间', 'update_time' => '更新时间'))
            ->keyDefault('PORTFOLIO_SHOW_ORDER_FIELD','view')
            ->keyRadio('PORTFOLIO_SHOW_ORDER_TYPE', '排序方式', '展示模块的数据排序方式', array('desc' => '倒序，从大到小', 'asc' => '正序，从小到大'))
            ->keyDefault('PORTFOLIO_SHOW_ORDER_TYPE','desc')
            ->keyText('PORTFOLIO_SHOW_CACHE_TIME', '缓存时间', '默认600秒，以秒为单位')
            ->keyDefault('PORTFOLIO_SHOW_CACHE_TIME','600')

            ->group('基本配置', 'PORTFOLIO_SHOW_POSITION')
            ->group('首页展示配置', 'PORTFOLIO_SHOW_COUNT,PORTFOLIO_SHOW_TITLE,PORTFOLIO_SHOW_DESCRIPTION,PORTFOLIO_SHOW_TYPE,PORTFOLIO_SHOW_ORDER_TYPE,PORTFOLIO_SHOW_ORDER_FIELD,PORTFOLIO_SHOW_CACHE_TIME')
            ->groupLocalComment('本地评论配置','index')
            ->buttonSubmit()->buttonBack()
            ->display();
    }


    //作品列表start
    public function index($page=1,$r=20)
    {
        $aCate=I('cate',0,'intval');
        if($aCate){
            $cates=$this->portfolioCategoryModel->getCategoryList(array('pid'=>$aCate));
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

        list($list,$totalCount)=$this->portfolioModel->getListByPage($map,$page,'update_time desc','*',$r);
        $category=$this->portfolioCategoryModel->getCategoryList(array('status'=>array('egt',0)),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
        }
        unset($val);

        $optCategory=$category;
        foreach($optCategory as &$val){
            $val['value']=$val['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();
        $builder->title('作品列表')
            ->data($list)
            ->setSelectPostUrl(U('Admin/Portfolio/index'))
            ->select('','cate','select','','','',array_merge(array(array('id'=>0,'value'=>'全部')),$optCategory))
            ->select('推荐位：','pos','select','','','',array_merge(array(array('id'=>0,'value'=>'全部(含未推荐)')),$positions))
            ->buttonNew(U('Portfolio/editPortfolio'))
            ->buttonModalPopup(U('Portfolio/doAudit'),null,'审核不通过',array('data-title'=>'设置审核失败原因','target-form'=>'ids'))
            ->keyId()->keyUid()->keyText('title','标题')
            ->keyText('category','分类')->keyText('description','摘要')
            ->keyText('sort','排序')
            ->keyStatus()
            ->keyCreateTime()
            ->keyUpdateTime()
            ->keyDoActionEdit('Portfolio/editPortfolio?id=###');

        $builder->ajaxButton(U('Portfolio/setDel'),'','回收站')->keyDoAction('Portfolio/setDel?ids=###','回收站');
        $builder->pagination($totalCount,$r)
        ->display();
    }

    //待审核列表
    public function audit($page=1,$r=20)
    {
        $aAudit=I('audit',0,'intval');
        if($aAudit==2){
            $map['status']=array('in',array(0,2));
        }elseif($aAudit==1){
            $map['status']=0;
        }else{
            $map['status']=2;
        }
        list($list,$totalCount)=$this->portfolioModel->getListByPage($map,$page,'update_time desc','*',$r);
        $cates=array_column($list,'category');
        $category=$this->portfolioCategoryModel->getCategoryList(array('id'=>array('in',$cates),'status'=>1),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();

        $builder->title('审核列表（审核通过的不在该列表中）')
            ->data($list)
            ->setStatusUrl(U('Portfolio/setPortfolioStatus'))
            ->buttonEnable(null,'审核通过')
            ->buttonModalPopup(U('Portfolio/doAudit'),null,'审核不通过',array('data-title'=>'设置审核失败原因','target-form'=>'ids'))
            ->setSelectPostUrl(U('Admin/Portfolio/audit'))
            ->select('','audit','select','','','',array(array('id'=>0,'value'=>'待审核'),array('id'=>1,'value'=>'审核失败'),array('id'=>2,'value'=>'全部审核')))
            ->keyId()
            ->keyUid()
            ->keyText('title','标题')
            ->keyText('category','分类')
            ->keyText('description','摘要')
            ->keyText('sort','排序');
        if($aAudit==1){
            $builder->keyText('reason','审核失败原因');
        }
        $builder->keyCreateTime()->keyUpdateTime()
            ->keyDoActionEdit('Portfolio/editPortfolio?id=###')
            ->pagination($totalCount,$r)
            ->display();
    }

    //回收站列表
    public function recycle($page=1,$r=20)
    {

        $map['status']=-1;

        list($list,$totalCount)=$this->portfolioModel->getListByPage($map,$page,'update_time desc','*',$r);
        $cates=array_column($list,'category');
        $category=$this->portfolioCategoryModel->getCategoryList(array('id'=>array('in',$cates),'status'=>1),1);
        $category=array_combine(array_column($category,'id'),$category);
        foreach($list as &$val){
            $val['category']='['.$val['category'].'] '.$category[$val['category']]['title'];
        }
        unset($val);

        $builder=new AdminListBuilder();

        $builder->title('回收站列表')
            ->data($list)
            ->setStatusUrl(U('Portfolio/setPortfolioStatus'))
            ->buttonEnable(null,'审核通过')
            ->setSelectPostUrl(U('Admin/Portfolio/recycle'))

            ->keyId()
            ->keyUid()
            ->keyText('title','标题')
            ->keyText('category','分类')
            ->keyText('description','摘要')
            ->keyText('sort','排序')
            ->keyCreateTime()
            ->keyUpdateTime();

        $builder->keyDoActionEdit('Portfolio/editPortfolio?id=###')
            ->buttonModalPopup(U('Portfolio/setTrueDel'),'','彻底删除',array('data-title'=>'是否彻底删除','target-form'=>'ids'))
            ->pagination($totalCount,$r)
            ->display();
    }

    /**
     * 审核失败原因设置
     */
    public function doAudit()
    {
        if(IS_POST){
            $ids=I('post.ids','','text');
            $ids=explode(',',$ids);
            $reason=I('post.reason','','text');
            $res=$this->portfolioModel->where(array('id'=>array('in',$ids)))->setField(array('reason'=>$reason,'status'=>-1));
            if($res){
                $result['status']=1;
                $result['url']=U('Admin/Portfolio/audit');
                //发送消息
                $messageModel=D('Common/Message');
                foreach($ids as $val){
                    $portfolio=$this->portfolioModel->getData($val);
                    $tip = '你的发布的【'.$portfolio['title'].'】审核失败，失败原因：'.$reason;
                    $messageModel->sendMessage($portfolio['uid'], '作品发布审核失败！',$tip,  'Portfolio/Index/detail',array('id'=>$val), is_login(), 2);
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
            $this->display(T('Portfolio@Admin/audit'));
        }
    }

    public function setPortfolioStatus($ids,$status=1)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $builder = new AdminListBuilder();
        S('portfolio_home_data',null);
        //发送消息
        $messageModel=D('Common/Message');
        foreach($ids as $val){
            $portfolio=$this->portfolioModel->getData($val);
            $tip = '你的发布的【'.$portfolio['title'].'】审核通过。';
            $messageModel->sendMessage($portfolio['uid'],'作品发布审核通过！', $tip,  'Portfolio/Index/detail',array('id'=>$val), is_login(), 2);
        }
        //发送消息 end
        $builder->doSetStatus('Portfolio', $ids, $status);
    }

    public function editPortfolio()
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
            $data['cover']=I('post.cover','','op_t');
            $data['linkinfo']=I('post.linkinfo','','op_t');
            $data['view']=I('post.view',0,'intval');
            $data['comment']=I('post.comment',0,'intval');
            $data['collection']=I('post.collection',0,'intval');
            $data['sort']=I('post.sort',0,'intval');
            $data['status']=I('post.status',1,'intval');
            $data['source']=I('post.source','','op_t');
            $data['position']=0;
            $position=I('post.position','','op_t');
            $position=explode(',',$position);
            foreach($position as $val){
                $data['position']+=intval($val);
            }
            $this->_checkOk($data);
            $result=$this->portfolioModel->editData($data);
            if($result){
                S('portfolio_home_data',null);
                $aId=$aId?$aId:$result;
                $this->success($title.'成功！',U('Portfolio/editPortfolio',array('id'=>$aId)));
            }else{
                $this->error($title.'失败！',$this->portfolioModel->getError());
            }
        }else{
            $position_options=$this->_getPositions();
            if($aId){
                $data=$this->portfolioModel->find($aId);
                $detail=$this->portfolioDetailModel->find($aId);
                $data['content']=$detail['content'];
                $position=array();
                foreach($position_options as $key=>$val){
                    if($key&$data['position']){
                        $position[]=$key;
                    }
                }
                $data['position']=implode(',',$position);
            }
            $category=$this->portfolioCategoryModel->getCategoryList(array('status'=>array('egt',0)),1);
            $options=array();
            foreach($category as $val){
                $options[$val['id']]=$val['title'];
            }
            $builder=new AdminConfigBuilder();
            $builder->title($title.'作品')
                    ->data($data)
                    ->keyId()
                    ->keyReadOnly('uid','发布者')->keyDefault('uid',get_uid())
                    ->keyText('title','标题')
                    ->keyText('keywords','关键字','多个关键字用（,）分隔')
                    ->keyEditor('content','内容','','all',array('width' => '700px', 'height' => '400px'))
                    ->keySelect('category','分类','',$options)
                    ->keyTextArea('description','摘要')
                    ->keyMultiImage('cover','封面')
                    ->keyText('linkinfo','链接')
                    ->keyInteger('view','阅读量')
                    ->keyDefault('view',0)
                    ->keyInteger('comment','评论数')
                    ->keyDefault('comment',0)
                    ->keyInteger('collection','收藏量')
                    ->keyDefault('collection',0)
                    ->keyInteger('sort','排序')
                    ->keyDefault('sort',0)
                    ->keyCheckBox('position','推荐位','多个推荐，则将其推荐值相加',$position_options)
                    ->keyStatus()
                    ->keyDefault('status',1)

                    ->group('基础','id,uid,title,keywords,cover,content,category,linkinfo')
                    ->group('扩展','description,view,comment,sort,position,status')

                    ->buttonSubmit()->buttonBack()
                    ->display();
        }
    }


    public function setDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $res=$this->portfolioModel->setDel($ids);
        if($res){
            S('portfolio_home_data',null);
            $this->success('操作成功！',U('Portfolio/index'));
        }else{
            $this->error('操作失败！'.$this->portfolioModel->getError());
        }
    }
    //真实删除
    public function setTrueDel($ids)
    {
    if(IS_POST){
        $ids=I('post.ids','','text');
        $ids=explode(',',$ids);
        //!is_array($ids)&&$ids=explode(',',$ids);
        $res=$this->portfolioModel->setTrueDel($ids);
        if($res){
            S('portfolio_home_data',null);
            $this->success('彻底删除成功！',U('Portfolio/recycle'));
        }else{
            $this->error('操作失败！'.$this->portfolioModel->getError());
        }
    }else{
        $ids=I('ids');
            $ids=implode(',',$ids);
            $this->assign('ids',$ids);
            $this->display(T('Portfolio@Admin/truedel'));
        }
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
        $positons=modC('PORTFOLIO_SHOW_POSITION',$default_position,'Portfolio');
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
} 