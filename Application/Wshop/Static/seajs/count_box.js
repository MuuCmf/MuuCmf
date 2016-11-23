define(function (require,exports,module) {

    /*如果页面多出显示同样数据的count，可以用name作标识*/
    /*提取最小值最大值设置操作*//*可以正的就最大值，负的最小值，先只做最大值，现在最小值是1*/

    var max_value = 10;
    exports.max = function (max) {
    };

    exports.add = function (box) {
        var input = box.find('.count-input');
        var now_num = parseInt(input.val());
        var new_num = (isNaN(now_num))?1:now_num+1;
        input.val(new_num);
    };

    exports.cut = function (box) {
        var input = box.find('.count-input');
        var now_num = parseInt(input.val());
        var new_num = (isNaN(now_num))?1:now_num-1;
        if(new_num>=1) input.val(new_num)
    };


});

