<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// Adapted from the like-named page handling with the following history:
// - Integrated COWAA v1.0
//
 //use the following difines if you want to turn off payment, products, shipping
 define('DISPLAY_PAYMENT', true);
 define('DISPLAY_SHIPPING', true);
 define('DISPLAY_PRODUCTS', true);
?>
<div class="centerColumn" id="orderStatus">
    <h1 id="orderHistoryHeading"><?php echo HEADING_TITLE; ?></h1>
<?php 
if ($messageStack->size('order_status') > 0) {
    echo $messageStack->output('order_status');
}

if (isset($order)) { 
?>
    <fieldset>
        <h2 id="orderHistoryDetailedOrder"><?php echo SUB_HEADING_TITLE . ORDER_HEADING_DIVIDER . sprintf(HEADING_ORDER_NUMBER, $_POST['order_id']); ?></h2>
        <div class="forward"><?php echo HEADING_ORDER_DATE . ' ' . zen_date_long($order->info['date_purchased']); ?></div>
<?php 
    if (DISPLAY_PRODUCTS) { 
?>
        <table border="0" width="100%" cellspacing="0" cellpadding="0" summary="Itemized listing of previous order, includes number ordered, items and prices">
            <tr class="tableHeading">
                <th scope="col" id="myAccountQuantity"><?php echo HEADING_QUANTITY; ?></th>
                <th scope="col" id="myAccountProducts"><?php echo HEADING_PRODUCTS; ?></th>
<?php
        if (count($order->info['tax_groups']) > 1) {
?>
                <th scope="col" id="myAccountTax"><?php echo HEADING_TAX; ?></th>
<?php
        }
?>
                <th scope="col" id="myAccountTotal"><?php echo HEADING_TOTAL; ?></th>
            </tr>
<?php
        for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
?>
            <tr>
                <td class="accountQuantityDisplay"><?php echo  $order->products[$i]['qty'] . QUANTITY_SUFFIX; ?></td>
                <td class="accountProductDisplay"><?php echo  $order->products[$i]['name'];

            if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) ) {
                echo '<ul id="orderAttribsList">';
                for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                    echo '<li>' . $order->products[$i]['attributes'][$j]['option'] . TEXT_OPTION_DIVIDER . nl2br($order->products[$i]['attributes'][$j]['value']) . '</li>';
                }
                echo '</ul>';
            }
?>
                </td>
<?php
            if (count($order->info['tax_groups']) > 1) {
?>
                <td class="accountTaxDisplay"><?php echo zen_display_tax_value($order->products[$i]['tax']) . '%' ?></td>
<?php
            }
?>
                <td class="accountTotalDisplay"><?php echo $currencies->format(zen_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ($order->products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->format(zen_add_tax($order->products[$i]['onetime_charges'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']) : '') ?></td>
            </tr>
<?php
        }
?>
        </table>
        <hr />
        <div id="orderTotals">
<?php
        for ($i=0, $n=count($order->totals); $i<$n; $i++) {
?>
            <div class="amount larger forward"><?php echo $order->totals[$i]['text'] ?></div>
            <div class="lineTitle larger forward"><?php echo $order->totals[$i]['title'] ?></div>
            <br class="clearBoth" />
<?php
        }
?>
        </div>
<?php 
    }

    // -----
    // Displays any downloads associated with the order ... disabled for now.
    //
    if (false && DOWNLOAD_ENABLED == 'true') {
        require $template->get_template_dir('tpl_modules_os_downloads.php',DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_os_downloads.php';
    }
    
    // -----
    // Display the order-status information.
    //
    if (count($statusArray) > 0) {
?>
        <table border="0" width="100%" cellspacing="0" cellpadding="0" id="myAccountOrdersStatus" summary="Table contains the date, order status and any comments regarding the order">
            <caption><h2 id="orderHistoryStatus"><?php echo HEADING_ORDER_HISTORY; ?></h2></caption>
            <tr class="tableHeading">
                <th scope="col" id="myAccountStatusDate"><?php echo TABLE_HEADING_STATUS_DATE; ?></th>
                <th scope="col" id="myAccountStatus"><?php echo TABLE_HEADING_STATUS_ORDER_STATUS; ?></th>
                <th scope="col" id="myAccountStatusComments"><?php echo TABLE_HEADING_STATUS_COMMENTS; ?></th>
            </tr>
<?php
        foreach ($statusArray as $statuses) {
?>
            <tr>
                <td><?php echo zen_date_short($statuses['date_added']); ?></td>
                <td><?php echo $statuses['orders_status_name']; ?></td>
                <td><?php echo (empty($statuses['comments']) ? '&nbsp;' : nl2br(zen_output_string_protected($statuses['comments']))); ?></td> 
            </tr>
<?php
        }
?>
        </table>
<?php 
    } 
?>
        <hr />
<?php 
    if (DISPLAY_SHIPPING) { 
?>
        <div id="myAccountShipInfo" class="floatingBox back">
<?php 
        if (zen_not_null($order->info['shipping_method'])) { 
?>
            <h4><?php echo HEADING_SHIPPING_METHOD; ?></h4>
            <div><?php echo $order->info['shipping_method']; ?></div>
<?php 
        } else { // temporary just remove these 4 lines ?>
            <div>WARNING: Missing Shipping Information</div>
<?php
        }
?>
        </div>
<?php 
    }

    if (DISPLAY_PAYMENT) { 
?>
        <div id="myAccountPaymentInfo" class="floatingBox forward">
            <h4><?php echo HEADING_PAYMENT_METHOD; ?></h4>
            <div><?php echo $order->info['payment_method']; ?></div>
        </div>
<?php 
    } 
?>
        <br class="clearBoth" />
    </fieldset>
<?php 
} 

echo zen_draw_form('order_status', zen_href_link(FILENAME_ORDER_STATUS, 'action=status', $request_type), 'post');
?>
    <fieldset>
        <legend><?php echo HEADING_TITLE; ?></legend>
        <p><?php echo TEXT_LOOKUP_INSTRUCTIONS; ?></p>

        <label class="inputLabel"><?php echo ENTRY_ORDER_NUMBER; ?></label>
        <?php echo zen_draw_input_field('order_id', $orderID, 'size="10" id="order_id"', 'number'); ?> 
        <br />
        
        <label class="inputLabel"><?php echo ENTRY_EMAIL; ?></label>
        <?php echo zen_draw_input_field('query_email_address', $query_email_address, 'size="35" id="query_email_address"', 'email'); ?> 
        <br />
        
        <?php echo zen_draw_input_field('should_be_empty', '', ' size="40" id="CUAS" style="visibility:hidden; display:none;" autocomplete="off"'); ?>

        <div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_CONTINUE, BUTTON_CONTINUE_ALT); ?></div>

    </fieldset></form>
</div>
