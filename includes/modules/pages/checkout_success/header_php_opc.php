<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// If the previous order was placed via the One-Page Checkout's "Guest Checkout", set a flag for the
// template processing (to load the alternate template) and reset the session-related information
// associated with that guest-checkout.
//
$order_placed_by_guest = false;
if (zen_in_guest_checkout()) {
    $order_placed_by_guest = true;
    $_SESSION['opc']->resetSessionValues();
}