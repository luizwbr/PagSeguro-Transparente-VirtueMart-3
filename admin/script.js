/**
* MoIP VirtueMart 2.0.x
**/

// botão sim/não
jQuery(document).ready( function(){ 
    jQuery(".cb-enable").click(function(){
        var parent = jQuery(this).parents('.switch');
        jQuery('.cb-disable',parent).removeClass('selected');
        jQuery(this).addClass('selected');
        jQuery('.checkbox',parent).attr('checked', true);
    });
    jQuery(".cb-disable").click(function(){
        var parent = jQuery(this).parents('.switch');
        jQuery('.cb-enable',parent).removeClass('selected');
        jQuery(this).addClass('selected');
        jQuery('.checkbox',parent).attr('checked', false);
    });
	
	verifica_boleto();
	verifica_cartao();
	verifica_debito();
});


// esconder/mostrar ao ativar boleto
jQuery('input[name=ativar_boleto]').live('change',function(){
	verifica_boleto();
});
// esconder mostrar ativar cartao
jQuery('input[name=ativar_cartao]').live('change',function(){
	verifica_cartao();
});
// esconder mostrar ativar cartao
jQuery('input[name=ativar_debito]').live('change',function(){
	verifica_debito();
});

function verifica_boleto() {
	var campo = jQuery('input[name=boleto]').parent().parent().parent();
	if (jQuery('input[name=ativar_boleto]:checked').val()=='1') {
		campo.prev().show();
		campo.show();
	} else {
		campo.hide();
		campo.prev().hide();
	}
	//alert(jQuery('input[name=ativar_boleto]:checked').val())
}
function verifica_cartao() {
	var campo = jQuery('input[name=cartao_visa],input[name=cartao_master],input[name=cartao_hipercard],input[name=cartao_diners],input[name=cartao_amex],input[name=cartao_aura]').parent().parent().parent();
	if (jQuery('input[name=ativar_cartao]:checked').val()=='1') {
		campo.prev().show();
		campo.show();
	} else {
		campo.hide();
		campo.prev().hide();
	}
}
function verifica_debito() {
	var campo = jQuery('input[name=debito_bb],input[name=debito_bradesco],input[name=debito_banrisul],input[name=debito_itau],input[name=debito_hsbc]').parent().parent().parent();
	if (jQuery('input[name=ativar_debito]:checked').val()=='1') {
		campo.prev().show();
		campo.show();
	} else {
		campo.hide();
		campo.prev().hide();
	}
}