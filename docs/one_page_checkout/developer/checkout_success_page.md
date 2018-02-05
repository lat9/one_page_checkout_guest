# One-Page Checkout v2.0.0 #

This documentation contains implementation details associated with the *guest-checkout* and *registered-account* features introduced by v2.0.0 of ***One-Page Checkout*** (*OPC*) and augments the information already present in the plugin's readme. The roots of OPC's *guest-checkout* and *registered-account* processing (*OPC-GC/RA*) are found in the [COWOA](https://www.zen-cart.com/showthread.php?196995-COWOA-%28for-ZC-v1-5-x%29) (by @DivaVocals, @countrycharm and others) and [COWAA](https://www.zen-cart.com/downloads.php?do=file&id=2131) (by @davewest) plugins &hellip; but the implementation is quite different.

## Overview ##

*OPC-GC/RA* ***does not*** create `customers` database records for its processing; address- and contact-information for a guest-placed order is recorded *only in that order*.  Instead, its admin-initialization script creates "dummy" records that identify a guest-customer (in the `customers` and `customers_info` tables) as well as "dummy" records that identify a temporary billing- and shipping-address (in the `address_book` table).

Unlike its predecessors, here is no `no_account` page provided for *OPC-GC*.  Instead, an alternate template-display for the store's `login` page begins the guest's checkout and the guest-customer's contact information is gathered as a first step in the *OPC* checkout process.

*OPC-GC/RA* modifies the flow of that and other pages within the store when its features are enabled:

Page Name | Modifications
-------------  | -------------
address_book | Recognizes when a customer-account does not yet have a defined primary address (i.e. the customer has registered for an account).
checkout_success | Recognizes when an order has been placed by a guest.  The guest has the opportunity to "convert" to a fully-registered account by supplying an account password.
create_account | Displays a modified form for entry, requiring only the non-address-related elements for the customer.
create_account_success | Displays a modified version of the page when the just-created account is for a registered-account only.
download | Provides the order-lookup by order-id and email-address, enabling guest customers to download their purchases.
login | Displays an alternate form of the page when either the OPC's guest-checkout or registered-accounts processing is enabled.


### What are *Registered Accounts*? ###

Within the OPC's processing, a *registered account* is associated with a customer who has currently chosen not to provide address-related information.  This allows a store to accept *minimal* customer accounts, making it easier for customers to sign up for newsletters and other account-only store features.

When a registered-account holder goes through the checkout process, they'll be asked to supply that address information.

### Implementation Notes ###

*OPC*'s storefront guest-checkout and registered-accounts handling is controlled by two class modules:

1. `/includes/classes/OnePageCheckout.php`
2. `/includes/classes/observers/class.checkout_one_observer.php`

The main class-file is session-based &mdash; instantiated as `$_SESSION['opc']` &mdash; so that it can remember a customer from `login` through `checkout`; its observer-class loads fresh on each page-load. This allows the observer-class to act as a *conductor*, instructing the session-based processing what to do next based on current customer action; it also acts to "refresh" the session-based settings, just in case an admin configuration occurred during the customer's session.

## Storefront Considerations ##

[Customer-Address Management](address_management.md)

## Admin Considerations ##

[Identifying Orders Placed by Guests](admin_orders_configuration.md)