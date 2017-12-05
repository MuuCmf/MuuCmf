<?php
/**
     * APP discovery json接口
*/
namespace Restful\Controller;

use Think\Controller\RestController;


class CommentController extends RestController {
    protected $allowMethod    = array('get','post','put'); // REST允许的请求类型列表
    protected $allowType      = array('html','xml','json'); // REST允许请求的资源类型列表
    protected $Model;
    
    function _initialize()
    {
    	parent::_initialize();
        $this->Model = D('Addons://LocalComment/LocalComment');
    }
	//
    public function index($page=1,$r=6)
    {
        switch ($this->_method){
            case 'get': //get请求处理代码
                
                $uId = I('uid',0,'intval');
				$rowId = I('rowid',0,'intval');
				$app = I('app','','op_t');
                if($rowId)//给了app和row_id后执行
                { //
					$map['row_id']=$rowId;
				}
				if($app)//给了app和row_id后执行
                { //
					$map['app']=$app;
				}
				if($uId)//给了uId后执行
                { //
					$map['uid']=$uId;
				}
					$map['status']=1;
					$order='create_time desc';
					$totalCount=$this->Model->where($map)->count();
					if($totalCount){
						$data=$this->Model->where($map)->page($page,$r)->order($order)->select();
					}
                    foreach($data as &$val){
                        $val['userinfo']=query_user(array('uid','avatar32','nickname'),$val['uid']);
						$contentId['id'] = $val['row_id'];
						if($val['app']=='News'){
							$val['model']=M('News')->where($contentId)->find();
						}else if($val['app'] == 'Resources'){
							$val['model']=M('Resources')->where($contentId)->find();
						}else if($val['app'] == 'Design'){
							$val['model']=M('Design')->where($contentId)->find();
						}else{
							$val['model']=M('Discovery')->where($contentId)->find();
						}
                    }
                    unset($val);
				$info = '返回成功';
				
				$result['info'] = $info;
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
				$aMod = 'index';
				$aRowid = I('post.rowid',0,intval);
				$aContent = I('post.content','',text);
				if (empty($aContent)) {
					$result['info'] = '评论内容不能为空';
					$this->response($result,'json');
				}
				$aCreateTime = time();
				$aPid = 0;
				$aIp = get_client_ip(1);
				$aStatus = 1;
				$lookup = get_ip_lookup();
				$aArea = $lookup['province'];
				
				//写入数据库
				$data = array('uid' => $aUid,'app'=> $aApp, 'mod'=>$aMod,'row_id'=>$aRowid, 'content' => $aContent, 'create_time' => $aCreateTime, 'pid' => $aPid, 'status'=>$aStatus,'ip' => $aIp,'area'=>$aArea);
				$data = $this->Model->create($data);
				if (!$data) return false;
				$comment_id = $this->Model->add($data);
				if($comment_id){
					/* 更新评论数 */
					$map = array('id' => $aRowid);
					if($aApp=='News'){
							M('News')->where($map)->setInc('comment');
					}
					if($aApp=='Resources'){
							M('Resources')->where($map)->setInc('comment');
					}
					if($aApp=='Design'){
							M('Design')->where($map)->setInc('comment');
					}
					if($aApp=='Discovery'){
							M('Discovery')->where($map)->setInc('comment');
					}
					$result['info'] = '评论成功';
					$result['comment_id'] = $comment_id;
				}
			}else{
				$result['info'] = '需要登录哦';
			}
			$this->response($result,'json');
            break;
			case 'put':
                $result['info'] = 'PUT未定义';
            break;
        }
       // dump($data);
       
    }
    
}