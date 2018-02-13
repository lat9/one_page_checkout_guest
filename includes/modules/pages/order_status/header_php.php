<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// Adapted from the like-named page handling with the following history:
// - J_Schilz for Integrated COWOA - 2007
// - JT of GTI Custom Modified for Integrated COWOA 02-July-2010
// - Integrated COWAA v1.0 by @davewest
//

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_ORDER_STATUS');

// -----
// If the customer is currently logged in (and not a guest!), send them to their
// account_history page, instead.
//
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
    zen_redirect(zen_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
}

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

// -----
// Create the store-specific name of the spam "honeypot" by hashing the store's defined name.
//
$spam_input_name = md5(STORE_NAME);

if (isset($_GET['action']) && $_GET['action'] == 'status') {
    $error = false;
    unset($_SESSION['email_address'], $_SESSION['email_is_os']);
  
    $orderID = (int)$_POST['order_id'];
    if ($orderID < 1) {
        $error = true;
        $messageStack->add('order_status', ERROR_INVALID_ORDER);
    }
    
    $query_email_address = zen_db_prepare_input($_POST['query_email_address']); 
    if (!zen_validate_email($query_email_address)) {
        $error = true;
        $messageStack->add('order_status', ERROR_INVALID_EMAIL);
    }

    if (!$error) {
        $customeremail = $db->Execute(
            "SELECT orders_id FROM " . TABLE_ORDERS . " 
              WHERE customers_email_address = '" . zen_db_input($query_email_address) . "'
                AND orders_id = $orderID
              LIMIT 1"
        );
        if ($customeremail->EOF) {
            $error = true ;
            $messageStack->add('order_status', ERROR_NO_MATCH);
        }
    }
    
    if (!isset($_POST[$spam_input_name]) || $_POST[$spam_input_name] != '') {
        $zco_notifier->notify('NOTIFY_ORDER_STATUS_SPAM_DETECTED');
        $error = true;
    }
    
    // -----
    // Give a "listener" (like a captcha) the opportunity to disallow the display.  If
    // disallowed (i.e. the supplied value is set to boolean 'true', the observer has set
    // its message into the stack for the 'order_status' display.
    //
    $zco_notifier->notify('NOTIFY_ORDER_STATUS_VALIDATION_CHECK', '', $error);
 
    if ($error) {
        if (!isset($_SESSION['os_errors'])) {
            $_SESSION['os_errors'] = 0;
        }
        $_SESSION['os_errors']++;
        
        $slamming_threshold = (((int)CHECKOUT_ONE_ORDER_STATUS_SLAM_COUNT) > 0) ? (int)CHECKOUT_ONE_ORDER_STATUS_SLAM_COUNT : 3;
        $zco_notifier->notify('NOTIFY_ORDER_STATUS_SLAMMING_ALERT', $_SESSION['os_errors'], $slamming_threshold);
        if ($_SESSION['os_errors'] > (int)$slamming_threshold) {
            $zco_notifier->notify('NOTIFY_ORDER_STATUS_SLAMMING_LOCKOUT');
            zen_session_destroy();
            zen_redirect(zen_href_link(FILENAME_TIME_OUT, '', 'SSL'));
        }
    } else {
        $statuses_query = 
            "SELECT os.orders_status_name, osh.date_added, osh.comments
               FROM " . TABLE_ORDERS_STATUS . " os 
                    INNER JOIN " . TABLE_ORDERS_STATUS_HISTORY . " osh
                        ON osh.orders_status_id = os.orders_status_id
                       AND osh.orders_id = :ordersID
                       AND osh.customer_notified >= 0
              WHERE os.language_id = :languagesID
           ORDER BY osh.date_added";

        $statuses_query = $db->bindVars($statuses_query, ':ordersID', $orderID, 'integer');
        $statuses_query = $db->bindVars($statuses_query, ':languagesID', $_SESSION['languages_id'], 'integer');
        $statuses = $db->Execute($statuses_query);

        $statusArray = array();
        while (!$statuses->EOF) {
            $statusArray[] = $statuses->fields;
            $statuses->MoveNext();
        }

        require DIR_WS_CLASSES . 'order.php';
        $order = new order($orderID);
        
        // -----
        // Reset the count of order-status request errors, since the customer
        // has entered valid information.
        //
        $_SESSION['os_errors'] = 0;
        
        // -----
        // If downloads are enabled, set the matching order's email_address into the session
        // for possible use when the customer requests a download of their purchased
        // product.  Also set an indicator into the session to identify that the
        // email_address has been set by **this** processing, enabling the OPC's
        // observer to identify (and remove) the value when the customer navigates off
        // the order_status/download pages.
        //
        if (DOWNLOAD_ENABLED == 'true') {
            $_SESSION['email_address'] = $query_email_address;
            $_SESSION['email_is_os'] = true;
        }
    }
}

// -----
// Give a listener (like a captcha) the opportunity to supply its validation form-field(s) for the
// template's display.
//
$extra_validation_html = '';
$zco_notifier->notify('NOTIFY_ORDER_STATUS_EXTRA_VALIDATION', '', $extra_validation_html);

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_ORDER_STATUS');
