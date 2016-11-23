define(function (require,exports,module) {
    console.log("test内部");
    if(false){
        require('doT');
    }

    var a = require('seajs/tpl');


    if(false){
        require.async('swiper_js', function() {
            console.log('async')
        });
    }

    function init_cool(){
        console.log("init_cool")
    }
    exports.init_cool = init_cool;
    exports.gogogo = function () {
        console.log("执行回调参数gogogo")
    };


});