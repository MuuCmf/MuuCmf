<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 2/19/14
 * Time: 5:14 PM
 */

namespace Addons\LocalComment\Controller;

use Think\Controller;

class IndexController extends Controller
{
    protected $commentModel = '';

    public function _initialize()
    {
        $this->commentModel = D('Addons://LocalComment/LocalComment');
    }

    public function addComment()
    {


        $aPath = I('post.path', '', 'urldecode');
        $aPath = explode('/', $aPath);
        $aApp = $aPath[0];
        $aMod = $aPath[1];
        $aRowId = $aPath[2];


        $aUrl = I('post.this_url', '', 'text');
        $aExtra = I('post.extra','','text');
        parse_str($aExtra);
        $field = empty($field) ?'id':$field;
        $can_guest = modC($aMod . '_LOCAL_COMMENT_CAN_GUEST', 1, $aApp);
        if (!$can_guest) {
            if (!is_login()) {
                $this->error('请登录后评论。');
            }
        }
        $aCountModel = I('get.count_model', '', 'text');
        $aCountField = I('get.count_field', '', 'text');

        $aContent = I('content', '', 'text');
        $aUid = I('get.uid', '', 'intval');
        if (empty($aContent)) {
            $this->error('评论内容不能为空');
        }
        $commentModel = $this->commentModel;
        $lookup = get_ip_lookup();
        $data = array('app' => $aApp, 'mod' => $aMod, 'row_id' => $aRowId, 'content' => $aContent, 'uid' => is_login(), 'ip' => get_client_ip(1), 'area' => $lookup['province'] . $lookup['city']);
        $res = $commentModel->addComment($data);
        if ($res) {

            D($aCountModel)->where(array('id' => $aRowId))->setInc($aCountField);

            $class = get_addon_class('LocalComment');
            $object = new $class;
            $html = $object->getCommentHtml($res);
             //* @param $to_uids 接收消息的用户们
             //* @param string $title 消息标题
             //* @param string $content 消息内容
             //* @param string $url 消息指向的路径，U函数的第一个参数
             //* @param array $url_args 消息链接的参数，U函数的第二个参数
             //* @param int $from_uid 发送消息的用户
             //* @param string $type 消息类型标识，对应各模块message_config.php中设置的消息类型
             //* @param string $tpl 消息模板标识，对应各模块message_config.php中设置的消息模板
            if (!is_login()) {
                if ($aUid) {
                    $title = '游客' . '评论了您';
                    $message = $aContent;
                    D('Common/Message')->sendMessage($aUid, $title, $message, $aUrl, array($field => $aRowId),0,'Common_comment','Common_comment');
                }
                $result['status'] = 1;
                $result['data'] = $html;
                $result['info'] = '评论成功';
                $this->ajaxReturn($result);
            }


            if ($aUid) {

                $user = query_user(array('nickname', 'uid'), is_login());
                $title = $user['nickname'] . '评论了您';
                $message = $aContent;
                D('Common/Message')->sendMessage($aUid, $title, $message, $aUrl, array($field => $aRowId),is_login(),'Common_comment','Common_comment');

            }


            //通知被@到的人
            $uids = get_at_uids($aContent);
            $uids = array_unique($uids);
            $uids = array_subtract($uids, array($aUid));
            foreach ($uids as $uid) {
                $user = query_user(array('nickname', 'uid'), is_login());
                $title = $user['nickname'] . '@了您';
                $message = $aContent;
                D('Common/Message')->sendMessage($aUid, $title, $message, $aUrl, array($field => $aRowId),is_login(),'Common_comment','Common_comment');
            }
            $result['status'] = 1;
            $result['data'] = $html;
            $result['info'] = '评论成功';
            $this->ajaxReturn($result);

        } else {
            $result['status'] = 0;
            $result['data'] = '';
            $result['info'] = '评论失败';
            $this->ajaxReturn($result);
        }
    }


    public function getCommentList()
    {
        $aApp = I('post.app', '', 'text');
        $aMod = I('post.mod', '', 'text');
        $aRowId = I('post.row_id', '', 'intval');
        $aPage = I('post.page', '', 'intval');
        $count = modC($aMod . '_LOCAL_COMMENT_COUNT', 10, $aApp);
        $commentModel = $this->commentModel;

        $param['where'] = array('app' => $aApp, 'mod' => $aMod, 'row_id' => $aRowId, 'status' => 1);
        $param['page'] = $aPage;
        $param['count'] = $count;
        $sort = modC($aMod . '_LOCAL_COMMENT_ORDER', 0, $aApp) == 0 ? 'desc' : 'asc';
        $param['order'] = 'create_time ' . $sort;
        $param['field'] = 'id';
        $list = $commentModel->getList($param);
        $html = '';
        $class = get_addon_class('LocalComment');
        $object = new $class;
        foreach ($list as $v) {
            $html .= $object->getCommentHtml($v);
        }
        $total_count = $object->getCommentCount($aApp, $aMod, $aRowId);
        $pageCount = ceil($total_count / $count);
        $html .= '<div class="pager">' . getPageHtml('local_comment_page', $pageCount, array('app' => $aApp, 'mod' => $aMod, 'row_id' => $aRowId), $aPage) . '</div>';
        $this->ajaxReturn(array('html' => $html));

    }


    public function deleteComment()
    {
        $aId = I('post.id', 0, 'intval');
        $aCountModel = I('get.count_model', '', 'text');
        $aCountField = I('get.count_field', '', 'text');

        $commentModel = $this->commentModel;
        $comment = $commentModel->getComment($aId);
        if (empty($comment) || $aId <= 0) {
            $this->error('删除评论失败。评论不存在。');
        }
        if (!is_login()) {
            $this->error('请登陆后再操作！');
        }
        if (!check_auth('deleteLocalComment', $comment['uid'])) {
            $this->error('删除评论失败！权限不足');
        }

        $result = $commentModel->deleteComment($aId);
        if ($result) {
            D($aCountModel)->where(array('id' => $comment['row_id']))->setDec($aCountField);
            $this->success('删除评论成功。', 'refresh');
        } else {
            $this->error('删除评论失败。' . $commentModel->getError());
        }


    }
}