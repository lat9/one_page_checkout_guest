<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<!--bof billing-address block -->
    <div id="checkoutOneBillto">
      <fieldset>
        <legend><?php echo TITLE_BILLING_ADDRESS; ?></legend>
<?php
$opc_address_type = 'bill';
$opc_disable_address_change = $flagDisablePaymentAddressChange;
require $template->get_template_dir('tpl_modules_opc_address_block.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_opc_address_block.php';

if (!$flagDisablePaymentAddressChange) { 
?>
        <br class="clearBoth" />
        <div class="buttonRow opc-right">
            <span id="opc-bill-cancel" class="hiddenField"><?php echo zen_image_button(BUTTON_IMAGE_CANCEL, BUTTON_CANCEL_CHANGES_ALT); ?></span>
            <span id="opc-bill-save" class="hiddenField"><?php echo zen_image_button(BUTTON_IMAGE_UPDATE, BUTTON_SAVE_CHANGES_ALT); ?></span>
        </div>
<?php 
} 
?>
      </fieldset>
    </div>
<!--eof billing-address block -->
