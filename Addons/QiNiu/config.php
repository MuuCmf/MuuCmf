<?php

return array(

    'switch'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'是否开启七牛云存储：',//表单的文字
        'type'=>'radio',		 //表单的类型：text、textarea、checkbox、radio、select等
        'options'=>array(
            '1'=>'启用',
            '0'=>'禁用',
        ),
        'value'=>'0',
        'tip'=>'启用时请确保其他云存储插件为禁用状态'
    ),



    'accessKey'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'七牛的ak：',//表单的文字
        'type'=>'text',		 //表单的类型：text、textarea、checkbox、radio、select等
        'value'=>'',			 //表单的默认值
        'tip'=>'七牛的ak'
    ),


    'secrectKey'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'七牛的sk：',//表单的文字
        'type'=>'text',		 //表单的类型：text、textarea、checkbox、radio、select等
        'value'=>'',			 //表单的默认值
        'tip'=>'七牛的sk'
    ),

    'bucket'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'七牛的空间名称：',//表单的文字
        'type'=>'text',		 //表单的类型：text、textarea、checkbox、radio、select等
        'value'=>'',			 //表单的默认值
        'tip'=>'七牛的空间名称'
    ),

    'domain'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'七牛的空间对应的域名：',//表单的文字
        'type'=>'text',		 //表单的类型：text、textarea、checkbox、radio、select等
        'value'=>'',			 //表单的默认值
        'tip'=>'七牛的空间对应的域名'
    ),



);


