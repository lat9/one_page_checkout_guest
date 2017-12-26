<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<script type="text/javascript"><!--
var selected;
var form = '';
var submitted = false;
var error = false;
var error_message = '';

function check_input(field_name, field_size, message) 
{
    if (form.elements[field_name] && (form.elements[field_name].type != "hidden")) {
        if (field_size == 0) return;
        var field_value = form.elements[field_name].value;

        if (field_value == '' || field_value.length < field_size) {
            error_message = error_message + "* " + message + "\n";
            error = true;
        }
    }
}

function check_radio(field_name, message) 
{
    var isChecked = false;

    if (form.elements[field_name] && (form.elements[field_name].type != "hidden")) {
        var radio = form.elements[field_name];

        for (var i=0; i<radio.length; i++) {
            if (radio[i].checked == true) {
                isChecked = true;
                break;
            }
        }

        if (isChecked == false) {
            error_message = error_message + "* " + message + "\n";
            error = true;
        }
    }
}

function check_select(field_name, field_default, message) 
{
    if (form.elements[field_name] && (form.elements[field_name].type != "hidden")) {
        var field_value = form.elements[field_name].value;

        if (field_value == field_default) {
            error_message = error_message + "* " + message + "\n";
            error = true;
        }
    }
}

function check_confirmed_field(field_name_1, field_name_2, field_size, message_1, message_2) 
{
    if (form.elements[field_name_1] && (form.elements[field_name_1].type != "hidden")) {
        var main_field = form.elements[field_name_1].value;
        var confirmation = form.elements[field_name_2].value;

        if (main_field == '' || main_field.length < field_size) {
            error_message = error_message + "* " + message_1 + "\n";
            error = true;
        } else if (main_field != confirmation) {
            error_message = error_message + "* " + message_2 + "\n";
            error = true;
        }
    }
}

function check_form(form_name) 
{
    if (submitted == true) {
        alert("<?php echo JS_ERROR_SUBMITTED; ?>");
        return false;
    }

    error = false;
    form = form_name;
    error_message = "<?php echo JS_ERROR; ?>";

<?php 
if (ACCOUNT_GENDER == 'true') {
    echo '  check_radio("gender", "' . ENTRY_GENDER_ERROR . '");' . PHP_EOL;
}

if ((int)ENTRY_FIRST_NAME_MIN_LENGTH > 0) { 
?>
    check_input("firstname", <?php echo (int)ENTRY_FIRST_NAME_MIN_LENGTH; ?>, "<?php echo ENTRY_FIRST_NAME_ERROR; ?>");
<?php 
} 

if ((int)ENTRY_LAST_NAME_MIN_LENGTH > 0) { 
?>
    check_input("lastname", <?php echo (int)ENTRY_LAST_NAME_MIN_LENGTH; ?>, "<?php echo ENTRY_LAST_NAME_ERROR; ?>");
<?php 
}

if (ACCOUNT_DOB == 'true' && (int)ENTRY_DOB_MIN_LENGTH != 0) {
    echo '  check_input("dob", ' . (int)ENTRY_DOB_MIN_LENGTH . ', "' . ENTRY_DATE_OF_BIRTH_ERROR . '");' . "\n"; 
}

if (ACCOUNT_COMPANY == 'true' && (int)ENTRY_COMPANY_MIN_LENGTH != 0) {
    echo '  check_input("company", ' . (int)ENTRY_COMPANY_MIN_LENGTH . ', "' . ENTRY_COMPANY_ERROR . '");' . "\n";
}

if ((int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH > 0) {
?>
    check_confirmed_field('email_address', 'email_address_confirm', <?php echo (int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?>, '<?php echo ENTRY_EMAIL_ADDRESS_ERROR; ?>', '<?php echo ENTRY_EMAIL_ERROR_NOT_MATCHING; ?>');
<?php 
}

if ((int)ENTRY_TELEPHONE_MIN_LENGTH > 0) { 
?>
    check_input("telephone", <?php echo ENTRY_TELEPHONE_MIN_LENGTH; ?>, "<?php echo ENTRY_TELEPHONE_NUMBER_ERROR; ?>");
<?php 
}

if ((int)ENTRY_PASSWORD_MIN_LENGTH > 0) { 
?>
    check_confirmed_field('password', 'confirmation', <?php echo (int)ENTRY_PASSWORD_MIN_LENGTH; ?>, '<?php echo ENTRY_PASSWORD_ERROR; ?>', '<?php echo ENTRY_PASSWORD_ERROR_NOT_MATCHING; ?>');
<?php 
} 
?>
    if (error == true) {
        alert(error_message);
        return false;
    } else {
        submitted = true;
        return true;
    }
}
//--></script>
