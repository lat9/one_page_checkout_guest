# Integration with pre-Zen Cart 1.5.6 Stores #
For full integration with a pre-Zen Cart 1.5.6 store's admin, there are two (2) edits that you'll need to make to the store's `/YOUR_ADMIN/orders.php` if you want to have indicators on the orders' listing to identify orders placed by guests.  That module is very "volatile" (i.e. tends to change with each Zen Cart version), and the *OPC* plugin will not distribute version(s) of that module for that reason.

Find:
```php
           <tr>
             <td class="smallText"><?php echo TEXT_LEGEND . ' ' . zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . ' ' . TEXT_BILLING_SHIPPING_MISMATCH; ?>
           </td>
```

... and make the changes below:

```php
<?php
//-bof-one_page_checkout-lat9-Additional notifiers to enable additional order status-icons.  *** 1 of 2 ***
    $additional_legend_icons = '';
    $zco_notifier->notify('NOTIFY_ADMIN_ORDERS_ADD_LEGEND', '', $additional_legend_icons);
?>
          <tr>
            <td class="smallText"><?php echo TEXT_LEGEND . ' ' . zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . ' ' . TEXT_BILLING_SHIPPING_MISMATCH . $additional_legend_icons; ?>
          </tr>
<?php
//-eof-one_page_checkout-lat9-Additional notifiers to enable additional order status-icons.  *** 1 of 2 ***
?>
```
Next, find:
```php

      $show_difference = '';
      if ((strtoupper($orders->fields['delivery_name']) != strtoupper($orders->fields['billing_name']) and trim($orders->fields['delivery_name']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
      if ((strtoupper($orders->fields['delivery_street_address']) != strtoupper($orders->fields['billing_street_address']) and trim($orders->fields['delivery_street_address']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
```
... and make the change below:
```php

      $show_difference = '';
      if ((strtoupper($orders->fields['delivery_name']) != strtoupper($orders->fields['billing_name']) and trim($orders->fields['delivery_name']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
      if ((strtoupper($orders->fields['delivery_street_address']) != strtoupper($orders->fields['billing_street_address']) and trim($orders->fields['delivery_street_address']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
      
//-bof-one_page_checkout-lat9-Additional "difference" icons added on a per-order basis.  *** 2 of 2 ***
      $zco_notifier->notify('NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE', $orders->fields, $show_difference);
//-eof-one_page_checkout-lat9-Additional "difference" icons added on a per-order basis.  *** 2 of 2 ***
```
Those edits, adding two notifiers to the store's `/YOUR_ADMIN/orders.php` will enable the store to include icons that identify that an order was placed via guest-checkout.