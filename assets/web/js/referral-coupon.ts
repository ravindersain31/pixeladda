declare var grecaptcha: any;
declare function resetSpecificRecaptcha(id: string | null): void;
document.addEventListener('DOMContentLoaded', () => {

    $('#referralModal').on('show.bs.modal', function () {
        
        $('#ref-email-step').removeClass('d-none');
        $('#ref-link-step').addClass('d-none');
        $('.terms-condition-text').removeClass('d-none');
        $('#referral_coupon_email').val('');   
        $('#referralLink').val('');  
       
        $('#referralLink').val('');

        $('#referralForm .invalid-feedback').remove();
        $('#referralForm input').removeClass('is-invalid');    

        const recaptchaContactUs = $(`#referralForm [id^="recaptcha_render_box_"]`);
		const recaptchaIdContactUs = recaptchaContactUs.attr('id') || null; 
		resetSpecificRecaptcha(recaptchaIdContactUs);
  
    });

    const $form = $('#referralForm');
    const $emailInput = $('#referral_coupon_email');
    const $button = $('#getReferralLink');
    
    if ($button.length === 0 || $emailInput.length === 0) {
        return;
    }

    // @ts-ignore
    $.validator.setDefaults({ ignore: [] });

    // @ts-ignore
    $form.validate({
        rules: {
            'referral_coupon[email]': {
                required: true,
                email: true
            },
            'referral_coupon[recaptcha][token]': {
                required: true
            }
                   
        },
        messages: {
            'referral_coupon[email]': {
                required: 'Email address is required.',
                email: 'Please enter a valid email address.'
            },
           'referral_coupon[recaptcha][token]': {
                required: "Please check the 'I am not a robot' box."
            }
                      
        },

        errorElement: 'div',
        errorClass: 'invalid-feedback',

        highlight: function (element: any) {
            $(element).addClass('is-invalid');
        },

        unhighlight: function (element: any) {
            $(element).removeClass('is-invalid');
        },

        errorPlacement: function (error: any, element: any) {
           if (element.attr("name") === "referral_coupon[recaptcha][token]") {
                error.appendTo(".recaptcha-wrapper");
            } else {
                error.insertAfter(element);
            }
        },

        submitHandler: function ($form: JQuery) {

            const emailValue = $emailInput.val();

            if (typeof emailValue !== 'string') {
                return;
            }

            const email = $.trim(emailValue);
           
            $.ajax({
                url: '/customer-referral',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email }),
                dataType: 'json',

                success: function (data: any) {
                    if (!data.status) {
                        alert(data.message || 'Failed to generate referral link.');
                        return;
                    }
                
                    $('#ref-email-step').addClass('d-none');
                    $('#ref-link-step').removeClass('d-none');
            
                    $('#referralLink').val(data.referralUrl);
                    $('.terms-condition-text').addClass('d-none');
                   
                    if (data.userExists === false && data.message) {
                        $('#referralMessage')
                            .removeClass('d-none')
                            .text(data.message);
                    } else {
                        $('#referralMessage').addClass('d-none');
                    }
                },

                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    alert('Something went wrong. Please try again.');
                }
            });
        }
        
    });

    $button.on('click', function (e) {
        e.preventDefault();
        $form.submit();
    });

});

function copyReferralLink(): void {

    const $input = $('#referralLink');
    const linkValue = $input.val();

    if (typeof linkValue !== 'string' || !linkValue.length) {
        alert('No referral link to copy.');
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
            .writeText(linkValue)
            .then(() => console.log('Referral link copied!'))
            .catch(() => alert('Failed to copy.'));
    } else {
        $input.trigger('select');
        document.execCommand('copy');
        console.log('Referral link copied!');
    }
}

(window as any).copyReferralLink = copyReferralLink;

