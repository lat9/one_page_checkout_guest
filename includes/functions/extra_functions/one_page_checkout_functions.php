<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//

// -----
// This function identifies whether (true) or not (false) the current customer session is
// associated with a guest-checkout process.
//
if (!function_exists('zen_in_guest_checkout')) {
    function zen_in_guest_checkout()
    {
        return $_SESSION['opc']->isGuestCheckout();
    }
}

// -----
// This function identifies whether (true) or not (false) a customer is currently logged into the site.
//
if (!function_exists('zen_is_logged_in')) {
    function zen_is_logged_in()
    {
        return (!empty($_SESSION['customer_id']));
    }
}