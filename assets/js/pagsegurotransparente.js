	/**
 *  Métodos que tratam do acesso à janela popup dos pagamentos
 */
var retorno;
var mpg_popup;
window.name="loja";
var parcelas_array = new Array();
var debito = new Array();
debito['bradesco'] 	= 'Bradesco';
debito['bb'] 		= 'BancoDoBrasil';
debito['banrisul']	= 'Banrisul';
debito['itau'] 		= 'Itau';
debito['hsbc'] 		= 'Hsbc';
var cartoes = new Array();
cartoes['amex'] 	= 'American Express';
cartoes['diners'] 	= 'Diners';
cartoes['hipercard']= 'Hipercard';
cartoes['visa'] 	= 'Visa';
cartoes['master'] 	= 'Mastercard';
cartoes['elo'] 		= 'Elo';
cartoes['aura']		= 'Aura';
function fabrewin(jan) {
    if(navigator.appName.indexOf("Netscape") != -1) {
       mpg_popup = window.open("", "mpg_popup","toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=0,screenX=0,screenY=0,left=0,top=0,width=800,height=600");
     }
    else {
       mpg_popup = window.open("", "mpg_popup","toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=1,screenX=0,screenY=0,left=0,top=0,width=800,height=600");
    }
	if (jan == 1) {
	
	}
	return true;
}
function FormataValor(id, tammax, teclapres) {
	if(window.event) { // Internet Explorer
	    var tecla = teclapres.keyCode;
	} else if(teclapres.which) { // Nestcape / firefox
    	var tecla = teclapres.which;
	}
	vr = document.getElementById(id).value;
	vr = vr.toString().replace( "/", "" );
	vr = vr.toString().replace( "/", "" );
	vr = vr.toString().replace( ",", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	tam = vr.length;
	if (tam < tammax && tecla != 8){ tam = vr.length + 1; }
	if (tecla == 8 ){ tam = tam - 1; }
    if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
		if ( tam <= 2 ){
		    document.getElementById(id).value = vr; }
		if ( (tam > 2) && (tam <= 5) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 2 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 6) && (tam <= 8) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 5 ) + '' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 9) && (tam <= 11) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 12) && (tam <= 14) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 11 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 15) && (tam <= 17) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 14 ) + '.' + vr.substr( tam - 14, 3 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam );
	    }
	}
}
var tentativa = 1;
var aviso = 1;
function getBloqueador() {
    var janela = window.open("#","janelaBloq", "width=1, height=1, top=0, left=0, scrollbars=no, status=no, resizable=no, directories=no, location=no, menubar=no, titlebar=no, toolbar=no");
    if (janela == null) {
        if (tentativa == 1) {
            alert("Bloqueador de popup ativado. Clique na barra amarela do seu navegador e marque a opção 'Sempre permitir para este site'.");
            tentativa++;
            return false;
        } else if ((tentativa > 1) && (tentativa <= 3)) {
            alert("Tentativa " + tentativa + " de 3: O bloqueador ainda está ativado.");
            tentativa++;
            return false;
        } else if (tentativa > 3) {
			if (aviso == 1) {
				if (confirm("O bloqueador de popups ainda está ativado, você pode ter dificuldades para acessar o site.\n\nDeseja continuar assim mesmo?")) {
					aviso = 0;
					return true;
                } else {
					aviso = 0;
					return false;
                }
			}
        }
    } else {
		janela.close();
		return true;
    }
}
/**
 *  Métodos de acesso/persistencia na base
 */
var erro = false;
// função que arrenda números x com n casas decimais
function arredondamento (x, n){
	if (n < 0 || n > 10) return x;
	var pow10 = Math.pow (10, n);
	var y = x * pow10;
	return (Math.round (y)/pow10).toFixed(2);
}
function show_parcelas(item) {
	var id		= '';
	//var debito  = new Array('visa_electron','maestro');	
	var cartoes_parcelas = new Array('visa','master','diners','elo','amex','discover','jcb','aura','visa_electron','maestro','hipercard');
	
	jQuery(cartoes_parcelas).each(function(index,cartao){    	
		id = '#div_'+cartao;		
		if (jQuery(id).length > 0) {
			if (this.erro) {
				mostra_erro(true);
			} else {
				mostra_erro(false);
			}
			mostra_div(id,item,cartao);
		}
	});
}
function mostra_div(id,item,valor) {
	if (item == valor) {
		var bandeira = valor;	
		if (valor == 'master') {
			bandeira = 'mastercard';
		}
		var texto_parcela = '';
		
		if (max_parcela_sem_juros > 1) {
			PagSeguroDirectPayment.getInstallments({
			 	amount: order_total,
			 	brand: bandeira,		 	
			 	maxInstallmentNoInterest: max_parcela_sem_juros,
			 	success: function(response) {
					parcelamento_retorno(id, bandeira, valor, response);
			 	},
			 	error: function(response) {
			   		//tratamento do erro
			 	},
			 	complete: function(response) {
			  		//tratamento comum para todas chamadas
			 	}
			});
		} else {
			PagSeguroDirectPayment.getInstallments({
			 	amount: order_total,
			 	brand: bandeira,		 	
			 	success: function(response) {
					parcelamento_retorno(id, bandeira, valor, response);
			 	},
			 	error: function(response) {
			   		//tratamento do erro
			 	},
			 	complete: function(response) {
			  		//tratamento comum para todas chamadas
			 	}
			});
		}
	} else {
		jQuery(id).hide();
	}
}
function parcelamento_retorno(id, bandeira, valor, response) {

	//opções de parcelamento disponível
	jQuery.each(response.installments[bandeira], function(index, parcela){
		
		id_parcela = id+' #p0'+parcela.quantity;
		if (jQuery(id_parcela).length > 0) {												
			texto_parcela = float2moeda(parcela.installmentAmount);
			if (parcela.interestFree) {
				texto_parcela += ' sem juros';
			} else {
				texto_parcela += ' *';							
			}
			// modo antigo com ul li
			// jQuery(id_parcela).next().html('R$ '+texto_parcela);
			jQuery(id_parcela).text(parcela.quantity+' x de R$ '+texto_parcela);
			if (parcela.quantity == 1) {
				parcelas_array[cartoes[valor]] = new Array();
			}
			parcelas_array[cartoes[valor]][parcela.quantity] = parcela.installmentAmount;// global;
		}
	});

	var total_parcelas_retorno = response.installments[bandeira].length;
	jQuery(id+" select[name=parcelamento] option:gt("+(total_parcelas_retorno-1)+")").remove();

	jQuery(id).show();
}
function mostra_erro(erro) {
	if (erro) {
		jQuery('#div_erro').show();
	} else {
		jQuery('#div_erro').hide();
	}
}
function status_erro() {
	return jQuery('#div_erro').css('display');
}
// Método que marca o campo radio manualmente ( para o ie )
function marcar_radio(id) {
	jQuery('#'+id).attr('checked','checked');
}
jQuery(document).ready(function(){
	jQuery('#cvv').mask("999?9");  
	jQuery('#expiry_date').mask("99/99");	 
	jQuery('#phone').mask("(99) 9999-9999?9");  
	jQuery('#cpf_titular').mask("999.999.999-99");
	jQuery('#birthdate').mask("99/99/9999");	 	
});
/*
 Envio dos dados do cartão
*/
var erro = false;
function erro_cartao(id) {
	jQuery('form#'+id+' input[type=submit]').val('Efetuar Pagamento');
	erro = true;
	return false;
}
function msgPop() {
	jQuery.facebox(jQuery('#system-message-cartao').clone().attr('id','system-message-cartao').html());
}
function pagamentoEmAndamento(texto_msg) {
	jQuery('#div_erro').removeClass('error');
	jQuery('#div_erro_conteudo').html('<div align="center">'+texto_msg+'<br /><br /><img src="'+url_assets_pagseguro+'images/carregando.gif" border="0"/></div>');
	msgPop();
}
function submeter_cartao(formulario) {	
    var id = 'form#'+jQuery(formulario).attr('id');
    var cartao_selecionado 	= jQuery(id+' input[name=tipo_pgto]:checked').val();
	var qtde_parcelas 		= jQuery(id+' select[name=parcelamento]:visible').length;
	var parcela_selecionada = jQuery(id+' select[name=parcelamento]:visible').val();
	var numero_cartao 		= jQuery(id+' input#card_number').val();
	var validade_cartao 	= jQuery(id+' input#expiry_date').val();
	var cvv_cartao 			= jQuery(id+' input#cvv').val();
	var titular_cartao 		= jQuery(id+' input#name_on_card').val();
	jQuery('#div_erro').show();
	jQuery('#div_erro').addClass('error');
	if (qtde_parcelas == 0) {
		jQuery('#div_erro_conteudo').text('Selecione um parcelamento do Cartão de Crédito');
		msgPop();
		return erro_cartao(id);
	}
	
	if (numero_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite o número do Cartão de Crédito');
		msgPop();
		return erro_cartao(id);
	}
	if (numero_cartao.length < 14) {
		jQuery('#div_erro_conteudo').text('Número de cartão de crédito inválido');
		msgPop();
		return erro_cartao(id);
	}
	if (validade_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite a validade do Cartão de Crédito');	
		msgPop();
		return erro_cartao(id);
	}		
	if (cvv_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite o código de verificação Cartão de Crédito');	
		msgPop();
		return erro_cartao(id);
	}	
	if (cartao_selecionado == '') {
		jQuery('#div_erro_conteudo').text('Selecione um Cartão de Crédito');
		msgPop();
		return erro_cartao(id);
	}
	if (cartao_selecionado == 'amex' && cvv_cartao.length != 4) {
		jQuery('#div_erro_conteudo').text('O Código de verificação deve ser de 4 dígitos.');
		msgPop();
		return erro_cartao(id);	
	} 
	if(cartao_selecionado != 'amex' && cvv_cartao.length != 3) {
		jQuery('#div_erro_conteudo').text('O Código de verificação deve ser de 3 dígitos.');
		msgPop();
		return erro_cartao(id);	
	}
	if (titular_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite o nome impresso no Cartão de Crédito');
		msgPop();
		return erro_cartao(id);
	}
	erro = false;
	pagamentoEmAndamento('Pagamento em Andamento... ');	
	
	jQuery('#forma_pagamento').val('CartaodeCredito');
	// jQuery('#tipo_pagamento').val(cartoes[cartao_selecionado]+' - '+parcela_selecionada+'x |'+titular_cartao+'|'+nascimento_titular+'|'+telefone_titular+'|'+cpf_titular);
	jQuery('#tipo_pagamento').val(cartoes[cartao_selecionado]);
	jQuery('#parcela_selecionada').val(parcela_selecionada);
	// redireciona para o Pagseguro
	solicitaPagamento();
	return false;
}
/*
 Envio dos dados do cartão
*/
function submeter_boleto(formulario) {
	var id = 'form#'+formulario.id;
	var dados_pagamento = {
		"Forma": "BoletoBancario"
	}
	jQuery('#div_erro').show();
	jQuery('#div_erro').addClass('error');
	jQuery('#forma_pagamento').val('BoletoBancario');
	jQuery('#tipo_pagamento').val('Santander');
	jQuery('#parcela_selecionada').val(1);
	
	erro = false;
	pagamentoEmAndamento('Pagamento em Andamento... ');
	// redireciona para o Pagseguro
	solicitaPagamento();
	return false;
}
function submeter_debito(formulario) {
    var id = 'form#'+formulario.id;
    var debito_selecionado 	= jQuery(id+' input[name=tipo_pgto_debito]:checked').val();
	jQuery('#div_erro').show();
	jQuery('#div_erro').addClass('error');
	if (debito_selecionado == '') {
		jQuery('#div_erro_conteudo').text('Selecione um Débito Bancário');
		erro = true;
		return false;
	}
	erro = false;
	jQuery('#forma_pagamento').val('DebitoBancario');
	jQuery('#tipo_pagamento').val(debito[debito_selecionado]);
	jQuery('#parcela_selecionada').val(1);
	pagamentoEmAndamento('Pagamento em Andamento... ');
	// redireciona para o Pagseguro
	solicitaPagamento();
	
	return false; 
} 
function solicitaPagamento() {
	pagamentoEmAndamento('Estabelecendo conexão segura com o PagSeguro... ');
	var forma_pagamento = jQuery('#forma_pagamento').val();
	var tipo_pagamento = jQuery('#tipo_pagamento').val();
	var parcela_selecionada = jQuery('#parcela_selecionada').val();
	var hash = PagSeguroDirectPayment.getSenderHash();
	var dados = 'tipo_pagamento='+tipo_pagamento+
	'&forma_pagamento='+forma_pagamento+
	'&parcela_selecionada='+parcela_selecionada+
	'&order_number='+jQuery('#order_number').val()+
	'&senderHash='+hash;
	if (forma_pagamento == 'CartaodeCredito' || forma_pagamento == 'CartaodeDebito') {
		// recupera o hash do pagseguro
		// console.log(tipo_pagamento);
		// console.log(parcelas_array[tipo_pagamento]);
		// console.log(parcelas_array[tipo_pagamento][parcela_selecionada]);
		dados += '&c_holder='+jQuery('#name_on_card').val()+
		'&c_holder='+jQuery('#name_on_card').val()+
		'&c_phone='+jQuery('#phone').val()+
		'&c_birthdate='+jQuery('#birthdate').val()+
		'&c_cpf='+jQuery('#cpf_titular').val()+		
		'&valor_parcela='+parcelas_array[tipo_pagamento][parcela_selecionada];
		//'&c_number='+jQuery('#card_number').val()+
		//'&c_securityCode='+jQuery('#cvv').val()+
		//'&c_expiry_date='+jQuery('#expiry_date').val()+				
		var cartao = jQuery('#expiry_date').val();
		var arr_cartao = cartao.split('/');
		var expirationMonth_ps = arr_cartao[0];
		var expirationYear_ps = '20'+arr_cartao[1];
		var bandeiraPagseguro;
		/*
			PagSeguroDirectPayment.getBrand({
				cardBin: cartao,
				success: function(response) {
					//bandeira encontrada				
					bandeiraPagseguro = response.brand.name;
				},
				error: function(response) {
					//tratamento do erro
				},
				complete: function(response) {
					//tratamento comum para todas chamadas
					console.log(response);
				}
			});
		*/
		pagamentoEmAndamento('Garantindo a segurança do cartão de crédito... ');
		var param = {
			cardNumber: jQuery('#card_number').val(),
			cvv: jQuery('#cvv').val(),
			brand: tipo_pagamento.toLowerCase(),
			expirationMonth: expirationMonth_ps,
			expirationYear: expirationYear_ps,
			success: function(response) {
				//token gerado, esse deve ser usado na chamada da API do Checkout Transparente
				var token_compra = response.card.token;
				dados += "&token_compra="+token_compra;
				postWebservice(dados, forma_pagamento);
			},
			error: function(response) {
				// tratamento do erro				
			},
			complete: function(response) {				
				// tratamento comum para todas chamadas
				if (response.error == true) {
					var mensagem;									
					var mensagem_html = 'Erros: <br />';							
					var mensagem_erro = '';
					for (var i in response.errors) {
					    if (response.errors.hasOwnProperty(i) && typeof(i) !== 'function') {
					    	mensagem_erro = response.errors[i];
					    	if (mensagem_erro == 'creditcard number with invalid length') {
					    		mensagem_erro = 'Digite um número de Cartão de crédito válido';
					    	}
					        mensagem_html += "<b>"+i+"</b>"+" - "+ mensagem_erro+ '<br/>';
					    }
					}					
					mensagem = mensagem_html;					
					jQuery('#div_erro_conteudo').html(mensagem);
					msgPop();
				}
				// console.log(response);
			}
		}
		PagSeguroDirectPayment.createCardToken(param);
	} else {
		postWebservice(dados,forma_pagamento);
	}
}
function postWebservice(dados,forma_pagamento) {
	pagamentoEmAndamento('Solicitando ao servidor do PagSeguro... ');
	jQuery.ajax({
		type: "POST",
		url: redireciona_pagseguro,
		data: dados,
		dataType: "json",
		success: function(retorno) {
			// mensagem de retorno do pagamento
			var mensagem_pagamento = '';
			if (retorno.tipo_pagamento == 'BoletoBancario') {
				jQuery(document).bind('beforeReveal.facebox', function() {
					jQuery('#facebox *').width('800px');
					jQuery('#facebox .close').width('10px').click(function(){
						jQuery('#container_pagseguro .mainpagseguro').hide('slow');
						location.href = url_recibo_pagseguro;
					});
				});
				jQuery.facebox('<iframe width="1024" height="680" name="iframepagseguro" src="'+retorno.paymentLink+'"></iframe>');
				mensagem_pagamento += 'Caso não tenha aberto a popup com o boleto, <a href="'+retorno.paymentLink+'" target="_blank">clique aqui</a> para realizar o pagamento. <br/>';
			}

			if (retorno.tipo_pagamento == 'DebitoBancario') {
				window.open(retorno.paymentLink);
				// jQuery('#container_pagseguro form').parent().hide('slow');
				jQuery('#container_pagseguro .mainpagseguro').hide('slow');
				mensagem_pagamento += '<b>Pagamento com débito bancário em andamento</b><br/>';
				mensagem_pagamento += 'Caso não tenha aberto a popup, <a href="'+retorno.paymentLink+'" target="_blank">clique aqui</a> para realizar o pagamento. <br/>';

				jQuery('#div_erro_conteudo').show().html(mensagem_pagamento+'<br />');
				jQuery('#div_erro_conteudo').animate({"padding":"20px","font-size":"15px"}, 1000);
				jQuery.facebox(jQuery('#div_erro_conteudo').clone().attr('id','system-message-cartao').html());
			}

			if (!retorno.erro) {
				// pagamento aprovado/em anaĺise/em andamento
				if (retorno.status == '1' || retorno.status == '2' || retorno.status == '3'){
					jQuery('#div_erro').addClass('success').removeClass('error').show();
				} else {
					jQuery('#div_erro').addClass('error').show();						
				}
				
				if (forma_pagamento == 'CartaodeCredito' || forma_pagamento == 'CartaodeDebito') {
					mensagem_pagamento +='Em alguns segundos você será redirecionado automaticamente  para o comprovante do Pagamento ou <a href="'+url_recibo_pagseguro+'">clique aqui</a>.';
					jQuery('#div_erro_conteudo').show().html(retorno.msg+'<br /><br />'+mensagem_pagamento);
					msgPop();												
					var t = setTimeout('redireciona_recibo()',5000);
				} else {
					mensagem_pagamento += 'Clique no <a href="'+url_recibo_pagseguro+'">link</a> para acessar os detalhes do pedido.';
					jQuery('#div_erro_conteudo').show().html(retorno.msg+'<br /><br />'+mensagem_pagamento);
				}
				jQuery('#container_pagseguro .mainpagseguro').hide('slow');
				jQuery('#div_erro_conteudo').animate({"padding":"20px","font-size":"15px"}, 1000);
			} else {
				jQuery('#div_erro').addClass('error').show();
				var mensagem;
				if (typeof retorno.msg_erro !== 'undefined' && retorno.msg_erro !== null && typeof retorno.msg_erro.length === 'number') {
					var mensagem_html = 'Erros: <br />';
					for(i=0; i<retorno.msg_erro.length; i++) {
					    mensagem_html += ' - ' +retorno.msg_erro[i] + '<br />';
					}
					mensagem = mensagem_html;
				} else {
					mensagem = retorno.msg_erro;
				}
				if (mensagem == 'Pagamento já foi realizado') {
					mensagem +='<br/> <a href="'+url_recibo_pagseguro+'">Clique aqui para ser redirecionado</a> para o status do Pagamento.';
					// jQuery('#container_pagseguro form').parent().hide('slow');
					jQuery('#container_pagseguro .mainpagseguro').hide('slow');
				}
				jQuery('#div_erro_conteudo').show().html(mensagem+'<br />');
				jQuery('#div_erro_conteudo').animate({"padding":"20px","font-size":"15px"}, 1000);
				jQuery.facebox(jQuery('#div_erro_conteudo').clone().addClass('error').attr('id','system-message-cartao').html());
			}
		}			
	});
}
function redireciona_recibo() {
	// jQuery('#container_pagseguro form').hide();
	jQuery('#container_pagseguro .mainpagseguro').hide();
	location.href = url_recibo_pagseguro;
}
function efeito_divs(mostra) {
	jQuery('.div_pagamentos form .conteudo_pagseguro').hide();
	jQuery('#'+mostra+' form .conteudo_pagseguro').show();
}
var id_div_pagamento;
jQuery(document).ready(function(){	
	jQuery('form ul.cards input[type=radio]').click(function(){
		id_div_pagamento = jQuery(this).parents('.div_pgtos').attr('id');
		//  console.log('id_div_pagamento '+id_div_pagamento);
		jQuery('div#'+id_div_pagamento+' input[type=radio][name=toggle_pagamentos]').attr('checked','checked');
		efeito_divs(id_div_pagamento);
	});
	jQuery('a.info_cvv').click(function(){
		var html_cvv = '<div style="width: 300px"><h4>Código de segurança</h4>'+
		'<div>Para sua segurança, solicitamos que informe alguns números do seu cartão de crédito.</div>'+
		'<div style="background: #efefef"><div><b>Onde encontrar:</b></div>'+
		'<div><img src="'+url_assets_pagseguro+'/images/default_cart.png" width="194" height="132" border="0" align="left" style="margin: 10px"/>Informe os <b>três últimos números localizados</b> no verso do cartão.</div></div></div>';
		jQuery.facebox(html_cvv);
	});
});
function float2moeda(num) {
   	x = 0;
   	if(num<0) {
      num = Math.abs(num);
      x = 1;
   	}
   	if(isNaN(num)) num = "0";
      cents = Math.floor((num*100+0.5)%100);
   	num = Math.floor((num*100+0.5)/100).toString();
   	if(cents < 10) cents = "0" + cents;
      for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
         num = num.substring(0,num.length-(4*i+3))+'.'
               +num.substring(num.length-(4*i+3));
   	ret = num + ',' + cents;
   	if (x == 1) ret = ' - ' + ret;return ret;
}
function moeda2float(moeda) {
   moeda = moeda.replace(".","");
   moeda = moeda.replace(",",".");
   return parseFloat(moeda);
}

jQuery(function($){
	
	var activepg = jQuery('.pg_nav li.active').find('a').attr("data-id");
		
	$(activepg).show();
	
	//jQuery('div#'+activepg+' input[type=radio][name=toggle_pagamentos]').attr('checked','checked');
	
	
	// Set o radio dos cartoes
	jQuery('.cartoes input:radio').click(function(){ 
		jQuery('.cartoes label').removeClass('rchecked');
		jQuery('.cartoes label[for='+jQuery(this).attr("id")+']').addClass('rchecked'); // checkedClass is defined in your CSS
		
	});
	
	// Seta as tabs
	jQuery('.pg_nav li').click(function(){
		jQuery('.pg_nav li').removeClass('active');
		jQuery('.tabcontent').hide();
		var area = jQuery(this).find('a').attr("data-id")
		
		jQuery(this).addClass('active');
		jQuery(area).show();
		
		jQuery('div'+area+' input[type=radio][name=toggle_pagamentos]').attr('checked','checked');
	});

});

