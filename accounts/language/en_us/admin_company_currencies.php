<?php
/**
 * Language definitions for the Admin Company Currency settings controller/views
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminCompanyCurrencies.!success.setup_updated'] = "The Currency Setup settings were successfully updated!";
$lang['AdminCompanyCurrencies.!success.add_created'] = "The %1\$s currency was successfully created!"; // %1$s is the currency code
$lang['AdminCompanyCurrencies.!success.edit_updated'] = "The %1\$s currency was successfully updated!"; // %1$s is the currency code
$lang['AdminCompanyCurrencies.!success.delete_deleted'] = "The %1\$s currency was successfully deleted!"; // %1$s is the currency code
$lang['AdminCompanyCurrencies.!success.rates_updated'] = "The exchange rates were successfully updated.";


// Cancel button
$lang['AdminCompanyCurrencies.!cancel.field_cancel'] = "Cancel";


// Currency Setup
$lang['AdminCompanyCurrencies.setup.page_title'] = "Settings > Company > Currencies > Currency Setup";
$lang['AdminCompanyCurrencies.setup.tooltip_currency_pricing'] = "This option requires package pricing to be set in the specified currency to be available for creating new services. If it is unchecked and package pricing does not exist for the currency, the price will be calculated based on the exchange rate from the default currency.

Renewing services always prefer package pricing in the chosen currency, but will use the exchange rate from the default currency if not specified for the package.";

$lang['AdminCompanyCurrencies.setup.boxtitle_setup'] = "Currency Setup";

$lang['AdminCompanyCurrencies.setup.heading_general'] = "General";

$lang['AdminCompanyCurrencies.setup.field_default_currency'] = "Default Currency";
$lang['AdminCompanyCurrencies.setup.field_show_currency_code'] = "Show Currency Code";
$lang['AdminCompanyCurrencies.setup.field_client_set_currency'] = "Allow Client to Set Currency";

$lang['AdminCompanyCurrencies.setup.heading_multicurrency'] = "Multi-Currency";

$lang['AdminCompanyCurrencies.setup.field_multi_currency_package'] = "Use Package Pricing for New Services Only";
$lang['AdminCompanyCurrencies.setup.field_exchange_rates_auto_update'] = "Automatically Update Exchange Rates";
$lang['AdminCompanyCurrencies.setup.field_last_updated'] = "Rates Last Updated";
$lang['AdminCompanyCurrencies.setup.field_exchange_rates_padding'] = "Pad Exchange Rates";
$lang['AdminCompanyCurrencies.setup.field_exchange_rates_processor'] = "Exchange Rates Processor";
$lang['AdminCompanyCurrencies.setup.field_setupsubmit'] = "Update Settings";

$lang['AdminCompanyCurrencies.setup.no_exchange_updated'] = "Never";
$lang['AdminCompanyCurrencies.setup.open_parenthesis'] = "(";
$lang['AdminCompanyCurrencies.setup.closed_parenthesis'] = ")";


// Active Currencies
$lang['AdminCompanyCurrencies.active.page_title'] = "Settings > Company > Currencies > Active Currencies";
$lang['AdminCompanyCurrencies.active.boxtitle_active'] = "Active Currencies";

$lang['AdminCompanyCurrencies.active.categorylink_addcurrency'] = "Add Currency";
$lang['AdminCompanyCurrencies.active.no_results'] = "There are no active currencies.";

$lang['AdminCompanyCurrencies.active.text_currency_code'] = "Currency Code (ISO 4217)";
$lang['AdminCompanyCurrencies.active.text_format'] = "Format";
$lang['AdminCompanyCurrencies.active.text_exchange_rate'] = "Exchange Rate";
$lang['AdminCompanyCurrencies.active.text_exchange_updated'] = "Last Updated";
$lang['AdminCompanyCurrencies.active.text_options'] = "Options";

$lang['AdminCompanyCurrencies.active.option_edit'] = "Edit";
$lang['AdminCompanyCurrencies.active.option_delete'] = "Delete";

$lang['AdminCompanyCurrencies.active.confirm_delete'] = "Are you sure you want to delete this currency?";

$lang['AdminCompanyCurrencies.active.no_exchange_updated'] = "Never";


// Add Currency
$lang['AdminCompanyCurrencies.add.page_title'] = "Settings > Company > Currencies > Add Currency";
$lang['AdminCompanyCurrencies.add.boxtitle_add'] = "Add Currency";

$lang['AdminCompanyCurrencies.add.field_code'] = "Currency Code (ISO 4217)";
$lang['AdminCompanyCurrencies.add.field_format'] = "Format";
$lang['AdminCompanyCurrencies.add.field_prefix'] = "Prefix Symbol";
$lang['AdminCompanyCurrencies.add.field_suffix'] = "Suffix Symbol";
$lang['AdminCompanyCurrencies.add.field_exchange_rate'] = "Exchange Rate";
$lang['AdminCompanyCurrencies.add.field_addsubmit'] = "Create Currency";

$lang['AdminCompanyCurrencies.add.confirm_add'] = "Are you sure you want to create this currency? This currency's exchange rate will be overwritten when exchange rates are automatically updated in the system. You may disable automatic updates under [Settings] > [Company] > [Currencies] > [Currency Setup].";


// Edit Currency
$lang['AdminCompanyCurrencies.edit.page_title'] = "Settings > Company > Currencies > Edit Currency";
$lang['AdminCompanyCurrencies.edit.boxtitle_edit'] = "Edit Currency";

$lang['AdminCompanyCurrencies.edit.field_code'] = "Currency Code (ISO 4217)";
$lang['AdminCompanyCurrencies.edit.field_format'] = "Format";
$lang['AdminCompanyCurrencies.edit.field_prefix'] = "Prefix Symbol";
$lang['AdminCompanyCurrencies.edit.field_suffix'] = "Suffix Symbol";
$lang['AdminCompanyCurrencies.edit.field_exchange_rate'] = "Exchange Rate";
$lang['AdminCompanyCurrencies.edit.field_editsubmit'] = "Edit Currency";

$lang['AdminCompanyCurrencies.edit.confirm_edit'] = "Are you sure you want to update this currency? This currency's exchange rate will be overwritten when exchange rates are automatically updated in the system. You may disable automatic updates under [Settings] > [Company] > [Currencies] > [Currency Setup].";
?>