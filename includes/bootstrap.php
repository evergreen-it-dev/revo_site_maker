<script src="//code.jquery.com/jquery-1.12.0.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
<script>
    jQuery(document).ready(function() {

		//search-context
        jQuery(document).on("click", ".search-context", function (event) {
            var form_data = jQuery('#context_form').serialize();
            form_data = form_data+'&action=search';
            jQuery.ajax({
                url: '<?php echo $ajax; ?>form_submit.php',
                type: "post",
                dataType: 'json',
                data: form_data,
                success: function(data){
                    //jQuery(".alerts").html(data);
                    console.log(data);
                    if(data.status == 'error'){
                        alert(data.message);
                    }
                    else if(data.status == 'success'){
                        $('#tpl_name').val(data.name);
                        $('#tpl_context').val(data.context);
                        $('#hidden_context').val(data.context);
                        $('#tpl_description').val(data.description);
                        $('#tpl_siteurl').val(data.siteurl);

                        $.each(data.statuses, function (key, v) {
                            checkStatus(key, v);
                        });

                        $('.user_name').text(data.context);
                        $('.context_name').text(data.context);

                        var text = $('.config-text').text();
                        $('.config').text(text);
                        $('.show-config').show();

                        $('.btn-start').show();
                        $('.context-block').show();
                    }
                },
                error: function (request, status, error) {
                    //alert(request.responseText);
                }
            });
			event.preventDefault();
		});
        /*
        $(document).on('keypress', function(e) {
            var tag = e.target.tagName.toLowerCase();
            if ( e.which === 119 && tag != 'input' && tag != 'textarea')
                doSomething();
        });
        */
		//search-context

		//save context settings
        jQuery(document).on("click", ".save-settings", function (event) {
            var form_data = jQuery('#context_form').serialize();
            form_data = form_data+'&action=update';
            jQuery.ajax({
                url: '<?php echo $ajax; ?>form_submit.php',
                type: "post",
                dataType: 'json',
                data: form_data,
                success: function(data){
                    //jQuery(".alerts").html(data);
                    console.log(data);
                    if(data.status == 'error'){
                        alert(data.message);
                    }
                    else if(data.status == 'success'){
                        alert(data.message);
                    }
                },
                error: function (request, status, error) {
                    //alert(request.responseText);
                }
            });
            event.preventDefault();
		});
		//save context settings

		//save context
        jQuery(document).on("click", "button.create-task", function (event) {
			if(window.confirm("Запустити завдання?")){
                var config = $(this).data('config');
				componentCreate(config);
			}
			event.preventDefault();
		});
		//save context

    });

        function componentCreate(config){
            var form_data = jQuery('#context_form').serialize();
            form_data = form_data+'&action=create&config='+config;
            jQuery.ajax({
                url: '<?php echo $ajax; ?>form_submit.php',
                type: "post",
                dataType: 'json',
                data: form_data,
                beforeSend: function () {
                    $('.ajax-preloader').show();
                },
                success: function(data){
                    $('.ajax-preloader').hide();
                    //jQuery(".alerts").html(data);
                    console.log(data);
                    if(data.status == 'error'){
                        alert(data.message);
                    }
                    else if(data.status == 'success'){
                        console.log(data.message);
                        checkStatus(config, 1);
                        if(data.next != ''){
                            console.log(data.next);
                            componentCreate(data.next);
                        }
                    }
                },
                error: function (request, status, error) {
                    //alert(request.responseText);
                }
            });
        }

        function checkStatus(status, check){
            if(check == 1){
                $('.create-'+status+' .undone').hide();
                $('.create-'+status+' .done').show();
            }else{
                $('.create-'+status+' .undone').show();
                $('.create-'+status+' .done').hide();
            }
        }
</script>

<!-- modx revo menu styles -->
<style>
	#modx-content{
		height:90%;
	}
	.container {
		padding:25px;
		width: 100%;
		height: 100%;
		overflow-y: auto;
	}
	.modx-subnav * {
		-webkit-box-sizing: content-box!important;
		-moz-box-sizing: content-box!important;
		box-sizing: content-box!important;
	}
</style>
<!-- modx revo menu styles -->

<style>
    .create-site-container{font-family: monospace;}
    .create-site-container .mb25{margin-bottom:25px;}
    .create-site-container .mt25{margin-top:25px;}
    .create-site-container .mb15{margin-bottom:15px;}
    .create-site-container .mt15{margin-top:15px;}
    .create-site-container .center{text-align:center;}
    .create-site-container .right{text-align:right;}
    .create-site-container .f16{font-size:16px;}
    .create-site-container .f18{font-size:18px;}
    .create-site-container .border{border-bottom: 1px dashed #ccc;margin: 25px 0px;}
    .create-site-container .tv_border{border:1px dotted #ccc;border-radius:3px;}
    .create-site-container .p15{padding:15px;}
    .create-site-container .h250{height:250px!important;}
    .create-site-container #tpl_code{height: 500px;}
    .create-site-container .context-block{display:none;}
    .create-site-container .done{display:none;}
    .create-site-container .ajax-preloader{display:none; position: fixed;left: 0;top: 50%;z-index: 9999;}
    .create-site-container .ajax-preloader-img{width:125px;}
    .create-site-container .show-config{display: none;}
    .create-site-container .config-text{display:none;}
    .create-site-container .config{height:500px;}
    .create-site-container .create-task{display:none;}
</style>