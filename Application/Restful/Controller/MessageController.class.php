<?php
/**
     * APP discovery json接口
     * 消息接口控制器
*/
namespace Restful\Controller;

use Think\Controller\RestController;


class MessageController extends BaseController {

    protected $codeModel;
    protected $Model;
	protected $ModelContent;
    function _initialize()
    {
    	parent::_initialize();
    	$this->codeModel = D('Restful/Code');
        $this->Model = D('Restful/Message');
		$this->ModelContent = D('Restful/MessageContent');
    }
	//
    public function index($page=1,$r=6)
    {
        switch ($this->_method){
            case 'get': //get请求处理代码
                
                $this->_needLogin();
                $uId = I('uid',0,'intval');
				$isRead = I('isread',1,'intval');
                if($uId)//给了app和row_id后执行
                { //
					$map['to_uid']=$uId;
					$map['status']=1;
					if($isRead==0 || $isRead==1){
						$map['is_read']=$isRead;
					}
					$order='create_time desc';
					$totalCount=$this->Model->where($map)->count();
						if($totalCount){
							$data=$this->Model->where($map)->page($page,$r)->order($order)->select();
						}
					foreach($data as &$val){
						$val['toUser']=query_user(array('uid','avatar32','avatar64','nickname'),$val['to_uid']);
						$val['fromUser']=query_user(array('uid','avatar32','avatar64','nickname'),$val['from_uid']);
						$contentId['id'] = $val['content_id'];
						$val['content']=$this->ModelContent->where($contentId)->find();
					}
					unset($val);
					$result['info'] = '返回成功';
					$result['totalCount'] = $totalCount;
					$result['data'] = $data;
					$result['code'] = 200;
				}
				$this->response($result,$this->type);
            break;

            case 'post'://post请求处理代码,写入评论内容

            break;
			case 'put':
                $result['info'] = 'PUT未定义';
            break;
        }
       // dump($data);
       
    }
    public function detail()
    {
        switch ($this->_method){
            case 'get': //get请求处理代码
				$id = I('id',0,'intval');
					$map['id']=$id;
					$map['status']=1;
					$data=$this->Model->where($map)->find();
						$data['toUser']=query_user(array('uid','avatar32','avatar64','nickname'),$data['to_uid']);
						$data['fromUser']=query_user(array('uid','avatar32','avatar64','nickname'),$data['from_uid']);
						$contentId['id'] = $data['content_id'];
						$data['content']=$this->ModelContent->where($contentId)->find();
						$isRead['is_read'] = 1;
						$this->Model->where($map)->save($isRead); 
						if(!empty($data['content']['url'])){//获取消息的来源内容
						if(!empty($data['content']['args'])){
							$url = $data['content']['url'];
							$n = strpos($url,'/');
							if ($n) $str=substr($url,0,$n);//获取模型名称
							unset($n);
							$data['formModelName'] = $str;
							$args = $data['content']['args'];
							$n = strpos($args,'":');
							if ($n) $idName = substr($args,2,$n-2);//获取id名，Uid或id
							unset($n);
							$n = strpos($args,':"');
							$m = strpos($args,'"}');
							if ($n) $id = substr($args,$n+2,$m-$n-2);//获取id值
							unset($n);
							unset($m);
							if($idName=='id'){
								if($str=='News'){
									$map['id'] = $id;
									$data['fromModelInfo']= M('News')->where($map)->find();
									$data['fromModelInfo']['Thumbnail'] = getThumbImageById($data['fromModelInfo']['cover'],352,240);
								}
								if($str=='Resources'){
									$map['id'] = $id;
									$data['fromModelInfo']= M('Resources')->where($map)->find();
									$data['fromModelInfo']['Thumbnail'] = getThumbImageById($data['fromModelInfo']['cover'],352,240);
								}
								if($str=='Design'){
									$map['id'] = $id;
									$data['fromModelInfo']= M('Design')->where($map)->find();
									$data['fromModelInfo']['Thumbnail'] = getThumbImageById($data['fromModelInfo']['cover'],352,240);
								}
								if($str=='Discovery'){
									$map['id'] = $id;
									$data['fromModelInfo']= M('Discovery')->where($map)->find();
								}
							}
						}
						}
						
					$result['info'] = '返回成功';
					$result['data'] = $data;
					$result['code'] = 200;
				$this->response($result,$this->type);
            break;
        }
       // dump($data);
       
    }
    
}