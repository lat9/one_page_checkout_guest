<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class, instantiated in the current customer session, "watches" a customer's login and checkout
// progression with the aid of the OPC's observer-class.
//
class OnePageCheckoutHelper extends base
{
    // -----
    // These constants are used for the setting of the COWOA_account field of the customers database table.
    //
    const ACCOUNT_TYPE_REGULAR = 0,      //-Regular account, address recorded
          ACCOUNT_TYPE_GUEST = 1,        //-Guest account, no addresses
          ACCOUNT_TYPE_NO_ADDRESS = 2;   //-Basic account, address is present but contains default values
          
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
                    ab.entry_is_temporary AS is_temporary
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
        $address_info->fields['temporary'] = false;
        
       $this->notify('NOTIFY_OPC_HELPER_INIT_ADDRESS_FROM_DB', $session_var_name, $address_info->fields);
        
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
            'temporary' => true
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
    
    public function validateAjaxPostedAddress($which, &$messages)
    {
        if ($which != 'bill' && $which != 'ship') {
            trigger_error("Unknown address selection ($which) received.", E_USER_ERROR);
        }
        if (!isset($this->addressValues)) {
            trigger_error("Invalid request, addressValues not set.", E_USER_ERROR);
        }
        
        $messages = $this->validateUpdatedAddress($_POST, $which, false);
        
        return (count($messages) != 0);
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
        
        return $messages;
    }
    
    public function saveCustomerAddress($which)
    {
            $sql_data_array= array(
                array('fieldName' => 'entry_firstname', 'value' => $firstname, 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_lastname', 'value' => $lastname, 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_street_address', 'value' => $street_address, 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_postcode', 'value' => $postcode, 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_city', 'value' => $city, 'type' => 'stringIgnoreNull'),
                array('fieldName' => 'entry_country_id', 'value' => $country, 'type' => 'integer')
            );

            if (ACCOUNT_GENDER == 'true') {
                $sql_data_array[] = array('fieldName' => 'entry_gender', 'value' => $gender, 'type' => 'enum:m|f');
            }
            
            if (ACCOUNT_COMPANY == 'true') {
                $company = zen_db_prepare_input($_POST['company'][$which]);
                $sql_data_array[] = array('fieldName' => 'entry_company', 'value' => $company, 'type' => 'stringIgnoreNull');
            }
            
            if (ACCOUNT_SUBURB == 'true') {
                $suburb = zen_db_prepare_input($_POST['suburb'][$which]);
                $sql_data_array[] = array('fieldName' => 'entry_suburb', 'value' => $suburb, 'type' => 'stringIgnoreNull');
            }
            
            if (ACCOUNT_STATE == 'true') {
                if ($zone_id > 0) {
                    $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => $zone_id, 'type' => 'integer');
                    $sql_data_array[] = array('fieldName' => 'entry_state', 'value'=> '', 'type' => 'stringIgnoreNull');
                } else {
                    $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => '0', 'type' => 'integer');
                    $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => $state, 'type' => 'stringIgnoreNull');
                }
            }

        if ($_POST['action'] == 'update') {
          $where_clause = "address_book_id = :edit and customers_id = :customersID";
          $where_clause = $db->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
          $where_clause = $db->bindVars($where_clause, ':edit', $_GET['edit'], 'integer');
          $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', $where_clause);

          $zco_notifier->notify('NOTIFY_MODULE_ADDRESS_BOOK_UPDATED_ADDRESS_BOOK_RECORD', array_merge(array('address_book_id' => $_GET['edit'], 'customers_id' => $_SESSION['customer_id']), $sql_data_array));

          // re-register session variables
          if ( (isset($_POST['primary']) && ($_POST['primary'] == 'on')) || ($_GET['edit'] == $_SESSION['customer_default_address_id']) ) {
            $_SESSION['customer_first_name'] = $firstname;
            $_SESSION['customer_last_name'] = $lastname;
            $_SESSION['customer_country_id'] = $country;
            $_SESSION['customer_zone_id'] = (($zone_id > 0) ? (int)$zone_id : '0');
            $_SESSION['customer_default_address_id'] = (int)$_GET['edit'];

            $sql_data_array = array(array('fieldName'=>'customers_firstname', 'value'=>$firstname, 'type'=>'stringIgnoreNull'),
                                    array('fieldName'=>'customers_lastname', 'value'=>$lastname, 'type'=>'stringIgnoreNull'),
                                    array('fieldName'=>'customers_default_address_id', 'value'=>$_GET['edit'], 'type'=>'integer'));

            if (ACCOUNT_GENDER == 'true') $sql_data_array[] = array('fieldName'=>'customers_gender', 'value'=>$gender, 'type'=>'enum:m|f');
            $where_clause = "customers_id = :customersID";
            $where_clause = $db->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
            $db->perform(TABLE_CUSTOMERS, $sql_data_array, 'update', $where_clause);
            $zco_notifier->notify('NOTIFY_MODULE_ADDRESS_BOOK_UPDATED_CUSTOMER_RECORD', array_merge(array('customers_id' => $_SESSION['customer_id']), $sql_data_array));
          }
        } else {

          $sql_data_array[] = array('fieldName'=>'customers_id', 'value'=>$_SESSION['customer_id'], 'type'=>'integer');
          $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array);

          $new_address_book_id = $db->Insert_ID();
          $zco_notifier->notify('NOTIFY_MODULE_ADDRESS_BOOK_ADDED_ADDRESS_BOOK_RECORD', array_merge(array('address_id' => $new_address_book_id), $sql_data_array));


          // register session variables
          if (isset($_POST['primary']) && ($_POST['primary'] == 'on')) {
            $_SESSION['customer_first_name'] = $firstname;
            $_SESSION['customer_last_name'] = $lastname;
            $_SESSION['customer_country_id'] = $country;
            $_SESSION['customer_zone_id'] = (($zone_id > 0) ? (int)$zone_id : '0');
            //if (isset($_POST['primary']) && ($_POST['primary'] == 'on'))
            $_SESSION['customer_default_address_id'] = $new_address_book_id;

            $sql_data_array = array(array('fieldName'=>'customers_firstname', 'value'=>$firstname, 'type'=>'stringIgnoreNull'),
                                    array('fieldName'=>'customers_lastname', 'value'=>$lastname, 'type'=>'stringIgnoreNull'));

            if (ACCOUNT_GENDER == 'true') $sql_data_array[] = array('fieldName'=>'customers_gender', 'value'=>$gender, 'type'=>'stringIgnoreNull');
            //if (isset($_POST['primary']) && ($_POST['primary'] == 'on'))
            $sql_data_array[] = array('fieldName'=>'customers_default_address_id', 'value'=>$new_address_book_id, 'type'=>'integer');

            $where_clause = "customers_id = :customersID";
            $where_clause = $db->bindVars($where_clause, ':customersID', $_SESSION['customer_id'], 'integer');
            $db->perform(TABLE_CUSTOMERS, $sql_data_array, 'update', $where_clause);
            $zco_notifier->notify('NOTIFY_MODULE_ADDRESS_BOOK_UPDATED_PRIMARY_CUSTOMER_RECORD', array_merge(array('address_id' => $new_address_book_id, 'customers_id' => $_SESSION['customer_id']), $sql_data_array));
          }
        }
    }
}
