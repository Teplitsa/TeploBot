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
	
	
	/** Feedback form */
    var $form = $('#feedback'),
        $loader = $('#feedback-loader'),
        $message_ok = $('#message-ok'),
        $message_error = $('#message-error');

    $form.submit(function(e){

        e.preventDefault();

        if( !validate_feedback_form() ) {
            return false;
        }

        $form.hide();
        $loader.show();

        $.post(gwptb.ajaxurl, {
            action: 'gwptb_send_feedback',
            topic: $form.find('#feedback-topic').val(),
            name: $form.find('#feedback-name').val(),
            email: $form.find('#feedback-email').val(),
            text: $form.find('#feedback-text').val(),
            nonce: $form.find('#nonce').val()
        }, function(response){

            $loader.hide();

            if(response && response == 0)
                $message_ok.fadeIn(100);
            else
                $message_error.fadeIn(100);
        });

        return true;
    });

    function validate_feedback_form() {

        var $form = $('#feedback'),
            is_valid = true,
			$req = $form.find('.req');
			
			$req.each(function(i){
				var field = $(this);
				
				if (!field.val() ) {
					is_valid = false;
					$form.find('#'+ field.attr('id') +'-error').html(gwptb.field_required).show();
				}
				else{
					$form.find('#'+ field.attr('id') +'-error').html('').hide();
				}
			});
			
			if (is_valid) {
				//test for email
				var $email = $form.find('#feedback-email');
				console.log($email.val());
				console.log(is_email($email.val()));
				
				if(!is_email($email.val())){
					is_valid = false;
					$form.find('#'+ $email.attr('id') +'-error').html(gwptb.email_invalid).show();
				}
				else {
					$form.find('#'+ $email.attr('id') +'-error').html('').hide();
				}
			}

        return is_valid;
    }
	
	
});

function is_email(email) {
    return /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/.test(email);
}