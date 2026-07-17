window.AppNotify = window.AppNotify || {
    show: function(message, type) {
        let container = document.getElementById('app-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'app-toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1090';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-' + (type || 'danger') + ' border-0';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
        toast.querySelector('.toast-body').textContent = message;
        container.appendChild(toast);
        if (window.bootstrap && window.bootstrap.Toast) {
            const instance = bootstrap.Toast.getOrCreateInstance(toast, { delay: 5000 });
            toast.addEventListener('hidden.bs.toast', function() { toast.remove(); });
            instance.show();
        } else {
            toast.classList.add('show');
            setTimeout(function() { toast.remove(); }, 5000);
        }
        return toast;
    },
    error: function(message) { return this.show(message, 'danger'); }
};

function updateCsrfToken(response){
    if(response && response.token){
        $('input[name=_token]').val(response.token);
    }
}

$(document).ready(function(){
    'use strict';

    $('[data-trigger=server-form]').submit(function(e){
        e.preventDefault();
        let action = $(this).attr('action');
        let valid = true;

        $('.form-control').removeClass('is-invalid');

        $(this).find('[required]').each(function(){
            
            let min = $(this).attr('min') ? $(this).attr('min') : 3;

            if($(this).val().length < min){
                valid = false;
                $(this).addClass('is-invalid');
                if($(this).data('error')) {
                    AppNotify.error($(this).data('error'));
                }
            }
        });
        
        if(valid == false) return false;

        $.ajax({
            type: 'POST',
            url: action,
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function(){
                $('body').append('<div class="preloader"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
            },
            complete: function(){
                $('.preloader').remove();
            },
            success: function(response){
                $('input[name=_token]').val(response.token);
                if(response.error){
                    AppNotify.error(response.message);
                } else {
                    AppNotify.show(response.message, 'success');
                    if(typeof response.html !== 'undefined'){
                        $('body').append(response.html);
                    }
                    $(this).find('input,textarea').val('');
                }
            }
        });
    });

    $('[data-trigger=shorten-form]').submit(function(e){
        e.preventDefault();
        let form = $(this);
        let action = form.attr('action');
        let data = new FormData(this);
        let file = $('#metaimage');

        $('.form-control').removeClass('is-invalid');
        $('#return-error .alert').remove();

        if($("input[name=multiple]").val() == "1"){
            var url = form.find("#urls");
        }else{
            var url = form.find("#url");
        }

        if(url.val().length == 0){
            AppNotify.error(lang.error);
            $('#url,#urls').addClass('is-invalid');
            return false;
        }

        if($("#metaimage").length > 0 && file.get(0).files.length != 0 && file.get(0).files[0].size > 1*1024*1024){
            if(["image/jpeg", "image/jpg"].includes(file.get(0).files[0].type) == false){
                AppNotify.error(lang.imageerror);
                return false;
            }
        }

        let text = form.find('button[type=submit]').text();
        $.ajax({
            type: 'POST',
            url: action,
            data: data,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function(){                
                form.find('button[type=submit]').html('<div class="preloader"><div class="spinner-border spinner-border-sm text-white" role="status"><span class="sr-only">Loading...</span></div></div>');
            },
            complete: function(){
                $('.preloader').remove();
                form.find('button[type=submit]').text(text);
            },
            success: function(response){
                if(response.error){
                    return AppNotify.error(response.message);
                }
                let shorturl = response.data.shorturl;
                AppNotify.show(response.message, 'success');

                if($("input[name=multiple]").val() == "1"){
                    refreshlinks();
                    return url.val(response.data);
                }

                if($('#output-result').length > 0){
                    $('#output-result #qr-result').html('<span class="p-2 bg-light border border-success d-inline-block rounded"><img src="'+shorturl+'/qr" width="100" class="rounded"></span>');
                    $('#output-result').removeClass('d-none');
                }

                if($('#successModal').length > 0){
                    triggerShortModal(shorturl);
                    refreshlinks();
                    $("#advancedOptions").removeClass('show');
                    form.find('input,textarea,select').val('');
                } else {
                    url.val(shorturl);

                    form.find("[type=submit]").addClass('d-none');
                    form.find("[type=button]").attr("data-clipboard-text", shorturl).removeClass('d-none');
                    $("#advancedOptions").removeClass('show');

                    new ClipboardJS('[data-trigger=shorten-form] [type=button]').on('success', function(){
                        form.find("[type=submit]").removeClass('d-none');
                        form.find("[type=button]").addClass('d-none');
                        form.find('input,textarea,select').val('');
                    }); 
                    
                }
            }            
        });
    });    

    $("#search").submit(function(e){
        e.preventDefault();
        var val = $(this).find("input[type=text]").val();
        var action = $(this).attr("action");
          $.ajax({
              type: "GET",
              url: action,
              data: "q="+val,
              beforeSend: function() {
                $("#return-ajax").html('<div class="preloader"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
              },
              complete: function() {      
                $('.preloader').fadeOut("fast", function(){$(this).remove()});
              },          
              success: function (response) { 
                $("#return-ajax").html(response);
                $("#link-holder").slideUp('fast');
                $("#return-ajax").slideDown('fast');
                feather.replace();
              }
          });           
    });

    $(document).on('click', '[data-trigger=archiveselected]', function(e){
        e.preventDefault();
        let trigger = $(this);
        let form = trigger.closest('form');
        let ids = [];
		$('[data-dynamic]').each(function(){
			if($(this).prop('checked')) ids.push($(this).val());
		});

        form.find('input[name=selected]').val(JSON.stringify(ids));

        $.ajax({
            type: "POST",
            url: trigger.attr('formaction') || form.attr('action'),
            data: {
                _token: form.find('input[name=_token]').val(),
                link: form.find('input[name=link]').val(),
                selected: form.find('input[name=selected]').val()
            },
            beforeSend: function() {
              $("#return-ajax").html('<div class="preloader"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
            },
            complete: function() {      
              $('.preloader').fadeOut("fast", function(){$(this).remove()});
            },          
            success: function (response) { 
                updateCsrfToken(response);
                if(response.error){
                    return AppNotify.error(response.message);
                }
                AppNotify.show(response.message, 'success');
                refreshlinks(ids);
                feather.replace();
            },
            error: function(xhr) {
                updateCsrfToken(xhr.responseJSON || {});
                let message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An unexpected error occurred. Please try again.';
                AppNotify.error(message);
            }
        });   
    });

    $(document).on('change', "#payment-form select", function(){
        var $total = $("#total");
        $.ajax({
            type: "GET",
            url: $("#taxrate").data('url'),
            data: "country="+$(this).val()+"&coupon="+$("#coupon").val(),        
            success: function (response) { 
              if(!response.error){			
                $("#taxrate").html(response.html);
				$total.text(response.newprice);
              }
            }
        }); 
    });
    $('#payment-form').submit(function(){
        $(this).find('button[type=submit]').attr('disabled','disabled').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    });
    $('[data-blockid]').click(function(){
        let id = $(this).data('blockid');
        $.ajax({
            type: "POST",
            url: window.location.href,
            data: "action=clicked&blockid="+id,
        }); 
    });
});

function refreshlinks(ids = null){
    
    if($("#link-holder").length < 1) return false;

    if(ids){
        ids.forEach(function(item){
            $.ajax({
                type: "GET",
                url: $("#link-holder").data('fetch')+'?id='+item,
                success: function (response) {
                  $("#link-"+item).html(response);
                  feather.replace();
                }
            });
        });
    } else {
        $.ajax({
            type: "GET",
            url: $("#link-holder").data('refresh'),
            success: function (response) {
              $("#link-holder").html(response);
              feather.replace();
            }
        });
    }
}

function triggerShortModal(shorturl){
    $('#successModal #modal-input').val(shorturl);
    $('#successModal .modal-qr p').html('<img src="'+shorturl+'/qr" width="150" class="rounded">');
    $('#successModal .copy').attr('data-clipboard-text', shorturl);
    $('#successModal #downloadPNG').attr('href', shorturl+'/qr/download/png/1000');
    $('#successModal ul li').filter(':first-child').find('a').attr('href', shorturl+'/qr/download/pdf/1000');
    $('#successModal ul li').filter(':nth-child(2)').find('a').attr('href', shorturl+'/qr/download/svg/1000');
    $('#successModal #modal-share a').each(function(){
        let href = $(this).attr('href');
        $(this).attr('href', href.replace('--url--', encodeURI(shorturl)));
    })

    new ClipboardJS('#successModal .copy', {
        container: document.getElementById('successModal')
    }).on('success', function(){
        $('#successModal .copy').text(lang.copy);
    }); 
    bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal')).show();
}
