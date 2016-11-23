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


class MuucmfController extends AdminController
{
    protected $sysUpdateModel;
    protected $muucmflogModel;
    protected $muucmfDownMode;

    function _initialize()
    {
        parent::_initialize();
        $this->sysUpdateModel = D('Muucmf/Sysupdate'); //系统在线升级模型
        $this->muucmfLogModel = D('Muucmf/MuucmfLog'); //系统更新日志模型
        $this->muucmfDownModel = D('Muucmf/MuucmfDown'); //系统更新日志模型
    }


    public function config()
    {
        $builder = new AdminConfigBuilder();
        $data = $builder->handleConfig();

        $data['OPEN_LOGIN_PANEL'] = $data['OPEN_LOGIN_PANEL'] ? $data['OPEN_LOGIN_PANEL'] : 1;


        $builder->title('页面设置');

        $modules = D('Common/Module')->getAll();
        foreach ($modules as $m) {
            if ($m['is_setup'] == 1 && $m['entry'] != '') {
                if (file_exists(APP_PATH . $m['name'] . '/Widget/MuucmfBlockWidget.class.php')) {
                    $module[] = array('data-id' => $m['name'], 'title' => $m['alias']);
                }
            }
        }
        $module[] = array('data-id' => 'custom', 'title' => '自定义页面');

        $default = array(array('data-id' => 'disable', 'title' => L('_DISABLED_'), 'items' => $module), array('data-id' => 'enable', 'title' =>L('_ENABLED_'), 'items' => array()));
        $builder->keyKanban('BLOCK', L('_DISPLAY_BLOCK_'),L('_TIP_DISPLAY_BLOCK_'));
        $data['BLOCK'] = $builder->parseKanbanArray($data['BLOCK'], $module, $default);
        $builder->group(L('_DISPLAY_BLOCK_'), 'BLOCK');

        $show_blocks = get_kanban_config('BLOCK_SORT', 'enable', array(), 'muucmf');
        //
        $builder->keyText('MUUCMF_DOC_URL', '文档手册地址', 'MuuCmf开发文档手册地址');
        $builder->keyEditor('MUUCMF_UPDATA_INFO', '升级包使用说明', '制作升级版时的注意事项');
        $builder->group('常用配置', 'MUUCMF_DOC_URL,MUUCMF_UPDATA_INFO');
        $builder->buttonSubmit();
        $builder->data($data);
        $builder->display();
    }

    /*
    ***********************************************************************************
    *系统在线更新
    */
    public function sysupdateList($page=1,$r=20)
    {
        $aOrder=I('get.order','create_time','text');
        $aOrder=$aOrder.' desc';
        $aStatus=I('get.status',0,'intval');
        switch($aStatus){
            case 1:
                $map['status']=1;
                break;
            case 2:
                $map['status']=0;
                break;
            default:
                $map['status']=array('in','0,1');
        }

        list($list,$totalCount)=$this->sysUpdateModel->getListByPage($map,$page,'update_time desc','*',$r);

        $builder=new AdminListBuilder();
        $builder->title('系统在线升级列表')
            ->data($list)

            ->buttonNew(U('Muucmf/editSysupdate'))
            ->setSelectPostUrl(U('Muucmf/SysupdateList'))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'全部'),array('id'=>1,'value'=>'启用'),array('id'=>2,'value'=>'禁用')))
            ->select('排序方式：','order','select','','','',array(array('id'=>'create_time','value'=>'创建时间'),array('id'=>'version','value'=>'新版本号')))
            ->keyId()
            ->keyText('version','新版本号')
            ->keyText('enable_version','允许升级的版本号')
            ->keyText('content','详细描述')
            ->setStatusUrl(U('setSysupdateStatus'))
            ->keyStatus()
            ->keyCreateTime()
            ->keyUpdateTime()
            ->keyDoActionEdit('Muucmf/editSysupdate?id=###');

        $builder->pagination($totalCount,$r);
        $builder->explain('升级补丁的制作说明',modC('MUUCMF_UPDATA_INFO',0,'Muucmf'));
        $builder->display();
    }

    public function editSysupdate()
    {
        $aId=I('id',0,'intval');
        $title=$aId?"编辑":"新增";
        if(IS_POST){
            $aId&&$data['id']=$aId;
            $data['uid']=I('post.uid',get_uid(),'intval');
            $data['version']=I('post.version','','op_t');
            $data['enable_version']=I('post.enable_version','','op_t');
            $data['content']=I('post.content','','op_h');
            $data['download_file']=I('post.download_file',0,'intval');
            $data['status']=I('post.status',1,'intval');
           
            $result=$this->sysUpdateModel->editData($data);
            if($result){
                $aId=$aId?$aId:$result;
                $this->success($title.'成功！',U('Muucmf/editSysupdate',array('id'=>$aId)));
            }else{
                $this->error($title.'失败！',$this->sysupdateModel->getError());
            }
        }else{
            if($aId){
                $data=$this->sysUpdateModel->find($aId);
            }
            $builder=new AdminConfigBuilder();
            $builder->title($title.'系统在线升级补丁列表')
                ->data($data)
                ->keyId()
                ->keyReadOnly('uid','发布者')->keyDefault('uid',get_uid())
                ->keyText('version','版本号')
                ->keyText('enable_version','允许升级的版本号')
                ->keyEditor('content','详细描述','','all',array('width' => '600px', 'height' => '400px'))
                ->keySingleFile('download_file','上传文件')
                ->keyStatus()->keyDefault('status',1)
                ->buttonSubmit()->buttonBack()
                ->display();
        }

    }

    public function setSysupdateStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('sysupdate', $ids, $status);
    }

    /**
    * MUUCMF更新日志列表管理
    * @author 大蒙<59262424@qq.com>
    */
    public function muucmfLog($page = 1 , $r = 20)
    {
        $aOrder=I('get.order','create_time','text');
        $aOrder=$aOrder.' desc';
        $aStatus=I('get.status',0,'intval');
        switch($aStatus){
            case 1:
                $map['status']=1;
                break;
            case 2:
                $map['status']=0;
                break;
            default:
                $map['status']=array('in','0,1');
        }

        list($list,$totalCount)=$this->muucmfLogModel->getListByPage($map,$page,'update_time desc','*',$r);

        $builder=new AdminListBuilder();
        $builder->title('系统更新日志列表')
            ->data($list)

            ->buttonNew(U('Muucmf/editMuucmfLog'))
            ->buttonModalPopup(U('Muucmf/setTrueDel'),'','彻底删除',array('data-title'=>'是否彻底删除关键字','target-form'=>'ids'))
            ->setSelectPostUrl(U('Muucmf/SysupdateList'))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'全部'),array('id'=>1,'value'=>'启用'),array('id'=>2,'value'=>'禁用')))
            ->select('排序方式：','order','select','','','',array(array('id'=>'create_time','value'=>'创建时间'),array('id'=>'version','value'=>'新版本号')))
            ->keyId()
            ->keyText('title','标题')
            ->keyUid('uid','作者')
            ->keyText('description','摘要')
            ->keyText('sort','排序')
            ->setStatusUrl(U('setMuucmfLogStatus'))
            ->keyStatus()
            ->keyCreateTime()
            ->keyUpdateTime()
            ->keyDoActionEdit('Muucmf/editMuucmfLog?id=###')
            ->keyDoActionModalPopup('Muucmf/setTrueDel?ids=###','删除','操作',array('data-title'=>'是否彻底删除'));

        $builder->pagination($totalCount,$r)->display();
    }
    /*
    *新增、编辑系统更新日志
    */
    public function editMuucmfLog()
    {
        $aId=I('id',0,'intval');
        $title=$aId?"编辑":"新增";
        if(IS_POST){
            $aId&&$data['id']=$aId;
            $data['uid']=I('post.uid',get_uid(),'intval');
            $data['title']=I('post.title','','op_t');
            $data['description']=I('post.description','','op_t');
            $data['content']=I('post.content','','op_h');
            $data['sort']=I('post.sort',0,'intval');
            $data['status']=I('post.status',1,'intval');
           
            $result=$this->muucmfLogModel->editData($data);
            if($result){
                $aId=$aId?$aId:$result;
                $this->success($title.'成功！',U('Muucmf/editMuucmfLog',array('id'=>$aId)));
            }else{
                $this->error($title.'失败！',$this->muucmfLogModel->getError());
            }
        }else{
            if($aId){
                $data=$this->muucmfLogModel->find($aId);
            }
            $builder=new AdminConfigBuilder();
            $builder->title($title.'系统更新日志')
                ->data($data)
                ->keyId()
                ->keyReadOnly('uid','发布者')->keyDefault('uid',get_uid())
                ->keyText('title','标题')
                ->keyTextArea('description','摘要')
                ->keyEditor('content','详细描述','','all',array('width' => '600px', 'height' => '400px'))
                ->keyText('sort','排序')
                ->keyStatus()
                ->keyDefault('status',1)
                ->buttonSubmit()
                ->buttonBack()
                ->display();
        }
    }

    public function setMuucmfLogStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('muucmfLog', $ids, $status);
    }

    public function setTrueDel($ids)
    {
    if(IS_POST){
        $ids=I('post.ids','','text');
        $ids=explode(',',$ids);

        $res=$this->muucmfLogModel->setTrueDel($ids);
        if($res){
            $this->success('彻底删除成功！',U('Muucmf/muucmflog'));
        }else{
            $this->error('操作失败！'.$this->muucmfLogModel->getError());
        }
    }else{
        $ids=I('ids');
        if(is_array($ids)){
            $ids=implode(',',$ids);
        }
        //dump($ids);exit;
        $this->assign('ids',$ids);
        $this->display(T('Muucmf@admin/settruedel'));
        }
    }

    /**
     * 系统新版本下载列表
     * @param int $page
     * @param int $r
     */
    public function download($page=1,$r=20){
        $aOrder=I('get.order','create_time','text');
        $aOrder=$aOrder.' desc';
        $aStatus=I('get.status',0,'intval');
        switch($aStatus){
            case 1:
                $map['status']=1;
                break;
            case 2:
                $map['status']=0;
                break;
            default:
                $map['status']=array('in','0,1');
        }
        list($list,$totalCount)=$this->muucmfDownModel->getListByPage($map,$page,$aOrder,'*',$r);

        $builder=new AdminListBuilder();
        $builder->title('系统下载列表')
            ->data($list)
            ->buttonNew(U('Muucmf/editMuucmfDown'))
            ->setSelectPostUrl(U('Muucmf/download'))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'全部'),array('id'=>1,'value'=>'启用'),array('id'=>2,'value'=>'禁用')))
            ->select('排序方式：','order','select','','','',array(array('id'=>'create_time','value'=>'创建时间'),array('id'=>'update_time','value'=>'更新时间'),array('id'=>'version','value'=>'版本号')))
            ->keyId()
            ->keyText('title','标题')
            ->keyText('version','版本号')
            ->keyUid('uid','发布人')
            ->keyText('down_num','下载次数')
            ->setStatusUrl(U('setMuucmfDownStatus'))
            ->keyStatus()
            ->keyCreateTime()
            ->keyUpdateTime()
            ->keyDoActionEdit('Muucmf/editMuucmfDown?id=###');

        $builder->pagination($totalCount,$r)->display();
    }

    public function editMuucmfDown()
    {
        $aId=I('id',0,'intval');
        $title=$aId?"编辑":"新增";
        if(IS_POST){
            $aId&&$data['id']=$aId;
            $data['uid']=I('post.uid',get_uid(),'intval');
            $data['title']=I('post.title','','op_t');
            $data['version']=I('post.version','','op_t');
            $data['description']=I('post.description','','op_t');
            $data['content']=I('post.content','','op_h');
            $data['file']=I('post.file',0,'intval');
            $data['status']=I('post.status',1,'intval');

            $result=$this->muucmfDownModel->editData($data);
            if($result){
                $aId=$aId?$aId:$result;
                $this->success($title.'成功！',U('Muucmf/editMuucmfDown',array('id'=>$aId)));
            }else{
                $this->error($title.'失败！',$this->muucmfDownModel->getError());
            }
        }else{
            if($aId){
                $data=$this->muucmfDownModel->find($aId);
            }
            $builder=new AdminConfigBuilder();
            $builder->title($title.'系统下载')
                ->data($data)
                ->keyId()
                ->keyReadOnly('uid','发布者')->keyDefault('uid',get_uid())
                ->keyText('title','标题')
                ->keyText('version','版本号')
                ->keyTextArea('description','摘要')
                ->keyEditor('content','详细描述','','all',array('width' => '600px', 'height' => '400px'))
                ->keySingleFile('file','系统打包文件','推荐打包为ZIP')
                ->keyStatus()
                ->keyDefault('status',1)
                ->buttonSubmit()
                ->buttonBack()
                ->display();
        }
    }

    /**
     * @param $ids
     * @param $status
     */
    public function setMuucmfDownStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('muucmfDown', $ids, $status);
    }
}
