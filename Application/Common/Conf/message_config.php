<?php
/**
 * 消息类型
 */

return array(
    'session'=>array(
        array('name'=>'system','title'=>'系统消息','tpl_name'=>'_message_li','logo'=>'system.png','sort'=>100,'default'=>1),
        array('name'=>'comment','title'=>'评论消息','tpl_name'=>'_comment','logo'=>'system.png','sort'=>99),
        array('name'=>'announce','title'=>'全站公告','tpl_name'=>'_announce','logo'=>'announce.png','sort'=>98),
        array('name'=>'support','title'=>'赞','tpl_name'=>'_message_li','logo'=>'announce.png','sort'=>97)
    )
);