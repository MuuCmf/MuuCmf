<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 下午3:30
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Articles\Model;


use Common\Model\ContentHandlerModel;
use Think\Model;

class ArticlesDetailModel extends Model{

    public function editData($data=array())
    {
        $contentHandler=new ContentHandlerModel();
        $data['content']=$contentHandler->filterHtmlContent($data['content']);
        if($this->find($data['articles_id'])){
            $res=$this->save($data);
        }else{
            $res=$this->add($data);
        }
        return $res;
    }

    public function getData($id)
    {
        $contentHandler=new ContentHandlerModel();
        $res=$this->where(array('articles_id'=>$id))->find();
        $res['content']=$contentHandler->displayHtmlContent($res['content']);
        return $res;
    }

}