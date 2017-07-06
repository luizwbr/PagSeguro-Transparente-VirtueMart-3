<?php
if (!defined('_VALID_MOS') && !defined('_JEXEC')) {
		die('Direct Access to ' . basename(__FILE__) . ' is not allowed.'); 
}
/**
 * @version $Id: pagsegurotransparente.php,v 1.6 2012/08/31 11:00:57 ei
 *
 * a special type of 'pagseguro transparente':
 * @author Max Milbers, Valérie Isaksen, Luiz Weber
 * @version $Id: pagsegurotransparente.php 5122 2012-02-07 12:00:00Z luizwbr $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2004-2008 soeren - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
if (!class_exists('vmPSPlugin')) {
		require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');  
}
class plgVmPaymentPagsegurotransparente extends vmPSPlugin {
	// instance of class
	public static $_this = false;
	function __construct(& $subject, $config) {
	//if (self::$_this)
	//   return self::$_this;
	parent::__construct($subject, $config);
	$this->_loggable  = true;
	$this->tableFields  = array_keys($this->getTableSQLFields());
	$this->_tablepkey   = 'id'; 
	$this->_tableId   = 'id'; 
	$varsToPush     = $this->getVarsToPush ();
			$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
	$this->domdocument = false;
	
	if (!class_exists('VirtueMartModelOrders'))
		require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
	// Set the language code
	$lang = JFactory::getLanguage();
	$lang->load('plg_vmpayment_' . $this->_name, JPATH_ADMINISTRATOR);    
			// self::$_this = $this;
	
	if (!class_exists('CurrencyDisplay'))
					require( JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php' );
	
	}
	/**
	 * Create the table for this plugin if it does not yet exist.
	 * @author Valérie Isaksen
	 */
	protected function getVmPluginCreateTableSQL() {
			return $this->createTableSQL('Payment Pagsegurotransparente Table');
	}
	/**
	 * Fields to create the payment table
	 * @return string SQL Fileds
	 */
	function getTableSQLFields() {
		// tabela com as configurações de cada transação 
			$SQLfields = array(
					'id' => 'bigint(10) unsigned NOT NULL AUTO_INCREMENT',
					'transactionCode' => ' varchar(50) NOT NULL',            
					'virtuemart_order_id' => 'bigint(11) UNSIGNED DEFAULT NULL',
					'order_number' => 'char(50) DEFAULT NULL',
					'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL',
					'payment_name' => 'char(255) NOT NULL DEFAULT \'\' ',
					'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
					'payment_currency' => 'char(3) ',
					'type_transaction' => 'varchar(200) DEFAULT NULL ',
					'log' => ' varchar(200) DEFAULT NULL',
					'status' => ' char(1) not null default \'P\'',
					'msg_status' => ' varchar(255) NOT NULL',
					'url_redirecionar' => ' varchar(255) NOT NULL',
					'tax_id' => 'smallint(11) DEFAULT NULL',
			);
		return $SQLfields;
	}
	
	/**
	 * @param $name
	 * @param $id
	 * @param $data
	 * @return bool
	 */
	function plgVmDeclarePluginParamsPaymentVM3( &$data) {
		return $this->declarePluginParams('payment', $data);
	}
	
	function getPluginParams(){
		$db = JFactory::getDbo();
		$sql = "select virtuemart_paymentmethod_id from #__virtuemart_paymentmethods where payment_element = 'pagsegurotransparente'";
		$db->setQuery($sql);
		$id = (int)$db->loadResult();
		return $this->getVmPluginMethod($id);
	}
	/**
	 *
	 *
	 * @author Valérie Isaksen
	 */
	function plgVmConfirmedOrder($cart, $order) {
		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
				return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
				return false;
		}
		vmJsApi::js('facebox');
		vmJsApi::css('facebox');
		$this->order_id = $order['details']['BT']->order_number;
		$url = JURI::root();
		// carrega os js e css
		$doc        = & JFactory::getDocument();
		$url_lib      = $url. '/' .'plugins'. '/' .'vmpayment'. '/' .'pagsegurotransparente'.'/';
		$url_assets     = $url_lib . 'assets'. '/';
		$url_js       = $url_assets . 'js'. '/';
		$url_css      = $url_assets . 'css'. '/';
		$this->url_imagens  = $url_lib . 'imagens'. '/';
		// redirecionar dentro do componente para validar
		$url_redireciona_pagsegurotransparente  = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&task2=redirecionarPagseguroAPI&tmpl=component&pm='.$order['details']['BT']->virtuemart_paymentmethod_id."&order_number=".$this->order_id);
		$url_pedidos              				= JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=orders');

		if ($method->url_redirecionamento) {
			$url_recibo_pagsegurotransparente     	= JROUTE::_($method->url_redirecionamento);
		} else {
			$url_recibo_pagsegurotransparente     	= JROUTE::_(JURI::root() .'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&on='.$this->order_id.'&pm='.$order['details']['BT']->virtuemart_paymentmethod_id);
		}

		$session_id_pagseguro = $this->getSessionIdPagseguro($method);
		if (!$session_id_pagseguro) {
			JFactory::getApplication()->enqueueMessage( 'Erro ao configurar e-mail e token do PagSeguro', 'error' );
			return false;
		}
		$url_js_directpayment = $this->getUrlJsPagseguro($method);
		$doc->addCustomTag('
			<script type="text/javascript" src="'.$url_js_directpayment.'/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>
			<script type="text/javascript">
				PagSeguroDirectPayment.setSessionId(\''.$session_id_pagseguro.'\');
				jQuery.noConflict();
				var redireciona_pagseguro = "'.$url_redireciona_pagsegurotransparente.'";
				var url_pedidos = "'.$url_pedidos.'";
				var url_recibo_pagseguro = "'.$url_recibo_pagsegurotransparente.'";
				var url_assets_pagseguro = "'.$url_assets.'";
				var order_total = '.round($order['details']['BT']->order_total,2).';
				var max_parcela_sem_juros = '.$method->max_parcela_sem_juros.';
			</script>
			<script type="text/javascript" language="javascript" src="'.$url_js.'jquery.mask.js"></script>
			<script type="text/javascript" charset="utf-8" language="javascript" src="'.$url_js.'pagsegurotransparente.js"></script>      
			<script type="text/javascript" language="javascript" src="'.$url_js.'jquery.card.js"></script>
			<script type="text/javascript" language="javascript" src="'.$url_js.'validar_cartao.js"></script>
			'.($load_squeezebox!=0?$sq_js:'').'
			<link href="'.$url_css.'css_pagamento.css" rel="stylesheet" type="text/css"/>
			
			<link href="'.$url_css.'style.css" rel="stylesheet" type="text/css"/>
			'.($load_squeezebox!=0?$sq_css:'').'
		');
				$lang = JFactory::getLanguage();
				$filename = 'com_virtuemart';
				$lang->load($filename, JPATH_ADMINISTRATOR);
				$vendorId = 0;
		$this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');    
				$html = "";
				if (!class_exists('VirtueMartModelOrders')) {
						require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
		}
		$this->getPaymentCurrency($method);
		$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
		$db = &JFactory::getDBO();
		$db->setQuery($q);
		$currency_code_3 = $db->loadResult();
		$paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
		$totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);
		$cd = CurrencyDisplay::getInstance($cart->pricesCurrency);
		// pega o nome do método de pagamento
		$dbValues['payment_name']           = $this->renderPluginName($method);
		
		$html = '<table>' . "\n";
		$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_PAYMENT_NAME', $dbValues['payment_name']);
		if (!empty($payment_info)) {
			$lang = & JFactory::getLanguage();
			if ($lang->hasKey($method->payment_info)) {
					$payment_info = JText::_($method->payment_info);
			} else {
					$payment_info = $method->payment_info;
			}
			$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_INFO', $payment_info);
		}
		if (!class_exists('VirtueMartModelCurrency')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
		}
		$currency = CurrencyDisplay::getInstance('', $order['details']['BT']->virtuemart_vendor_id);
		$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_ORDER_NUMBER', $order['details']['BT']->order_number);
		$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_AMOUNT', $currency->priceDisplay($order['details']['BT']->order_total));
		$html .= '
		<input type="hidden" name="order_number" id="order_number" value="'. $order['details']['BT']->order_number .'"/>
		</table>' . "\n";
		$this->_virtuemart_paymentmethod_id     = $order['details']['BT']->virtuemart_paymentmethod_id;        
		$dbValues['order_number']         = $order['details']['BT']->order_number;
		$dbValues['virtuemart_paymentmethod_id']  = $this->_virtuemart_paymentmethod_id;
		$dbValues['cost_per_transaction']       = $method->cost_per_transaction;
		$dbValues['cost_percent_total']       = $method->cost_percent_total;
		$dbValues['payment_currency']         = $currency_code_3;
		$dbValues['payment_order_total']      = $totalInPaymentCurrency;
		$dbValues['tax_id']             = $method->tax_id;
		$this->storePSPluginInternalData($dbValues);
		// grava os dados do pagamento
		//$this->gravaDados($method,0,$arr_pagamento['status']);
		//$retorno = $this->createTransaction($method,$order);
		$html .= $this->Pagsegurotransparente_mostraParcelamento($method, $order);
		$cart->_confirmDone = FALSE;
		$cart->_dataValidated = FALSE;
		$cart->setCartIntoSession ();
		JRequest::setVar ('html', $html);
		
	}

	public function Pagsegurotransparente_mostraParcelamento($method, $order) { 
		
		$doc = JFactory::getDocument();
		//$doc->addScript($this->url_js); 
	
		if ($method->ativar_cartao ) {
			$lt_cartao = '<li class="active" id="litabcartao"><a href="javascript:void(0)" data-id="#tabcartao">Cartão de Crédito</a></li>';
		}
	
		if ($method->ativar_debito ) {
			$lt_cartaod = '<li id="litabdebito"><a href="javascript:void(0)" data-id="#tabdebito">Débito Bancário</a></li>';	  
			if ( !$method->ativar_cartao ) {
				$lt_cartaod = '<li class="active" id="litabdebito"><a href="javascript:void(0)" data-id="#tabdebito">Débito Bancário</a></li>';	
			}
		}	
	
		if ($method->ativar_boleto) {
			$lt_boleto = ' <li id="#litabboleto"><a  href="javascript:void(0)" data-id="#tabboleto">Boleto</a></li>';
		
			if ( !$method->ativar_cartao && !$method->ativar_debito ) {
				 $lt_boleto = ' <li id="#litabboleto" class="active" ><a  href="javascript:void(0)" data-id="#tabboleto">Boleto</a></li>';
			}	  
		}

		$conteudo = '   
		
			<div id="PagsegurotransparenteWidget"></div>      
				
			<div align="left">
				<h3>'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_TITLE').'</h3>
			</div>
	
			
			<dl id="system-message-cartao" class="system-message-cartao">
				<dd class="message error" id="div_erro" style="display:none">
					<ul>
						<li id="div_erro_conteudo"></li>
					</ul>
				</dd>
			</dl>
			<div class="mainpagseguro">
					
				 <!-- pg_top -->
					 <div class="pg_top">
							 <div class="pg_topleft"></div>
							 <div class="pg_dados">
								<span>
									Pague com
								</span>
							 </div>
							 <ul class="pg_nav">
								'.$lt_cartao .'
								'.$lt_cartaod.'
								'.$lt_boleto .'
							 </ul>
							 <div class="pg_topright"></div>
					 </div>
						<!-- end pg_top -->
								
				
			
				<input type="hidden" value="" name="forma_pagamento" id="forma_pagamento"/>
				<input type="hidden" value="" name="tipo_pagamento" id="tipo_pagamento"/>
				<input type="hidden" value="" name="parcela_selecionada" id="parcela_selecionada"/>
				
				<div id="container_pagseguro" >
					<div class="pg_main">
				';
						
				
				if ($method->ativar_cartao) {
					$conteudo .= $this->getPagamentoCartao($method, $order);
				}
				
				
				if ($method->ativar_debito) {
					$conteudo .= $this->getPagamentoDebito($method, $order);  
				} 
				if ($method->ativar_boleto) {
					$conteudo .= $this->getPagamentoBoleto($method, $order);
				}
				$conteudo .= "
					</div> <!-- pg_main -->
				</div>
			</div>        
		
			<br style='clear:both'/>      
			";
			
		return $conteudo;
	}
	
	public function getRadioTermosPagseguro(){
		/*
		return "<input type='checkbox' name='radio_contract' value='1' class='radio_terms'/>                  
				Por favor, leia e aceite os termos de <a href='https://www.pagseguro.com.br/checkout/pay/contrato' class='modal' rel=\"{handler: 'iframe', size: {x: 750, y: 450}}\">gestão de pagamentos do Pagseguro.</a>";
		*/
		return;
	}
	public function getPagamentoCartao($method, $order) {
		$order_total    = round($order['details']['BT']->order_total,2); 
		$cartoes_aceitos = array();
		$method->cartao_visa?$cartoes_aceitos[] = 'visa':'';
		$method->cartao_master==1?$cartoes_aceitos[] = 'master':'';
		$method->cartao_elo==1?$cartoes_aceitos[] = 'elo':'';
		$method->cartao_diners==1?$cartoes_aceitos[] = 'diners':'';
		$method->cartao_amex==1?$cartoes_aceitos[] = 'amex':'';
		$method->cartao_hipercard==1?$cartoes_aceitos[] = 'hipercard':'';
		$method->cartao_aura==1?$cartoes_aceitos[] = 'aura':'';
		
		$html_radio_termos_pagseguro = $this->getRadioTermosPagseguro();
		// campo telefone
		$bt_comprador = $order['details']['BT'];
		$phone = $bt_comprador->phone_1;
		// cpf, data de nascimento
		$campo_cpf = $method->campo_cpf;
		$campo_cep = $method->campo_cep;
		$campo_cnpj = $method->campo_cnpj;
		$campo_data_nascimento = $method->campo_data_nascimento;
		// campo data de nascimento
		$birthdate = $this->formataData((isset($bt_comprador->$campo_data_nascimento) and !empty($bt_comprador->$campo_data_nascimento))?$bt_comprador->$campo_data_nascimento:'');
		$cpf = $this->formataCPF((isset($bt_comprador->$campo_cpf) and !empty($bt_comprador->$campo_cpf))?$bt_comprador->$campo_cpf:'');

		$campo_nome 	 = $method->campo_nome;
		$campo_sobrenome = $method->campo_sobrenome;

		$nome = $bt_comprador->$campo_nome.' '.$bt_comprador->$campo_sobrenome;
		$html = '<!-- tabcontent cartao -->
			 <div class="tabcontent tab_cc" id="tabcartao">
		 
			 <div id="div_cartao"  class="div_pgtos">
			 <div>
					<h4 class="titulo_toggle">
						 <input type="radio" name="toggle_pagamentos" value="cartao" id="toggle_cartao" onclick="efeito_divs(\'div_cartao\')"  style="float:left" class="radiofield"/>
						<label for="toggle_cartao">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_CREDIT_CARD').'</label>
					</h4>
				</div>
			
			<form name="pagamento_cartao" onsubmit="return submeter_cartao(this)" id="pagamento_cartao" target="iframepagseguro">
						 
			 
				<ul>
						<!--  cartões -->
						<li class="titulo_toggle"><ul class="cards cartoes">';
							foreach($cartoes_aceitos as $v) {   
								$html .= "<li><label for=\"tipo_".$v."\"><input type=\"radio\" class=\"radiofield\" name=\"tipo_pgto\" style=\"width:15px\" id=\"tipo_".$v."\" value=\"".$v."\" onclick=\"show_parcelas(this.value)\" /><img src=\"".$this->url_imagens.$v."_cartao.jpg\" border=\"0\" align=\"absmiddle\" onclick=\"marcar_radio('tipo_".$v."');show_parcelas('".$v."');\" /></label></li>";
							}
							$html .= '
							</ul>
			 
			 
				<div class="conteudo_pagseguro" style="display:none">
							<div class="subtitle">
								 Escolha em quantas vezes quer pagar
							</div>
				
					<!-- select parcelas -->
						 <div class="pg_row">
								 <div class="pgc1-2">
										 <ul>';
												
								$html .= ($method->cartao_visa==1)?$this->calculaParcelasCredito($method, $order_total,'div_visa'):'';
								$html .= ($method->cartao_master==1)?$this->calculaParcelasCredito($method, $order_total,'div_master'):'';
								$html .= ($method->cartao_elo==1)?$this->calculaParcelasCredito($method, $order_total,'div_elo'):'';
								$html .= ($method->cartao_diners==1)?$this->calculaParcelasCredito($method, $order_total,'div_diners',1):'';
								$html .= ($method->cartao_amex==1)?$this->calculaParcelasCredito($method, $order_total,'div_amex'):'';
								$html .= ($method->cartao_hipercard==1)?$this->calculaParcelasCredito($method, $order_total,'div_hipercard'):'';
								$html .= ($method->cartao_aura==1)?$this->calculaParcelasCredito($method, $order_total,'div_aura'):'';
								$html .= "</ul>";
								$html .=' </div>
						 </div>
						 <!-- end select parcelas de credito -->
			';
		$html .= '  <!-- pg_row -->
						 <div class="pg_row">
									<div class="pgc1-2">
											<div class="bgcartao">';
						$html .= '<div class="pgi_cartao">
													<label for="card_number">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_CARD_NUMBER').'</label>
													<input name="card_number" id="card_number" type="text" maxlength="19" />
												</div>';
						 $html .= '<div class="pgi_datavalidade">
								<label for="expiry_date">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_EXPIRY_DATE').'</label>
							<input name="expiry_date" id="expiry_date" maxlength="5" type="text" size="5" />
						</div>
						 <div class="pgi_cvv">
							<label for="cvv">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_VERIFY_NUMBER2').'</label>
							<input name="cvv" id="cvv" maxlength="4" type="text" size="3" />
							<a class="info_cvv icquestion" href="#creditCardCvvInfo" rel="tooltip-0" title="código de segurança"></a>
												</div>
						<br class="clear" />
											</div>
											 <!-- end bgcartao -->
									</div>
					<!-- end pgc1-2 -->';
						$html .= '  <!-- end pgc2-1 -->
					<div class="pgc2-1">
						<div class="pg_dadosp">
						 <div class="pgi_titular">
							<label for="name_on_card">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_CARD_OWNER').'</label>
							<input name="name_on_card" id="name_on_card" type="text" value="'.$nome.'" />
						 </div>
						 <div class="pgi_cpf">
							<label for="cpf">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_CPF').'</label>
							<input name="cpf" id="cpf_titular" type="text" value="'.$cpf.'" />
						 </div>
						 <div class="pgi_datanasc">
							<label for="birthdate">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_BIRTHDATE').'</label>
							<input name="birthdate" id="birthdate" maxlength="11" type="text" size="8" value="'.$birthdate.'" />
						 </div>
							<div class="pgi_telefone">
							<label for="phone">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_PHONE').'</label>
							<input name="phone" id="phone" maxlength="15" type="text" size="8"  value="'.$phone.'" />
						 </div>
					
					</div>
					<!-- end pg_dadosp -->
					</div>
					<!-- end pgc2-1 -->';
			$html .='
				
					</div>
						 <!-- end pg_row -->
			
			<div class="pg_row">
			<div class="pgcenter">
				'.$html_radio_termos_pagseguro.'
				<input type="submit" class="buttonPagsegurotransparente btn-pagar" value="'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_BUTTON').'" />  
			</div>
			</div>
					
			</div><!-- end conteudo_pagseguro -->
			
			</form>
		</div>
			</div><!-- end tabcontent tab_cc -->
			
		';
		return $html;
	}
	
	
	
	public function getPagamentoBoleto($method, $order) {
		$order_total = round($order['details']['BT']->order_total,2);
		$html_radio_termos_pagseguro = $this->getRadioTermosPagseguro();
		$html = '
		<div class="tabcontent tab_cc" id="tabboleto">
		<div id="div_boleto" class="div_pgtos"> 
	
	
			<div>
				<h4 class="titulo_toggle ">
					<input type="radio" name="toggle_pagamentos" id="toggle_boleto" value="boleto" onclick="efeito_divs(\'div_boleto\')" style="float:left" class="radiofield"/>
					<label for="toggle_boleto">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_CREDIT_BOLETO').'</label>
				</h4>
			</div>
		
			<form name="pagamento_boleto" onsubmit="return submeter_boleto(this)" id="pagamento_boleto" target="iframepagseguro">
				<ul>
					<li>
						<ul class="cards cartoes">
							<li>
								<label for="tipo_boletobancario">
									<input type="radio" name="tipo_pgto_boleto" style="width:15px" id="tipo_boletobancario" value="1" class="radiofield"/>
									<img src="'.$this->url_imagens.'boleto_bancario.jpg" border="0" align="absmiddle" onclick="marcar_radio(\'tipo_boletobancario\');" />
								</label>          
							</li>
						</ul>
					</li>
				</ul>
				<br style="clear:both"/>
				<div class="conteudo_pagseguro" style="display:none">       
					<!-- parcelas debito -->
					<ul>';
					$html .= $this->calculaParcelasDebitoBoleto($method, $order_total,'div_boletobancario');
					$html .= '
					</ul>
			
			';	
			
			
			$html .= '<div class="pg_row">
			<div class="pgcenter">
				'.$html_radio_termos_pagseguro.'
				<input type="submit" class="buttonPagsegurotransparente btn-pagar" value="'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_BUTTON_BOLETO').'" />  
			</div>
			</div>';
			
			
					$html .= '
				</div>
			</form>
		</div>
	 </div>';
		return $html;
	}
	
	public function getPagamentoDebito($method, $order) {
		
		$order_total = round($order['details']['BT']->order_total,2);   
		$debitos_aceitos = array();
		$method->debito_bb?$debitos_aceitos[] = 'bb':'';
		$method->debito_bradesco==1?$debitos_aceitos[] = 'bradesco':'';
		$method->debito_banrisul==1?$debitos_aceitos[] = 'banrisul':'';
		$method->debito_itau==1?$debitos_aceitos[] = 'itau':'';
		$method->debito_hsbc==1?$debitos_aceitos[] = 'hsbc':'';
		$html_radio_termos_pagseguro = $this->getRadioTermosPagseguro();
	
		$html ='
		<div class="tabcontent tab_cc" id="tabdebito">
			<div id="div_debito"  class="div_pgtos">
		
				<div>
					<h4 class="titulo_toggle">
					<input type="radio" name="toggle_pagamentos" id="toggle_debito" value="debito" onclick="efeito_divs(\'div_debito\')"  style="float:left" class="radiofield"/>
					<label for="toggle_debito">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_DEBIT').'</label>
					</h4>
				</div>
		
		
				<form name="pagamento_debito" onsubmit="return submeter_debito(this)" id="pagamento_debito"  target="iframepagseguro">
						<ul>
						<li>
							<ul class="cards cartoes">';
							foreach($debitos_aceitos as $v) {   
								$html .= "<li><label for=\"tipo_".$v."\"><input type=\"radio\" class=\"radiofield\" name=\"tipo_pgto_debito\" style=\"width:15px\" id=\"tipo_".$v."\" value=\"".$v."\" /><img src=\"".$this->url_imagens.$v."_debito.jpg\" border=\"0\" align=\"absmiddle\" /></label></li>";
							}
							$html .= "
							</ul>
						</li>       
					</ul>
					<br style='clear:both'/>
					<div class='conteudo_pagseguro' style='display:none'>
						<li>            
							<!-- parcelas debito -->
							<ul>";
							$html .= $this->calculaParcelasDebitoBoleto($method, $order_total,'div_debitobancario');
							$html .= "
							</ul>
						</li>
			
			";
			
			
			$html .= '<div class="pg_row">
			<div class="pgcenter">
				'.$html_radio_termos_pagseguro.'
				<input type="submit" class="buttonPagsegurotransparente btn-pagar" value="'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_TRANSACTION_BUTTON').'" />  
			</div>
			</div>';
			
						$html .= "
					</div>
				</form>
		
		
			</div>
		 </div>
		
		";
		return $html;
	} 
	public function getSessionIdPagseguro($method) {
		$codigo = $this->getSessionidPagSeguro_request($method);
		return $codigo;
		/*
		for ($i=0; $i < 2; $i++) {
			$codigo = $this->getSessionidPagSeguro_request($method); 
			if (!empty($codigo)) {        
				return $codigo;
			}     
		}
		return false;
		*/
	}
	public function getSessionidPagSeguro_request($method) {
		$email_pagseguro  = $this->getSellerEmail($method);
		$token_pagseguro  = $this->getToken($method);
		$url_ws_pagseguro   = $this->getUrlWsPagseguro($method);
		# /v2/sessions?email={email}&token={token}
		// $url_completa = $url_ws_pagseguro.'/v2/sessions?email='.$email_pagseguro.'&token='.$token_pagseguro;   
		if ($method->modo_debug) {
			echo $url_ws_pagseguro.'/v2/sessions?email='.$email_pagseguro.'&token='.$token_pagseguro;   
		}
		$params = array();
		$params['email']  = $email_pagseguro;
		$params['token']  = $token_pagseguro;   
		if(function_exists('curl_exec')) {
			
			ob_start();
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url_ws_pagseguro.'/v2/sessions');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
			// curl_setopt($ch, CURLOPT_HTTPHEADER, $oAuth);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			
			curl_exec($ch); 
			$resposta = ob_get_contents(); 
			ob_end_clean();
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
			curl_close($ch);
		} else if(ini_get('allow_url_fopen') == '1') {
			$postdata = http_build_query(
					array(
							'email' => $email_pagseguro,
							'token' => $token_pagseguro
					)
			);
			$opts = array('http' =>
					array(
							'method'  => 'POST',
							'header'  => 'Content-type: application/x-www-form-urlencoded',
							'content' => $postdata
					)
			);
			$context    = stream_context_create($opts);
			$resposta   = file_get_contents($url_ws_pagseguro.'/v2/sessions', false, $context);            
		} else {
			die('Para o funcionamento do módulo, é necessário CURL ou file_get_contents ativo');
		}
		if ($method->modo_debug) {
			print_r($resposta);
		}
		$xml  = new DomDocument();
		$dom  = $xml->loadXML($resposta);
		$codigo = $xml->getElementsByTagName('id')->item(0)->nodeValue;
		return $codigo;
	}
	
	// grava os dados do retorno da Transação
	public function gravaDadosRetorno($method, $status="",  $msg_status="", $url_redirecionar='', $tipo_pagamento="", $forma_pagamento="", $parcela_selecionada="") {
		$timestamp = date('Y-m-d').'T'.date('H:i:s');   
		// recupera as informações do pagamento
		$db = JFactory::getDBO();
		$query = 'SELECT payment_name, payment_order_total, payment_currency, virtuemart_paymentmethod_id
						FROM `' . $this->_tablename . '`
						WHERE order_number = "'.$this->order_number.'"';
		$db->setQuery($query);
		$pagamento = $db->loadObjectList();
		$type_transaction = $tipo_pagamento.' - '.$forma_pagamento.($parcela_selecionada!=''?' - '.$parcela_selecionada.'x ':'');
		// $log = $this->timestamp.'|'.$this->transactionCode.'|'.$msg_status.'|'.$tipo_pagamento.'|'.$forma_pagamento.'|'.$pagamento[0]->payment_order_total;
		$log = $timestamp.'|'.$this->transactionCode.'|'.$msg_status.'|'.$tipo_pagamento.'|'.$forma_pagamento.'|'.$pagamento[0]->payment_order_total;
		
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($this->order_number))) {
			return NULL;
		}
		$response_fields = array();
		$response_fields['virtuemart_order_id']   = $virtuemart_order_id;
		$response_fields['transactionCode']     = $this->transactionCode;
		$response_fields['type_transaction']    = $type_transaction;
		$response_fields['log']           = $log;
		$response_fields['status']          = $status;
		$response_fields['msg_status']        = $msg_status;
		$response_fields['order_number']      = $this->order_number;    
		if ($url_redirecionar != '') {
			$response_fields['url_redirecionar']      = $url_redirecionar;
		}
		
		$response_fields['payment_name']      = $pagamento[0]->payment_name;
		$response_fields['payment_currency']    = $pagamento[0]->payment_currency;
		$response_fields['payment_order_total']   = $pagamento[0]->payment_order_total;
		$response_fields['virtuemart_paymentmethod_id'] = $pagamento[0]->virtuemart_paymentmethod_id;
		
		$this->storePSPluginInternalData($response_fields, 'virtuemart_order_id', true);
	}
	
	
	function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return false;
		}
		$this->getPaymentCurrency($method);
		$paymentCurrencyId = $method->payment_currency;
	}
	
		/**
		 * Display stored payment data for an order
		 *
		 */
		function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
				if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
						return null; // Another method was selected, do nothing
				}
				$db = JFactory::getDBO();
				$q = 'SELECT * FROM `' . $this->_tablename . '` '
								. 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
				$db->setQuery($q);
				if (!($paymentTable = $db->loadObject())) {
						vmWarn(500, $q . " " . $db->getErrorMsg());
						return '';
				}
				$this->getPaymentCurrency($paymentTable);
				$html = '<table class="adminlist">' . "\n";
				$html .=$this->getHtmlHeaderBE(); 
		
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_PAYMENT_NAME', 'Pagseguro API Transparente');
		$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_PAYMENT_DATE', $paymentTable->modified_on);
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_CODIGO_PAGSEGUROTRANSPARENTE', $paymentTable->transactionCode);
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_STATUS', $paymentTable->status . ' - ' . $paymentTable->msg_status);
		if (!empty($paymentTable->cofre))
			$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_COFRE', $paymentTable->cofre);
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
		$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_TYPE_TRANSACTION', $paymentTable->type_transaction);
		if (!empty($paymentTable->nome_titular_cartao))
			$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_NOME_TITULAR_CARTAO', $paymentTable->nome_titular_cartao);
		if (!empty($paymentTable->nascimento_titular_cartao))
			$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_NASCIMENTO_TITULAR_CARTAO', $paymentTable->nascimento_titular_cartao);
		if (!empty($paymentTable->telefone_titular_cartao))
			$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_TELEFONE_TITULAR_CARTAO', $paymentTable->telefone_titular_cartao);
		if (!empty($paymentTable->cpf_titular_cartao))
			$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_CPF_TITULAR_CARTAO', $paymentTable->cpf_titular_cartao);
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_LOG', $paymentTable->log);
				$html .= '</table>' . "\n";
				return $html;
		}
		function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
				if (preg_match('/%$/', $method->cost_percent_total)) {
						$cost_percent_total = substr($method->cost_percent_total, 0, -1);
				} else {
						$cost_percent_total = $method->cost_percent_total;
				}
				return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
		}
		/**
		 * Check if the payment conditions are fulfilled for this payment method
		 * @author: Valerie Isaksen
		 *
		 * @param $cart_prices: cart prices
		 * @param $payment
		 * @return true: if the conditions are fulfilled, false otherwise
		 *
		 */
		protected function checkConditions($cart, $method, $cart_prices) {
		//  $params = new JParameter($payment->payment_params);
				$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
				$amount = $cart_prices['salesPrice'];
				$amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
								OR
								($method->min_amount <= $amount AND ($method->max_amount == 0) ));
				if (!$amount_cond) {
						return false;
				}
				$countries = array();
				if (!empty($method->countries)) {
						if (!is_array($method->countries)) {
								$countries[0] = $method->countries;
						} else {
								$countries = $method->countries;
						}
				}
				// probably did not gave his BT:ST address
				if (!is_array($address)) {
						$address = array();
						$address['virtuemart_country_id'] = 0;
				}
				if (!isset($address['virtuemart_country_id']))
						$address['virtuemart_country_id'] = 0;
				if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
						return true;
				}
				return false;
		}
		/*
		 * We must reimplement this triggers for joomla 1.7
		 */
		/**
		 * Create the table for this plugin if it does not yet exist.
		 * This functions checks if the called plugin is active one.
		 * When yes it is calling the pagsegurotransparente method to create the tables
		 * @author Valérie Isaksen
		 *
		 */
		function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
				return $this->onStoreInstallPluginTable($jplugin_id);
		}
		/**
		 * This event is fired after the payment method has been selected. It can be used to store
		 * additional payment info in the cart.
		 *
		 * @author Max Milbers
		 * @author Valérie isaksen
		 *
		 * @param VirtueMartCart $cart: the actual cart
		 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
		 *
		 */
		public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
				return $this->OnSelectCheck($cart);
		}
		/**
		 * plgVmDisplayListFEPayment
		 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
		 *
		 * @param object $cart Cart object
		 * @param integer $selected ID of the method selected
		 * @return boolean True on succes, false on failures, null when this plugin was not selected.
		 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
		 *
		 * @author Valerie Isaksen
		 * @author Max Milbers
		 */
		public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
				return $this->displayListFE($cart, $selected, $htmlIn);
		}
		/*
		 * plgVmonSelectedCalculatePricePayment
		 * Calculate the price (value, tax_id) of the selected method
		 * It is called by the calculator
		 * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
		 * @author Valerie Isaksen
		 * @cart: VirtueMartCart the current cart
		 * @cart_prices: array the new cart prices
		 * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
		 *
		 *
		 */
		public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
				return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
		}
		/**
		 * plgVmOnCheckAutomaticSelectedPayment
		 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
		 * The plugin must check first if it is the correct type
		 * @author Valerie Isaksen
		 * @param VirtueMartCart cart: the cart object
		 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
		 *
		 */
		function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
				return $this->onCheckAutomaticSelected($cart, $cart_prices);
		}
		/**
		 * This method is fired when showing the order details in the frontend.
		 * It displays the method-specific data.
		 *
		 * @param integer $order_id The order ID
		 * @return mixed Null for methods that aren't active, text (HTML) otherwise
		 * @author Max Milbers
		 * @author Valerie Isaksen
		 */
		public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
			$orderModel = VmModel::getModel('orders');
			$orderDetails = $orderModel->getOrder($virtuemart_order_id);
			if (!($method = $this->getVmPluginMethod($orderDetails['details']['BT']->virtuemart_paymentmethod_id))) {
				return false;
			}
			if (!$this->selectedThisByMethodId ($virtuemart_paymentmethod_id)) {
					return NULL;
			} // Another method was selected, do nothing
			$view = JRequest::getVar('view');
			if ($view == 'orders' and $orderDetails['details']['BT']->virtuemart_paymentmethod_id == $virtuemart_paymentmethod_id) {
				$orderModel   = VmModel::getModel('orders');
				$orderDetails   = $orderModel->getOrder($virtuemart_order_id);
				$order_id     = $orderDetails['details']['BT']->order_number;
				$virtuemart_paymentmethod_id = $orderDetails['details']['BT']->virtuemart_paymentmethod_id;

				// consulta se há código de transação no pedido
				$db     = JFactory::getDBO();
				$query  = 'SELECT transactionCode
							FROM `' . $this->_tablename . '`
							WHERE order_number = "'.$order_id.'"';
				$db->setQuery($query);
				$dados_pagseguro = $db->loadObjectList();				

				if ($method->transacao_em_andamento == $orderDetails['details']['BT']->order_status) {
					JHTML::_('behavior.modal'); 
					$url_recibo = JRoute::_('index.php?option=com_virtuemart&view=pluginresponse&tmpl=component&task=pluginresponsereceived&on='.$order_id.'&pm='.$virtuemart_paymentmethod_id);
					$html = '<br /><b><a href="'.$url_recibo.'" class="modal" rel="{size: {x: 700, y: 500}, handler:\'iframe\'}" >Clique aqui para visualizar o status detalhado da transação no Pagseguro</a></b> <br /><br />';
					JFactory::getApplication()->enqueueMessage(
						$html, 'Prazos para aprovação do Pagamento via pagseguro: Cartão e Boleto 24h úteis Débito Online 2h.'
					);
				} else if (
						($method->transacao_cancelada == $orderDetails['details']['BT']->order_status)
						or 
						($method->transacao_em_andamento == $orderDetails['details']['BT']->order_status and isset($dados_pagseguro[0]->transactionCode) and $dados_pagseguro[0]->transactionCode == "")
					) {

					vmJsApi::js('facebox');
					vmJsApi::css('facebox');

					$this->order_id = $orderDetails['details']['BT']->order_number;
					$url = JURI::root();
					// carrega os js e css
					$doc        		=  JFactory::getDocument();
					$url_lib      		= $url. '/' .'plugins'. '/' .'vmpayment'. '/' .'pagsegurotransparente'.'/';
					$url_assets     	= $url_lib . 'assets'. '/';
					$url_js       		= $url_assets . 'js'. '/';
					$url_css      		= $url_assets . 'css'. '/';
					$this->url_imagens  = $url_lib . 'imagens'. '/';

					// redirecionar dentro do componente para validar
					$url_redireciona_pagsegurotransparente  = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&task2=redirecionarPagseguroAPI&tmpl=component&pm='.$orderDetails['details']['BT']->virtuemart_paymentmethod_id."&order_number=".$this->order_id);
					$url_pedidos              				= JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=orders');

					if ($method->url_redirecionamento) {
						$url_recibo_pagsegurotransparente     	= JROUTE::_($method->url_redirecionamento);
					} else {
						$url_recibo_pagsegurotransparente     	= JROUTE::_(JURI::root() .'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&on='.$this->order_id.'&pm='.$orderDetails['details']['BT']->virtuemart_paymentmethod_id);
					}

					$session_id_pagseguro = $this->getSessionIdPagseguro($method);
					if (!$session_id_pagseguro) {
						JFactory::getApplication()->enqueueMessage( 'Erro ao configurar e-mail e token do PagSeguro', 'error' );
						return false;
					}
					$url_js_directpayment = $this->getUrlJsPagseguro($method);
					$doc->addCustomTag('
						<script type="text/javascript" src="'.$url_js_directpayment.'/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>
						<script type="text/javascript">
							PagSeguroDirectPayment.setSessionId(\''.$session_id_pagseguro.'\');
							jQuery.noConflict();
							var redireciona_pagseguro = "'.$url_redireciona_pagsegurotransparente.'";
							var url_pedidos = "'.$url_pedidos.'";
							var url_recibo_pagseguro = "'.$url_recibo_pagsegurotransparente.'";
							var url_assets_pagseguro = "'.$url_assets.'";
							var order_total = '.round($orderDetails['details']['BT']->order_total,2).';
							var max_parcela_sem_juros = '.$method->max_parcela_sem_juros.';
						</script>
						<script type="text/javascript" language="javascript" src="'.$url_js.'jquery.mask.js"></script>
						<script type="text/javascript" charset="utf-8" language="javascript" src="'.$url_js.'pagsegurotransparente.js"></script>      
						<script type="text/javascript" language="javascript" src="'.$url_js.'jquery.card.js"></script>
						<script type="text/javascript" language="javascript" src="'.$url_js.'validar_cartao.js"></script>
						'.($load_squeezebox!=0?$sq_js:'').'
						<link href="'.$url_css.'css_pagamento.css" rel="stylesheet" type="text/css"/>
						
						<link href="'.$url_css.'style.css" rel="stylesheet" type="text/css"/>
						'.($load_squeezebox!=0?$sq_css:'').'
					');
						

					$html .= $this->Pagsegurotransparente_mostraParcelamento($method, $orderDetails);
					echo $html;

				}
				
			}
			
		

			$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);				
		}

		/**
		 * This event is fired during the checkout process. It can be used to validate the
		 * method data as entered by the user.
		 *
		 * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
		 * @author Max Milbers
		 *
		 * public function plgVmOnCheckoutCheckDataPayment(  VirtueMartCart $cart) {
		 *  return null;
		 * }
		 */
		/**
		 * This method is fired when showing when priting an Order
		 * It displays the the payment method-specific data.
		 *
		 * @param integer $_virtuemart_order_id The order ID
		 * @param integer $method_id  method used for this order
		 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
		 * @author Valerie Isaksen
		 */
		function plgVmonShowOrderPrintPayment($order_number, $method_id) {
				return $this->onShowOrderPrint($order_number, $method_id);
		}
		function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
				return $this->declarePluginParams('payment', $name, $id, $data);
		}
		function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
				return $this->setOnTablePluginParams($name, $id, $table);
		}
		//Notice: We only need to add the events, which should work for the specific plugin, when an event is doing nothing, it should not be added
		/**
		 * Save updated order data to the method specific table
		 *
		 * @param array $_formData Form data
		 * @return mixed, True on success, false on failures (the rest of the save-process will be
		 * skipped!), or null when this method is not actived.
		 * @author Oscar van Eijk
		 *
		 * public function plgVmOnUpdateOrderPayment(  $_formData) {
		 * return null;
		 * }
	 *
		 * Save updated orderline data to the method specific table
		 *
		 * @param array $_formData Form data
		 * @return mixed, True on success, false on failures (the rest of the save-process will be
		 * skipped!), or null when this method is not actived.
		 * @author Oscar van Eijk
		 *
		 * public function plgVmOnUpdateOrderLine(  $_formData) {
		 * return null;
		 * }
	 *
	 *    
		 * plgVmOnEditOrderLineBE
		 * This method is fired when editing the order line details in the backend.
		 * It can be used to add line specific package codes
		 *
		 * @param integer $_orderId The order ID
		 * @param integer $_lineId
		 * @return mixed Null for method that aren't active, text (HTML) otherwise
		 * @author Oscar van Eijk
		 *
		 * public function plgVmOnEditOrderLineBEPayment(  $_orderId, $_lineId) {
		 * return null;
		 * }
		 * This method is fired when showing the order details in the frontend, for every orderline.
		 * It can be used to display line specific package codes, e.g. with a link to external tracking and
		 * tracing systems
		 *
		 * @param integer $_orderId The order ID
		 * @param integer $_lineId
		 * @return mixed Null for method that aren't active, text (HTML) otherwise
		 * @author Oscar van Eijk
		 *
		 * public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
		 * return null;
		 * }
	 *
		 * /**
		 * This event is fired when the  method notifies you when an event occurs that affects the order.
		 * Typically,  the events  represents for payment authorizations, Fraud Management Filter actions and other actions,
		 * such as refunds, disputes, and chargebacks.
		 *
		 * NOTE for Plugin developers:
		 *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
		 *
		 * @param $return_context: it was given and sent in the payment form. The notification should return it back.
		 * Used to know which cart should be emptied, in case it is still in the session.
		 * @param int $virtuemart_order_id : payment  order id
		 * @param char $new_status : new_status for this order id.
		 * @return mixed Null when this method was not selected, otherwise the true or false
		 *
		 * @author Valerie Isaksen
		 *
		 *
		 * public function plgVmOnPaymentNotification() {
		 *  return null;
		 * }
	 */
	function plgVmOnPaymentNotification() {

		if (!class_exists('VirtueMartCart'))
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		if (!class_exists('shopFunctionsF'))
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		if (!class_exists('VirtueMartModelOrders'))
			require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

		// redireciona o fluxo para a api do Pagseguro
		$task2 = JRequest::getVar('task2', '');


		if ($task2 == 'redirecionarPagseguroAPI') {
			// trata os retornos no Virtuemart ( atualizando status )
			$pm       			 = JRequest::getVar('pm');
			$order_number   	 = JRequest::getVar('order_number');
			$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);

			$this->logInfo('plgVmOnPaymentNotification: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');
			if (!$virtuemart_order_id) {
				return;
			}
			$vendorId = 0;
			$payment = $this->getDataByOrderId($virtuemart_order_id);   
			if($payment->payment_name == '') {
				return false;
			}
			// recupera as informações do método de pagamento
			$method = $this->getVmPluginMethod($pm);
			if (!$this->selectedThisElement($method->payment_element)) {
				return false;
			}
			if (!$payment) {
				$this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');
				return null;
			}
			$forma_pagamento  = JRequest::getVar('forma_pagamento');
			$tipo_pagamento   = JRequest::getVar('tipo_pagamento');
			// json de retorno js
			$json_retorno     = array();
			$json_retorno['tipo_pagamento'] = $forma_pagamento;
			$json_retorno['status'];
			// cria a transação com o webservice do PagSeguro
			$retorno      = $this->createTransaction();
			$arr_retorno    = $retorno['msg'];
			if ($retorno['erro'] == 'true') {   
				$json_retorno['erro']     = true;
				$json_retorno['msg_erro'] = $this->trataRetornoFalhaPagseguro($arr_retorno, $method);
				//$json_retorno['paymentLink'] = '';
			} else {
				$this->trataRetornoSucessoPagseguro($arr_retorno, $method);
				// no caso de boleto já retorna com o link
				$json_retorno['paymentLink']= $arr_retorno['paymentLink'];
				$json_retorno['erro']     = false;
				$json_retorno['status']   = $arr_retorno['status'];
				$json_retorno['msg']    = 'Transação: '.$arr_retorno['descriptionStatus'];
			}
			echo json_encode($json_retorno);
			die();
		} else {
			// retorno automático boleto/débito bancário Pagseguro
			header("Status: 200 OK");
			$pagseguro_data = $_REQUEST;
			/*
			if (!isset($pagseguro_data['notificationType'])) {
				return;
			}
			*/
			$order_number = $pagseguro_data['order_number'];
			$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
			$this->logInfo('plgVmOnPaymentNotification: Pagseguro - '.$pagseguro_data['transacao_id'].' - '.$pagseguro_data['pedido'].' - '.$pagseguro_data['status']);
			if (!$virtuemart_order_id) {
				return;
			}
			$vendorId = 0;
			$payment = $this->getDataByOrderId($virtuemart_order_id);
			if($payment->payment_name == '') {
				return false;
			}   
			$method = $this->getVmPluginMethod($payment->virtuemart_paymentmethod_id);
			if (!$this->selectedThisElement($method->payment_element)) {
				return false;
			}
			if (!$payment) {
				$this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');
				return null;
			}
			$this->logInfo('pagseguro_ws_data ' . implode('    ', $pagseguro_data), 'message');
			// get all know columns of the table
			$db         = JFactory::getDBO();
			$url_ws_pagseguro   = $this->getUrlWsPagseguro($method);
		
			// código de notificação da transação no PagSeguro
			$notificationCode   = $pagseguro_data['notificationCode'];
			$notificationType   = $pagseguro_data['notificationType'];      
			$emailPagseguro   	= $this->getSellerEmail($method);
			$tokenPagseguro   	= $this->getToken($method);
			$urlPost      		= $url_ws_pagseguro.'/v2/transactions/notifications/'.$notificationCode.'/?email='.$emailPagseguro.'&token='.$tokenPagseguro;
			// $params = array(
			//  "email" => $this->getSellerEmail($method),
			//  "token" => $this->getToken($method)
			// );
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $urlPost); 
			curl_setopt($ch, CURLOPT_POST, false);
			// curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			$resposta = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// faz a validação dos dados
			if($httpCode == "200") {
				// pega os dados da transação por completo
				$xml          = new DomDocument();
				$dom          = $xml->loadXML($resposta);
				$code_transacao     = $xml->getElementsByTagName("code")->item(0)->nodeValue;
				// consulta os dados do pagseguro
				$query  = 'SELECT order_number
							FROM `' . $this->_tablename . '`
							WHERE transactionCode = "'.$code_transacao.'"';
				$db->setQuery($query);
				$dados_pagseguro = $db->loadObjectList();
				if ($dados_pagseguro[0]->order_number != $order_number){
					// não é a mesma transação do código da transação
					$this->logInfo('plgVmOnPaymentNotification - return false transaction. Order number: ' . $order_number.' - Order number DB: '.$dados_pagseguro[0]->order_number, 'error');
					return;
				}
						
				$status         		= $xml->getElementsByTagName("status")->item(0)->nodeValue;       
				$reference        		= $xml->getElementsByTagName("reference")->item(0)->nodeValue;
				$type           		= $xml->getElementsByTagName("type")->item(0)->nodeValue;
				$cancellationSource   	= $xml->getElementsByTagName("cancellationSource")->item(0)->nodeValue;
				$installmentCount     	= $xml->getElementsByTagName("installmentCount")->item(0)->nodeValue;
				// codigo meio pagamento
				$code_payment       	= $xml->getElementsByTagName("paymentMethod")->item(0)->getElementsByTagName("paymentMethod")->item(0)->nodeValue;

				$arr_status_pagamento   = $this->getStatusPagamentoPagseguroRetorno($method, $status);
				$novo_status      		= $arr_status_pagamento[0];
				$mensagem         		= $arr_status_pagamento[1];
//              $meio_pagamento     	= $transacao_dados->transacao->meio_pagamento;
				$codigo_meio_pagamento  = $code_payment;
				$forma_pagamento    	= $this->getPaymentMethod($code_payment);
				$tipo_pagamento     	= $this->getNamePaymentByCode($type);
				$parcela_selecionada  	= $installmentCount;
				$transactionCode    	= $code_transacao;
				$this->logInfo('plgVmOnPaymentNotification return new_status:' . $novo_status, 'message');
					// grava os dados de retorno e já troca o status do pedido
				$this->gravaDadosRetorno($method, $novo_status, $mensagem,'',$tipo_pagamento,$forma_pagamento,$parcela_selecionada);
				// não atualiza o pedido para transação concluída
				if ($status != 4) {
					$this->trocaStatusPedidoPagseguroAPI($transactionCode, $novo_status, $mensagem, $method, $order_number);
				}
				$this->emptyCart($return_context);
 
			}
			die('ok');
		}
		
	}
	
	/**
	 * plgVmOnPaymentResponseReceived
	 * This event is fired when the  method returns to the shop after the transaction
	 *
	 *  the method itself should send in the URL the parameters needed
	 * NOTE for Plugin developers:
	 *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
	 *
	 * @param int $virtuemart_order_id : should return the virtuemart_order_id
	 * @param text $html: the html to display
	 * @return mixed Null when this method was not selected, otherwise the true or false
	 *
	 * @author Valerie Isaksen
	 *
	 *
	 * function plgVmOnPaymentResponseReceived(, &$virtuemart_order_id, &$html) {
	 * return null;
	 * }
	 */
	function plgVmOnPaymentResponseReceived(&$html='') {
		// recibo da transação do Pagseguro
		if (!class_exists('VirtueMartCart'))
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		if (!class_exists('shopFunctionsF'))
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		if (!class_exists('VirtueMartModelOrders'))
			require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
		$pagsegurotransparente_data = JRequest::get('post');
		vmdebug('PAGSEGUROTRANSPARENTE plgVmOnPaymentResponseReceived', $pagsegurotransparente_data);
		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);
		
		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return null;
		}
		$order_number = JRequest::getString('on', 0);
		$vendorId = 0;
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number) )) {
			return null;
		}
		if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id) )) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}
		$payment_name = $this->renderPluginName($method);
		$modelOrder = VmModel::getModel('orders');
		$orderdetails = $modelOrder->getOrder($virtuemart_order_id);                
		$html = $this->_getPaymentResponseHtml($paymentTable, $payment_name, $orderdetails['details'], $method);
		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();
	}

	function _getPaymentResponseHtml($pagsegurotransparenteTable, $payment_name, $orderDetails=null, $method=null) {
		$html = '<table>' . "\n";
		$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_PAYMENT_NAME', $payment_name);
		$task = JRequest::getVar('task','');
		$img_pagamentos = array();
		/*
		$img_pagamentos['BoletoBancario - Boleto Bancário'] = 'boleto_bancario.jpg';
		$img_pagamentos['DebitoBancario - Bradesco']    = 'bradesco_debito.jpg';
		$img_pagamentos['DebitoBancario - BancoDoBrasil']   = 'bb_debito.jpg';
		$img_pagamentos['DebitoBancario - Banrisul']    = 'banrisul_debito.jpg';
		$img_pagamentos['DebitoBancario - Itau']      = 'itau_debito.jpg';
		*/
		if ($task == 'pluginresponsereceived') {
			JFactory::getApplication()->enqueueMessage(
				JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_CHECK_TRANSACTION')
			);
			$link_pedido = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=orders&layout=details&order_number='.$pagsegurotransparenteTable->order_number.'&order_pass='.$orderDetails['BT']->order_pass);
			if (!empty($pagsegurotransparenteTable)) {
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_ORDER_NUMBER', $pagsegurotransparenteTable->order_number);
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_PAYMENT_DATE', $pagsegurotransparenteTable->modified_on);
				$html .= '<tr><td colspan="2"><br /></td></tr>';
				if ($pagsegurotransparenteTable->transactionCode) {
					$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_CODIGO_PAGSEGUROTRANSPARENTE','<b>'.$pagsegurotransparenteTable->transactionCode.'</b>');
				}
				$pagsegurotransparente_status = '<b>Transação em Andamento</b>';
				if ($pagsegurotransparenteTable->msg_status) {
					//$pagsegurotransparente_status = '<b>'.$pagsegurotransparenteTable->status. " - " . $pagsegurotransparenteTable->msg_status.'</b><br />';
					$pagsegurotransparente_status = '<b>Transação: '. $pagsegurotransparenteTable->msg_status.'</b><br />';
				}
				if ($orderDetails['BT']->order_status == $method->transacao_em_andamento and $pagsegurotransparenteTable->url_redirecionar != '') {
					//$url_imagem = JURI::root().DS.'plugins'.DS.'vmpayment'.DS.'pagsegurotransparente'.DS.'imagens'.DS;
					//$url_imagem .= $img_pagamentos[$pagsegurotransparenteTable->type_transaction];
					//$imagem_redirecionar = '<img src="'.$url_imagem.'" border="0"/>';
					if (!empty($pagsegurotransparenteTable->url_redirecionar)) {
						$pagsegurotransparente_status .= '<div style="padding: 10px"><br /><a target="blank" href="'.urldecode($pagsegurotransparenteTable->url_redirecionar).'">Clique aqui para segunda via do Boleto ou Transferência Bancária.</a><br /><br /></div>';            
					}
				}
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_STATUS', $pagsegurotransparente_status);        
				$html .= '<tr><td colspan="2"><br /></td></tr>';
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_AMOUNT', $pagsegurotransparenteTable->payment_order_total. " " . $pagsegurotransparenteTable->payment_currency);
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_TYPE_TRANSACTION', $pagsegurotransparenteTable->type_transaction);
				$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_LOG', $pagsegurotransparenteTable->log);
				$html .= '</table>' . "\n";
				$html .= '<br />';
				$tmpl = JRequest::getVar('tmpl');
				if ($tmpl != 'component') {
					$html .= '<a href="'.$link_pedido.'" class="button">'.JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_ORDER_DETAILS').'</a>
					' . "\n";
				}
			}
		} else {
			$html .= $this->getHtmlRowBE('PAGSEGUROTRANSPARENTE_ORDER_NUMBER', $this->order_id);      
		}   
		$html .= '</table>' . "\n";
		return $html;
	}   
		
	function plgVmOnUserPaymentCancel() {
		if (!class_exists('VirtueMartModelOrders'))
		require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
		$order_number = JRequest::getVar('on');
		if (!$order_number)
		return false;
		$db = JFactory::getDBO();
		$query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";
		$db->setQuery($query);
		$virtuemart_order_id = $db->loadResult();
		if (!$virtuemart_order_id) {
			return null;
		}
		$this->handlePaymentUserCancel($virtuemart_order_id);
		return true;
	}

	public function getPaymentMethod($codigo){
		// cartão de crédito
		$arr_method_payments[101] = 'Visa';
		$arr_method_payments[102] = 'MasterCard';
		$arr_method_payments[103] = 'American Express';
		$arr_method_payments[104] = 'Diners';
		$arr_method_payments[105] = 'Hipercard';
		$arr_method_payments[106] = 'Aura';
		$arr_method_payments[107] = 'Elo';
		$arr_method_payments[108] = 'PLENOCard';
		$arr_method_payments[109] = 'PersonalCard';
		$arr_method_payments[110] = 'JCB';
		$arr_method_payments[111] = 'Discover';
		$arr_method_payments[112] = 'BrasilCard';
		$arr_method_payments[113] = 'FORTBRASIL';
		$arr_method_payments[114] = 'CARDBAN';
		$arr_method_payments[115] = 'VALECARD';
		$arr_method_payments[116] = 'Cabal';
		$arr_method_payments[117] = 'Mais!';
		$arr_method_payments[118] = 'Avista';
		$arr_method_payments[119] = 'GRANDCARD';
		$arr_method_payments[120] = 'Sorocred';
		// boleto bancário
		$arr_method_payments[201] = 'Bradesco';
		$arr_method_payments[202] = 'Santander';
		// débito
		$arr_method_payments[301] = 'Débito online Bradesco';
		$arr_method_payments[302] = 'Débito online Itaú';
		$arr_method_payments[303] = 'Débito online Unibanco';
		$arr_method_payments[304] = 'Débito online Banco do Brasil';
		$arr_method_payments[305] = 'Débito online Banco Real';
		$arr_method_payments[306] = 'Débito online Banrisul';
		$arr_method_payments[307] = 'Débito online HSBC';
		// saldo
		$arr_method_payments[401] = 'Saldo PagSeguro';
		// oi paggo
		$arr_method_payments[501] = 'Oi Paggo';
		// deposito
		$arr_method_payments[701] = 'Depósito em conta - Banco do Brasil';
		$arr_method_payments[702] = 'Depósito em conta - HSBC';
		return $arr_method_payments[$codigo];
	}
	public function getNamePaymentByCode($codigo) {
		$arr_method_payments[1] = 'CartaodeCredito';
		$arr_method_payments[2] = 'BoletoBancario';
		$arr_method_payments[3] = 'DebitoBancario';
		$arr_method_payments[4] = 'SaldoPagseguro';
		$arr_method_payments[5] = 'OiPaggo';
		$arr_method_payments[7] = 'DepositoemConta';
		return $arr_method_payments[$codigo];
	}
	public function getCreditCard() {
		$arr_cartao         = array();
		$holder           = JRequest::getVar('c_holder');
		$number           = JRequest::getVar('c_number');
		$securityCode         = JRequest::getVar('c_securityCode');
		$expiry_date        = $this->getExpiryDate(JRequest::getVar('c_expiry_date'));
		$arr_cartao['holder']     = $holder;
		$arr_cartao['number']   = $number;
		$arr_cartao['securityCode'] = $securityCode;
		$arr_cartao['maturityMonth']= $expiry_date['maturityMonth'];
		$arr_cartao['maturityYear'] = '20'.$expiry_date['maturityYear'];
		return $arr_cartao;
	}
	public function getExpiryDate($data) {
		$arr_data = explode('/',$data);
		$return_data['maturityMonth']   = $arr_data[0];
		$return_data['maturityYear']  = $arr_data[1];
		return $return_data;
	}
	public function getShipmentName($orderdetails) {
		$shipmentmethods  = VmModel::getModel('shipmentmethod');
		$data_shipment    = $shipmentmethods->getTable('shipmentmethods');
		$data_shipment->load($orderdetails->virtuemart_shipmentmethod_id);
		if (isset($data_shipment)) {
			return $data_shipment->shipment_name;
		} else {
			return '';
		}
	}
	public function getStateName($state_id) {
		$state = VmModel::getModel('state');
		$data_state = $state->getTable('states');
		$data_state->load($state_id);
		if (isset($data_state)) {
			return $data_state->state_2_code;
		} else {
			return '';
		}
	}
	public function getParcelas() {
		$parcelas = JRequest::getVar('parcela_selecionada',1);
		return $parcelas;
	}
	public function getTransactionKey($orderdetails) {
		return '';
	}
	public function getToken($method) {
		if ($method->modo_teste) {
			$token  = $method->token_teste;
		} else {
			$token  = $method->token;
		}
		return $token;
	
	}
	public function getSellerEmail($method) {
		if ($method->modo_teste) {
			$sellerMail  = $method->sellermail_teste;
		} else {
			$sellerMail  = $method->sellermail;
		}
		return $sellerMail;
	
	}
	public function getConsumerKey($method) {
		if ($method->modo_teste) {
			$consumerKey = $method->oauth_consumer_key_teste;
		} else {
			$consumerKey = $method->oauth_consumer_key;
		}
		return $consumerKey;
	}
	public function getUrlWsPagseguro($method) {
		if ($method->modo_teste) {
			return 'https://ws.sandbox.pagseguro.uol.com.br';
		} else {
			return 'https://ws.pagseguro.uol.com.br';
		}
	}
	public function getUrlJsPagseguro($method) {    
		if ($method->modo_teste) {
			return 'https://stc.sandbox.pagseguro.uol.com.br';
		} else {
			return 'https://stc.pagseguro.uol.com.br';
		}
	}
	public function createTransaction() {
		// retorno da transação
		$arr_retorno = array();
		if (!class_exists('VirtueMartModelOrders')) {
			require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );    
		}
		$order_number = JRequest::getVar('order_number');
		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
		if (!$virtuemart_order_id) {
			$arr_retorno['msg']  = 'Erro ao recuperar o id do pedido ao redirecionar';
			$arr_retorno['erro'] = 'true';
		}
		$vendorId = 0;
		$payment = $this->getDataByOrderId($virtuemart_order_id);
		if($payment->payment_name == '') {
			$arr_retorno['msg']   = 'Método de pagamento não encontrado';
			$arr_retorno['erro']  = 'true';
		}
		// recupera as informações do método de pagamento
		$virtuemart_paymentmethod_id = ($payment->virtuemart_paymentmethod_id)?$payment->virtuemart_paymentmethod_id:$pm; 
		$method     = $this->getVmPluginMethod($virtuemart_paymentmethod_id);   
		// carregando pedido manualmente
		$order      = VirtueMartModelOrders::getOrder($virtuemart_order_id);
		// cria a transação com o Pagseguro
		$time       = time()*1000;
		$microtime    = microtime();
		$rand       = mt_rand();
		$charset    = 'UTF-8';
		// dados do pagseguro ( configuração )
		$sellerMail   = $this->getSellerEmail($method);
		$token_pagseguro= $this->getToken($method);   
		$token_compra   = JRequest::getVar('token_compra');
		$forma_pagamento= JRequest::getVar('forma_pagamento');
		$tipo_pagamento = JRequest::getVar('tipo_pagamento');
		$senderHash   = JRequest::getVar('senderHash');
		// $numero_metodo_pagamento = $this->getPaymentMethod($forma_pagamento, $tipo_pagamento);
		// if ($numero_metodo_pagamento) {
			$json_pedido                = array();
			// dados do pedido
			$json_pedido['email']           = $sellerMail;
			$json_pedido['token']           = $token_pagseguro;
			$json_pedido['paymentMode']     = 'default';
			$json_pedido['receiverEmail']   = $this->getSellerEmail($method);
			$json_pedido['currency']        = 'BRL';
			$total_tax                  	= $order['details']['BT']->order_tax;     

			// desconto e tarifa no mesmo campo
			if (!empty($order["details"]["BT"]->coupon_discount)) {
				// $extraAmount              = $total_tax + ( (float)$order["details"]["BT"]->coupon_discount * -1);
				$extraAmount              = ( (float)$order["details"]["BT"]->coupon_discount );
			} else {
				// $extraAmount             = $total_tax;
				$extraAmount              = 0;
			}

			// adiciona a tarifa do método de pagamento
			if (!empty($order["details"]["BT"]->order_payment)) {
				$extraAmount += $order["details"]["BT"]->order_payment;
			}

			$json_pedido['extraAmount']         = number_format(round($extraAmount,2),2,'.','');
			$i = 1;
			foreach ($order['items'] as $chave => $produto) {     
				$json_pedido['itemId'.$i]         = ($produto->order_item_sku!='')?$produto->order_item_sku:$produto->virtuemart_product_id;
				$json_pedido['itemDescription'.$i]    = substr($produto->order_item_name,0,100);
				$json_pedido['itemAmount'.$i]       = number_format(round($produto->product_final_price,2),2,'.','');
				$json_pedido['itemQuantity'.$i]     = $produto->product_quantity;
				$i++;
			}
			$url_notificacao              	= str_replace('https://','http://',JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm='.$order['details']['BT']->virtuemart_paymentmethod_id));
			$json_pedido['notificationURL'] = $url_notificacao;
			$order_number                 	= JRequest::getVar('order_number');
			$json_pedido['reference']       = $order_number;
			// campo cpf
			$campo_cpf                  	= $method->campo_cpf;
			$campo_cep                  	= $method->campo_cep;
			// cnpj
			$campo_cnpj                  	= $method->campo_cnpj;
			$campo_numero                 	= $method->campo_numero;
			$campo_bairro                 	= $method->campo_bairro;
			$campo_complemento              = $method->campo_complemento;
			$campo_data_nascimento          = $method->campo_data_nascimento;
			$bt_comprador                 	= $order['details']['BT'];
			if (isset($order['details']['ST'])) {
				$st_comprador               = $order['details']['ST'];
			} else {
				$st_comprador             	=	$bt_comprador;
			}

			$campo_nome 	 				= $method->campo_nome;
			$campo_sobrenome 				= $method->campo_sobrenome;
			// billing data
			$json_pedido['senderName']      = $bt_comprador->$campo_nome.' '.$bt_comprador->$campo_sobrenome;

			// cpf do comprador     
			$cpfComprador                 	= $this->formataCPF((isset($bt_comprador->$campo_cpf) and !empty($bt_comprador->$campo_cpf))?$bt_comprador->$campo_cpf:'');
			if (empty($cpfComprador)) {
				$cpfComprador               = $this->formataCPF(JRequest::getVar('cpf'));
			}
			if ($method->modo_debug) {
				print_r($bt_comprador);   
			}

			$json_pedido['senderCPF']       = $cpfComprador;

			if (!empty($campo_cnpj) and $bt_comprador->$campo_cnpj != '') {
				$cnpjComprador 				= $this->formataCNPJ($bt_comprador->$campo_cnpj);
				$json_pedido['senderCNPJ']  = $cnpjComprador;
				unset($json_pedido['senderCPF']);
			}

			$phone = $bt_comprador->phone_1;

			$telefone                       = preg_replace('#[^0-9]#', '', $phone);     
			$json_pedido['senderAreaCode']  = substr($telefone, 0, 2);
			$json_pedido['senderPhone']     = substr($telefone, 2, 9);

			if ($method->modo_teste) {
				$json_pedido['senderEmail'] = $method->email_teste;       
			} else {
				$json_pedido['senderEmail'] = $bt_comprador->email;       
			}
			$json_pedido['senderHash']      = $senderHash;
			// shipping address
			$json_pedido['shippingAddressStreet']     = $st_comprador->address_1;
			$json_pedido['shippingAddressNumber']     = ((isset($st_comprador->$campo_numero) and !empty($st_comprador->$campo_numero))?$st_comprador->$campo_numero:'');
			$json_pedido['shippingAddressComplement'] = ((isset($st_comprador->$campo_complemento) and !empty($st_comprador->$campo_complemento))?$st_comprador->$campo_complemento:'');
			$json_pedido['shippingAddressDistrict']   = ((isset($st_comprador->$campo_bairro) and !empty($st_comprador->$campo_bairro))?$st_comprador->$campo_bairro:'');
			$json_pedido['shippingAddressPostalCode'] = str_replace('-','',$st_comprador->$campo_cep);
			$json_pedido['shippingAddressCity']       = $st_comprador->city;
			$json_pedido['shippingAddressState']      = $this->getStateName($st_comprador->virtuemart_state_id);
			$json_pedido['shippingAddressCountry']    = 'BRA';
			// shipping
			$json_pedido['shippingType']        	  = '3'; // outros
			if (isset($order['details']['BT']->order_shipping)){
				$json_pedido['shippingCost']      = number_format(round($order['details']['BT']->order_shipping,2),2,'.','');
			} elseif (isset($order['details']['BT']->order_shipment)){
				$json_pedido['shippingCost']      = number_format(round($order['details']['BT']->order_shipment,2),2,'.','');
			} else {
				$json_pedido['shippingCost']      = 0;
			}
			// cartão de crédito
			$json_pedido['creditCardToken']       = $token_compra;
			$parcelas_compra              = $this->getParcelas();
			$json_pedido['installmentQuantity']     = $parcelas_compra;
			$valor_parcela                = JRequest::getVar('valor_parcela','');
			if (!empty($valor_parcela)) {
				$json_pedido['installmentValue']    = number_format(round($valor_parcela,2),2,'.','');
			} else {
				if ($json_pedido['installmentQuantity'] == 1) {
					// $json_pedido['installmentValue']     = number_format($valor_parcela,2,'.','');
					$json_pedido['installmentValue']    = number_format(round($order['details']['BT']->order_total,2),2,'.','');
				} else {                
					/* 
					-- método antigo de calcular a parcela
					// calcular as parcelas
					$order_total = $order['details']['BT']->order_total;
					// $total_parcela = round($order_total / $parcelas_compra,2);
					if ($parcelas_compra <= $method->max_parcela_sem_juros) {
						$total_parcela = round($order_total / $parcelas_compra,2);
						// $json_pedido['noInterestInstallmentQuantity']  = $method->max_parcela_sem_juros;
						$json_pedido['noInterestInstallmentQuantity']   = $parcelas_compra;
					} else {
						$tipo_parcelamento_juros = true; // com juros
						$total_parcela = round($this->calculaParcelaPRICE($order_total,$parcelas_compra,$method->taxa_parcelado),2);
					}
					$json_pedido['installmentValue']    = number_format($total_parcela,2,'.','');       
					*/
				}
			}
			if ($method->max_parcela_sem_juros > 1) {
				$json_pedido['noInterestInstallmentQuantity']   = $method->max_parcela_sem_juros;
			}
			
			// billing address
			$json_pedido['billingAddressStreet']    = $bt_comprador->address_1;
			$json_pedido['billingAddressNumber']    = ((isset($bt_comprador->$campo_numero) and !empty($bt_comprador->$campo_numero))?$bt_comprador->$campo_numero:'');
			$json_pedido['billingAddressComplement']  = ((isset($bt_comprador->$campo_complemento) and !empty($bt_comprador->$campo_complemento))?$bt_comprador->$campo_complemento:'');
			$json_pedido['billingAddressDistrict']    = ((isset($bt_comprador->$campo_bairro) and !empty($bt_comprador->$campo_bairro))?$bt_comprador->$campo_bairro:'');
			$json_pedido['billingAddressPostalCode']  = str_replace('-','',$bt_comprador->$campo_cep);
			$json_pedido['billingAddressCity']      = $bt_comprador->city;
			$json_pedido['billingAddressState']     = $this->getStateName($bt_comprador->virtuemart_state_id);
			$json_pedido['billingAddressCountry']     = 'BRA';
			// recupera a url de retorno
			$json_pedido['notificationURL']       = JROUTE::_(JURI::root().'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&order_number='.$order['details']['BT']->order_number.'&pm='. $order['details']['BT']->virtuemart_paymentmethod_id);
			// $json_pedido['urlReturn']          = JROUTE::_(JURI::root().'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm='. $order['details']['BT']->virtuemart_paymentmethod_id);
			if ($forma_pagamento == 'CartaodeCredito') {
				// sempre pega o do formulário
				$cpf_form = JRequest::getVar('c_cpf');
				$json_pedido['senderCPF']         = $this->formataCPF($cpf_form);
				$c_holder                 = JRequest::getVar('c_holder');
				$c_cpf                    = $this->formataCPF($cpf_form);
				$c_phone                  = JRequest::getVar('c_phone');
				$c_birthdate              = JRequest::getVar('c_birthdate');
				// $birthDate             = $this->formataData(((isset($bt_comprador->$campo_data_nascimento) and !empty($bt_comprador->$campo_data_nascimento))?$bt_comprador->$campo_data_nascimento:''));                
				$birthDate                = $this->formataData($c_birthdate);
				$json_pedido['paymentMethod']       = 'creditCard';
				$json_pedido['creditCardHolderName']  = $c_holder;
				$json_pedido['creditCardHolderCPF']   = $c_cpf;
				$json_pedido['creditCardHolderBirthDate']= $birthDate;
				$telefone                 = preg_replace('#[^0-9]#', '', $c_phone);
				$json_pedido['creditCardHolderAreaCode']= substr($telefone, 0, 2);
				$json_pedido['creditCardHolderPhone']   = substr($telefone, 2, 9);
			} elseif ($forma_pagamento == 'BoletoBancario') {
				$json_pedido['paymentMethod']       = 'boleto'; 
			} elseif ($forma_pagamento == 'DebitoBancario')  {
				$json_pedido['paymentMethod']       = 'eft'; 
				$json_pedido['bankName']        = $tipo_pagamento; 
			} else {
				return false;
			}
			foreach ($json_pedido as $chave => $valor) {
				$json_pedido[$chave] = utf8_decode($valor);
			}
			if ($method->modo_debug) {
				print_r($json_pedido);
				// die();       
			}
			// url webservice
			$url_ws  = $this->getUrlWsPagseguro($method);
			$urlPost = $url_ws."/v2/transactions";
			ob_start();
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $urlPost);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($json_pedido, '', '&'));
			// curl_setopt($ch, CURLOPT_HTTPHEADER, $oAuth);
			curl_setopt($ch, CURLOPT_ENCODING ,"");
			//Por default o CURL requer um ambiente SSL, durante testes/desenvolvimento ou caso não possua o protocolo de segurança, pode-se evitar a verificação SSL do CURL através das duas lonhas abaixo:
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_exec($ch);
			$resposta = ob_get_contents();
			ob_end_clean();
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			/**
			* 200 Informação processada com sucesso
			* 400 Requisição com parâmetros obrigatórios vazios ou inválidos
			* 401 Falha na autenticação ou sem acesso para usar o serviço
			* 405 Método não permitido, o serviço suporta apenas POST
			* 415 Content-Type não suportado
			* 500 Erro fatal na aplicação, executar a solicitação mais tarde
			* 503 Serviço está indisponível
			**/

			$xml  = new DomDocument();
			$dom  = $xml->loadXML($resposta);
			if ($method->modo_debug) {
				print_r($resposta);
				die();        
			}
			$arr_retorno['erro'] = 'true';      
			if ($httpCode == "200") {
				$arr_retorno['erro'] = 'false';
				$json_array = array();
				// código da transação
				$json_array['code']         = $xml->getElementsByTagName("code")->item(0)->nodeValue;
				$json_array['date']         = $xml->getElementsByTagName("date")->item(0)->nodeValue;
				$json_array['lastEventDate']    = $xml->getElementsByTagName("lastEventDate")->item(0)->nodeValue;
				$json_array['reference']      = $xml->getElementsByTagName("reference")->item(0)->nodeValue;
				$json_array['type']         = $xml->getElementsByTagName("type")->item(0)->nodeValue;
				$json_array['cancellationSource'] = $xml->getElementsByTagName("cancellationSource")->item(0)->nodeValue;
				$json_array['paymentLink']      = $xml->getElementsByTagName("paymentLink")->item(0)->nodeValue;
				/*
				1 Aguardando pagamento: o comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.
				2 Em análise: o comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.
				3 Paga: a transação foi paga pelo comprador e o PagSeguro já recebeu uma confirmação da instituição financeira responsável pelo processamento.
				4 Disponível: a transação foi paga e chegou ao final de seu prazo de liberação sem ter sido retornada e sem que haja nenhuma disputa aberta.
				5 Em disputa: o comprador, dentro do prazo de liberação da transação, abriu uma disputa.
				6 Devolvida: o valor da transação foi devolvido para o comprador.
				7 Cancelada: a transação foi cancelada sem ter sido finalizada.
				*/
				$json_array['status']         = $xml->getElementsByTagName("status")->item(0)->nodeValue;
				$status_pagamento           = $this->getStatusPagamentoPagseguroRetorno($method, $json_array['status']);
				$json_array['status_pedido']    = $status_pagamento[0];
				$json_array['descriptionStatus']  = $status_pagamento[1];
				$json_resposta            = json_encode($json_array);       
				$arr_retorno['msg']         = $json_array;
			} else {
				//$arr_retorno['msg'] = 'Requisição com parâmetros obrigatórios vazios ou inválidos';
				$errors = $xml->getElementsByTagName("errors");
				$errors_list = array();
				if ($errors->length >= 1) {
						foreach( $errors as $erro ) {
						$code         = $erro->getElementsByTagName("code")->item(0)->nodeValue;
						$message      = $erro->getElementsByTagName("message")->item(0)->nodeValue;
						$errors_list[$code] = $message;
					}
				}
				// se não tiver vindo nada do PagSeguro
				if (count($errors_list) == 0) {
					$errors_list[$httpCode] = "Erro interno";
				}
				//$json_errors = json_encode($errors_list);
				$arr_retorno['msg']     = $errors_list;
				$arr_retorno['erro']    = 'true';
				$arr_retorno['tipo']    = $httpCode;
			}
			// $arr_retorno['msg'] = $json_resposta;
		// } else {
		//  $arr_retorno['msg']       = 'Erro ao capturar o método de pagamento';
		//  $arr_retorno['erro']      = 'true';
		//  //$resposta = 'Erro ao capturar o método de pagamento';
		// }
		return $arr_retorno;
	} 
	public function getValidCreditCard($number) { 
		$odd = true;
		$sum = 0;
		foreach ( array_reverse(str_split($number)) as $num) {
			$sum += array_sum( str_split(($odd = !$odd) ? $num*2 : $num) );
		}
		if (($sum % 10 == 0) && ($sum != 0) && (($sum/10) > 0)) {
			return true;
		} else {
			return false;
		}
	}
		public function redirecionaPedido($mensagem, $tipo='message',$email=1) {
		$url_pedido = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=orders&layout=details&order_number='.$this->order_number);
				// formata a mensagem
				$msg = "TRANSA&Ccedil;&Atilde;O Pagseguro <b>N. ".$this->transactionCode."</b><br /><hr/>".$mensagem;
		if ($email) {
			$msg .= "<br />Verifique em seu <b>e-mail</b> o extrato desta transação.";
		}
				$app = JFactory::getApplication();
			$app->redirect($url_pedido, $msg, $tipo);
		} 
	/**
	* Calcula as parcelas do crédito
	*/
	public function calculaParcelasCredito( $method, $order_total, $id, $numero_parcelas=null ) {
		$conteudo = "<div id='".$id."' class='div_parcelas div_pagamentos'>";
		/*
		$parcelas_juros = 1;
		$paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
		if (is_null($numero_parcelas)) {
			$limite_sem_juros = $method->max_parcela_sem_juros;
		} else {
			$limite_sem_juros = $numero_parcelas;
		}
		if (!empty($limite_sem_juros)) {
			for ($i=1; $i<=$limite_sem_juros; $i++) {
				$valor_parcela = $order_total / $i;
				$parcelas_juros ++;
				// caso o valor da parcela seja menor do que o permitido, não a exibe
				if (($valor_parcela < $method->valor_minimo or $valor_parcela < 5) and $i != 1) {
					continue;
				}
				//$valor_formatado_credito = 'R$ '.number_format($valor_parcela,2,',','.');
				$valor_formatado_credito = $paymentCurrency->priceDisplay($valor_parcela,$paymentCurrency->payment_currency);
			
				// novo tipo de formatação de preço
				// $conteudo .= '<div class="field_visa"><label><input type="radio" class="radiofield" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco">'.$valor_formatado_credito.' sem juros</span></label></div>';
				$conteudo .= '<div class="field_visa"><label><input type="radio" class="radiofield" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco"></span></label></div>';
				if ($method->max_parcela_com_juros == $i) {
					break;
				}
			}
		}
		if (is_null($numero_parcelas)) {
			$limite_parcelamento = $method->max_parcela_com_juros;
		} else {
			$limite_parcelamento = $numero_parcelas;
		}
		
		$asterisco = false;
		for($i=$parcelas_juros; $i<=$limite_parcelamento; $i++) {
			// verifica se o juros será para o emissor ou para o comprador
			// caso o valor da parcela seja menor do que o permitransactionCodeo, não a exibe
			if (($valor_parcela < $method->valor_minimo or $valor_parcela < 5) and $i != 1) {
				continue;
			}
			
			if ($i==1) {
				$valor_parcela  = $order_total * (1+$method->taxa_credito); // calcula o valor da parcela
			} else {
				$valor_parcela = $this->calculaParcelaPRICE($order_total,$i,$method->taxa_parcelado);
				$asterisco = true;
			}
			$valor_formatado_credito = $paymentCurrency->priceDisplay($valor_parcela,$paymentCurrency->payment_currency);
			
			// $conteudo .= '<div class="field_visa"><label><input type="radio" class="radiofield" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco">'.$valor_formatado_credito.' * </span></label></div>';
			$conteudo .= '<div class="field_visa"><label><input type="radio" class="radiofield" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco"></span></label></div>';
			if ($limite_parcelamento == $i) {
				break;
			}
		}
		*/
	
		$parcelas_juros = 1;
		$paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
		if (is_null($numero_parcelas)) {
			$limite_sem_juros = $method->max_parcela_sem_juros;
		} else {
			$limite_sem_juros = $numero_parcelas;
		}
		$conteudo .= "<select name='parcelamento' class='select_parcelamento'>";
		if (!empty($limite_sem_juros)) {
			for ($i=1; $i<=$limite_sem_juros; $i++) {
				$valor_parcela = $order_total / $i;
				$parcelas_juros ++;
				// caso o valor da parcela seja menor do que o permitido, não a exibe
				if (($valor_parcela < $method->valor_minimo or $valor_parcela < 5) and $i != 1) {
					continue;
				}
				//$valor_formatado_credito = 'R$ '.number_format($valor_parcela,2,',','.');
				$valor_formatado_credito = $paymentCurrency->priceDisplay($valor_parcela,$paymentCurrency->payment_currency);
			
				// novo tipo de formatação de preço
				// $conteudo .= '<div class="field_visa"><label><input type="radio" class="radiofield" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco">'.$valor_formatado_credito.' sem juros</span></label></div>';
				$conteudo .= '<option value="'.$i.'" id="p0'.$i.'">'.$i.' x </option>';
				if ($method->max_parcela_com_juros == $i) {
					break;
				}
			}
		}
		if (is_null($numero_parcelas)) {
			$limite_parcelamento = $method->max_parcela_com_juros;
		} else {
			$limite_parcelamento = $numero_parcelas;
		}
		
		$asterisco = false;
		for($i=$parcelas_juros; $i<=$limite_parcelamento; $i++) {
			// verifica se o juros será para o emissor ou para o comprador
			// caso o valor da parcela seja menor do que o permitransactionCodeo, não a exibe
			if (($valor_parcela < $method->valor_minimo or $valor_parcela < 5) and $i != 1) {
				continue;
			}
			
			if ($i==1) {
				$valor_parcela  = $order_total * (1+$method->taxa_credito); // calcula o valor da parcela
			} else {
				$valor_parcela = $this->calculaParcelaPRICE($order_total,$i,$method->taxa_parcelado);
				$asterisco = true;
			}
			$valor_formatado_credito = $paymentCurrency->priceDisplay($valor_parcela,$paymentCurrency->payment_currency);
			
			// $conteudo .= '<div class="field_visa"><label><input type="radio" class="radiofield" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco">'.$valor_formatado_credito.' * </span></label></div>';
			// $conteudo .= '<div class="field_visa"><label><input type="radio" class="radiofield" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco"></span></label></div>';
			$conteudo .= '<option value="'.$i.'" id="p0'.$i.'">'.$i.' x </option>';
			if ($limite_parcelamento == $i) {
				break;
			}
		}
		$conteudo .= "</select>";
		if ($asterisco) {
			$conteudo .= "<div>* Valores sujeitos à alteração ao efetuar o pagamento via Cartão (".$method->taxa_parcelado."% a.m.).</div>";      
		}
		$conteudo .= '</div>';    
		return $conteudo;
	}
	
	/**
	* Calcula as parcelas do crédito
	*/
	public function calculaParcelasDebitoBoleto( $method, $order_total, $id, $numero_parcelas=1 ) {
		$conteudo = "<div id='".$id."' class=''>";
		$paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
		$valor_formatado_debito = $paymentCurrency->priceDisplay($order_total,$paymentCurrency->payment_currency);
		$conteudo .= '<div class="field_visa">
						<label><span><b>Valor</b>:</span> <span id="p01">1 x </span>&nbsp;<span class="asterisco">'.$valor_formatado_debito.'</span></label>    
							</div>';
		return $conteudo;
	}

	public function calculaParcelaPRICE($Valor, $Parcelas, $Juros) {
		$Juros = bcdiv($Juros,100,15);
		$E=1.0;
		$cont=1.0;
		for($k=1;$k<=$Parcelas;$k++) {
			$cont= bcmul($cont,bcadd($Juros,1,15),15);
			$E=bcadd($E,$cont,15);
		}
		$E=bcsub($E,$cont,15);
		$Valor = bcmul($Valor,$cont,15);
		return round(bcdiv($Valor,$E,15),2);
	}
	
	// recupera o transactionCode com base no numero do pedido
	public function recuperaCodigoPagsegurotransparente($order_number) {
		$db = JFactory::getDBO();
		$query = 'SELECT ' . $this->_tablename . '.`transactionCode` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";
		$db->setQuery($query);
		$this->transactionCode =  $db->loadResult();    
	} 

	// reformata o valor que vem do servidor da Pagsegurotransparente
	public function reformataValor($valor) {
			$valor = substr($valor,0,strlen($valor)-2).'.'.substr($valor,-2);
			return $valor;
	}

	public function formataData($valor,$formato="d/m/Y") {
		if (!empty($valor) and $valor != 'null') {
		return date($formato, strtotime($valor));
		} else {
			return '';
		}
	}

	public function formataTelefone($telefone) {
		return str_replace(array('(',')',' ','-'),array('','','',''),$telefone);
	}

	public function formataCPF($cpf) {
		return str_replace(array('.','-'),array('',''),$cpf);
	}

	public function formataCNPJ($cpf) {
		return str_replace(array('.','-','/'),array('','',''),$cpf);
	}

	public function trataRetornoSucessoPagseguro($retorno, $method) {
		// código da transação
		$this->transactionCode  = $retorno['code'];
		$this->order_number   = $retorno['reference'];
	
			// recupera os status de constants
		$status_pagamento   = $retorno['status'];
		$status_pedido      = $retorno['status_pedido'];
		$mensagem         = $retorno['descriptionStatus'];
		
		$url_redirecionar = '';
		if (trim($retorno['paymentLink']) != '') {
			$url_redirecionar   = $retorno['paymentLink'];
		}
		$tipo_pagamento     = JRequest::getVar('tipo_pagamento');
		$forma_pagamento    = JRequest::getVar('forma_pagamento');
				$parcela_selecionada  = JRequest::getVar('parcela_selecionada');
		$this->gravaDadosRetorno($method, $status_pagamento, $mensagem, $url_redirecionar, $tipo_pagamento, $forma_pagamento, $parcela_selecionada);
		$this->trocaStatusPedidoPagseguroAPI($this->transactionCode, $status_pedido, $mensagem, $method, $this->order_number);
	}

	public function trataRetornoFalhaPagseguro($retorno, $method) {     
		$msgs_erro = array();
		foreach ($retorno as $codigo => $mensagem) {
			if ($method->modo_teste) {
				$msgs_erro[] = 'Erro: <b>'.$codigo."</b> - ".$mensagem."";
			} else {
				$msgs_erro[] = 'Erro: <b>'.$codigo."</b> - ".$this->traduzErro($codigo)."";         
			}
		}
		// recupera os status de constants
		//$this->gravaDadosRetorno($method, $status_pagamento, $mensagem.$msgs_erros);
		//$this->trocaStatusPedidoPagseguroAPI($this->transactionCode, $status_pagamento, $mensagem, $method, $this->order_number);
		return $msgs_erro;
	}

	public function trocaStatusPedidoPagseguroAPI($transactionCode, $status, $mensagem, $method, $order_number) {
		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
		// recupera as informações do pagamento
		$db = JFactory::getDBO();
		$query = 'SELECT *
					FROM `' . $this->_tablename . '`
					WHERE order_number = "'.$order_number.'"';
		$db->setQuery($query);
		$pagamento        = $db->loadObjectList();
		$type_transaction     = $pagamento[0]->type_transaction;
		// $forma_pagamento     = $pagamento[0]->forma_pagamento;
		$payment_order_total  = $pagamento[0]->payment_order_total;
		$timestamp = date('Y-m-d').'T'.date('H:i:s');
		$log = $timestamp.'|'.$transactionCode.'|'.$mensagem.'|'.$type_transaction.'|'.$payment_order_total;
		// notificação do pagamento realizado
		$notificacao = "<b>".JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_TRANSACTION')." </b>\n";
		$notificacao .= JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_CODIGO_PAGSEGUROTRANSPARENTE')." ".$transactionCode."\n";
		$notificacao .= JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_PEDIDO')." ".$order_number."\n";
		$notificacao .= "<hr />";
		$notificacao .= JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_STATUS')." <b>".(($status==$method->transacao_aprovada)?JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_PAID'):JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_NOTPAID'))."</b>\n";
		$notificacao .= JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_TYPE_TRANSACTION')." <b>".$type_transaction."</b>\n";
		$notificacao .= JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_TYPE_MESSAGE')." <b>".$mensagem." </b>\n";
		$notificacao .= JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_ORDER_TOTAL')." <b>R$ ".number_format($payment_order_total,2,',','.')."</b> \n";
		$notificacao .= "\n";
		$notificacao .= JText::_('VMPAYMENT_PAGSEGUROTRANSPARENTE_NOTIFY_AUTHENTICATE')."<a href='http://www.pagseguro.com.br'>Pagseguro</a>";
		if ($virtuemart_order_id) {
			// send the email only if payment has been accepted
			if (!class_exists('VirtueMartModelOrders'))
				require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
			$modelOrder = new VirtueMartModelOrders();
			$orderitems = $modelOrder->getOrder($virtuemart_order_id);
			$nb_history = count($orderitems['history']);
			$order = array();     
			$order['order_status']      	= $status;
			$order['virtuemart_order_id']   = $virtuemart_order_id;
			$order['comments']        		= $notificacao;
			$order['customer_notified']   	= 1;
			$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
			if ($nb_history == 1) {
				if (!class_exists('shopFunctionsF'))
					require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
				$this->logInfo('Notification, sentOrderConfirmedEmail ' . $order_number. ' '. $order['order_status'], 'message');
			}
		}     
		
		// $cart = VirtueMartCart::getCart();
		// $cart->emptyCart();    
	}
	private function getStatusPagamentoPagseguroRetorno($method, $codigo) {
		$status_pagamento    = array();
		$status_pagamento[1] = array($method->transacao_em_andamento,'Aguardando a confirmação de pagamento');
		$status_pagamento[2] = array($method->transacao_em_analise,'Aguardando aprovação de risco');
		$status_pagamento[3] = array($method->transacao_aprovada,'Transação Paga');
		$status_pagamento[4] = array($method->transacao_concluida,'Transação concluída');
		$status_pagamento[5] = array($method->transacao_disputa,'Transação em disputa');
		$status_pagamento[6] = array($method->transacao_devolvida,'Transação devolvida');
		$status_pagamento[7] = array($method->transacao_cancelada,'Transação Cancelada');
		if (isset($status_pagamento[$codigo])) {
			return $status_pagamento[$codigo];
		} else {
			return null;
		}
	}

	private function traduzErro($codigo) {
		$erros = array (
			"11001"=>"O campo e-mail de configuração é obrigatório.",
			"11002"=>"Tamanho do e-mail de configuração inválido",
			"11003"=>"E-mail de configuração inválido.",
			"11004"=>"A moeda é obritatória.",
			"11005"=>"Moeda inválida",
			"11006"=>"Tamanho do campo redirectURL inválido",
			"11007"=>"Valor inválido para o campo redirectURL",
			"11008"=>"Tamanho do vampo referência inválido",
			"11009"=>"Tamanho do campo e-mail inválido",
			"11010"=>"Valor inválido para o e-mail",
			"11011"=>"Tamanho do nome inválido",
			"11012"=>"Valor inválido para o nome",
			"11013"=>"Valor do código de área inválido",
			"11014"=>"Valor inválido para o telefone",
			"11015"=>"Tipo de entrega é obrigatório.",
			"11016"=>"Valor inválido para o tipo de entrega",
			"11017"=>"Valor do cep inválido",
			"11018"=>"Endereço da rua inválido",
			"11019"=>"Tamanho inválido para o número do endereço",
			"11020"=>"Tamanho inválido para o complemento do endereço",
			"11021"=>"Tamanho inválido para o bairro",
			"11022"=>"Tamanho inválido para a cidade",
			"11023"=>"Valor inválido para o estado, deve ser no formato SIGLA, ex. 'SP'",
			"11024"=>"Quantidade de itens inválida.",
			"11025"=>"O Item id é obrigatório.",
			"11026"=>"A quantidade do item é obrigatória.",
			"11027"=>"Número inválido para a quantidade do item",
			"11028"=>"Total do item é obrigatório, ex. 10.00",
			"11029"=>"Formato do total do item inválido.",
			"11030"=>"Número inválido para o total do item",
			"11031"=>"Formato inválido para o total de entrega",
			"11032"=>"Número inválido para o total de entrega",
			"11033"=>"Descrição do item é obrigatória.",
			"11034"=>"Tamanho inválido para a descrição do item",
			"11035"=>"Peso inválido para o item",
			"11036"=>"Formato inválido para o valor extra",
			"11037"=>"Número inválido para o valor extra",
			"11038"=>"Cliente inválido para o checkout, favor cliente verificar o status da conta no PagSeguro.",
			"11039"=>"Requisição de XML malformada.",
			"11040"=>"Formato do campo idade inválido",
			"11041"=>"Número inválido para o campo idade",
			"11042"=>"Formato do campo maxUses inválido",
			"11043"=>"Número inválido para o campo maxUses.",
			"11044"=>"A data inicial é obrigatória.",
			"11045"=>"A data inicial deve ser menor do que o limite permitido.",
			"11046"=>"A data inicial deve maior do que 6 meses.",
			"11047"=>"A data inicial deve ser menor ou igual à data final.",
			"11048"=>"O intervalor de busca deve ser menor ou igual à 30 dias.",
			"11049"=>"A data final deve ser menor do que a data permitida.",
			"11050"=>"Formato da data inicial inválido, use o formato 'yyyy-MM-ddTHH:mm' (ex. 2010-01-27T17:25).",
			"11051"=>"Formato da data final inválido, use o formato 'yyyy-MM-ddTHH:mm' (ex. 2010-01-27T17:25).",
			"11052"=>"Valor inválido para a página.",
			"11053"=>"Valor inválido para o total de resultados da página (deve ser entre 1 e 1000).",
			"11157"=>"CPF inválido",
			"53004"=>"Quantidade de itens inválida.",
			"53005"=>"A moeda é obrigatória.",
			"53006"=>"Moeda informada é inválida",
			"53007"=>"Tamanho da referência inválido",
			"53008"=>"Tamanho da url de notificação inválido.",
			"53009"=>"Url de notificação inválido.",
			"53010"=>"O e-mail do cliente é obrigatório.",
			"53011"=>"Tamanho inválido para o e-mail do cliente",
			"53012"=>"Valor inválido para o e-mail do cliente",
			"53013"=>"O nome do cliente é obrigatório.",
			"53014"=>"Tamanho inválido para o nome do cliente",
			"53015"=>"Valor inválido para o nome do cliente",
			"53017"=>"Valor inválido para o cpf",
			"53018"=>"Código de área é obrigatório.",
			"53019"=>"Valor inválido para o código de área",
			"53020"=>"O telefone é obrigatório.",
			"53021"=>"Valor inválido para o telefone",
			"53022"=>"O cep de entrega é obrigatório.",
			"53023"=>"Valor inválido para o cep de entrega.",
			"53024"=>"Endereço de entrega é obrigatório.",
			"53025"=>"Valor inválido para o endereço de entrega.",
			"53026"=>"O número do endereço de entrega é obrigatório.",
			"53027"=>"Tamanho inválido para o número do endereço de entrega",
			"53028"=>"Tamanho inválido para o complemento do endereço de entrega",
			"53029"=>"O bairro é obrigatório.",
			"53030"=>"Valor inválido para o bairro do endereço de entrega",
			"53031"=>"A cidade do endereço de entrega é obrigatória.",
			"53032"=>"Tamanho inválido para a cidade do endereço de entrega",
			"53033"=>"O estado do endereço de entrega é obrigatório.",
			"53034"=>"Valor inválido para o estado do endereço de entrega.",
			"53035"=>"O país do endereço de entrega é obrigatório.",
			"53036"=>"Tamanho inválido para o país do endereço de entrega",
			"53037"=>"O token do cartão de crédito é obrigatório.",
			"53038"=>"A quantidade de parcelas é obrigatória.",
			"53039"=>"Tamanho inválido para a quantidade de parcelas",
			"53040"=>"O valor da parcela é obrigatório.",
			"53041"=>"Tamanho inválido do valor da parcela",
			"53042"=>"O titular do cartão é obrigatório.",
			"53043"=>"Tamanho inválido para o campo titular do cartão.",
			"53044"=>"Valor inválido para o campo titular do cartão.",
			"53045"=>"O cpf do titular do cartão é obrigatório.",
			"53046"=>"Valor inválido para o cpf do titular do cartão.",
			"53047"=>"A data de nascimento do titular do cartão é obrigatória.",
			"53048"=>"Valor inválido para a data de nascimento do titular do cartão",
			"53049"=>"O código de área do titular do cartão é obrigatório.",
			"53050"=>"Valor inválido para o código de área do titular do cartão",
			"53051"=>"O telefone do titular do cartão é obrigatório.",
			"53052"=>"Valor inválido para o telefone do titular do cartão.",
			"53053"=>"O cep de cobrança é obrigatório.",
			"53054"=>"Valor inválido para o cep de cobrança.",
			"53055"=>"Endereço de cobrança é obrigatório.",
			"53056"=>"Valor inválido para o endereço de cobrança.",
			"53057"=>"O número do endereço de cobrança é obrigatório.",
			"53058"=>"Tamanho inválido para o número do endereço de cobrança",
			"53059"=>"Tamanho inválido para o complemento do endereço de cobrança",
			"53060"=>"O bairro do endereço de cobrança é obrigatório.",
			"53061"=>"Valor inválido para o bairro do endereço de cobrança",
			"53062"=>"A cidade do endereço de cobrança é obrigatória.",
			"53063"=>"Tamanho inválido para a cidade do endereço de cobrança",
			"53064"=>"Tamanho inválido para o país do endereço de cobrança",
			"53065"=>"O estado do endereço de cobrança é obrigatório.",
			"53066"=>"Valor inválido para o estado do endereço de cobrança.",
			"53067"=>"O país do endereço de cobrança é obrigatório.",
			"53068"=>"Tamanho inválido para o e-mail do lojista",
			"53069"=>"Valor inválido para o e-mail do lojista",
			"53070"=>"O item id é obrigatório",
			"53071"=>"Tamanho inválido para o ID do item",
			"53072"=>"Descrição do item é obrigatória.",
			"53073"=>"Tamanho inválido para a descrição do item",
			"53074"=>"A quantidade do item é obrigatória.",
			"53075"=>"Valor inválido para a quantidade do item",
			"53076"=>"Formato inválido para a quantidade do item",
			"53077"=>"O valor do item é obrigatório.",
			"53078"=>"Formato inválido para a quantidade do item",
			"53079"=>"O valor do item é inválido.",
			"53081"=>"O cliente tem relação com o lojista.",
			"53084"=>"Cliente inválido, favor verificar o status da conta do lojista e checar se é uma conta de vendedor.",
			"53085"=>"Método de pagamento indisponível.",
			"53086"=>"Total do carrinho inválido",
			"53087"=>"Número do Cartão de crédito inválido.",
			"53091"=>"Hash do cartão de crédito inválido.",
			"53092"=>"Bandeira do cartão de crédito não-aceita.",
			"53095"=>"Formato inválido para o tipo de entrega",
			"53096"=>"Formato inválido para o custo de entrega",
			"53097"=>"Custo de entrega inválido",
			"53098"=>"Valor total do carrinho está negativo",
			"53099"=>"Formato do valor extra inválido. Deve ser no formato -/+xx.xx",
			"53101"=>"Modo de pagamento inválido.",
			"53102"=>"Método de pagamento inválido, são aceitos cartão de crédito, boleto e transferência.",
			"53104"=>"Custo de entrega foi enviado, mas o endereço de entrega deve estar completo.",
			"53105"=>"Dados do cliente enviados, mas o e-mail é obrigatório.",
			"53106"=>"Titular do cartão de crédito incompleto.",
			"53109"=>"O endereço de entrega foi enviado, mas o e-mail do cliente é obrigatório.",
			"53110"=>"O banco para transferência é obrigatório.",
			"53111"=>"Banco para transferência informato não é aceito.",
			"53115"=>"Valor inválido para data de nascimento do cliente",
			"53122"=>"Domínio do e-mail do cliente inválido, deve obrigatoriamente ser um email de @sandbox.pagseguro.com.br",
			"53140"=>"Valor inválido da quantidade de parcelamento. O valor deve ser maior do que zero.",
			"53141"=>"O cadastro do cliente está bloqueado.",
			"53142"=>"Token do cartão de crédito inválido.",
			"400" => "Requisição com parâmetros obrigatórios vazios ou inválidos",
			"401" => "Falha na autenticação ou sem acesso para usar o serviço",     
			"405" => "Método não permitido, o serviço suporta apenas POST",
			"415" => "Content-Type não suportado",
			"500" => "Erro fatal na aplicação, executar a solicitação mais tarde",
			"503" => "Serviço está indisponível"
		);
		if (isset($erros[$codigo])) {
			$erro_traduzido = $erros[$codigo];
		} else {
			$erro_traduzido = "Erro interno";
		}
		return $erro_traduzido;
	}
	function setCartPrices (VirtueMartCart $cart, &$cart_prices, $method, $progressive=true) {
		if ($method->modo_calculo_desconto == '2') {
				return parent::setCartPrices($cart, $cart_prices, $method, false);
		} else {
				return parent::setCartPrices($cart, $cart_prices, $method, true);
		}
	}
}