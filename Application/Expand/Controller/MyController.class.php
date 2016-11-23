<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/27 0027
 * Time: 上午 8:32
 */
namespace Expand\Controller;

use Think\Controller;

class MyController extends IndexController
{
    protected $expandModel;
    protected $expandCategoryModel;
    protected $expandRecordsModel;
    protected $expandVersionModel;
    function _initialize(){
        parent::_needLogin();
        $this->expandModel = D('Expand/Expand');
        $this->expandCategoryModel = D('Expand/ExpandCategory');
        $this->expandRecordsModel = D('Expand/ExpandRecords');
        $this->expandVersionModel = D('Expand/ExpandVersion');
    }
    
    /**
     * 我购买的应用
     * @param  integer $page [description]
     * @param  integer $r    [description]
     * @return [type]        [description]
     */
    public function bought($page=1,$r=20)
    {
        $aId=I('id',0,'intval');

        $map['uid'] = is_login();
        list($list,$totalCount) = $this->expandRecordsModel->getListByPage($map,$page,'id desc,create_time desc','*',$r);
        $ids = array();
        foreach($list as &$val){
            $ids[] = $val['expand_id'];
        }
        unset($val);
        $ids=implode(',',$ids);
        $myList = $this->expandModel->getListByIds($ids);
        foreach($myList as &$val){
            $map['expand_id'] = $val['id'];
            $map['status'] = 1;
            $version = $this->expandVersionModel->getList($map,'id desc',1,'*');
            $val['downBtn'] = parent::_downBtn($version[0]['expand_id'],$version[0]['download_file'],$val['price']);
            $val['file_id'] = $version[0]['download_file'];
        }
        
        $this->setTitle('我购买的应用');
        $this->assign('list', $myList);
        $this->assign('totalCount',$totalCount);
        $this->display();
    }
    
}