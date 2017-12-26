<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class, instantiated in the current customer session, "watches" a customer's login and checkout
// progression with the aid of the OPC's observer-class.
//
class OnePageCheckoutGuest extends base
{
    // -----
    // These constants are used for the setting of the COWOA_account field of the customers database table.
    //
    const ACCOUNT_TYPE_REGULAR = 0,      //-Regular account, address recorded
          ACCOUNT_TYPE_GUEST = 1,        //-Guest account, no addresses
          ACCOUNT_TYPE_NO_ADDRESS = 2;   //-Basic account, address is present but contains default values
          
    protected $customerAccountType,
              $isGuestEnabled,
              $guestIsActive,
              $isEnabled;
    
    public function __construct()
    {
        $this->isEnabled = false;
        $this->guestIsActive = false;
        $this->isGuestEnabled = (defined('CHECKOUT_ONE_ENABLE_GUEST') && CHECKOUT_ONE_ENABLE_GUEST == 'true');
    }
    
    public function setGuestCheckoutEnabled()
    {
        $this->isEnabled = false;
        if ($this->isGuestEnabled && isset($GLOBALS['checkout_one']) && $GLOBALS['checkout_one']->isEnabled()) {
            $this->isEnabled = true;
        }
        return $this->guestCheckoutEnabled();
    }
    
    public function guestCheckoutEnabled()
    {
        return $this->isEnabled;
    }
    
    public function startGuestOnePageCheckout()
    {
        if ($this->setGuestCheckoutEnabled()) {
            if (isset($_SESSION['COWOA']) || ($GLOBALS['current_page_base'] == FILENAME_CHECKOUT_ONE && isset($_POST['guest_checkout']))) {
                $this->guestIsActive = true;
                $_SESSION['COWOA'] = true;
            }
        }
    }
    
    public function setAccountTypeByEmail($email_address)
    {
        $account_check_sql = 
            "SELECT COWOA_account
               FROM " . TABLE_CUSTOMERS . "
              WHERE customers_email_address = :emailAddress:
              LIMIT 1";
        $account_check_sql = $GLOBALS['db']->bindVars($account_check_sql, ':emailAddress', $p1, 'string');
        $account_check = $GLOBALS['db']->Execute($account_check_sql);
        if ($account_check->EOF) {
            $this->customerAccountType = self::ACCOUNT_TYPE_GUEST;
            trigger_error("No customer account found for the email address '$email_address', assuming guest access.", E_USER_WARNING);
        } else {
            $this->customerAccountType = $account_check->fields['COWOA_account'];
        }
    }
}
