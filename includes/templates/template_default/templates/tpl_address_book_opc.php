<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This template is loaded for the address_book page by /includes/modules/pages/address_book/main_template_vars.php
// when the One-Page Checkout plugin is installed.
//
?>
<div class="centerColumn" id="addressBookDefault">

    <h1 id="addressBookDefaultHeading"><?php echo HEADING_TITLE; ?></h1>
 
<?php 
if ($messageStack->size('addressbook') > 0) {
    echo $messageStack->output('addressbook');
}

if ($no_registered_addresses) {
?>
    <div id="addressBookNoPrimary"><?php echo TEXT_NO_ADDRESSES; ?></div>
<?php
} else {
?>     
    <h2 id="addressBookDefaultPrimary"><?php echo PRIMARY_ADDRESS_TITLE; ?></h2>
    <address class="back"><?php echo zen_address_label($_SESSION['customer_id'], $_SESSION['customer_default_address_id'], true, ' ', '<br />'); ?></address>
    <div class="instructions"><?php echo PRIMARY_ADDRESS_DESCRIPTION; ?></div>
    <br class="clearBoth" />

    <fieldset>
        <legend><?php echo ADDRESS_BOOK_TITLE; ?></legend>
        <div class="alert forward"><?php echo sprintf(TEXT_MAXIMUM_ENTRIES, MAX_ADDRESS_BOOK_ENTRIES); ?></div>
        <br class="clearBoth" />
<?php
}
/**
 * Used to loop thru and display address book entries
 */
$edit_button = zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT);
$delete_button = zen_image_button(BUTTON_IMAGE_DELETE_SMALL, BUTTON_DELETE_SMALL_ALT);
foreach ($addressArray as $addresses) {
    $address_book_id = $addresses['address_book_id'];
    $text_primary = ($address_book_id != $_SESSION['customer_default_address_id']) ? '' : '&nbsp;' . PRIMARY_ADDRESS;
    $edit_link = zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, "edit=$address_book_id", 'SSL');
    $delete_link = zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, "delete=$address_book_id", 'SSL');
?>
        <h3 class="addressBookDefaultName"><?php echo zen_output_string_protected($addresses['firstname'] . ' ' . $addresses['lastname']) . $text_primary; ?></h3>

        <address><?php echo zen_address_format($addresses['format_id'], $addresses['address'], true, ' ', '<br />'); ?></address>

        <div class="buttonRow forward">
            <a href="<?php echo $edit_link; ?>"><?php echo $edit_button; ?></a>&nbsp;
            <a href="<?php echo $delete_link; ?>"><?php echo $delete_button; ?></a>
        </div>
        <br class="clearBoth" />
<?php
}
?>
    </fieldset>

<?php
if ($enable_add_address) {
?>
    <div class="buttonRow forward">
        <a href="<?php echo zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, '', 'SSL'); ?>"><?php echo zen_image_button(BUTTON_IMAGE_ADD_ADDRESS, BUTTON_ADD_ADDRESS_ALT); ?></a>
    </div>
<?php
}
?>
    <div class="buttonRow back">
        <a href="<?php echo zen_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>"><?php echo zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT); ?></a>
    </div>
    <br class="clearBoth" />
</div>
