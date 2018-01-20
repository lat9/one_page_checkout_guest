<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This module overrides the base Zen Cart processing' determination of the base template-rendering file
// to be loaded.  Since the OPC introduces the concept of a customer account without permanent addresses, the
// template needs to account for that condition.
//
$zco_notifier->notify('NOTIFY_MAIN_TEMPLATE_VARS_START_ADDRESS_BOOK');

// -----
// If the One-Page Checkout plugin is not installed, use the default processing.  Note that any
// temporary address-book entries will be displayed.
//
if (!defined('CHECKOUT_ONE_ENABLED')) {
    $page_template = 'tpl_address_book_default.php';
    
// -----
// Otherwise, weed-out any temporary addresses from the display and use the OPC's version of the
// template for the display -- it accounts for the possibility that there might not be any
// permanent addresses defined.
//
} else {
    $zco_notifier->notify('NOTIFY_MAIN_TEMPLATE_VARS_OPC_ADDRESS_BOOK_START');
    $page_template = 'tpl_address_book_register.php';
    if (!isset($addressArray)) {
        $addressArray = array();
    }
    $enable_add_address = (count($addressArray) < MAX_ADDRESS_BOOK_ENTRIES);
    $no_registered_addresses = (count($addressArray) == 0);
    $zco_notifier->notify('NOTIFY_MAIN_TEMPLATE_VARS_OPC_ADDRESS_BOOK_END');
}

// -----
// Load the template file.
//
require $template->get_template_dir($page_template, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$page_template";
