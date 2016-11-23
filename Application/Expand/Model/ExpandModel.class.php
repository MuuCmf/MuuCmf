<?php

namespace Expand\Model;

use Think\Model;

class ExpandModel extends Model{

    protected $_validate  =  array(   
        array('title','require','应用名称不能为空！'), //默认情况下用正则进行验证
        array('category',array(1,31),'请选择应用分类！',1,'between',''), 
        array('description','require','简短描述不能为空！'), 
        array('content','require','应用详细描述不能为空！'),
        array('price',array(0,99999),'价格太离谱了!',1,'between',''),
    );

    public function editData($data)
    {
        if(!mb_strlen($data['description'],'utf-8')){
            $data['description']=msubstr(op_t($data['content']),0,200);
        }
        $data['reason']='';
        if($data['id']){
            $data['update_time']=time();
            $res=$this->save($data);  
        }else{
            $data['create_time']=$data['update_time']=time();
            $res=$this->add($data);
            action_log('add_expand', 'Expand', $res, is_login());
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

    /**
     * 获取推荐位数据列表
     * @param $pos 推荐位 1-系统首页，2-推荐阅读，4-本类推荐
     * @param null $category
     * @param $limit
     * @param bool $field
     * @return mixed
     */
    public function position($pos, $category = null, $limit = 5, $field = true,$order='sort desc,view desc'){
        $map = $this->listMap($category, 1, $pos);
        $res=$this->field($field)->where($map)->order($order)->limit($limit)->select();
        /* 读取数据 */
        return $res;
    }

    /**
     * 设置where查询条件
     * @param  number  $category 分类ID
     * @param  number  $pos      推荐位
     * @param  integer $status   状态
     * @return array             查询条件
     */
    private function listMap($category, $status = 1, $pos = null){
        /* 设置状态 */
        $map = array('status' => $status);

        /* 设置分类 */
        if(!is_null($category)){
            $cates=D('Muucmf/expandCategory')->getCategoryList(array('pid'=>$category,'status'=>1));
            $cates=array_column($cates,'id');
            $map['category']=array('in',array_merge(array($category),$cates));
        }
        $map['dead_line'] = array('gt',time());

        /* 设置推荐位 */
        if(is_numeric($pos)){
            $map[] = "position & {$pos} = {$pos}";
        }
        return $map;
    }

} 