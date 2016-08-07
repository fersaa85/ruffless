$(document).ready(function(){

    share();
    results();

});

function share(){

    $('body').on('click', '.share', function(e){
        e.preventDefault();
        $.post($(this).attr('href'), { share: $(this).data('share') });
    });

}

function results(){
    $('form[name=postResults]').on('click', 'input[type=radio]', function(e){

        var $confirm = confirm("Confirma el resultado del partido \n Esta accion asignara los puntos a los participantes de este partido");
        if($confirm){
            $.post($('form[name=postResults]').attr('action'), { name: $(this).attr('name'), value: $(this).val(), _token: $('input[name=_token]').val() });
        }

    });
}