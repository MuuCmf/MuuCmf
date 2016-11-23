define(function (require,exports,module) {

    require('plupload');

    var uploader_img = new plupload.Uploader({
        browse_button: 'uploadBtn',
        url: uploader_url,
        filters: {
            max_file_size: '20mb',
            mime_types: [
                {title: "Image files", extensions: "jpg,gif,png,bmp"}
            ]
        },
        init: {
            FilesAdded: function (up, files) {
                uploader_img.start();
            },
            FileUploaded: function (up, files, res) {
                res = JSON.parse(res.response); //PHP上传成功后返回的参数
                console.log(res,res.data.url);
                if(res.data.url){
                    var picTpl = doT.template($('#pic-tpl').text());
                    $('#uploadBtn').after(picTpl(res.data.url))
                }
            },
            Error: function (upload,error) {
                if(error.code==(-600)){
                    alert('图片大小为0-1mb范围内')
                }
                else{
                    alert('上传失败，未知错误:'+error.code)
                }
            }
        }
    });
    uploader_img.init();
});
