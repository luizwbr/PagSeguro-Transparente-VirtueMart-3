/*(function() {

  jQuery(function() {
    jQuery('.demo .numbers li').wrapInner('<a href="#"></a>').click(function(e) {
      e.preventDefault();
      return jQuery('#card_number').val(jQuery(this).text()).trigger('input');
    });
    jQuery('.vertical.maestro').hide().css({
      opacity: 0
    });
    return jQuery('#card_number').validateCreditCard(function(result) {
		var cartoes = new Array();
		cartoes['mastercard'] = 'master';
		cartoes['visa'] 	= 'visa';
		cartoes['discover'] = 'discover';
		cartoes['hipercard']= 'hipercard';
		cartoes['diners'] 	= 'diners';
		cartoes['amex'] 	= 'amex';

		if (!(result.card_type != null)) {
			jQuery('#card_number').removeClass('valid');
			//jQuery('input[name=tipo_pgto]').attr('checked',false);
			//jQuery('input[name=tipo_pgto]').attr('disabled',true);
			jQuery('.div_parcelas').hide();
			return;
		}

		if (result.card_type.name != '') {
			var id = '#tipo_'+cartoes[result.card_type.name];
			//jQuery(id).attr('checked',true);
			//jQuery(id).removeAttr('disabled');
			show_parcelas(cartoes[result.card_type.name]);
		}

		if (result.length_valid && result.luhn_valid) {
			return jQuery('#card_number').addClass('valid');
		} else {
			return jQuery('#card_number').removeClass('valid');
		}
    });
  });

}).call(this);
*/