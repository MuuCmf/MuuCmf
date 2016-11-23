<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-8
 * Time: PM4:14
 */

namespace Forum\Model;

use Think\Model;

class ForumLzlReplyModel extends Model
{
    protected $_validate = array(
         array('content', '1,999999', '内容太短', self::EXISTS_VALIDATE, 'length'),
        array('content', '0,40000', '内容太长', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('ctime', NOW_TIME, self::MODEL_INSERT),
        array('is_del', '0', self::MODEL_INSERT),
    );

    public function addLZLReply($post_id, $to_f_reply_id, $to_reply_id, $to_uid, $content,$p, $send_message = true)
    {
        //新增一条回复
        $data = array('uid' => is_login(), 'post_id' => $post_id, 'to_f_reply_id' => $to_f_reply_id, 'to_reply_id' => $to_reply_id, 'to_uid' => $to_uid, 'content' => $content);

        $data = $this->create($data);
        if (!$data) return false;
        $result = $this->add($data);
        action_log('add_post_reply', 'ForumLzlReply', $result, is_login());
        S('post_replylist_' . $post_id, null);
        S('post_replylzllist_' . $to_f_reply_id, null);
        $postModel = D('ForumPost');

        //增加帖子的回复数
        $postModel->where(array('id' => $post_id))->setInc('reply_count');
        //更新最后回复时间
        $postModel->where(array('id' => $post_id))->setField('last_reply_time', time());

        $post = $postModel->find($post_id);
        D('Forum')->where(array('id' => $post['forum_id']))->setField('last_reply_time', time());

        if ($send_message) {
            $this->sendReplyMessage(is_login(), $post_id, $content, $to_uid, $to_f_reply_id,$result,$p);
        }

        $this->handleAt($post_id, $to_f_reply_id, $content, $p, $map);
        //返回结果
        return $result;
    }

    /**
     * @param $uid
     * @param $post_id
     * @param $content
     * @param $to_uid
     * @param $result
     */
    private function sendReplyMessage($uid, $post_id, $content, $to_uid, $to_f_reply_id,$result,$p)
    {

        $limit = 5;
        $map['is_del']=0;
        $map['to_f_reply_id']=$to_f_reply_id;
        $count = D('ForumLzlReply')->where($map)->count();
        $pageCount = ceil($count / $limit);

        //增加微博的评论数量
        $user = query_user(array('nickname', 'space_url'), $uid);
        $title = $user['nickname'] . L('_REPLY_TO_YOUR_COMMENTS_WITH_PERIOD_');
        $content = L('_REPLY_CONTENT_WITH_COLON_') . mb_substr($content, 0, 20);

        D('Message')->sendMessage($to_uid,$title,  $content, 'Forum/Index/detail#'.$to_f_reply_id,array('id' => $post_id,'page'=>$p,'sr'=>$to_f_reply_id,'sp'=>$pageCount), $uid, 2);

    }

    public function delLZLReply($id)
    {
        $lzl = D('ForumLzlReply')->where('id=' . $id)->find();
        $data['is_del']=1;
        CheckPermission(array($lzl['uid'])) && $res = $this->where('id=' . $id)->save($data);
        D('ForumPost')->where(array('id' => $lzl['post_id']))->setDec('reply_count');
        S('post_replylist_' . $lzl['post_id'], null);
        S('post_replylzllist_' . $lzl['to_f_reply_id'], null);
        return $res;
    }

    public function getLZLReplyList($to_f_reply_id, $order, $page=1, $limit)
    {
        $list = S('post_replylzllist_' . $to_f_reply_id);
        if ($list == null) {
            $list = D('forum_lzl_reply')->where('is_del=0 and to_f_reply_id=' . $to_f_reply_id)->order($order)->select();
            foreach ($list as $k => &$v) {
                $v['userInfo'] = query_user(array('avatar128', 'nickname', 'uid', 'space_url'), $v['uid']);
                $v['content'] = op_t($v['content']);
            }
            unset($v);
            S('post_replylzllist_' . $to_f_reply_id, $list, 60);
        }
        $list = getPage($list, $limit, $page);
        return $list;
    }

    /**
     * @param $post_id
     * @param $to_f_reply_id
     * @param $content
     * @param $p
     * @param $map
     * @auth 陈一枭
     */
    private function handleAt($post_id, $to_f_reply_id, $content, $p, $map)
    {
        $limit = 5;
        $map['is_del'] = 0;
        $map['to_f_reply_id'] = $to_f_reply_id;
        $count = D('ForumLzlReply')->where($map)->count();
        $pageCount = ceil($count / $limit);
        //增加微博的评论数量
        D('ContentHandler')->handleAtWho($content, 'Forum/Index/detail#'.$to_f_reply_id,array('id' => $post_id, 'page' => $p, 'sr' => $to_f_reply_id, 'sp' => $pageCount) ,'',1);
    }


}