/* Scripts */
jQuery(document).ready(function($){
	
	/* Test token **/
	$('#gwptb_test_token').on('click', function(e){
		
		e.preventDefault();
		var target = $(e.target),
			result = '#'+target.attr('id') + '-response';
		
		$.ajax({
			type : "post",
			dataType : "json",
			url : gwptb.ajaxurl,
			data : {
				'action': target.attr('id'),			
				'nonce' : target.attr('data-nonce')					
			},
			beforeSend : function () {
				$(result).addClass('loading');
			},				
			success: function(response) {

				if (response.type == 'ok') {
					$(result).empty().html(response.data);			
					$(result).removeClass('loading');					
				}
			}
		});
	});
	
	
	$('.gwptb-cs-response').delay(2800).fadeOut(800);
	
});