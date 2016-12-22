//还是不可以多级下去，级里面刷新丢失page_stack，会有BUG
define(function (require,exports,module) {
    require('zepto');
    var page_stack = [];
    var callback = {};
    var $container = $('.slide_container');
    /*Hash*/
    function Hash(id){
        var html = $('#tpl_' + id);
        if(html.length==0){
            return
        }
        location.hash = '#' + id;
    }
    /*滑入功能*/
    function slideIn(id){
        var $tpl = $($('#tpl_' + id).html()).addClass('slideIn').addClass(id);
        $container.append($tpl);
        var stack_data = {
            id:id,
            tpl:$tpl
        };
        page_stack.push(stack_data);
        $($tpl)
            .on('animationend', function (){
                $(this).removeClass('slideIn');
            })
            .on('webkitAnimationEnd', function (){
                $(this).removeClass('slideIn');
            })
        ;
        if(callback[id]&&(typeof callback[id]=='function')){
            callback[id]()
        }
    }
    /*todo:slideIn不会触发slideOut的animationend，但slideOut的却会触发In的？？？*/
    /*滑出功能*/
    function slideOut(){
        var $top = page_stack.pop();
        if (!$top) {
            return;
        }
        $top.tpl.addClass('slideOut')
            .on('animationend', function () {
                this.remove();
            })
            .on('webkitAnimationEnd', function () {
                this.remove();
            })
        ;
    }
    /*
        基础事件
    */
    /*页面初始化*/
    if (/#.*/gi.test(location.href)) {
        slideIn(location.hash.slice(1));
    }
    /*history前进后退和location.hash触发*/
    $(window).on('hashchange', function (e) {
        /*判断现在这页有没有#*/
        if (/#.+/gi.test(e.newURL)) {
            var hash_index = e.newURL.indexOf('#');
            var id = e.newURL.substring(hash_index+1);  /*现在的id*/
            var stack_length = page_stack.length;       //todo:应该是-1，为什么-2！？？！？！，难道是已经执行了下面的函数再获取！？
            var prev_id = (page_stack[stack_length-2])?page_stack[stack_length-2]['id']:null;//获取之前的id
            //console.log(page_stack,stack_length,prev_id,id,prev_id==id);
            if(prev_id==id){
                slideOut()
            }
            else{
                slideIn(id);
            }
        }
        else{
            slideOut()
        }
    });
    $container.on('click', '.slide_to[data-id]', function () {
        var id = $(this).data('id');
        Hash(id)
    });
    exports.go = function (id) {
        Hash(id)
    };
    exports.back = slideOut;
    /*滑动事件的回调，但页面初始化滑动不会触发*/
    exports.slideCallback = function (option) {
        if(option.id&&option.callback){
            callback[option.id] = option.callback
        }
    }
});