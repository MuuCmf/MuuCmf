<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM5:41
 */

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;


class EventController extends AdminController
{
    protected $eventModel;
    protected $eventTypeModel;

    function _initialize()
    {
        $this->eventModel = D('Event/Event');
        $this->eventTypeModel = D('Event/EventType');
        parent::_initialize();
    }
    public function config()
    {
        $admin_config = new AdminConfigBuilder();
        $data = $admin_config->handleConfig();

        $admin_config->title('活动基本设置')
            ->keyBool('NEED_VERIFY', '创建活动是否需要审核','默认无需审核')
            ->group('基本配置','NEED_VERIFY')
            ->groupLocalComment('本地评论配置','event')
            ->buttonSubmit('', '保存')->data($data);
        $admin_config->display();
    }
    public function event($page = 1, $r = 10)
    {
        //读取列表
        $map = array('status' => 1);
        $model = $this->eventModel;
        $list = $model->where($map)->page($page, $r)->select();
        unset($li);
        $totalCount = $model->where($map)->count();

        //显示页面
        $builder = new AdminListBuilder();

        $attr['class'] = 'btn ajax-post';
        $attr['target-form'] = 'ids';

        $builder->title('内容管理')
            ->setStatusUrl(U('setEventContentStatus'))->buttonDisable('', '审核不通过')->buttonDelete()->button('设为推荐', array_merge($attr, array('url' => U('doRecommend', array('tip' => 1)))))->button('取消推荐', array_merge($attr, array('url' => U('doRecommend', array('tip' => 0)))))
            ->keyId()->keyLink('title', '标题', 'Event/Index/detail?id=###')->keyUid()->keyCreateTime()->keyStatus()->keyMap('is_recommend', '是否推荐', array(0 => '否', 1 => '是'))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 设置推荐or取消推荐
     * @param $ids
     * @param $tip
     * autor:xjw129xjt
     */
    public function doRecommend($ids, $tip)
    {
        D('Event')->where(array('id' => array('in', $ids)))->setField('is_recommend', $tip);
        $this->success('设置成功', $_SERVER['HTTP_REFERER']);
    }

    /**
     * 审核页面
     * @param int $page
     * @param int $r
     * autor:xjw129xjt
     */
    public function verify($page = 1, $r = 10)
    {
        //读取列表
        $map = array('status' => 0);
        $model = $this->eventModel;
        $list = $model->where($map)->page($page, $r)->select();
        unset($li);
        $totalCount = $model->where($map)->count();

        //显示页面
        $builder = new AdminListBuilder();
        $attr['class'] = 'btn ajax-post';
        $attr['target-form'] = 'ids';
        $builder->title('审核内容')
            ->setStatusUrl(U('setEventContentStatus'))->buttonEnable('', '审核通过')->buttonDelete()
            ->keyId()->keyLink('title', '标题', 'Event/Index/detail?id=###')->keyUid()->keyCreateTime()->keyStatus()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 设置状态
     * @param $ids
     * @param $status
     * autor:xjw129xjt
     */
    public function setEventContentStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        if ($status == 1) {
            foreach ($ids as $id) {
                $content = D('Event')->find($id);
                D('Common/Message')->sendMessage($content['uid'],$title = '专辑内容审核通知', "管理员审核通过了您发布的内容。现在可以在列表看到该内容了。",  'Event/Index/detail', array('id' => $id ), is_login(), 2);
                if (D('Common/Module')->isInstalled('Weibo')) { //安装了微博模块
                    /*同步微博*/
                    $user = query_user(array('username', 'space_link'), $content['uid']);
                    $weibo_content = '管理员审核通过了@' . $user['username'] . ' 的内容：【' . $content['title'] . '】，快去看看吧：' . "http://$_SERVER[HTTP_HOST]" . U('Event/Index/detail', array('id' => $content['id']));
                    $model = D('Weibo/Weibo');
                    $model->addWeibo(is_login(), $weibo_content);
                    /*同步微博end*/
                }
            }

        }
        $builder->doSetStatus('Event', $ids, $status);

    }

    public function contentTrash($page = 1, $r = 10)
    {
        //读取微博列表
        $map = array('status' => -1);
        $model = D('Event');
        $list = $model->where($map)->page($page, $r)->select();
        $totalCount = $model->where($map)->count();

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('内容回收站')
            ->setStatusUrl(U('setEventContentStatus'))->buttonRestore()
            ->keyId()->keyLink('title', '标题', 'Event/Index/detail?id=###')->keyUid()->keyCreateTime()->keyStatus()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


    public function index()
    {

        //显示页面
        $builder = new AdminTreeListBuilder();

        $tree = D('Event/EventType')->getTree(0, 'id,title,sort,pid,status');


        $builder->title('活动分类管理')
            ->buttonNew(U('Event/add'))->setLevel(1)
            ->data($tree)
            ->display();
    }

    public function add($id = 0, $pid = 0)
    {
        if (IS_POST) {
            if ($id != 0) {
                $eventtype = $this->eventTypeModel->create();
                if ($this->eventTypeModel->save($eventtype)) {

                    $this->success('编辑成功。');
                } else {
                    $this->error('编辑失败。');
                }
            } else {
                $eventtype = $this->eventTypeModel->create();
                if ($this->eventTypeModel->add($eventtype)) {

                    $this->success('新增成功。');
                } else {
                    $this->error('新增失败。');
                }
            }

        } else {
            $builder = new AdminConfigBuilder();
            $eventtypes =$this->eventTypeModel->where(array('pid'=>0))->select();
            $opt = array();
            foreach ($eventtypes as $eventtype) {
                $opt[$eventtype['id']] = $eventtype['title'];
            }
            if ($id != 0) {
                $eventtype = $this->eventTypeModel->find($id);
            } else {
                $eventtype = array('pid' => $pid, 'status' => 1);
            }


            $builder->title('新增分类')->keyId()->keyText('title', '标题')
                ->keyStatus()->keyCreateTime()->keyUpdateTime()
                ->data($eventtype)
                ->buttonSubmit(U('Event/add'))->buttonBack()->display();
        }

    }

    public function operate($type = 'move', $from = 0)
    {
        $builder = new AdminConfigBuilder();
        $from = D('EventType')->find($from);

        $opt = array();
        $types = $this->eventTypeModel->select();
        foreach ($types as $event) {
            $opt[$event['id']] = $event['title'];
        }
        if ($type === 'move') {

            $builder->title('移动分类')->keyId()->keySelect('pid', '父分类', '选择父分类', $opt)->buttonSubmit(U('Event/add'))->buttonBack()->data($from)->display();
        } else {

            $builder->title('合并分类')->keyId()->keySelect('toid', '合并至的分类', '选择合并至的分类', $opt)->buttonSubmit(U('Event/doMerge'))->buttonBack()->data($from)->display();
        }

    }

    public function doMerge($id, $toid)
    {
        $effect_count=D('Event')->where(array('type_id'=>$id))->setField('type_id',$toid);
        D('EventType')->where(array('id'=>$id))->setField('status',-1);
        $this->success('合并分类成功。共影响了'.$effect_count.'个内容。',U('index'));
        //TODO 实现合并功能 issue
    }




    public function eventTypeTrash($page = 1, $r = 20)
    {
        //读取微博列表
        $map = array('status' => -1);
        $model = $this->eventTypeModel;
        $list = $model->where($map)->page($page, $r)->select();
        $totalCount = $model->where($map)->count();

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('活动类型回收站')
            ->setStatusUrl(U('setStatus'))->buttonRestore()
            ->keyId()->keyText('title', '标题')->keyStatus()->keyCreateTime()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }
    /**
     * 设置活动分类状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function setStatus($ids, $status)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        if(in_array(1,$ids)){
            $this->error('id为 1 的分类是活动基础分类，不能被禁用、删除！');
        }
        $builder = new AdminListBuilder();
        $builder->doSetStatus('EventType', $ids, $status);
    }
}
