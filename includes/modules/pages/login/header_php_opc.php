<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// If the One-Page Checkout's "Guest Checkout", instruct the template-formatting to disable the right and left sideboxes.
//
if (isset($_SESSION['opc']) && $_SESSION['opc']->temporaryAddressesEnabled()) {
    $flag_disable_right = $flag_disable_left = true;
}