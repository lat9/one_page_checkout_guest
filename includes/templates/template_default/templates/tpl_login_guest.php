<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('CHECKOUT_ONE_DISPLAY_ACCOUNT_BENEFITS')) {
    define('CHECKOUT_ONE_DISPLAY_ACCOUNT_BENEFITS', 'false');
}
?>
<div class="centerColumn" id="loginOpcDefault">
    <h1 id="loginDefaultHeading"><?php echo HEADING_TITLE; ?></h1>
<?php 
if ($messageStack->size('login') > 0) {
    echo $messageStack->output('login');
}
?>
    <div class="opc-block">
        <h2><?php echo HEADING_RETURNING_CUSTOMER_OPC; ?></h2>
        <div class="information"><?php echo TEXT_RETURNING_CUSTOMER_OPC; ?></div>
<?php 
    echo zen_draw_form('login', zen_href_link(FILENAME_LOGIN, 'action=process' . (isset($_GET['gv_no']) ? '&gv_no=' . preg_replace('/[^0-9.,%]/', '', $_GET['gv_no']) : ''), 'SSL'), 'post', 'id="loginForm"'); 
?>
        <div class="opc-label"><?php echo ENTRY_EMAIL_ADDRESS; ?></div>
<?php 
    echo zen_draw_input_field('email_address', '', 'size="18" id="login-email-address" autofocus placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"' . ((int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH > 0 ? ' required' : ''), 'email'); 
?>

        <div class="opc-label"><?php echo ENTRY_PASSWORD; ?></div>
<?php 
    echo zen_draw_password_field('password', '', 'size="18" id="login-password" autocomplete="off" placeholder="' . ENTRY_REQUIRED_SYMBOL . '"' . ((int)ENTRY_PASSWORD_MIN_LENGTH > 0 ? ' required' : '')); 
?>
        <div class="buttonRow" id="opc-pwf"><?php echo '<a href="' . zen_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a>'; ?></div>
        <div class="buttonRow"><?php echo zen_image_submit(BUTTON_IMAGE_LOGIN, BUTTON_LOGIN_ALT); ?></div>
<?php
    echo '</form>';
?>
    </div>
    
    <div class="opc-block">
<?php 
// ** BEGIN PAYPAL EXPRESS CHECKOUT **
if ($ec_button_enabled) { 
?>
        <div class="information"><?php echo TEXT_NEW_CUSTOMER_INTRODUCTION_SPLIT; ?></div>
        <div class="center"><?php require DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/paypal/tpl_ec_button.php'; ?></div>
        <hr />
<?php 
    echo TEXT_NEW_CUSTOMER_POST_INTRODUCTION_DIVIDER;
}
// ** END PAYPAL EXPRESS CHECKOUT **

if ($_SESSION['cart']->count_contents() > 0 && $_SESSION['opc']->guestCheckoutEnabled()) {
?>
        <h2><?php echo HEADING_GUEST_OPC; ?></h2>
        <div class="information"><?php echo TEXT_GUEST_OPC; ?></div>
<?php
    echo zen_draw_form('guest', zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'), 'post') . zen_draw_hidden_field('guest_checkout', 1);
?>
        <div class="buttonRow"><?php echo zen_image_submit(BUTTON_IMAGE_CHECKOUT, BUTTON_CHECKOUT_ALT); ?></div>
<?php
    echo '</form><hr />';
}
?>
        <h2><?php echo HEADING_NEW_CUSTOMER_OPC; ?></h2>

        <div class="information"><?php echo TEXT_NEW_CUSTOMER_OPC; ?></div>
<?php 
    echo zen_draw_form('create', zen_href_link(FILENAME_CREATE_ACCOUNT, (isset($_GET['gv_no']) ? '&gv_no=' . preg_replace('/[^0-9.,%]/', '', $_GET['gv_no']) : ''), 'SSL'), 'post');
?>
        <div class="buttonRow"><?php echo zen_image_submit(BUTTON_IMAGE_CREATE_ACCOUNT, BUTTON_CREATE_ACCOUNT_ALT); ?></div>
<?php
    echo '</form>';
?>
    </div>
<?php
if (CHECKOUT_ONE_DISPLAY_ACCOUNT_BENEFITS == 'true') {
?> 
    <div class="opc-block">
        <h2><?php echo HEADING_ACCOUNT_BENEFITS_OPC; ?></h2>
        <div class="opc-info"><?php echo TEXT_ACCOUNT_BENEFITS_OPC; ?></div>
<?php
    for ($i = 1; $i < 5; $i++) {
        $benefit_heading = "HEADING_BENEFIT_$i";
        $benefit_text = "TEXT_BENEFIT_$i";
        if (defined($benefit_heading) && constant($benefit_heading) != '' && defined($benefit_text) && constant($benefit_text) != '') {
?>
        <div class="opc-head"><?php echo constant($benefit_heading); ?></div>
        <div class="opc-info"><?php echo constant($benefit_text); ?></div>
<?php
        }
    }
?>
    </div>
<?php
}
?>
    <br class="clearBoth" />
</div>
