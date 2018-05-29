<?php
namespace Common\Widget;

use Think\Controller;

class UeditorWidget extends Controller
{

    public function editor($id = 'myeditor', $name = 'content', $default='', $config='', $style='', $param='', $width='100%')
    {
        $this->assign('id',$id);
        $this->assign('name',$name);
        $this->assign('default',$default);
        $this->assign('width',$width);
        $this->assign('height',$height);
        $this->assign('style',$style);
        if($config=='')
        {
            $config="{toolbars:[['source','|','bold','italic','underline','fontsize','forecolor','fontfamily','backcolor','|','insertimage','insertcode','link','emotion','scrawl','wordimage']]}";
        }
        if($config == 'all'){
            $config='{}';
        }
        
        $this->assign('config',$config);
        $this->assign('param',$param);
        cookie('video_get_info',U('Home/Public/getVideo'));

        $this->display(T('Application://Common@Widget/ueditor'));
    }

}
