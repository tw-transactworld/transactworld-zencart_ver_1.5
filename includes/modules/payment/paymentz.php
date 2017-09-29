<?php
/**
 * @package money order payment module
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: transactworld.php 15420 2010-02-04 21:27:05Z drbyte $
 */

  class paymentz extends base {
    var $code, $title, $description, $enabled;
	
// class constructor
    function paymentz() {
		global $order,$messageStack;
		
		$this->code = 'paymentz';
		$this->codeVersion = '1.0.1';
		$this->title = MODULE_PAYMENT_PAYMENTZ_TEXT_TITLE;
		if (IS_ADMIN_FLAG === true && (MODULE_PAYMENT_PAYMENTZ_MERCHANT_ID == 'TransactWorldMerchantID' || MODULE_PAYMENT_PAYMENTZ_MERCHANT_ID == '')) $this->title .= '<span class="alert"> (not configured - needs MerchantID)</span>';
		$this->description = MODULE_PAYMENT_PAYMENTZ_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_PAYMENT_PAYMENTZ_SORT_ORDER;
		$this->enabled = ((MODULE_PAYMENT_PAYMENTZ_STATUS == 'True') ? true : false);
		
		if ((int)MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID > 0) {
		$this->order_status =MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID;
		}
		
		if (is_object($order)) $this->update_status();
		
		if (MODULE_PAYMENT_PAYMENTZ_MODE == 'Test') {
           // $this->form_action_url = 'https://staging.paymentz.com/transaction/PayProcessController';
           // $this->form_action_url = MODULE_PAYMENT_PAYMENTZ_TEST_URL;
            $this->form_action_url = MODULE_PAYMENT_PAYMENTZ_TEST_URL;
			} else {
		//$this->form_action_url = 'https://secure.paymentz.com/icici/servlet/PayProcessController';
		//$this->form_action_url = MODULE_PAYMENT_PAYMENTZ_LIVE_URL;
		$this->form_action_url = MODULE_PAYMENT_PAYMENTZ_LIVE_URL;
		}
   
   		if (PROJECT_VERSION_MAJOR != '1' && substr(PROJECT_VERSION_MINOR, 0, 3) != '0.1') $this->enabled = false;
		
		$this->email_footer = MODULE_PAYMENT_PAYMENTZ_TEXT_EMAIL_FOOTER;
  
    }

// class methods
    function update_status() {
      global $order, $db;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYMENTZ_ZONE > 0) ) {
        $check_flag = false;
        $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYMENTZ_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while (!$check->EOF) {
          if ($check->fields['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        $check->MoveNext();
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
	  return false;
    }

    function process_button() 
	{
	global $order, $currencies,$customer_id, $MerchantId, $totype, $amount, $Url, $WorkingKey, $Checksum, $db,$zone_code;
	
	$MerchantId = MODULE_PAYMENT_PAYMENTZ_MERCHANT_ID;
	$totype = MODULE_PAYMENT_PAYMENTZ_PARTNER_NAME;
	$partenerid = MODULE_PAYMENT_PAYMENTZ_PARTNER_ID;
	$ipaddr = MODULE_PAYMENT_PAYMENTZ_IPADDR;
	$amount = $order->info['total']* $currencies->get_value('INR');
	$currency = $order->info['currency'];
	$customamount = number_format(($order->info['total'] * $currencies->get_value($currency)), $currencies->get_decimal_places($currency));

	
	for ($i=0; $i<sizeof($order->products); $i++) 
	{
		$quantity = $order->products[$i]['qty'];
		$products = $order->products[$i]['name'];
        $gg .= $quantity."-".$products." ";
		//$gg .= " | " . $quantity . " - " . $products . " ";
		
		if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) )
		{
		  for ($j=0; $j<sizeof($order->products[$i]['attributes']); $j++) 
		  {
			$attrib = $order->products[$i]['attributes'][$j]['option'];
			$attribs = $order->products[$i]['attributes'][$j]['value'];
			
			$gg .=  "($attrib : $attribs)";
		  }
		}
	}
	
	$orderdescription = $gg;
	
	$OrderId = $_SESSION['customer_id'] . '-' . date('Ymdhis');
	$description = $OrderId;
	$Url = zen_href_link(FILENAME_CHECKOUT_PROCESS,'','SSL',true,false);
	$pattern='http://www\.';
    if(!(preg_match($pattern,$Url,$reg)))
   preg_replace('http://', $pattern, $Url);
	
	$WorkingKey = MODULE_PAYMENT_PAYMENTZ_WORKING_KEY;
	
	$str = "$MerchantId|$totype|$customamount|$description|$Url|$WorkingKey";
        $checksum = md5(trim($this->merchant_id) . "|" . trim($this->totype) . "|" . trim($order->order_total) . "|" . trim($order_id) . "|" . trim($redirecturl) . "|" . trim($this->working_key));
	$Checksum = md5($str);
	
	$zone_code = $this->get_zone_code($order->billing['state']);
	
	
	  $process_button_string = zen_draw_hidden_field('toid', $MerchantId) .   
							   zen_draw_hidden_field('totype', $totype) .
		  					   zen_draw_hidden_field('partenerid', $partenerid) .
							   zen_draw_hidden_field('ipaddr', $ipaddr) .
					           zen_draw_hidden_field('key', $WorkingKey) .
					           zen_draw_hidden_field('amount', $customamount) .
                               zen_draw_hidden_field('TMPL_AMOUNT', $customamount) .
							   zen_draw_hidden_field('description', $OrderId) .
							   zen_draw_hidden_field('orderdescription', $orderdescription) .

							   zen_draw_hidden_field('TMPL_CURRENCY', $order->info['currency']) .
							   zen_draw_hidden_field('fromtype', 'icicicredit').
				               zen_draw_hidden_field('TMPL_street', $order->billing['street_address']) .
							   zen_draw_hidden_field('TMPL_city', $order->billing['city']) .
							   zen_draw_hidden_field('TMPL_state', $zone_code) .
							   zen_draw_hidden_field('TMPL_zip', $order->billing['postcode']) .
							   zen_draw_hidden_field('TMPL_telnocc', '011').
							   zen_draw_hidden_field('TMPL_telno', $order->customer['telephone']).
						       zen_draw_hidden_field('TMPL_' . $order->billing['country']['iso_code_2'], 'selected') .
							   zen_draw_hidden_field('TMPL_emailaddr', $order->customer['email_address']) .
							   zen_draw_hidden_field('checksum',$Checksum) .
							   zen_draw_hidden_field('redirecturl',$Url). 
		  					   zen_draw_hidden_field('pctype',"1_1|1_2").					
	                           zen_draw_hidden_field('reservedField1',"").					
	                           zen_draw_hidden_field('reservedField2',"").
	                           zen_draw_hidden_field('paymenttype',"").					
	                           zen_draw_hidden_field('cardtype',"");   
	 
						   
	  return $process_button_string;
	// return zen_draw_hidden_field('toid', '123456');
}
	
	function get_zone_code($zone_name){
		global $db;
		  $zone_query = $db->Execute("select zone_code from " . TABLE_ZONES . " where  zone_name = '" . $zone_name . "'");
         return $zone_code = $zone_query->fields['zone_code'];
	}
function before_process() {

	global $_REQUEST,$WorkingKey,$sum;
	
 $key = MODULE_PAYMENT_PAYMENTZ_WORKING_KEY;


     $trackingid="null";//$_REQUEST['trackingid'];
	if($_REQUEST['trackingid'] !=null && $_REQUEST['trackingid'] != "")
	{
	  $trackingid=$_REQUEST['trackingid'];	
	}
      $amount = $_REQUEST['amount'];
	 $desc = $_REQUEST['desc'];
     $newchecksum = $_REQUEST['checksum'];
	 $status = $_REQUEST['status'];
	 $str = "$trackingid|$desc|$amount|$status|$key";

	$sum = md5($str);
	if($sum == $newchecksum)
		$Checksum = 'true' ;
	else
		$Checksum = 'false';

	if($Checksum != 'true'){

	zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'credit_class_error=' . urlencode(MODULE_PAYMENT_PAYMENTZ_ALERT_ERROR_MESSAGE), 'SSL',true, false));
	}
	
	if($Checksum =='true' && $status == 'N'){
	
	zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'credit_class_error=' . urlencode(MODULE_PAYMENT_PAYMENTZ_TEXT_ERROR_MESSAGE), 'SSL',true, false));
	}

}

    function after_process() {
      return false;
    }

  function get_error() {
    return false;
  }

    function check() {
      global $db;
      if (!isset($this->_check)) {
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYMENTZ_STATUS'");
        $this->_check = $check_query->RecordCount();
      }
      return $this->_check;

    }

    function install() {
      global $db, $messageStack;
      if (defined('MODULE_PAYMENT_PAYMENTZ_STATUS')) {
        $messageStack->add_session('transactworld module already installed.', 'error');
        zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=paymentz','NONSSL'));
        return 'failed';
      }
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable TransactWorld Module', 'MODULE_PAYMENT_PAYMENTZ_STATUS', 'True', 'Do you want to accept TransactWorld payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Id', 'MODULE_PAYMENT_PAYMENTZ_MERCHANT_ID', 'TransactWorldMerchantID', 'The Merchant Id to use for the TransactWorld service', '6', '1', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Partner Name [Totype]', 'MODULE_PAYMENT_PAYMENTZ_PARTNER_NAME', '', 'Enter Your Partner ID [Totype]', '6', '2', now())");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Partner Id', 'MODULE_PAYMENT_PAYMENTZ_PARTNER_ID', '', 'Enter Your Partner ID', '6', '2', now())");
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ip address', 'MODULE_PAYMENT_PAYMENTZ_IPADDR', '', 'Enter Your Ip Address', '6', '2', now())");
		
		
		
		
		
		
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('WorkingKey', 'MODULE_PAYMENT_PAYMENTZ_WORKING_KEY', '', 'Put in the 32 bit alphanumeric key. To get this key, Login to your TransactWorld Merchant Account and click Settings -> Generate Key', '6', '2', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Test Url', 'MODULE_PAYMENT_PAYMENTZ_TEST_URL', '', 'Enter your test environment url', '6', '2', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Live Url', 'MODULE_PAYMENT_PAYMENTZ_LIVE_URL', '', 'Enter your live environment url', '6', '2', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function,date_added ) values ('Transaction Mode', 'MODULE_PAYMENT_PAYMENTZ_MODE', 'Test', 'Transaction mode used for processing orders', '6', '3', 'zen_cfg_select_option(array(\'Test\', \'Live\'), ', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYMENTZ_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '4', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID', '2', 'Set the status of orders made with this payment module to this value', '6', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYMENTZ_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '6', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    }

    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_PAYMENTZ_STATUS', 'MODULE_PAYMENT_PAYMENTZ_MERCHANT_ID', 'MODULE_PAYMENT_PAYMENTZ_PARTNER_NAME', 'MODULE_PAYMENT_PAYMENTZ_PARTNER_ID', 'MODULE_PAYMENT_PAYMENTZ_IPADDR', 'MODULE_PAYMENT_PAYMENTZ_WORKING_KEY', 'MODULE_PAYMENT_PAYMENTZ_TEST_URL', 'MODULE_PAYMENT_PAYMENTZ_LIVE_URL', 'MODULE_PAYMENT_PAYMENTZ_MODE', 'MODULE_PAYMENT_PAYMENTZ_SORT_ORDER', 'MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID', 'MODULE_PAYMENT_PAYMENTZ_ZONE');
    }
 }
