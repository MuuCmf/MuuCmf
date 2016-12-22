seajs.config({
    charset: 'utf-8',
    paths:{
        'js':'/static/js',
        'css':'/static/css',
        'seajs':'/static/seajs'
    },
    alias: {
        'jquery':'js/jquery2.1.min.js',
        'jquery_cookie':'js/jquery.cookie.js',
        'zepto':'js/zepto.min.js',
        'zepto_cookie':'js/zepto.cookie.min.js',
        'doT':'js/doT.min.js',
        'plupload':'js/plupload.full.min.js',

        'swiper_js':'css/swiper/swiper.min.js',
        'swiper_css':'css/swiper/swiper.min.css',
        'test':'seajs/function1.js',
        'transit':'js/jquery.transit.min.js'
    }
//        , preload:'style'
});
seajs.use('zepto', function () {
    $(document).ready(function () {
        $(".header-title").tap(function () {
            window.location.reload()
        })
    })
});