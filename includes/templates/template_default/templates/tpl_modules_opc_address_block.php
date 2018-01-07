<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This module is included by tpl_modules_opc_billing_address.php and tpl_modules_opc_shipping_address.php and
// provides a common-formatting for those two address-blocks.
//
?>
<!--bof address block -->
<?php
// -----
// Sanitize module input values.
//
if (!isset($opc_address_type) || !in_array($opc_address_type, array('bill', 'ship'))) {
    trigger_error("Unknown value for opc_address_type ($opc_address_type).", E_USER_ERROR);
}

// -----
// If the account-bearing customer has previously-defined addresses, create a dropdown list
// from which they can select.
//
$address_selections = $_SESSION['opc']->formatAddressBookDropdown();
if (count($address_selections) != 0) {
?>
    <div id="choices-<?php echo $opc_address_type; ?>"><?php echo zen_draw_pull_down_menu("address-$opc_address_type", $address_selections, 0); ?></div>
<?php
}

// -----
// Start address formatting ...
//
$address_values = $_SESSION['opc']->getAddressValues($opc_address_type);
if (ACCOUNT_GENDER == 'true') {
    $field_name = "gender[$opc_address_type]";
    $male_id = "gender-male-$opc_address_type";
    $female_id = "gender-female-$opc_address_type";
    echo zen_draw_radio_field ($field_name, 'm', ($address_values['gender'] == 'm'), "id=\"$male_id\"") . 
    "<label class=\"radioButtonLabel\" for=\"$male_id\">" . MALE . '</label>' . 
    zen_draw_radio_field ($field_name, 'f', ($address_values['gender'] == 'f'), "id=\"$female_id\"") . 
    "<label class=\"radioButtonLabel\" for=\"$female_id\">" . FEMALE . '</label>' . 
    (zen_not_null(ENTRY_GENDER_TEXT) ? '<span class="alert">' . ENTRY_GENDER_TEXT . '</span>': ''); 
?>
      <br class="clearBoth" />
<?php
}

$field_name = "firstname[$opc_address_type]";
$field_id = "firstname-$opc_address_type";
?>
      <label class="inputLabel" for="<?php echo $field_id; ?>"><?php echo ENTRY_FIRST_NAME; ?></label>
      <?php echo zen_draw_input_field ($field_name, $address_values['firstname'], zen_set_field_length(TABLE_CUSTOMERS, 'customers_firstname', '40') . " id=\"$field_id\"") . 
      (zen_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="alert">' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />
<?php
$field_name = "lastname[$opc_address_type]";
$field_id = "lastname-$opc_address_type";
?>

      <label class="inputLabel" for="<?php echo $field_id; ?>"><?php echo ENTRY_LAST_NAME; ?></label>
      <?php echo zen_draw_input_field($field_name, $address_values['lastname'], zen_set_field_length(TABLE_CUSTOMERS, 'customers_lastname', '40') . " id=\"$field_id\"") . 
      (zen_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="alert">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />
<?php
if (ACCOUNT_COMPANY == 'true') {
    $field_name = "company[$opc_address_type]";
    $field_id = "company-$opc_address_type";
?>
      <label class="inputLabel" for="<?php echo $field_id; ?>"><?php echo ENTRY_COMPANY; ?></label>
      <?php echo zen_draw_input_field($field_name, $address_values['company'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_company', '40') . " id=\"$field_id\"") . 
      (zen_not_null(ENTRY_COMPANY_TEXT) ? '<span class="alert">' . ENTRY_COMPANY_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />
<?php
}

$field_name = "street_address[$opc_address_type]";
$field_id = "street-address-$opc_address_type";
?>
      <label class="inputLabel" for="<?php echo $field_id; ?>"><?php echo ENTRY_STREET_ADDRESS; ?></label>
        <?php echo zen_draw_input_field($field_name, $address_values['street_address'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_street_address', '40') . " id=\"$field_id\"") . 
        (zen_not_null (ENTRY_STREET_ADDRESS_TEXT) ? '<span class="alert">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />

<?php
if (ACCOUNT_SUBURB == 'true') {
    $field_name = "suburb[$opc_address_type]";
    $field_id = "suburb-$opc_address_type";
?>
      <label class="inputLabel" for="<?php echo $field_id; ?>"><?php echo ENTRY_SUBURB; ?></label>
      <?php echo zen_draw_input_field($field_name, $address_values['suburb'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_suburb', '40') . " id=\"$field_id\"") . 
      (zen_not_null(ENTRY_SUBURB_TEXT) ? '<span class="alert">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />
<?php
}

$field_name = "city[$opc_address_type]";
$field_id = "city-$opc_address_type";
?>     
      <label class="inputLabel" for="<?php echo $field_id; ?>"><?php echo ENTRY_CITY; ?></label>
      <?php echo zen_draw_input_field($field_name, $address_values['city'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_city', '40') . " id=\"$field_id\"") . 
      (zen_not_null(ENTRY_CITY_TEXT) ? '<span class="alert">' . ENTRY_CITY_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />

<?php
if (ACCOUNT_STATE == 'true') {
    $state_zone_id = "stateZone-$opc_address_type";
    $zone_label_id = "zoneLabel-$opc_address_type";
    $zone_field_name = "zone_id[$opc_address_type]";
    $break_id = "stBreak-$opc_address_type";
    $state_field_name = "state[$opc_address_type]";
    $state_field_id = "state-$opc_address_type";
    $state_label_id = "stateLabel-$opc_address_type";
    $state_text_id = "stText-$opc_address_type";
    
    if ($opc_flag_show_pulldown_states) {
?>
      <label class="inputLabel" for="<?php echo $state_zone_id; ?>" id="<?php echo $zone_label_id; ?>"><?php echo ENTRY_STATE; ?></label>
<?php
        echo zen_draw_pull_down_menu($zone_field_name, zen_prepare_country_zones_pull_down($address_values['country'], $address_values['zone_id']), $address_values['zone_id'], "id=\"$state_zone_id\"");
        if (zen_not_null(ENTRY_STATE_TEXT)) {
            echo '&nbsp;<span class="alert">' . ENTRY_STATE_TEXT . '</span>'; 
        }
?>
      <br class="clearBoth" id="<?php echo $break_id; ?>" />
<?php    
    }
?>
      <label class="inputLabel" for="<?php echo $state_field_id; ?>" id="<?php echo $state_label_id; ?>"><?php echo $address_values['state_field_label']; ?></label>
<?php
    echo zen_draw_input_field($state_field_name, $address_values['state'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_state', '40') . " id=\"$state_field_id\"");
    if (zen_not_null(ENTRY_STATE_TEXT)) {
        echo "&nbsp;<span class=\"alert\" id=\"$state_text_id\">" . ENTRY_STATE_TEXT . '</span>';
    }
    if (!$address_values['show_pulldown_states']) {
        echo zen_draw_hidden_field($zone_field_name, $address_values['zone_name'], "id=\"$state_zone_id\"");
    }
?>
      <br class="clearBoth" />
<?php
}

$field_name = "postcode[$opc_address_type]";
$field_id = "postcode-$opc_address_type";
?>
      <label class="inputLabel" for="<?php echo $field_id; ?>"><?php echo ENTRY_POST_CODE; ?></label>
      <?php echo zen_draw_input_field($field_name, $address_values['postcode'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_postcode', '40') . " id=\"$field_id\"") . 
      (zen_not_null (ENTRY_POST_CODE_TEXT) ? '<span class="alert">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />
<?php
$field_name = "zone_country_id[$opc_address_type]";
$field_id = "country-$opc_address_type";
?>
      <label class="inputLabel" for="country-bill"><?php echo ENTRY_COUNTRY; ?></label>
      <?php echo zen_get_country_list($field_name, $address_values['country'], "id=\"$field_id\" " . ($address_values['show_pulldown_states'] ? ('onchange="update_zone(this.form, \'' . $opc_address_type . '\');"') : '')) . 
      (zen_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="alert">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?>
      <br class="clearBoth" />
      
      <div id="messages-<?php echo $opc_address_type; ?>"></div>
<!--eof address block -->
