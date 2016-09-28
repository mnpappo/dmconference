jQuery( document ).ready(function() {
    sdLogsProcess();
    checkDuplicationMode();
});

function sdCheckProcess(button, url){
    sdShowMessage('');
    var button = jQuery(button);
    var block = button.closest('.sd_tmpl_block');
    var form = button.closest('form');
    var send = 1;

    //check fields
    send = checkFields(form, 'ftp');
    //if( jQuery('#field_duplicate_mode_2').is(':checked') ) {
        send = checkFields(form, 'db');
    //}

    if(send == 1){
        block.addClass('loading');
        form.find('#sd_sub_action').val('check');

        jQuery.ajax({
            url: url + '&sub_action=check',
            type: 'post',
            dataType: 'json',
            data: form.serialize(),
            success: function(data) {
                if(data){
                    if(data.msg){
                        sdShowMessage(data.msg, data.status);
                    }

                    if(data.status == 1){

                    }
                }
                block.removeClass('loading');
            },
            error: function(){
                block.removeClass('loading');
            }
        });
    }
}

function sdStartProcess(button, url){
    var button = jQuery(button);
    var block = button.closest('.sd_tmpl_block');
    var form = button.closest('form');
    var send = 1;

    //check fields
    if(send == 1) {
        send = checkFields(form, 'ftp');
    }
    if(send == 1) {
        send = checkFields(form, 'db', 'full');
    }
    if(send == 1) {
        send = checkFields(form, 'url');
    }

    if(send == 1){
        block.addClass('loading');
        form.find('#sd_sub_action').val('start');

        sdShowMessage(sd_parameters.scan_process_message,3,-1);

        jQuery.ajax({
            url: url + '&sub_action=check',
            type: 'post',
            dataType: 'json',
            data: form.serialize(),
            success: function(data) {
                if(data){

                    if(data.msg){
                        sdShowMessage(data.msg, data.status);
                    }else{
                        sdShowMessage('');
                    }

                    if(data.status == 1){

                    }

                    jQuery('#sd_scan_block').find('#max_execution_time').text(data.server.server_stat.max_execution_time);
                    jQuery('#sd_scan_block').find('#max_execution_time_up').text(data.server.server_stat.max_execution_time_up);
                    jQuery('#sd_scan_block').find('#memory_limit').text(data.server.server_stat.memory_limit);
                    jQuery('#sd_scan_block').find('#memory_limit_up').text(data.server.server_stat.memory_limit_up);
                    jQuery('#sd_scan_block').find('#zip').text(data.server.server_stat.zip);
                    jQuery('#sd_scan_block').find('#server_free_space').text(data.server.server_stat.server_free_space);
                    jQuery('#sd_scan_block').find('#mysql').text(data.server.server_stat.mysql);
                    jQuery('#sd_scan_block').find('#mysql_version').text(data.server.server_stat.mysql_version);
                    jQuery('#sd_scan_block').find('#queue_count').text(data.server.files_stat.queue_count);
                    jQuery('#sd_scan_block').find('#queue_size').text(data.server.files_stat.queue_size);
                    jQuery('#sd_scan_block').find('#skipped_count').text(data.server.files_stat.skipped_count);
                    jQuery('#sd_scan_block').find('#skipped_size').text(data.server.files_stat.skipped_size);
                    jQuery('#sd_scan_block').find('#mysql_tables_count').text(data.server.db_stat.count_tables);

                    jQuery('#site_duplicator_page_over').attr('class', 'wrap');
                    jQuery('#site_duplicator_page_over').addClass('sd_job_status_'+data.job_status);
                    jQuery('#site_duplicator_page_over').addClass('sd_job_step_'+data.job_step);
                }
                block.removeClass('loading');
            },
            error: function(){
                sdShowMessage('');
                block.removeClass('loading');
            }
        });
    }
}

function sdStopProcess(button, url){
    var button = jQuery(button);
    var block = button.closest('.sd_tmpl_block');
    var form = button.closest('form');
    var send = 1;

    if(send == 1){
        block.addClass('loading');
        form.find('#sd_sub_action').val('stop');

        jQuery.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: form.serialize(),
            success: function(data) {
                if(data){
                    if(data.msg){
                        sdShowMessage(data.msg, data.status);
                    }

                    if(data.status == 1){
                        jQuery('#site_duplicator_page_over').attr('class', 'wrap');
                        jQuery('#site_duplicator_page_over').addClass('sd_job_status_0');
                        jQuery('#site_duplicator_page_over').addClass('sd_job_step_0');

                        jQuery('#sd_logs_block').find('.sd_block_body_over').text('');
                    }
                }
                block.removeClass('loading');
            },
            error: function(){
                sdShowMessage('');
                block.removeClass('loading');
            }
        });
    }
}

function sdJobProcess(button, url, job){
    var button = jQuery(button);
    var block = button.closest('.sd_tmpl_block');
    var form = button.closest('form');
    var url = (url ? url : form.attr('action'));
    var send = 1;

    if(send == 1){
        block.addClass('loading');

        jQuery('#site_duplicator_page_over').attr('class', 'wrap');
        jQuery('#site_duplicator_page_over').addClass('sd_job_status_1');
        jQuery('#site_duplicator_page_over').addClass('sd_job_step_1');

        sdLogsProcess();


        jQuery.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            //data: form.serialize(),
            data: {'sign': jQuery('#sd_sign').val()},
            success: function(data) {
                if(data){
                    if(data.msg){

                    }

                    if(data.status == 1){
                        jQuery('#site_duplicator_page_over').attr('class', 'wrap');
                        jQuery('#site_duplicator_page_over').addClass('sd_job_status_'+data.job_status);
                        jQuery('#site_duplicator_page_over').addClass('sd_job_step_'+data.job_step);
                    }
                }
                block.removeClass('loading');
            },
            error: function(){
                block.removeClass('loading');
            }
        });
    }
}

function sdLogsProcess(){
    var url = sd_parameters.url+'admin-post.php?action=sd_job_request&sub_action=logs';

    if( jQuery('#site_duplicator_page_over').hasClass('sd_job_status_1') ){
        jQuery.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: {'sign': jQuery('#sd_sign').val()},
            cache:false,
            success: function(data) {
                if(data){

                    if(data.logs){
                        jQuery.each(data.logs, function(ind,val){
                            if( jQuery('#sd_logs_block').find('.sd_block_body_over').find('.log_line.log_id_'+ind).length == 0 ){
                                jQuery('#sd_logs_block').find('.sd_block_body_over').prepend(jQuery(val));
                            }
                        })
                    }

                    if(data.job_status && data.job_status != 2){
                        jQuery('#site_duplicator_page_over').attr('class', 'wrap');
                        jQuery('#site_duplicator_page_over').addClass('sd_job_status_'+data.job_status);
                        jQuery('#site_duplicator_page_over').addClass('sd_job_step_'+data.job_step);
                    }
                }

                setTimeout(function(){
                    sdLogsProcess();
                },5000);
            },
            error: function(){
                setTimeout(function(){
                    sdLogsProcess();
                },5000);
            }
        });
    }
}

var sdMessageTimer;
function sdShowMessage(text, status, timeout){
    clearTimeout(sdMessageTimer);

    if(status){
        var text = '<span class="status_'+status+'">'+text+'</span>'
    }
    var timeout = ( !timeout ? 4000 : timeout);

    jQuery('#sd_settings_message').html( text );

    //if(timeout > 0){
    //    sdMessageTimer = setTimeout(function(){ sdShowMessage('') },timeout);
    //}
}


function checkDuplicationMode(){
    /*if( jQuery('#field_duplicate_mode_2').is(':checked') ){
        jQuery('#sd_mysql_block').css('display','block');
    }else{
        jQuery('#sd_mysql_block').css('display','none');
    }*/
}

function checkFields(container, mode, sub_mode){
    var send = 1;
    var container = (!container ? jQuery(document.body) : container);

    if(mode == 'ftp'){
        if( jQuery.trim(container.find('#field_ftp_host').val()) == '' ){
            container.find('#field_ftp_host').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_ftp_host').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_ftp_username').val()) == '' ){
            container.find('#field_ftp_username').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_ftp_username').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_ftp_password').val()) == '' ){
            container.find('#field_ftp_password').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_ftp_password').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_ftp_path').val()) == '' ){
            container.find('#field_ftp_path').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_ftp_path').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_ftp_port').val()) == '' ){
            container.find('#field_ftp_port').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_ftp_port').removeClass('invalid');
        }
    }
    if(mode == 'db'){
        if( jQuery.trim(container.find('#field_db_host').val()) == '' ){
            container.find('#field_db_host').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_db_host').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_db_port').val()) == '' ){
            container.find('#field_db_port').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_db_port').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_db_name').val()) == '' ){
            container.find('#field_db_name').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_db_name').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_db_username').val()) == '' ){
            container.find('#field_db_username').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_db_username').removeClass('invalid');
        }
        if( jQuery.trim(container.find('#field_db_password').val()) == '' ){
            container.find('#field_db_password').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_db_password').removeClass('invalid');
        }
        if(sub_mode == 'full'){
            if( jQuery.trim(container.find('#field_db_table_prefix').val()) == '' ){
                container.find('#field_db_table_prefix').addClass('invalid');
                send = 0;
            }else{
                container.find('#field_db_table_prefix').removeClass('invalid');
            }
        }
    }
    if(mode == 'url'){
        if( jQuery.trim(container.find('#field_new_url').val()) == '' || jQuery.trim(container.find('#field_new_url').val()) == 'http://' ){
            container.find('#field_new_url').addClass('invalid');
            send = 0;
        }else{
            container.find('#field_new_url').removeClass('invalid');
        }
    }

    return send;
}

function popupAuth(button, link){
    var button = jQuery(button);

    jQuery.arcticmodal({
        type: 'ajax',
        url: link,
        ajax: {
            type: 'get',
            cache: false,
            dataType: 'html',
            success: function(data, el, responce) {
                var h = jQuery('<div id="sd_popup_form_over" class="box-modal" style="display:block;">'+responce+'</div>');
                data.body.html(h);
            }
        },
        beforeClose: function(data, el) {

        }
    });
}

function popupAuthType(button, type){
    var button = jQuery(button);
    var form = button.closest('form');
    if(type == 1){
        form.find('#tr_confirm_password').css('visibility','hidden');
        form.find('#auth_but_login').css('display','inline-block');
        form.find('#auth_but_register').css('display','none');
    }else{
        form.find('#tr_confirm_password').css('visibility','visible');
        form.find('#auth_but_login').css('display','none');
        form.find('#auth_but_register').css('display','inline-block');
    }
}

function processAuth(button){
    var button = jQuery(button);
    var form = button.closest('form');
    var send = 1;
    if( form.find('.auth_type_field:checked').val() == 2 ){
        if( jQuery.trim(form.find('.auth_email_field').val()) == '' ){
            form.find('.auth_email_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_email_field').removeClass('invalid');
        }
        if( jQuery.trim(form.find('.auth_password1_field').val()) == '' ){
            form.find('.auth_password1_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_password1_field').removeClass('invalid');
        }
        if( jQuery.trim(form.find('.auth_password2_field').val()) == '' ){
            form.find('.auth_password2_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_password2_field').removeClass('invalid');
        }
    }else if( form.find('.auth_type_field:checked').val() == 1 ){
        if( jQuery.trim(form.find('.auth_email_field').val()) == '' ){
            form.find('.auth_email_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_email_field').removeClass('invalid');
        }
        if( jQuery.trim(form.find('.auth_password1_field').val()) == '' ){
            form.find('.auth_password1_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_password1_field').removeClass('invalid');
        }
    }else{
        send = 0;
    }

    if(send == 1){
        button.css('visibility','hidden');
        jQuery.ajax({
            url: form.attr('action'),
            type: 'post',
            dataType: 'json',
            data: form.serialize(),
            success: function(data) {
                if(data){
                    if(data.msg){
                        form.find('#sd_answer_msg').remove();
                        var message = '<div id="sd_answer_msg" class="status_'+data.status+'">'+data.msg+'</div>';
                        jQuery( form ).prepend( message );
                    }

                    if(data.status == 1){
                        form.find('input[type="text"]').val('');
                        form.find('input[type="password"]').val('');
                    }
                    if(data.redirect_url && data.redirect_url != ''){
                        window.location.href = data.redirect_url;
                    }
                }
                button.css('visibility','visible');
            }
        });
    }
}

// set skl 0
function setVisible(id, visible)
{
    jQuery("#" + id).css('display', visible);
}

function sd_supportFormNormalize() {
    if (jQuery('#sd_support_text_container')[0].style.display == 'none') {
        jQuery('#sd_support_text').val('');
    }
    jQuery('#sd_support_text_container').show();
    jQuery('#sd-support_send_button').show();
    jQuery('#sd_support_thank_container').hide();
    jQuery('#sd_support_error_container').hide();
}



function sd_sendSupportText() {

    if(jQuery('#sd_support_text').val().trim() == '') {
        return;
    }

    var data = {
        'action': 'sd_sendSupport',

        'message': jQuery('#sd_support_text').val(),
        'attache_logs': (jQuery('#sd_attach_logs').attr("checked") == 'checked') ? 1 : 0
    }

    jQuery.post(ajaxurl, data, function (response) {
        try {
            var res = jQuery.parseJSON(response);
            if (res) {
                jQuery('#sd_support_text_container').hide();
                jQuery('#sd-support_send_button').hide();
                if(res.status=='success') {
                    jQuery('#sd_support_thank_container').show();
                } else if(res.status=='error') {
                    jQuery('#sd_support_error_container').show();
                }
            } else {
                jQuery('.tb-close-icon').click();
            }
        } catch (e) {
            jQuery('.tb-close-icon').click();
        }
    });
}
