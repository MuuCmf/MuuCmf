<?php


namespace Event\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function _initialize()
    {
        $tree = D('EventType')->where(array('status' => 1))->select();
        $this->assign('tree', $tree);

        $sub_menu =
            array(
                'left' =>
                    array(
                        array('tab' => 'home', 'title' => '首页', 'href' => U('event/index/index')),
                        array('tab' => 'myevent', 'title' => '我的活动', 'href' => U('event/index/myevent')),
                    ),
            );
        $this->assign('sub_menu', $sub_menu);
        $this->assign('current', 'home');
    }

    /**
     * 活动首页
     * @param int $page
     * @param int $type_id
     * @param string $norh
     * autor:xjw129xjt
     */
    public function index($page = 1, $type_id = 0, $norh = 'new')
    {
        $type_id = intval($type_id);
        if ($type_id != 0) {
            $map['type_id'] = $type_id;
        }
        $map['status'] = 1;
        $order = 'create_time desc';
        $norh == 'hot' && $order = 'signCount desc';
        $content = D('Event')->where($map)->order($order)->page($page, 10)->select();

        $totalCount = D('Event')->where($map)->count();
        foreach ($content as &$v) {
            $v['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['type'] = $this->getType($v['type_id']);
            $v['check_isSign'] = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $v['id']))->select();
        }
        unset($v);
        $this->assign('type_id', $type_id);
        $this->assign('contents', $content);
        $this->assign('norh', $norh);
        $this->assign('totalPageCount', $totalCount);
        $this->getRecommend();
        $this->setTitle('活动首页');
        $this->setKeywords('活动');
        $this->display();
    }

    /**
     * 获取推荐活动数据
     * autor:xjw129xjt
     */
    public function getRecommend()
    {
        $rec_event = D('Event')->where(array('is_recommend' => 1))->limit(2)->order('rand()')->select();
        foreach ($rec_event as &$v) {
            $v['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['type'] = $this->getType($v['type_id']);
            $v['check_isSign'] = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $v['id']))->select();
        }
        unset($v);

        $this->assign('rec_event', $rec_event);
    }

    /**
     * 我的活动页面
     * @param int $page
     * @param int $type_id
     * @param string $norh
     * autor:xjw129xjt
     */
    public function myevent($page = 1, $type_id = 0, $lora = '')
    {

        $type_id = intval($type_id);
        if ($type_id != 0) {
            $map['type_id'] = $type_id;
        }

        $map['status'] = 1;
        $order = 'create_time desc';
        if ($lora == 'attend') {
            $attend = D('event_attend')->where(array('uid' => is_login()))->select();
            $enentids = getSubByKey($attend, 'event_id');
            $map['id'] = array('in', $enentids);
        } else {
            $map['uid'] = is_login();
        }
        $content = D('Event')->where($map)->order($order)->page($page, 10)->select();

        $totalCount = D('Event')->where($map)->count();
        foreach ($content as &$v) {
            $v['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar128', 'rank_html'), $v['uid']);
            $v['type'] = $this->getType($v['type_id']);

            $v['check_isSign'] = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $v['id']))->select();
        }
        unset($v);
        $this->assign('type_id', $type_id);
        $this->assign('contents', $content);
        $this->assign('lora', $lora);
        $this->assign('totalPageCount', $totalCount);
        $this->getRecommend();
        $this->setTitle('我的活动——活动');
        $this->assign('current', 'myevent');
        $this->display();
    }

    /**
     * 获取活动类型
     * @param $type_id
     * @return mixed
     * autor:xjw129xjt
     */
    private function getType($type_id)
    {
        $type = D('EventType')->where('id=' . $type_id)->find();
        return $type;
    }

    /**
     * 发布活动
     * @param int $id
     * @param int $cover_id
     * @param string $title
     * @param string $explain
     * @param string $sTime
     * @param string $eTime
     * @param string $address
     * @param int $limitCount
     * @param string $deadline
     * autor:xjw129xjt
     */
    public function doPost($id = 0, $cover_id = 0, $title = '', $explain = '', $sTime = '', $eTime = '', $address = '', $limitCount = 0, $deadline = '', $type_id = 0)
    {
        if (!is_login()) {
            $this->error('请登陆后再投稿。');
        }
        if (!$cover_id) {
            $this->error('请上传封面。');
        }
        if (trim(op_t($title)) == '') {
            $this->error('请输入标题。');
        }
        if ($type_id == 0) {
            $this->error('请选择分类。');
        }
        if (trim(op_h($explain)) == '') {
            $this->error('请输入内容。');
        }
        if (trim(op_h($address)) == '') {
            $this->error('请输入地点。');
        }
        if ($eTime < $deadline) {
            $this->error('报名截止不能大于活动结束时间');
        }
        if ($deadline == '') {
            $this->error('请输入截止日期');
        }
        if ($sTime > $eTime) {
            $this->error('活动开始时间不能大于活动结束时间');
        }
        $content = D('Event')->create();
        $content['explain'] = op_h($content['explain']);
        $content['title'] = op_t($content['title']);
        $content['sTime'] = strtotime($content['sTime']);
        $content['eTime'] = strtotime($content['eTime']);
        $content['deadline'] = strtotime($content['deadline']);
        $content['type_id'] = intval($type_id);
        if ($id) {
            $content_temp = D('Event')->find($id);
            $this->checkAuth('Event/Index/edit', $content_temp['uid'], '您无该活动编辑权限。');
            $content['uid'] = $content_temp['uid']; //权限矫正，防止被改为管理员
            $rs = D('Event')->save($content);
            if (D('Common/Module')->isInstalled('Weibo')) { //安装了微博模块
                $postUrl = "http://$_SERVER[HTTP_HOST]" . U('Event/Index/detail', array('id' => $id));
                $weiboModel = D('Weibo/Weibo');
                $weiboModel->addWeibo("我修改了活动【" . $title . "】：" . $postUrl);
            }
            if ($rs) {
                $this->success('编辑成功。', U('detail', array('id' => $content['id'])));
            } else {
                $this->success('编辑失败。', '');
            }
        } else {
            $this->checkActionLimit('add_event', 'event', 0, is_login(), true);
            $this->checkAuth('Event/Index/add', -1, '您无活动发布权限。');
            if (modC('NEED_VERIFY', 0) && !is_administrator()) //需要审核且不是管理员
            {
                $content['status'] = 0;
                $tip = '但需管理员审核通过后才会显示在列表中，请耐心等待。';
                $user = query_user(array('username', 'nickname'), is_login());
                D('Common/Message')->sendMessage(C('USER_ADMINISTRATOR'), $title = '活动发布提醒', "{$user['nickname']}发布了一个活动，请到后台审核。",  'Admin/Event/verify', array(),is_login(), 2);
            }

            $content['attentionCount'] = 1;
            $content['signCount'] = 1;
            $rs = D('Event')->add($content);


            $data['uid'] = is_login();
            $data['event_id'] = $rs;
            $data['create_time'] = time();
            $data['status'] = 1;
            D('event_attend')->add($data);


            if (D('Common/Module')->isInstalled('Weibo')) { //安装了微博模块
                //同步到微博
                $postUrl = "http://$_SERVER[HTTP_HOST]" . U('Event/Index/detail', array('id' => $rs));

                $weiboModel = D('Weibo/Weibo');
                $weiboModel->addWeibo("我发布了一个新的活动【" . $title . "】：" . $postUrl);
            }

            if ($rs) {
                $this->success('发布成功。' . $tip, U('index'));
            } else {
                $this->success('发布失败。', '');
            }
        }
    }

    /**
     * 活动详情
     * @param int $id
     * autor:xjw129xjt
     */
    public function detail($id = 0)
    {

        $check_isSign = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $id))->select();

        $this->assign('check_isSign', $check_isSign);

        $event_content = D('Event')->where(array('status' => 1, 'id' => $id))->find();
        if (!$event_content) {
            $this->error('404 not found');
        }
        D('Event')->where(array('id' => $id))->setInc('view_count');

        $event_content['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $event_content['uid']);
        $event_content['type'] = $this->getType($event_content['type_id']);


        $menber = D('event_attend')->where(array('event_id' => $id, 'status' => 1))->select();
        foreach ($menber as $k => $v) {
            $event_content['member'][$k] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $v['uid']);

        }

        $this->assign('content', $event_content);
        $this->setTitle('{$content.title|op_t}' . '——活动');
        $this->setKeywords('{$content.title|op_t}' . ',活动');
        $this->getRecommend();
        $this->display();
    }

    /**
     * 活动成员
     * @param int $id
     * @param string $tip
     * autor:xjw129xjt
     */
    public function member($id = 0, $tip = 'all')
    {
        if ($tip == 'sign') {
            $map['status'] = 0;
        }
        if ($tip == 'attend') {
            $map['status'] = 1;
        }
        $check_isSign = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $id))->select();
        $this->assign('check_isSign', $check_isSign);

        $event_content = D('Event')->where(array('status' => 1, 'id' => $id))->find();
        if (!$event_content) {
            $this->error('404 not found');
        }
        $map['event_id'] = $id;
        $event_content['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $event_content['uid']);
        $menber = D('event_attend')->where($map)->select();
        foreach ($menber as $k => $v) {
            $event_content['member'][$k] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'avatar128', 'rank_html', 'signature'), $v['uid']);
            $event_content['member'][$k]['name'] = $v['name'];
            $event_content['member'][$k]['phone'] = $v['phone'];
            $event_content['member'][$k]['status'] = $v['status'];
        }

        $this->assign('all_count', D('event_attend')->where(array('event_id' => $id))->count());
        $this->assign('sign_count', D('event_attend')->where(array('event_id' => $id, 'status' => 0))->count());
        $this->assign('attend_count', D('event_attend')->where(array('event_id' => $id, 'status' => 1))->count());

        $this->assign('content', $event_content);
        $this->assign('tip', $tip);
        $this->setTitle('{$content.title|op_t}' . '——活动');
        $this->setKeywords('{$content.title|op_t}' . ',活动');
        $this->display();
    }

    /**
     * 编辑活动
     * @param $id
     * autor:xjw129xjt
     */
    public function edit($id)
    {
        $event_content = D('Event')->where(array('status' => 1, 'id' => $id))->find();
        if (!$event_content) {
            $this->error('404 not found');
        }
        $this->checkAuth('Event/Index/edit', $event_content['uid'], '您无该活动编辑权限。');
        $event_content['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $event_content['uid']);
        $this->assign('content', $event_content);
        $this->setTitle('编辑活动' . '——活动');
        $this->setKeywords('编辑' . ',活动');
        $this->display();
    }

    public function add()
    {
        $this->checkAuth('Event/Index/add', -1, '您无活动发布权限。');
        $this->setTitle('添加活动' . '——活动');
        $this->setKeywords('添加' . ',活动');
        $this->display();
    }

    /**
     * 报名参加活动
     * @param $event_id
     * @param $name
     * @param $phone
     * autor:xjw129xjt
     */
    public function doSign($event_id, $name, $phone)
    {
        if (!is_login()) {
            $this->error('请登陆后再报名。');
        }
        if (!$event_id) {
            $this->error('参数错误');
        }
        if (trim(op_t($name)) == '') {
            $this->error('请输入姓名。');
        }
        if (trim($phone) == '') {
            $this->error('请输入手机号码。');
        }
        $check = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id))->select();
        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
        $this->checkAuth('Event/Index/doSign', $event_content['uid'], '你没有报名参加活动的权限！');
        $this->checkActionLimit('event_do_sign', 'event', $event_id, is_login());
        if (!$event_content) {
            $this->error('活动不存在！');
        }
        /*      if ($event_content['attentionCount'] + 1 > $event_content['limitCount']) {
                  $this->error('超过限制人数，报名失败');
              }*/
        if (time() > $event_content['deadline']) {
            $this->error('报名已经截止');
        }
        if (!$check) {
            $data['uid'] = is_login();
            $data['event_id'] = $event_id;
            $data['name'] = $name;
            $data['phone'] = $phone;
            $data['create_time'] = time();
            $res = D('event_attend')->add($data);
            if ($res) {

                D('Message')->sendMessageWithoutCheckSelf($event_content['uid'], '报名通知',query_user('nickname', is_login()) . '报名参加了活动]' . $event_content['title'] . ']，请速去审核！',  'Event/Index/member', array('id' => $event_id));

                D('Event')->where(array('id' => $event_id))->setInc('signCount');
                action_log('event_do_sign', 'event', $event_id, is_login());
                $this->success('报名成功。', 'refresh');
            } else {
                $this->error('报名失败。', '');
            }
        } else {
            $this->error('您已经报过名了。', '');
        }
    }

    /**
     * 审核
     * @param $uid
     * @param $event_id
     * @param $tip
     * autor:xjw129xjt
     */
    public function shenhe($uid, $event_id, $tip)
    {
        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
        if (!$event_content || $event_content['deadline'] < time()) {
            $this->error('活动不存在或活动已结束！');
        }
        $this->checkAuth('Event/Index/shenhe', $event_content['uid'], '你没有审核的权限！');
        $res = D('event_attend')->where(array('uid' => $uid, 'event_id' => $event_id))->setField('status', $tip);
        if ($tip) {
            if ($event_content['attentionCount'] + 1 == $event_content['limitCount']) {
                $data['deadline'] = time();
                $data['attentionCount'] = $event_content['limitCount'];
                D('Event')->where(array('id' => $event_id))->setField($data);
            } else {
                D('Event')->where(array('id' => $event_id))->setInc('attentionCount');
            }
            D('Message')->sendMessageWithoutCheckSelf($uid, '审核通知',query_user('nickname', is_login()) . '已经通过了您对活动' . $event_content['title'] . '的报名请求',  'Event/Index/detail', array('id' => $event_id));
        } else {
            D('Event')->where(array('id' => $event_id))->setDec('attentionCount');
            D('Message')->sendMessageWithoutCheckSelf($uid, '取消审核通知',query_user('nickname', is_login()) . '取消了您对活动[' . $event_content['title'] . ']的报名请求',  'Event/Index/member', array('id' => $event_id));
        }
        if ($res) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败！');
        }
    }

    /**
     * 取消报名
     * @param $event_id
     * autor:xjw129xjt
     */
    public function unSign($event_id)
    {

        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }

        $check = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id))->find();

        $res = D('event_attend')->where(array('uid' => is_login(), 'event_id' => $event_id))->delete();
        if ($res) {
            if ($check['status']) {
                D('Event')->where(array('id' => $event_id))->setDec('attentionCount');
            }
            D('Event')->where(array('id' => $event_id))->setDec('signCount');

            D('Message')->sendMessageWithoutCheckSelf($event_content['uid'],  '取消报名通知',query_user('nickname', is_login()) . '取消了对活动[' . $event_content['title'] . ']的报名', 'Event/Index/detail', array('id' => $event_id));

            $this->success('取消报名成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 报名弹出框页面
     * @param $event_id
     * autor:xjw129xjt
     */
    public function ajax_sign($event_id)
    {

        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
        $this->checkAuth('Event/Index/doSign', $event_content['uid'], '你没有报名参加活动的权限！');
        D('Event')->where(array('id' => $event_id))->setInc('view_count');
        $event_content['user'] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $event_content['uid']);
        $event_content['type'] = $this->getType($event_content['type_id']);

        $menber = D('event_attend')->where(array('event_id' => $event_id, 'status' => 1))->select();
        foreach ($menber as $k => $v) {
            $event_content['member'][$k] = query_user(array('id', 'username', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $v['uid']);

        }

        $this->assign('content', $event_content);
        $this->display();
    }

    /**
     * ajax删除活动
     * @param $event_id
     * autor:xjw129xjt
     */
    public function doDelEvent($event_id)
    {

        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
        $this->checkAuth('Event/Index/doDelEvent', $event_content['uid'], '你没有删除活动的权限！');
        $res = D('Event')->where(array('status' => 1, 'id' => $event_id))->setField('status', 0);
        if ($res) {
            $this->success('删除成功！', U('Event/Index/index'));
        } else {
            $this->error('操作失败！');
        }
    }

    /**
     * ajax提前结束活动
     * @param $event_id
     * autor:xjw129xjt
     */
    public function doEndEvent($event_id)
    {

        $event_content = D('Event')->where(array('status' => 1, 'id' => $event_id))->find();
        if (!$event_content) {
            $this->error('活动不存在！');
        }
        $this->checkAuth('Event/Index/doEndEvent', $event_content['uid'], '你没有结束活动的权限！');
        $data['eTime'] = time();
        $data['deadline'] = time();
        $res = D('Event')->where(array('status' => 1, 'id' => $event_id))->setField($data);
        if ($res) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }

    }

}