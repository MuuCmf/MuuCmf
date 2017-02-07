+(function($){
	$(window).scroll(function() {

		//$('.ecology .muu').each();

		$('.spanele').each(function(){
		var imagePos = $(this).offset().top;
		
		var topOfWindow = $(window).scrollTop();
			if (imagePos < topOfWindow+700) {
				if($(this).hasClass('muu')){
					$(this).addClass("fadeInDown");
				}
				if($(this).hasClass('deve')){
					$(this).addClass("fadeInLeft");
				}
				if($(this).hasClass('store')){
					$(this).addClass("fadeInRight");
				}
				if($(this).hasClass('need')){
					$(this).addClass("fadeInUp");
				}
			}
		});	
        
        $('.services').each(function(){
			var imagePos = $(this).offset().top;
		
			var topOfWindow = $(window).scrollTop();
			if (imagePos < topOfWindow+500) {
				$(this).addClass("bounceIn");
			}
		});	
        
        $('.developer .fh5co-box').each(function(){
		var imagePos = $(this).offset().top;
		
		var topOfWindow = $(window).scrollTop();
			if (imagePos < topOfWindow+500) {
				$(this).addClass("fadeInUp");
			}
		});	
				
	});

})(jQuery);
		