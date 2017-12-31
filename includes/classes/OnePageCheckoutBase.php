<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class, used in both admin and storefront processing, provides a consistent set of constants
// that define the values in the newly-added database fields.
//
class OnePageCheckoutBase extends base
{
    // -----
    // These constants are used for the setting of the account_type field of the customers database table.
    //
    const ACCOUNT_TYPE_REGULAR = 0,      //-Regular account, address recorded
          ACCOUNT_TYPE_GUEST = 1,        //-Guest account, no addresses
          ACCOUNT_TYPE_NO_ADDRESS = 2;   //-Basic account, address is present but contains default values
          
    // -----
    // These constants are used for the setting of the address_type field of the address_book database table.
    //
    const ADDRESS_TYPE_REGULAR = 0,     //-Permanent, associated with a fully-created customer account
          ADDRESS_TYPE_BILL_TEMP = 1,   //-Temporary, used for guest-checkout
          ADDRESS_TYPE_SHIP_TEMP = 2;   //-Temporary, used for guest-checkout
}
