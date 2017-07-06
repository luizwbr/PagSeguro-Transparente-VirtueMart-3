<?php
defined('_JEXEC') or die();

/**
 *
 * @package	VirtueMart
 * @subpackage Plugins  - Elements
 * @author Valérie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2011 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: $
 */
/*
 * This class is used by VirtueMart Payment or Shipment Plugins
 * which uses JParameter
 * So It should be an extension of JElement
 * Those plugins cannot be configured througth the Plugin Manager anyway.
 */
class JFormFieldVmAbouttransparente extends JFormField {

    /**
     * Element name
     * @access	protected
     * @var		string
     */
    public $type = 'Abouttransparente';

    protected function getInput() {
        $path = $this->getAttribute('path');
    
        $doc = JFactory::getDocument();
		$html = '<div style="float:left">
				<img src="'.JURI::root().$path.DS.'checkout_transparente_pagseguro.png" border="0"/><br />
				<h1> Pagseguro Transparente - VirtueMart 3.0 </h1>
				<div>Contato: <a href="http://weber.eti.br">Weber TI</a> </div>
		</div>';
        return $html;
    }

}