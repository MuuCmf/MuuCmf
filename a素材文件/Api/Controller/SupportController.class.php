<?php
/**
     * APP discovery json接口
*/
namespace Api\Controller;

use Think\Controller\RestController;


class SupportController extends RestController {
    protected $allowMethod    = array('get','post','put'); // REST允许的请求类型列表
    protected $allowType      = array('html','xml','json'); // REST允许请求的资源类型列表
    protected $Model;
    function _initialize()
    {
        $this->Model = D('Api/Support');
    }
	//
    public function index($page=1,$r=6)
    {
        switch ($this->_method){
            case 'get': //get请求处理代码
                
				$row = I('rowid',0,'intval');
                $uId = I('uid',0,'intval');
				$appname = I('app','','op_t');
					$map['status']=1;
					if($uId && $uId!=0){
						$map['uid']=$uId;
					}
					if($appname){
						$map['appname']=$appname;	
					}
					if($row && $row!=0){
						$map['row']=$row;
					}
					$order='create_time desc';
					$totalCount=$this->Model->where($map)->count();
						if($totalCount){
							$data=$this->Model->where($map)->page($page,$r)->order($order)->select();
						}
					foreach($data as &$val){
						$val['User']=query_user(array('uid','avatar32','avatar64','nickname'),$val['uid']);
					}
					unset($val);
					$result['info'] = '返回成功';
					$result['totalCount'] = $totalCount;
					$result['data'] = $data;
					$result['code'] = 200;
				
				$this->response($result,'json');
            break;

            case 'post'://post请求处理代码,写入评论内容
			$aOpen_id = I('post.open_id','',op_t);
			//验证open_id
			$access_openid=D('Member')->access_openid($aOpen_id);
			if($access_openid){
				$aUid = I('post.uid',0,intval);
				$aApp = I('post.app','',op_t);
				$aRowid = I('post.rowid',0,intval);
				$aTable = strtolower($aApp);
				$aCreateTime = time();
				//判断是否已经赞过
				$map['appname'] = ucfirst($aApp);//首字母大写
				$map['row'] = $aRowid;
				$map['uid'] = $aUid;
				$data=$this->Model->where($map)->select();
				if($data){
					$result['info'] = '已经赞过了';
				}else{
					$data = array('appname'=> $aApp, 'uid' => $aUid,'row'=>$aRowid,'create_time' => $aCreateTime, 'table' => $aTable);
					$data = $this->Model->create($data);
					if (!$data) return false;
						$this->Model->add($data);
					$result['info'] = '赞 +1';
				}
			}
			$result['code'] = 200;
			$this->response($result,'json');
            break;
			case 'put':
                $result['info'] = 'PUT未定义';
            break;
        }
       // dump($data);
       
    }
    
}