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
	
	/* Set hook */
	$('#gwptb_set_hook').on('click', function(e){
		
		e.preventDefault();
		
		var target = $(this),
			result = '#'+target.attr('id') + '-response';
		
		if (!target.hasClass('green')) {
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
					
					$(result).removeClass('loading');
					
					if (response.data) {
						$('.gwptb-test-response div').empty();
						$(result).html(response.data);						
					}
					
					if (response.type == 'ok') {
						target.addClass('green');
						$('#gwptb_del_hook').removeClass('hidden');
					}
				}
			});
		}
		
	});
	
	/* Remove hook */
	$('#gwptb_del_hook').on('click', function(e){
		
		e.preventDefault();
		
		var target = $(this),
			result = '#'+target.attr('id') + '-response';
		
		if (!target.hasClass('hidden')) {
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
	
					$(result).removeClass('loading');
					
					if (response.data) {
						$('.gwptb-test-response div').empty();
						$(result).html(response.data);
					}
					
					if (response.type == 'ok') {
						target.addClass('hidden');
						$('#gwptb_set_hook').removeClass('green');
					}
				}
			});
		}
		
	});
});