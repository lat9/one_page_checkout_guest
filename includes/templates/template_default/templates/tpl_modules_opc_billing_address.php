<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<!--bof billing-address block -->
    <div id="checkoutOneBillto" class="opc-base">
      <fieldset>
        <legend><?php echo TITLE_BILLING_ADDRESS; ?></legend>
<?php
$opc_address_values = $order->billing;
$opc_address_type = 'bill';
$opc_disable_address_change = $flagDisablePaymentAddressChange;
require $template->get_template_dir('tpl_modules_opc_address_block.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_opc_address_block.php';

if (!$flagDisablePaymentAddressChange) { 
?>
        <div class="buttonRow forward"><?php echo '<a href="' . zen_href_link(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
<?php 
} 
?>
      </fieldset>
      <div class="opc-overlay"></div>
    </div>
<!--eof billing-address block -->
