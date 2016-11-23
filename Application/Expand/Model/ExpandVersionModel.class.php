<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 */

namespace Expand\Model;


use Think\Model;

class ExpandVersionModel extends Model{

    protected $_validate  =  array(
        array('expand_id',array(1,99999999),'应用ID参数错误',1,'between',''),
        array('version','require','版本号不能为空！'), 
        array('update_log','require','更新日志不能为空！'),
        array('download_file','require','版本zip文件未上传！'),
    );

    public function editData($data)
    {
        $data['reason']='';
        if($data['id']){
            $data['update_time']=time();
            $res=$this->save($data);
        }else{
            $data['create_time']=$data['update_time']=time();
            $res=$this->add($data);
            action_log('add_expand_version', 'Expand', $res, is_login());
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

    public function getList($map,$order='view desc',$limit=5,$field='*')
    {
        $lists = $this->where($map)->order($order)->limit($limit)->field($field)->select();
        return $lists;
    }

    /**
    *设置为删除状态
    **/
    public function setDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->setField('status',-1);
        return $res;
    }
    /**
    *真实删除内容
    **/
    public function setTrueDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->delete();
        return $res;
    }

    public function getData($id)
    {
        if($id>0){
            $map['id']=$id;
            $data=$this->where($map)->find();
            return $data;
        }
        return null;
    }

} 