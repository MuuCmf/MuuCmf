define(function (require,exports,module) {
    require('transit');
    /*
    详情  滑轮切换效果
    */
    var scroll_to_top = 40;//滚到距离顶部多远
    var detail = $(".detail-section");
    var body = $("body");
    var box = $('.detail-box') ;
    var window_height = $(window).height();
    var header_height = body.children("header").height();
    var nav_height = body.children("nav").height();
    var footer_height = body.children("footer").height();
    var detail_height = window_height-header_height-nav_height-footer_height;
    detail.siblings("section:visible").each(function () {
        var section_height = $(this).outerHeight();
        detail_height = detail_height-section_height;
    });
    var position_top = parseInt(box.css('top'));
    detail_height = detail_height-position_top+10;//预留10触发事件
    detail.height(detail_height);//初始化高度

    /*事件*/
    $(window).scroll(function () {
        var scroll_top = $(this).scrollTop();
        var show = detail.css('display');
        //console.log(show,show=='block');
        if((scroll_top!=0)&&(show=='block')){
            detail_slide_up();
        }
    });
    detail.scroll(function () {
        var detail_scroll_top = $(this).scrollTop();
        var window_scroll_top = $(window).scrollTop();
//                    console.log(detail_scroll_top,window_scroll_top);
        if((detail_scroll_top==0)&&(window_scroll_top==0)){
            detail_slide_down()
        }
    });
    detail.click(function () {
        detail_slide_up();
    });
    $(".detail-box .bottom-btn").click(function () {
        detail_slide_down()
    });
    //功能
    var scroll_up_status = true;//效果开关
    var scroll_down_status = false;//效果开关
    function detail_slide_up(){
        if(scroll_up_status){
            scroll_up_status = false;//效果开始，关闭开关;
            $(".detail-shadow").show().transition({opacity:1},1000);
            $(".detail-box .bottom-btn").show();
            var slide_height = nav_height+position_top-scroll_to_top;
            var change_height = window_height-header_height-scroll_to_top-footer_height;
//                    var article_height = window_height-header_height-nav_height;console.log(article_height);//似乎不用预留
            detail.css('height',change_height).siblings("section").hide();
            //todo:!!!!!!!!!!!!!!!!!!!!!这句话要在下面的animate前！！？？不是的话css设置无效？！
            $("body").children("article").height(0);//防止撑开article
            box.transition({'top':-slide_height+position_top},1200, function () {
                detail.css('overflow-y','scroll');
                setTimeout(function () {
                    scroll_down_status = true;//
                },500)
            });
        }
    }
    function detail_slide_down(){
        if(scroll_down_status){
            scroll_down_status = false;
            $(".detail-shadow").transition({opacity:0},1000,function(){$(this).hide()});
            $(".detail-box .bottom-btn").hide();
//                        detail.siblings("section").show(function () {
//                            detail.css({'height':detail_height,'overflow-y':'hidden'})
//                        });
            detail.siblings("section").show();

            box.transition({'top':position_top},1200, function () {
                detail.css({'height':detail_height,'overflow-y':'hidden'});
                setTimeout(function () {
                    $("body").children("article").height('auto');
                    scroll_up_status = true;
                },500)
            });
        }
    }
    function getStatus(){
        return {up:scroll_up_status,down:scroll_down_status}
    }
    exports.functionUp = detail_slide_up;
    exports.functionDown = detail_slide_down;
    exports.getStatus = getStatus;
});