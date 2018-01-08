<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// Adapted from the like-named page handling with the following history:
// - J_Schilz for Integrated COWOA - 2007
// - JT of GTI Custom Modified for Integrated COWOA 02-July-2010
// - Integrated COWAA v1.0
//
// -----
// Define a couple of constants to "throttle" accesses to the page and identify the number of back-to-back
// failures that will be tolerated.
//
if (!defined('CHECKOUT_ONE_ORDER_STATUS_THROTTLE')) {
    define('CHECKOUT_ONE_ORDER_STATUS_THROTTLE', 60);
}
if (!defined('CHECKOUT_ONE_ORDER_STATUS_MAX_ERRS')) {
    define('CHECKOUT_ONE_ORDER_STATUS_SLAM_COUNT', 3);
}

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_ORDER_STATUS');

// -----
// If the customer is currently logged in (and not a guest!), send them to their
// account_history page, instead.
//
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
    zen_redirect(zen_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
}

//kill order status page
if (COWOA_ORDER_STATUS == 'false') zen_redirect(zen_href_link(FILENAME_DEFAULT)); 

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

if (isset($_GET['action']) && $_GET['action'] == 'status') {
    $error = $timeout = false;

    if (isset($_SESSION['order_status_timeout']) && $_SESSION['order_status_timeout'] > time()) {
        $timeout = true;
    }
  
    $orderID = zen_db_prepare_input($_POST['order_id']);
    if (!is_numeric($orderID)) {
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
              WHERE customers_email_address = '$query_email_address'
                AND orders_id = $orderID
              LIMIT 1"
        );
        if ($customeremail->EOF) {
            $error = true ;
            $messageStack->add('order_status', ERROR_NO_MATCH);
        }
    }
    
    $antiSpam = isset($_POST['should_be_empty']) ? zen_db_prepare_input($_POST['should_be_empty']) : '';
    if ($antiSpam != '') {
        $error = true;
    }
 
    if ($error || $timeout) {
        if ($error) {
            if (!isset($_SESSION['os_errors'])) {
                $_SESSION['os_errors'] = 0;
            }
            $_SESSION['os_errors']++;
            if ($_SESSION['os_errors'] > (int)CHECKOUT_ONE_ORDER_STATUS_SLAM_COUNT) {
                $_SESSION['order_status_timeout'] = time() + (int)CHECKOUT_ONE_ORDER_STATUS_THROTTLE;
            }
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

        while (!$statuses->EOF) {
            $statusArray[] = $statuses->fields;
            $statuses->MoveNext();
        }

        require DIR_WS_CLASSES . 'order.php';
        $order = new order($orderID);
        
        // -----
        // The customer needs to wait until searching for the next one.
        //
        $_SESSION['order_status_timeout'] = time() + (int)CHECKOUT_ONE_ORDER_STATUS_THROTTLE;
        $_SESSION['os_errors'] = 0;
    }
}

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_ORDER_STATUS');
