<?php

$_lang['commerce_payrexx'] = 'Payrexx Gateway';
$_lang['commerce_payrexx.description'] = 'Adds the Payrexx payment provider to Commerce. After enabling the module, you can configure the gateway under Configuration > Payment Methods.';
$_lang['commerce_payrexx.design'] = 'Design (look & feel)';
$_lang['commerce_payrexx.design_desc'] = 'The design to use in the hosted payment page. Different designs can be created in the Payrexx dashboard under Configuration > Look & Feel.';
$_lang['commerce_payrexx.default'] = '(default)';
$_lang['commerce_payrexx.instance'] = 'Instance';
$_lang['commerce_payrexx.instance_desc'] = 'The instance is the unique subdomain you login to Payrexx with, without payrexx.com or your platform domain. For example, if you sign in to Payrexx at <code>https://myaccount.payrexx.com/</code>, your instance is <code>myaccount</code>.';
$_lang['commerce_payrexx.apibase'] = 'Base Domain';
$_lang['commerce_payrexx.apibase_desc'] = 'When using a standard Payrexx account, leave this set to the default of <code>payrexx.com</code>. When using a platform account, provide your custom Payrexx domain.';
$_lang['commerce_payrexx.apikey'] = 'API Key';
$_lang['commerce_payrexx.apikey_desc'] = 'Add an API Key to your account by logging in to the Payrexx dashboard, and navigating to Integrations > API & Plugins.';
$_lang['commerce_payrexx.connection'] = 'Connection';
$_lang['commerce_payrexx.connection_desc'] = 'Enter an instance and API Key, and then save the payment method to check your connection to Payrexx.';
$_lang['commerce_payrexx.connection_success'] = '✔ Successfully connected';
$_lang['commerce_payrexx.webhook'] = 'Webhook';
$_lang['commerce_payrexx.webhook_desc'] = 'Using the webhook is recommended <b>on Commerce 1.3+</b> to ensure payment status updates are pushed to Commerce regardless of the customers\' return to the checkout. On older versions, enabling the webhook may result in double processing.<br><br>Based on your current configuration, the field above contains the webhook URL you would need to configure in the Payrexx dashboard, under Integrations > Webhooks.<br><br>When adding the webhook, enable only <code>Transaction</code> events end set the Webhook Type to <code>Normal (PHP-Post)</code>. Use the latest Webhook Version unless otherwise instructed. Note that the <code>Send Test Data</code> button will return a 400 error with response <em>Unable to identify transaction</em>; that is expected.';
$_lang['commerce_payrexx.restrict_to_instance'] = 'Restrict to instance';
$_lang['commerce_payrexx.restrict_to_instance_desc'] = 'When enabled, you must add an <a href="https://docs.modmore.com/en/Commerce/v1/Orders/Custom_Fields.html" target="_blank" rel="noopener">Order Field</a> to each order with name <code>payrexx_instance</code>. The value of the order field must match the Instance configured on the payment method. If the field is not set, or if the value does not match, this payment method will not be available in the checkout. You can use this when implementing with the Payrexx Platform; configure one payment method for each merchant and provide the instance when creating the order to determine the payment account.';

$_lang['commerce.payrexx_instance'] = 'Payrexx Platform Instance';
$_lang['commerce.payrexx_psp'] = 'Payment Provider';
$_lang['commerce.payrexx_pm'] = 'Payment Method(s)';
$_lang['commerce.payrexx_transaction_0'] = 'Transaction';
$_lang['commerce.payrexx_transaction_0_psp'] = 'Transaction: PSP';
$_lang['commerce.payrexx_transaction_0_amount'] = 'Transaction: Amount';
$_lang['commerce.payrexx_transaction_0_fee'] = 'Transaction: Payrexx Fee';
$_lang['commerce.payrexx_transaction_0_brand'] = 'Transaction: Brand';
$_lang['commerce.payrexx_transaction_0_status'] = 'Transaction: Status';
$_lang['commerce.payrexx_transaction_1'] = 'Transaction 2';
$_lang['commerce.payrexx_transaction_1_psp'] = 'Transaction 2: PSP';
$_lang['commerce.payrexx_transaction_1_amount'] = 'Transaction 2: Amount';
$_lang['commerce.payrexx_transaction_1_fee'] = 'Transaction 2: Payrexx Fee';
$_lang['commerce.payrexx_transaction_1_brand'] = 'Transaction 2: Brand';
$_lang['commerce.payrexx_transaction_1_status'] = 'Transaction 2: Status';
