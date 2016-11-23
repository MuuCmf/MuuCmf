<?

namespace Muucmf\Model;
use Think\Model;

class MuucmfLogModel extends Model{

    public function editData($data)
    {
        if(!mb_strlen($data['description'],'utf-8')){
            $data['description']=msubstr(op_t($data['content']),0,200);
        }
        if($data['id']){
            $data['update_time']=time();
            $res=$this->save($data);
        }else{
            $data['create_time']=$data['update_time']=time();
            $res=$this->add($data);
        }
        return $res;
    }

    public function getData($id)
    {
        if($id>0){
            $map['id'] = $id;
            $data = $this->where($map)->find();
            return $data;
        }else{
            return null;
        }
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
    *真实删除内容
    **/
    public function setTrueDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->delete();
        return $res;
    }

} 