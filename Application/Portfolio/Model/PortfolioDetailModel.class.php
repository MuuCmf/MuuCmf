<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * @author 大蒙<59262424@qq.com>
 */

namespace Portfolio\Model;

use Common\Model\ContentHandlerModel;
use Think\Model;

class PortfolioDetailModel extends Model{

    public function editData($data=array())
    {
        $contentHandler=new ContentHandlerModel();
        $data['content']=$contentHandler->filterHtmlContent($data['content']);
        if($this->find($data['portfolio_id'])){
            $res=$this->save($data);
        }else{
            $res=$this->add($data);
        }
        return $res;
    }

    public function getData($id)
    {
        $contentHandler=new ContentHandlerModel();
        $res=$this->where(array('portfolio_id'=>$id))->find();
        $res['content']=$contentHandler->displayHtmlContent($res['content']);
        return $res;
    }

}