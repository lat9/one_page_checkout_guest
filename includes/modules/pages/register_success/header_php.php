<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_REGISTRATION_SUCCESS');

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
$breadcrumb->add(NAVBAR_TITLE_1);
$breadcrumb->add(NAVBAR_TITLE_2);

// Remove this page from the navigation history and if the customer returns to this page after time-out, redirect them to the time_out page
$_SESSION['navigation']->remove_current_page();
if (!$_SESSION['customer_id']) {
    zen_redirect(zen_href_link(FILENAME_TIME_OUT, '', 'NONSSL'));
}

if (count($_SESSION['navigation']->snapshot) > 0) {
    $origin_href = zen_href_link($_SESSION['navigation']->snapshot['page'], zen_array_to_string($_SESSION['navigation']->snapshot['get'], array(zen_session_name())), $_SESSION['navigation']->snapshot['mode']);
    $_SESSION['navigation']->clear_snapshot();
} else {
    $origin_href = zen_href_link(FILENAME_DEFAULT);
}

// redirect customer to where they came from if their cart is not empty and they didn't click on create-account specifically
if ($_SESSION['cart']->count_contents() > 0) {
    if ($origin_href != zen_href_link(FILENAME_DEFAULT)) {
        zen_redirect($origin_href);
    }
}

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_REGISTRATION_SUCCESS');
