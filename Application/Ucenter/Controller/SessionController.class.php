<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/12/14
 * 创建时间: 12:49 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Ucenter\Controller;

use Think\Controller;

class SessionController extends BaseController
{
    protected $mTalkModel;

    public function _initialize()
    {
        parent::_initialize();
    }

    public function getSession($id)
    {
        $id = intval($id);
        //获取当前聊天
        $talk = $this->getTalk(0, $id);
        $uids = D('Talk')->getUids($talk['uids']);
        foreach ($uids as $uid) {
            if ($uid != is_login()) {
                $talk['first_user'] = query_user(array('avatar64', 'username'), $uid);
                $talk['ico'] = $talk['first_user']['avatar64'];
                break;
            }
        }
        $map['talk_id'] = $talk['id'];
        D('Common/TalkPush')->clearAll();
        $messages = D('TalkMessage')->where($map)->order('create_time desc')->limit(20)->select();
        $messages = array_reverse($messages);
        foreach ($messages as &$mes) {
            $mes['user'] = query_user(array('avatar64', 'uid', 'username'), $mes['uid']);
            $mes['ctime'] = date('m-d h:i', $mes['create_time']);
            $mes['avatar64'] = $mes['user']['avatar64'];
            $mes['content'] = parse_expression($mes['content']);
        }
        unset($mes);
        $talk['messages'] = $messages;
        $talk['self'] = query_user(array('avatar128'), is_login());
        $talk['mid'] = is_login();
        echo json_encode($talk);
    }

    /**消息页面
     * @param int $page
     * @param string $tab 当前tab
     */
    public function message($page = 1, $tab = 'unread')
    {
        //从条件里面获取Tab
        $map = $this->getMapByTab($tab, $map);

        $map['to_uid'] = is_login();

        $messages = D('Message')->where($map)->order('create_time desc')->page($page, 10)->select();
        $totalCount = D('Message')->where($map)->order('create_time desc')->count(); //用于分页

        foreach ($messages as &$v) {
            if ($v['from_uid'] != 0) {
                $v['from_user'] = query_user(array('username', 'space_url', 'avatar64', 'space_link'), $v['from_uid']);
            }
        }

        $this->assign('totalCount', $totalCount);
        $this->assign('messages', $messages);

        //设置Tab
        $this->defaultTabHash('message');
        $this->assign('tab', $tab);
        $this->display();
    }

    /**
     * @param $message
     * @return \Model
     */
    private function getMessageModel($message)
    {

        $appname = ucwords($message['appname']);
        $messageModel = D($appname . '/' . $appname . 'Message');
        return $messageModel;
    }

    /**
     * @param $tab
     * @param $map
     * @return mixed
     */
    private function getMapByTab($tab, $map)
    {
        switch ($tab) {
            case 'system':
                $map['type'] = 0;
                break;
            case 'user':
                $map['type'] = 1;
                break;
            case 'app':
                $map['type'] = 2;
                break;
            case 'all':
                break;
            default:
                $map['is_read'] = 0;
                break;
        }
        return $map;
    }

}