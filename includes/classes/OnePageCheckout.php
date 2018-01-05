<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class, instantiated in the current customer session, keeps track of a customer's login and checkout
// progression with the aid of the OPC's observer-class.
//
class OnePageCheckout extends base
{
    // -----
    // Various protected data elements:
    //
    // isGuestCheckoutEnabled ... Indicates whether (true) or not (false) the overall guest-checkout is enabled.
    // registeredAccounts ....... Indicates whether (true) or not (false) "registered" accounts are enabled.
    // isEnabled ................ Essentially follows the enable-value of the OPC observer.
    // guestIsActive ............ Indicates whether (true) or not (false) we're currently handling a guest-checkout
    // tempAddressValues ........ Array, if set, contains any temporary addresses used within the checkout process.
    // guestCustomerInfo ........ Array, if set, contains the customer-specific (i.e. email, phone, etc.) information for a guest customer.
    // guestCustomerId .......... Contains a sanitized/int version of the configured "guest" customer ID.
    // tempBilltoAddressBookId .. Contains a sanitized/int version of the configured "temporary" bill-to address-book ID.
    // tempShiptoAddressBookId .. Contains a sanitized/int version of the configured "temporary" ship-to address-book ID.
    //
    public $isGuestCheckoutEnabled,
              $registeredAccounts,
              $guestIsActive,
              $isEnabled,
              $tempAddressValues,
              $guestCustomerInfo,
              $guestCustomerId,
              $tempBilltoAddressBookId,
              $tempSendtoAddressBookId;
    
    public function __construct()
    {
        $this->isEnabled = false;
        $this->guestIsActive = false;
        $this->isGuestCheckoutEnabled = false;
        $this->registeredAccounts = false;
    }
    
    // -----
    // This function, called by the OPC's observer-class, provides the common-use debug filename.
    //
    public function getDebugLogFileName()
    {
        return DIR_FS_LOGS . '/myDEBUG-one_page_checkout-' . $_SESSION['customer_id'] . '.log';
    }
    
    public function guestCheckoutEnabled()
    {
        $this->initializeGuestCheckout();
        return ($this->isEnabled && $this->isGuestCheckoutEnabled);
    }
    
    // -----
    // Issued by the OPC's observer-class to determine whether order-related notifications need
    // to be monitored.
    //
    public function initTemporaryAddresses()
    {
        $this->initializeGuestCheckout();
        return ($this->isGuestCheckoutEnabled || $this->registeredAccounts);
    }
    
    public function temporaryAddressesEnabled()
    {
        $this->initializeGuestCheckout();
        return ($this->isEnabled && ($this->isGuestCheckoutEnabled || $this->registeredAccounts));
    }

    protected function initializeGuestCheckout()
    {
        $this->isGuestCheckoutEnabled = (defined('CHECKOUT_ONE_ENABLE_GUEST') && CHECKOUT_ONE_ENABLE_GUEST == 'true');
        $this->isEnabled = (isset($GLOBALS['checkout_one']) && $GLOBALS['checkout_one']->isEnabled());
        $this->guestCustomerId = (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) ? (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID : 0;
        $this->tempBilltoAddressBookId = (defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) ? (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID : 0;
        $this->tempSendtoAddressBookId = (defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) ? (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID : 0;
    }

    
    public function isGuestCheckout()
    {
        return (isset($_SESSION['is_guest_checkout']));
    }
    
    public function startGuestOnePageCheckout()
    {
        $this->guestIsActive = false;
        if ($this->guestCheckoutEnabled()) {
            if ($this->isGuestCheckout() || ($GLOBALS['current_page_base'] == FILENAME_CHECKOUT_ONE && isset($_POST['guest_checkout']))) {
                $this->guestIsActive = true;
            }
        }
        if ($this->guestIsActive) {
            $_SESSION['is_guest_checkout'] = true;
            $_SESSION['customer_id'] = $this->guestCustomerId;
            $_SESSION['customer_default_address_id'] = $this->tempBilltoAddressBookId;
        } else {
            unset($_SESSION['is_guest_checkout']);
        }
        $this->initializeTempAddressValues();
        $this->debugMessage('startGuestOnePageCheckout, exit: sendto: ' . ((isset($_SESSION['sendto'])) ? $_SESSION['sendto'] : 'not set') . ', billto: ' . ((isset($_SESSION['billto'])) ? $_SESSION['billto'] : 'not set') . var_export($this, true));
    }
    
    // -----
    // This function, called from the OPC's observer-class, provides any address/tax-basis
    // update when an order includes one or more temporary addresses (a superset of guest
    // checkout).
    //
    public function updateOrderAddresses($order, &$taxCountryId, &$taxZoneId)
    {
        if (zen_in_guest_checkout()) {
            $address = (array)$order->customer;
            $order->customer = array_merge($address, $this->createOrderAddressFromTemporary['bill'], $this->getGuestCustomerInfo());
        }
        
        $temp_billing_address = $temp_shipping_address = false;
        if (isset($_SESSION['sendto']) && ($_SESSION['sendto'] == $this->tempSendtoAddressBookId || $_SESSION['sendto'] == $this->tempBilltoAddressBookId)) {
            $temp_shipping_address = true;
            $address = (array)$order->delivery;
            $order->delivery = array_merge($address, $this->createOrderAddressFromTemporary('ship'));
        }
        if (isset($_SESSION['billto']) && $_SESSION['billto'] == $this->tempBilltoAddressBookId) {
            $temp_billing_address = true;
            $address = (array)$order->billing;
            $order->billing = array_merge($address, $this->createOrderAddressFromTemporary('bill'));
        }
        if ($temp_shipping_address || $temp_billing_address) {
            $tax_info = $this->recalculateTaxBasis($order, $temp_billing_address, $temp_shipping_address);
            $taxCountryId = $tax_info['tax_country_id'];
            $taxZoneId = $tax_info['tax_zone_id'];
        }
        $this->debugMessage("UpdateOrderAddresses, $temp_billing_address, $temp_shipping_address, $taxCountryId, $taxZoneId" . var_export($order->billing, true) . var_export($order->delivery, true));
    }
    
    protected function getGuestCustomerInfo()
    {
        if (!isset($this->guestCustomerInfo)) {
            trigger_error("Guest customer-info not set during guest checkout.", E_USER_ERROR);
        }
        $customer = array(
            'email_address' => $this->guestCustomerInfo['email_address'],
            'telephone' => $this->guestCustomerInfo['telephone']
        );
        return $customer;
    }
    protected function createOrderAddressFromTemporary($which)
    {
        $country_id = $this->tempAddressValues[$which]['country'];
        $country_info = $GLOBALS['db']->Execute(
            "SELECT *
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_id = $country_id
                AND status = 1
              LIMIT 1"
        );
        if ($country_info->EOF) {
            trigger_error("Unknown or disabled country present for '$which' address ($country_id).", E_USER_ERROR);
        }
        
        $address = array(
            'firstname' => $this->tempAddressValues[$which]['firstname'],
            'lastname' => $this->tempAddressValues[$which]['lastname'],
            'company' => $this->tempAddressValues[$which]['company'],
            'street_address' => $this->tempAddressValues[$which]['street_address'],
            'suburb' => $this->tempAddressValues[$which]['suburb'],
            'city' => $this->tempAddressValues[$which]['city'],
            'postcode' => $this->tempAddressValues[$which]['postcode'],
            'state' => ((zen_not_null($this->tempAddressValues[$which]['state'])) ? $this->tempAddressValues[$which]['state'] : $this->tempAddressValues[$which]['zone_name']),
            'zone_id' => $this->tempAddressValues[$which]['zone_id'],
            'country' => array(
                'id' => $country_id, 
                'title' => $country_info->fields['countries_name'], 
                'iso_code_2' => $country_info->fields['countries_iso_code_2'], 
                'iso_code_3' => $country_info->fields['countries_iso_code_3']
            ),
            'country_id' => $country_id,
            'format_id' => (int)$country_info->fields['address_format_id']
        );
        $this->debugMessage("createOrderAddressFromTemporary($which), returning:" . var_export($address, true));
        return $address;
    }
    protected function recalculateTaxBasis($order, $use_temp_billing, $use_temp_shipping)
    {
        $this->debugMessage("recalculateTaxBasis(order, $use_temp_billing, $useTempShipping): " . var_export($order, true) . var_export($this->tempAddressValues, true));
        switch (STORE_PRODUCT_TAX_BASIS) {
            case 'Shipping':
                if ($order->content_type == 'virtual') {
                    if ($use_temp_billing) {
                        $tax_country_id = $this->tempAddressValues['bill']['country'];
                        $tax_zone_id = $this->tempAddressValues['bill']['zone_id'];
                    } else {
                        $tax_country_id = $order->billing['country_id'];
                        $tax_zone_id = $order->billing['zone_id'];
                    }
                } else {
                    if ($use_temp_shipping) {
                        $tax_country_id = $this->tempAddressValues['ship']['country'];
                        $tax_zone_id = $this->tempAddressValues['ship']['zone_id'];
                    } else {
                        $tax_country_id = $order->delivery['country_id'];
                        $tax_zone_id = $order->delivery['zone_id'];
                    }
                }
                break;
                
            case 'Billing':
               if ($use_temp_billing) {
                    $tax_country_id = $this->tempAddressValues['bill']['country'];
                    $tax_zone_id = $this->tempAddressValues['bill']['zone_id'];
                } else {
                    $tax_country_id = $order->billing['country_id'];
                    $tax_zone_id = $order->billing['zone_id'];
                }
                break;
                
            default:
                if ($use_temp_billing && $this->tempAddressValues['bill']['zone_id'] == STORE_ZONE) {
                    $tax_country_id = $this->tempAddressValues['bill']['country'];
                    $tax_zone_id = $this->tempAddressValues['bill']['zone_id'];
                } elseif ((!$use_temp_billing && $order->billing['zone_id'] == STORE_ZONE) || $order->content_type == 'virtual') {
                    $tax_country_id = $order->billing['country_id'];
                    $tax_zone_id = $order->billing['zone_id'];
                } else {
                    $tax_country_id = $order->delivery['country_id'];
                    $tax_zone_id = $order->delivery['zone_id'];
                }
                break;
        }
        
        $this->debugMessage("recalculateTaxBasis, temp_billing($use_temp_billing), temp_shipping($use_temp_shipping), returning country_id = $tax_country_id, zone_id = $tax_zone_id.");
        return array(
            'tax_country_id' => $tax_country_id,
            'tax_zone_id' => $tax_zone_id
        );
    }
    
    public function validateBilltoSendto($which)
    {
        $this->inputPreCheck($which);
        
        if ($which == 'bill') {
            $address_book_id = $_SESSION['billto'];
            $is_temp_address = ($address_book_id == $this->tempBilltoAddressBookId);
        } else {
            $address_book_id = $_SESSION['sendto'];
            $is_temp_address = ($address_book_id == $this->tempShiptoAddressBookId);
            if (isset($_SESSION['shipping_billing'])) {
                $is_temp_address = $is_temp_address || ($address_book_id == $this->tempBilltoAddressBookId);
            }
        }
        
        $is_valid = true;
        if (zen_in_guest_checkout()) {
            if (!$is_temp_address) {
                $is_valid = false;
            }
        } else {
            if (!$is_temp_address) {
                $check_query =
                    "SELECT customers_id
                       FROM " . TABLE_ADDRESS_BOOK . "
                      WHERE customers_id = :customersID
                        AND address_book_id = :addressBookID
                      LIMIT 1";
                $check_query = $GLOBALS['db']->bindVars($check_query, ':customersID', $_SESSION['customer_id'], 'integer');
                $check_query = $GLOBALS['db']->bindVars($check_query, ':addressBookID', $address_book_id, 'integer');
                $check = $GLOBALS['db']->Execute($check_query);
                $is_valid = !$check->EOF;
            }
        }
        return $is_valid;
    }
    
    public function setAddressFromSavedSelections($which, $address_book_id)
    {
        $this->inputPreCheck($which);
        
        if ($which == 'bill') {
            $_SESSION['billto'] = $address_book_id;
        } else {
            $_SESSION['sendto'] = $address_book_id;
        }
    }
    
    public function getAddressValues($which)
    {
        $this->inputPreCheck($which);
        
        $address_book_id = (int)($which == 'bill') ? $_SESSION['billto'] : $_SESSION['sendto'];
        
        if ($address_book_id == $this->tempBilltoAddressBookId || $address_book_id == $this->tempShiptoAddressBookId) {
            $address_values = $this->tempAddressValues[$which];
        } else {
            $address_values = $this->getAddressValuesFromDb($address_book_id);
        }
        
        return $this->updateStateDropdownSettings($address_values);
    }
    
    protected function getAddressValuesFromDb($address_book_id)
    {
        $address_info_query = 
            "SELECT ab.entry_gender AS gender, ab.entry_company AS company, ab.entry_firstname AS firstname, ab.entry_lastname AS lastname, 
                    ab.entry_street_address AS street_address, ab.entry_suburb AS suburb, ab.entry_city AS city, ab.entry_postcode AS postcode, 
                    ab.entry_state AS state, ab.entry_country_id AS country, ab.entry_zone_id AS zone_id, z.zone_name, ab.address_book_id,
                    ab.address_book_id
               FROM " . TABLE_ADDRESS_BOOK . "  ab
                    LEFT JOIN " . TABLE_ZONES . " z
                        ON z.zone_id = ab.entry_zone_id
                       AND z.zone_country_id = ab.entry_country_id
              WHERE ab.customers_id = :customersID 
                AND ab.address_book_id = :addressBookID 
              LIMIT 1";
        $address_info_query = $GLOBALS['db']->bindVars($address_info_query, ':customersID', $_SESSION['customer_id'], 'integer');
        $address_info_query = $GLOBALS['db']->bindVars($address_info_query, ':addressBookID', $address_book_id, 'integer');

        $address_info = $GLOBALS['db']->Execute($address_info_query);
        if ($address_info->EOF) {
            trigger_error("unknown $which/$session_var_name address_book_id (" . $address_book_id . ') for customer_id (' . $_SESSION['customer_id'] . ')', E_USER_ERROR);
        }

        $address_info->fields['error_state_input'] = $address_info->fields['error'] = false;
        $address_info->fields['country_has_zones'] = $this->countryHasZones($address_info->fields['country']);
        
        $this->notify('NOTIFY_OPC_INIT_ADDRESS_FROM_DB', $address_book_id, $address_info->fields);
        
        $this->debugMessage("getAddressValuesFromDb($address_book_id), returning: " . var_export($address_info->fields, true)); 
        
        return $address_info->fields;
    }
    
    protected function initAddressValuesForGuest()
    {
        $address_values = array(
            'gender' => '',
            'company' => '',
            'firstname' => '',
            'lastname' => '',
            'street_address' => '',
            'suburb' => '',
            'city' => '',
            'postcode' => '',
            'state' => '',
            'country' => (int)STORE_COUNTRY,
            'zone_id' => (int)STORE_ZONE,
            'zone_name' => '',
            'address_book_id' => 0,
            'selected_country' => (int)STORE_COUNTRY,
            'country_has_zones' => $this->countryHasZones((int)STORE_COUNTRY),
            'state_field_label' => '',
            'show_pulldown_states' => false,
            'error' => false,
            'error_state_input' => false,
        );
        $this->notify('NOTIFY_OPC_INIT_ADDRESS_FOR_GUEST', '', $address_values);
        
        return $address_values;
    }
    
    public function formatAddressBookDropdown()
    {
        $select_array = array();
        if (isset($_SESSION['customer_id']) && !$this->isGuestCheckout()) {
            // -----
            // Build up address list input to create a customer-specific selection list of 
            // pre-existing addresses from which to choose.
            //
            $addresses = $GLOBALS['db']->Execute(
                "SELECT address_book_id 
                   FROM " . TABLE_ADDRESS_BOOK . " 
                  WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
               ORDER BY address_book_id"
            );
            if (!$addresses->EOF) {
                $select_array[] = array(
                    'id' => 0,
                    'text' => TEXT_SELECT_FROM_SAVED_ADDRESSES
                );
            }
            while (!$addresses->EOF) {
                $select_array[] = array( 
                    'id' => $addresses->fields['address_book_id'],
                    'text' => str_replace("\n", ', ', zen_address_label($_SESSION['customer_id'], $addresses->fields['address_book_id']))
                );
                $addresses->MoveNext();
            }
        }
        return $select_array;
    }
    
    protected function initializeTempAddressValues()
    {
        if (!isset($this->tempAddressValues)) {
            $this->tempAddressValues = array(
                'ship' => $this->initAddressValuesForGuest(),
                'bill' => $this->initAddressValuesForGuest()
            );
        }
    }
    
    protected function countryHasZones($country_id)
    {
        $check = $GLOBALS['db']->Execute(
            "SELECT zone_id
               FROM " . TABLE_ZONES . "
              WHERE zone_country_id = $country_id
              LIMIT 1"
        );
        return !$check->EOF;
    }
    
    protected function updateStateDropdownSettings($address_values)
    {
        $show_pulldown_states = ($address_values['zone_name'] == '' && $address_values['country_has_zones']) || ACCOUNT_STATE_DRAW_INITIAL_DROPDOWN == 'true' || $address_values['error_state_input'];
        $address_values['selected_country'] = $address_values['country'];
        $address_values['state'] = ($show_pulldown_states) ? $address_values['state'] : $address_values['zone_name'];
        $address_values['state_field_label'] = ($show_pulldown_states) ? '' : ENTRY_STATE;
        $address_values['show_pulldown_states'] = $show_pulldown_states;
        
        return $address_values;
    }
    
    public function validatePostedAddress($which)
    {
        $this->inputPreCheck($which);
        
        $messages = $this->validateUpdatedAddress($_POST[$which], $which);
        $error = false;
        if (count($messages) > 0) {
            $error = true;
            foreach ($messages as $field_name => $message) {
                $GLOBALS['messageStack']->add_session('addressbook', $message, 'error');
            }
        }
        return $error;
    }
    
    public function validateAndSaveAjaxPostedAddress($which, &$messages)
    {
        $this->inputPreCheck($which);

        $address_info = $_POST;
        if ($address_info['shipping_billing'] == 'true') {
            $_SESSION['shipping_billing'] = true;
        } else {
            unset($_SESSION['shipping_billing']);
        }
        unset($address_info['securityToken'], $address_info['add_address'], $address_info['shipping_billing']);
        $messages = $this->validateUpdatedAddress($address_info, $which, false);
        $address_validated = (count($messages) == 0);
        if ($address_validated) {
            $this->saveCustomerAddress($address_info, $which, (isset($_POST['add_address']) && $_POST['add_address'] === 'true'));
        }
        
        return $address_validated;
    }
    
    // -----
    // Called by various functions with public interfaces to validate the "environment"
    // for the caller's processing.  If either the 'which' (address-value) input is not
    // valid or the class' addressValues element is not yet initialized, there's a
    // sequencing error somewhere.
    //
    // If either condition is found, log an ERROR ... which results in the page's processing
    // to cease.
    //
    protected function inputPreCheck($which)
    {
        if ($which != 'bill' && $which != 'ship') {
            trigger_error("Unknown address selection ($which) received.", E_USER_ERROR);
        }
        if (!isset($this->tempAddressValues)) {
            trigger_error("Invalid request, tempAddressValues not set.", E_USER_ERROR);
        }
    }
    
    protected function validateUpdatedAddress(&$address_values, $which, $prepend_which = true)
    {
        $error = false;
        $zone_id = 0;
        $zone_name = '';
        $error_state_input = false;
        $entry_state_has_zones = false;
        $messages = array();
        
        $message_prefix = ($prepend_which) ? (($which == 'bill') ? ERROR_IN_BILLING : ERROR_IN_SHIPPING) : '';
        
        $this->debugMessage("Start validateUpdatedAddress, which = $which:" . var_export($address_values, true));
        
        $gender = false;
        $company = '';
        $suburb = '';

        if (ACCOUNT_GENDER == 'true') {
          $gender = zen_db_prepare_input($address_values['gender']);
            if ($gender != 'm' && $gender != 'f') {
                $error = true;
                $messages['gender'] = $message_prefix . ENTRY_GENDER_ERROR;
            }
        }
        
        $firstname = zen_db_prepare_input(zen_sanitize_string($address_values['firstname']));
        if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
            $error = true;
            $messages['firstname'] = $message_prefix . ENTRY_FIRST_NAME_ERROR;
        }
        
        $lastname = zen_db_prepare_input(zen_sanitize_string($address_values['lastname']));
        if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
            $error = true;
            $messages['lastname'] = $message_prefix . ENTRY_LAST_NAME_ERROR;
        }
        
        $street_address = zen_db_prepare_input($address_values['street_address']);
        if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
            $error = true;
            $messages['street_address'] = $message_prefix . ENTRY_STREET_ADDRESS_ERROR;
        }
        
        $city = zen_db_prepare_input($address_values['city']);
        if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
            $error = true;
            $messages['city'] = $message_prefix . ENTRY_CITY_ERROR;
        }
        
        $postcode = zen_db_prepare_input($address_values['postcode']);
        if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
            $error = true;
            $messages['postcode'] = $message_prefix . ENTRY_POST_CODE_ERROR;
        }
        
        $country = zen_db_prepare_input($address_values['zone_country_id']);
        if (!is_numeric($country)) {
            $error = true;
            $messages['zone_country_id'] = $message_prefix . ENTRY_COUNTRY_ERROR;
        } elseif (ACCOUNT_STATE == 'true') {
            $state = (isset($address_values['state'])) ? trim(zen_db_prepare_input($address_values['state'])) : false;
            $zone_id = (isset($address_values['zone_id'])) ? zen_db_prepare_input($address_values['zone_id']) : false;

            $country_has_zones = $this->countryHasZones((int)$country);
            if ($country_has_zones) {
                $zone_query = 
                    "SELECT DISTINCT zone_id, zone_name, zone_code
                       FROM " . TABLE_ZONES . "
                      WHERE zone_country_id = :zoneCountryID
                        AND " .
                             (($state != '' && $zone_id == 0) ? "(UPPER(zone_name) LIKE ':zoneState%' OR UPPER(zone_code) LIKE '%:zoneState%') OR " : '') . "
                             zone_id = :zoneID
                   ORDER BY zone_code ASC, zone_name";

                $zone_query = $GLOBALS['db']->bindVars($zone_query, ':zoneCountryID', $country, 'integer');
                $zone_query = $GLOBALS['db']->bindVars($zone_query, ':zoneState', strtoupper($state), 'noquotestring');
                $zone_query = $GLOBALS['db']->bindVars($zone_query, ':zoneID', $zone_id, 'integer');
                $zone = $GLOBALS['db']->Execute($zone_query);

                //look for an exact match on zone ISO code
                $found_exact_iso_match = ($zone->RecordCount() == 1);
                if ($zone->RecordCount() > 1) {
                    while (!$zone->EOF) {
                        if (strtoupper($zone->fields['zone_code']) == strtoupper($state) ) {
                            $found_exact_iso_match = true;
                            break;
                        }
                        $zone->MoveNext();
                    }
                }

                if ($found_exact_iso_match) {
                    $zone_id = $zone->fields['zone_id'];
                    $zone_name = $zone->fields['zone_name'];
                } else {
                    $error = true;
                    $error_state_input = true;
                    $messages['zone_id'] = $message_prefix . ENTRY_STATE_ERROR_SELECT;
                }
            } else {
                if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
                    $error = true;
                    $error_state_input = true;
                    $messages['state'] = $message_prefix . ENTRY_STATE_ERROR;
                }
            }
        }

        if (!$error) {
            $address_values = array_merge(
                $address_values,
                array(
                    'gender' => $gender,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'street_address' => $street_address,
                    'suburb' => $suburb,
                    'city' => $city,
                    'state' => $state,
                    'postcode' => $postcode,
                    'country' => $country,
                    'zone_id' => $zone_id,
                    'zone_name' => $zone_name,
                    'error_state_input' => $error_state_input,
                    'country_has_zones' => $country_has_zones,
                    'show_pulldown_states' => (($zone_name == '' && $county_has_zones) || ACCOUNT_STATE_DRAW_INITIAL_DROPDOWN == 'true' || $error_state_input),
                    'error' => $error
                )
            );
        }
        
        $this->debugMessage('Exiting validateUpdatedAddress.' . var_export($messages, true) . var_export($address_values, true));
        return $messages;
    }
    
    protected function saveCustomerAddress($address, $which, $add_address = false)
    {
        $this->debugMessage("saveCustomerAddress($which, $add_address), " . (isset($_SESSION['shipping_billing']) ? 'shipping=billing' : 'shipping!=billing') . ' ' . var_export($address, true));
        if (!$add_address || $this->isGuestCheckout()) {
            $this->tempAddressValues[$which] = $address;
            if ($which == 'ship') {
                $_SESSION['sendto'] = $this->tempShiptoAddressBookId;
            } else {
                $_SESSION['billto'] = $this->tempBilltoAddressBookId;
                if (isset($_SESSION['shipping_billing']) && $_SESSION['shipping_billing']) {
                    $_SESSION['sendto'] = $this->tempShiptoAddressBookId;
                    $this->tempAddressValues['ship'] = $this->tempAddressValues['bill'];
                }
            }
            $this->debugMessage("Updated tempAddressValues[$which]:" . var_export($this->tempAddressValues, true));
        } else {
            $sql_data_array = array(
                array('fieldName' => 'entry_firstname', 'value' => $address['firstname'], 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_lastname', 'value' => $address['lastname'], 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_street_address', 'value' => $address['street_address'], 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_postcode', 'value' => $address['postcode'], 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_city', 'value' => $address['city'], 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_country_id', 'value' => $address['country'], 'type' => 'integer')
            );

            if (ACCOUNT_GENDER == 'true') {
                $sql_data_array[] = array('fieldName' => 'entry_gender', 'value' => $address['gender'], 'type' => 'enum:m|f');
            }
            
            if (ACCOUNT_COMPANY == 'true') {
                $sql_data_array[] = array('fieldName' => 'entry_company', 'value' => $address['company'], 'type' => 'stringIgnoreNull');
            }
            
            if (ACCOUNT_SUBURB == 'true') {
                $sql_data_array[] = array('fieldName' => 'entry_suburb', 'value' => $address['suburb'], 'type' => 'stringIgnoreNull');
            }
            
            if (ACCOUNT_STATE == 'true') {
                if ($address['zone_id'] > 0) {
                    $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => $address['zone_id'], 'type' => 'integer');
                    $sql_data_array[] = array('fieldName' => 'entry_state', 'value'=> '', 'type' => 'stringIgnoreNull');
                } else {
                    $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => '0', 'type' => 'integer');
                    $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => $address['state'], 'type' => 'stringIgnoreNull');
                }
            }
            
            $existing_address_book_id = $this->findAddressBookEntry($address);
            if ($existing_address_book_id !== false) {
                $address_book_id = $existing_address_book_id;
            } else {
                $sql_data_array[] = array('fieldName' => 'customers_id', 'value' => $_SESSION['customer_id'], 'type'=>'integer');
                $GLOBALS['db']->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $address_book_id = $GLOBALS['db']->Insert_ID();
                
                $this->notify('NOTIFY_OPC_HELPER_ADDED_ADDRESS_BOOK_RECORD', array('address_book_id' => $address_book_id), $sql_data_array);
            }
            
            if ($which == 'bill') {
                $_SESSION['billto'] = $address_book_id;
            } else {
                $_SESSION['sendto'] = $address_book_id;
            }
        }
    }
    
    protected function findAddressBookEntry($address)
    {
        $country_id = $address['country'];
        $country_has_zones = $address['country_has_zones'];

        // do a match on address, street, street2, city
        $sql = 
            "SELECT address_book_id, entry_street_address AS street_address, entry_suburb AS suburb, entry_city AS city, 
                    entry_postcode AS postcode, entry_firstname AS firstname, entry_lastname AS lastname
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = :customerId
                AND entry_country_id = $country_id";
        if (!$country_has_zones) {
            $sql .= " AND entry_state = :stateValue LIMIT 1";
        } else {
            $sql .= " AND entry_zone_id = :zoneId LIMIT 1";
        }
        $sql = $GLOBALS['db']->bindVars($sql, ':zoneId', $address['zone_id'], 'integer');
        $sql = $GLOBALS['db']->bindVars($sql, ':stateValue', $address['state'], 'string');
        $sql = $GLOBALS['db']->bindVars($sql, ':customerId', $_SESSION['customer_id'], 'integer');
        $possible_addresses = $GLOBALS['db']->Execute($sql);
        
        $address_book_id = false;  //-Identifies that no match was found
        $address_to_match = $this->addressArrayToString($address);
        while (!$possible_addresses->EOF) {
            if ($address_to_match == $this->addressArrayToString($possible_addresses->fields)) {
                $address_book_id = $possible_addresses->fields['address_book_id'];
                break;
            }
            $possible_addresses->MoveNext();
        }
        $this->debugMessage("findAddressBookEntry, returning ($address_book_id) for '$address_to_match'" . var_export($address, true));
        return $address_book_id;
    }
    
    protected function addressArrayToString($address_array) 
    {
        $the_address = 
            $address_array['firstname'] . 
            $address_array['lastname'] . 
            $address_array['street_address'] . 
            $address_array['suburb'] . 
            $address_array['city'] . 
            $address_array['postcode'];
        $the_address = strtolower(str_replace(array("\n", "\t", "\r", "\0", ' ', ',', '.'), '', $the_address));
        return $the_address;
    }

    protected function debugMessage($message, $include_request = false)
    {
        $GLOBALS['checkout_one']->debug_message($message, $include_request, 'OnePageCheckout');
    }
}
