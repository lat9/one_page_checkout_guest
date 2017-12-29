<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class, instantiated in the current customer session, keeps track of a customer's login and checkout
// progression with the aid of the OPC's observer-class.
//
class OnePageCheckoutHelper extends base
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
          
    public $customerAccountType,
              $isGuestEnabled,
              $guestIsActive,
              $isEnabled,
              $addressValues;
    
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
            if (isset($_SESSION['is_guest_checkout']) || ($GLOBALS['current_page_base'] == FILENAME_CHECKOUT_ONE && isset($_POST['guest_checkout']))) {
                $this->guestIsActive = true;
                $_SESSION['is_guest_checkout'] = true;
            }
        }
    }
    
    public function setAccountTypeByEmail($email_address)
    {
        $account_check_sql = 
            "SELECT account_type
               FROM " . TABLE_CUSTOMERS . "
              WHERE customers_email_address = :emailAddress:
              LIMIT 1";
        $account_check_sql = $GLOBALS['db']->bindVars($account_check_sql, ':emailAddress', $p1, 'string');
        $account_check = $GLOBALS['db']->Execute($account_check_sql);
        if ($account_check->EOF) {
            $this->customerAccountType = self::ACCOUNT_TYPE_GUEST;
            trigger_error("No customer account found for the email address '$email_address', assuming guest access.", E_USER_WARNING);
        } else {
            $this->customerAccountType = $account_check->fields['account_type'];
        }
    }
    
    public function getAddressValues($which)
    {
        if ($which != 'bill' && $which != 'ship') {
            trigger_error("Unknown address selection ($which) received.", E_USER_ERROR);
        }
        return $this->addressValues[$which];
    }
    
    public function initializeAddressValues()
    {
        if (isset($this->addressValues)) {
            $address_values = $this->addressValues;
        } else {
            $which = 'ship';
            $session_var_name = 'sendto';
            $finished = false;
            $address_values = array( 
                'ship' => array (), 
                'bill' => array () 
            );
            while (!$finished) {
                if ($this->guestIsActive) {
                    $address_values[$which] = $this->initAddressValuesForGuest();
                } else {
                    $address_values[$which] = $this->initAddressValuesFromDb($session_var_name);
                }
                
                $address_values[$which] = $this->updateStateDropdownSettings($address_values[$which]);

                if ($which == 'ship') {
                    $which = 'bill';
                    $session_var_name = 'billto';
                } else {
                    $finished = true;
                }
            }
            $this->addressValues = $address_values;
        }
    }
    
    protected function initAddressValuesFromDb($session_var_name)
    {
        $address_book_id = (isset($_SESSION[$session_var_name])) ? $_SESSION[$session_var_name] : $_SESSION['customer_default_address_id'];
        $address_info_query = 
            "SELECT ab.entry_gender AS gender, ab.entry_company AS company, ab.entry_firstname AS firstname, ab.entry_lastname AS lastname, 
                    ab.entry_street_address AS street_address, ab.entry_suburb AS suburb, ab.entry_city AS city, ab.entry_postcode AS postcode, 
                    ab.entry_state AS state, ab.entry_country_id AS country, ab.entry_zone_id AS zone_id, z.zone_name, ab.address_book_id,
                    ab.address_type
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
        
       $this->notify('NOTIFY_OPC_HELPER_INIT_ADDRESS_FROM_DB', $session_var_name, $address_info->fields);
        
        return $address_info->fields;
    }
    
    protected function initAddressValuesForGuest($address_type)
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
            'address_type' => $address_type
        );
        $this->notify('NOTIFY_OPC_HELPER_INIT_ADDRESS_FOR_GUEST', '', $address_values);
        
        return $address_values;
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
        if ($which != 'bill' && $which != 'ship') {
            trigger_error("Unknown address selection ($which) received.", E_USER_ERROR);
        }
        if (!isset($this->addressValues)) {
            trigger_error("Invalid request, addressValues not set.", E_USER_ERROR);
        }
        
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
        if ($which != 'bill' && $which != 'ship') {
            trigger_error("Unknown address selection ($which) received.", E_USER_ERROR);
        }
        if (!isset($this->addressValues)) {
            trigger_error("Invalid request, addressValues not set.", E_USER_ERROR);
        }

        $messages = $this->validateUpdatedAddress($_POST, $which, false);
        $address_validated = (count($messages) == 0);
        if ($address_validated) {
            $this->saveCustomerAddress($which, (isset($_POST['add_address']) && $_POST['add_address'] === 'true'));
        }
        
        return $address_validated;
    }
    
    protected function validateUpdatedAddress($address_values, $which, $prepend_which = true)
    {
        $error = false;
        $zone_id = 0;
        $zone_name = '';
        $error_state_input = false;
        $entry_state_has_zones = false;
        $messages = array();
        
        $message_prefix = ($prepend_which) ? (($which == 'bill') ? ERROR_IN_BILLING : ERROR_IN_SHIPPING) : '';
        
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
            
            $check_query = 
                "SELECT zone_id
                   FROM " . TABLE_ZONES . "
                  WHERE zone_country_id = :zoneCountryID
                  LIMIT 1";
            $check_query = $GLOBALS['db']->bindVars($check_query, ':zoneCountryID', $country, 'integer');
            $check = $GLOBALS['db']->Execute($check_query);
            $country_has_zones = (!$check->EOF);
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
            $this->addressValues[$which] = array_merge(
                $this->addressValues[$which],
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
        
        return $messages;
    }
    
    protected function saveCustomerAddress($which, $add_address = false)
    {
        $address = $this->addressValues[$which];
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
        
        $existing_address_book_id = $this->findAddressBookEntry($which);
        if ($existing_address_book_id !== false) {
            $address_book_id = $existing_address_book_id;
        } elseif (!$add_address) {
            $address_book_id = $address['address_book_id'];
            $where_clause = "address_book_id = $address_book_id AND customers_id = :customersID LIMIT 1";
            $where_clause = $GLOBALS['db']->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
            $GLOBALS['db']->perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', $where_clause);
            
            $this->notify('NOTIFY_OPC_HELPER_UPDATED_ADDRESS_BOOK_RECORD', array('address_book_id' => $address_book_id, 'customers_id' => $_SESSION['customer_id']), $sql_data_array);
        } else {
            $sql_data_array[] = array('fieldName' => 'customers_id', 'value' => $_SESSION['customer_id'], 'type'=>'integer');
            $GLOBALS['db']->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
            $address_book_id = $GLOBALS['db']->Insert_ID();
            
            $this->notify('NOTIFY_OPC_HELPER_ADDED_ADDRESS_BOOK_RECORD', array('address_book_id' => $address_book_id), $sql_data_array);
        }
        
        $this->addressValues[$which]['address_book_id'] = $address_book_id;
        
        if ($address_book_id == $_SESSION['customer_default_address_id']) {
            $_SESSION['customer_first_name'] = $address['firstname'];
            $_SESSION['customer_last_name'] = $address['lastname'];
            $_SESSION['customer_country_id'] = $address['country'];
            $_SESSION['customer_zone_id'] = (((int)$address['zone_id']) > 0) ? (int)$address['zone_id'] : 0;
            
            $sql_data_array = array(
                array('fieldName' => 'customers_firstname', 'value' => $address['firstname'], 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'customers_lastname', 'value' => $address['lastname'], 'type' => 'stringIgnoreNull'),
            );
            if (ACCOUNT_GENDER == 'true') {
                $sql_data_array[] = array('fieldName' => 'customers_gender', 'value' => $address['gender'], 'type' => 'enum:m|f');
            }
            $where_clause = "customers_id = :customersID LIMIT 1";
            $where_clause = $GLOBALS['db']->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
            $GLOBALS['db']->perform(TABLE_CUSTOMERS, $sql_data_array, 'update', $where_clause);
        }
        
        if ($which == 'bill') {
            $_SESSION['billto'] = $address_book_id;
        } else {
            $_SESSION['sendto'] = $address_book_id;
        }
    }
    
    protected function findAddressBookEntry($which)
    {
        $country_id = $this->addressValues[$which]['country'];

        // -----
        // See if the country has states
        //
        $country_has_zones = $GLOBALS['db']->Execute(
            "SELECT zone_id
               FROM " . TABLE_ZONES . "
              WHERE zone_country_id = $country_id
              LIMIT 1"
        );

        // do a match on address, street, street2, city
        $sql = 
            "SELECT address_book_id, entry_street_address AS street_address, entry_suburb AS suburb, entry_city AS city, 
                    entry_postcode AS postcode, entry_firstname AS firstname, entry_lastname AS lastname
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = :customerId
                AND entry_country_id = $country_id";
        if ($country_has_zones->EOF) {
            $sql .= " AND entry_state = :stateValue LIMIT 1";
        } else {
            $sql .= " AND entry_zone_id = :zoneId LIMIT 1";
        }
        $sql = $GLOBALS['db']->bindVars ($sql, ':zoneId', $this->addressValues[$which]['zone_id'], 'integer');
        $sql = $GLOBALS['db']->bindVars ($sql, ':stateValue', $this->addressValues[$which]['state'], 'string');
        $sql = $GLOBALS['db']->bindVars ($sql, ':customerId', $_SESSION['customer_id'], 'integer');
        $possible_addresses = $GLOBALS['db']->Execute($sql);
        
        $address_book_id = false;  //-Identifies that no match was found
        $address_to_match = $this->addressArrayToString($this->addressValues[$which]);
        while (!$possible_addresses->EOF) {
            if ($address_to_match == $this->addressArrayToString($possible_addresses->fields)) {
                $address_book_id = $possible_addresses->fields['address_book_id'];
                break;
            }
            $possible_addresses->MoveNext();
        }
        trigger_error("findAddressBookEntry($which), returning ($address_book_id) for '$address_to_match'" . var_export($_POST, true), E_USER_WARNING);
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
        $the_address = strtolower(str_replace(array ("\n", "\t", "\r", "\0", ' ', ',', '.'), '', $the_address));
        return $the_address;
    }
}
