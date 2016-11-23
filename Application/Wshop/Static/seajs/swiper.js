define(function (require,exports,module) {
    require('swiper_js');
    /*
        轮播
    */
    var mySwiper = new Swiper('.nav-swiper-container', {
        //loop:true,
        autoplay: 3000,
        onInit: function (swiper) {
            if(swiper.loopedSlides){
                $(".active-index-num").text(1);
                $('.all-index-num').text(swiper.wrapper[0].childElementCount-2);
            }
            else $('.all-index-num').text(swiper.wrapper[0].childElementCount);
        },
        onSlideChangeStart: function (swiper) {
            var all = swiper.wrapper[0].childElementCount-2;//todo:总数获取的方法可能有问题
            if(swiper.loopedSlides){
                var active = $(".active-index-num");
                if(swiper.isEnd){
                    active.text(1);     //console.log("最后");
                }
                else if(swiper.isBeginning){
                    active.text(all);   //console.log("第一个");
                }
                else{
                    active.text(swiper.activeIndex);
                }
            }
            else $(".active-index-num").text(swiper.activeIndex+1)
        }
    });
});