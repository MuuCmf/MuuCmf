<?php
/**
 * 开发者认证模型
 */
namespace Expand\Model;

use Think\Model;

class ExpandDevauthModel extends Model{

    protected $_validate  =  array(
        array('uid',array(1,99999999),'应用ID参数错误',1,'between',''),
        array('tname','require','真实姓名不能为空！'), 
        array('snum','require','身份证号码不能为空！'), 
        array('spic','require','身份证复印件未上传！'),
    );
    /**
     * 编辑、发布开发者审核资料
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function editData($data)
    {
        $map['uid']=$data['uid'];
        $uid = $this->where($map)->find();
        $data['reason']='';//审核失败原因
        if($uid){
            $data['update_time']=time();
            $res=$this->save($data);
        }else{
            $data['create_time']=$data['update_time']=time();
            $res=$this->add($data);
        }
        return $res;
    }

    public function getListByPage($map,$page=1,$order='update_time desc',$field='*',$r=20)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }

    /**
    *根据ids获取数据
    **/
    public function getListByIds($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->select();
        return $res;
    }
    /**
     * 通过用户uid获取数据
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public function getDataByUid($uid)
    {
        if($uid>0){
            $map['uid']=$uid;
            $data=$this->where($map)->find();
            return $data;
        }
        return null;
    }
} 