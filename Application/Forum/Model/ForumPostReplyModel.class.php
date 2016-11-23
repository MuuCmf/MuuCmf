<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-8
 * Time: PM4:14
 */

namespace Forum\Model;

use Think\Model;

class ForumPostReplyModel extends Model
{
    protected $_validate = array(
              array('content', '1,40000', '内容长度不合法', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME),
        array('status', '1', self::MODEL_INSERT),
    );

    public function addReply($post_id, $content)
    {
        //新增一条回复
        $data = array('uid' => is_login(), 'post_id' => $post_id, 'parse' => 0, 'content' => $content);
        $data = $this->create($data);
        if (!$data) return false;
        $result = $this->add($data);
        action_log('add_post_reply', 'ForumPostReply', $result, is_login());

        S('post_replylist_' . $post_id, null);
        
        $postModel = D('ForumPost');
        //增加帖子的回复数
        $postModel->where(array('id' => $post_id))->setInc('reply_count');
        //更新最后回复时间
        $postModel->where(array('id' => $post_id))->setField('last_reply_time', time());
        $post = $postModel->find($post_id);
        //更新板块最后回复时间
        D('Forum')->where(array('id' => $post['forum_id']))->setField('last_reply_time', time());
        //处理@
        $this->handleAt($content, 'Forum/Index/detail#'.$result, array('id' => $post_id, 'page' => $pageCount));

        //返回结果
        return $result;
    }


    public function handleAt($content, $url,$args)
    {
        D('ContentHandler')->handleAtWho($content, $url,$args);
    }

    public function getReplyList($map, $order, $page, $limit)
    {
        $replyList = S('post_replylist_' . $map['post_id']);
        if ($replyList == null) {
            $replyList = $this->where($map)->order($order)->select();
            foreach ($replyList as &$reply) {
                $reply['user'] = query_user(array('avatar128', 'nickname', 'space_url', 'rank_link'), $reply['uid']);
                $reply['lzl_count'] = D('forum_lzl_reply')->where('is_del=0 and to_f_reply_id=' . $reply['id'])->count();
            }
            unset($reply);
            S('post_replylist_' . $map['post_id'], $replyList, 60);
        }
        $replyList = getPage($replyList, $limit, $page);
        return $replyList;
    }

    public function delPostReply($id)
    {
        $reply = $this->where('id=' . $id)->find();
        $data['status'] = 0;
        $res = $this->where('id=' . $id)->save($data);
        if ($res) {
            $lzlReply_idlist = D('ForumLzlReply')->where('is_del=0 and to_f_reply_id=' . $id)->field('id')->select();
            $info['is_del'] = 1;
            foreach ($lzlReply_idlist as $val) {
                $this>where('id=' . $val['id'])->save($info);
                D('ForumPost')->where(array('id' => $reply['post_id']))->setDec('reply_count');
            }
        }
        D('ForumPost')->where(array('id' => $reply['post_id']))->setDec('reply_count');
        S('post_replylist_' . $reply['post_id'], null);
        return $res;
    }


}