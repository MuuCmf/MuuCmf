$(function(){
	$(window).scroll(function(){
		var h = $(window).scrollTop();
			if(h>=70){
				$('.bootsnav').addClass('scroll');
			}else{
				$('.bootsnav').removeClass('scroll');
			};
	})
})
