Payrexx for Commerce
--------------------

This repository contains an implementation of the [Payrexx](https://payrexx.com) payment platform for [Commerce](https://modmore.com/commerce/), the powerful e-commerce platform for MODX Revolution.

Payrexx offers a wide range of supported payment options in a single implementation, with a primary focus on merchants from Europe. Many local payment option types are supported, either directly or through a third-party payment processor that can be linked with Payrexx.

_Special thanks to Visions for commission this payment gateway._

## Requirements

- MODX 2.8+
- Commerce 1.2+
- PHP 7.4+
- An active Payrexx account.  If you do not yet have a Payrexx account, please [use our partner link to signup](). That gives us a referral fee at no cost to you, which will help us maintain and support the intregation.

## Usage

After installing the extension in your MODX site from modmore.com, navigate to Commerce > Configuration > Modules and enable the Payrexx module to register the gateway.

Next, under Configuration > Payment Methods, add a new payment method and choose Payrexx as the gateway type.

On the Payrexx tab, enter the **Instance** and the **API Key**.

- The **Instance** is your unique subdomain name. If you log in to Payrexx at `https://modmore.payrexx.com/`, your instance is `modmore`.
- The **API Key** can be created in the Payrexx dashboard, under Integrations > API & Plugins.

Save the payment method, and open the Payrexx tab again. You should now see the note that Payrexx is successfully connected.

You can now also choose the Payment Service Provider to use, as well as the Look & Feel profile. If you leave Payment Service Provider empty, you'll still be able to accept payments, but the customer may need to click more often to get to their desired payment option.

Look & Feel profiles are managed in the Payrexx dashboard, under Admin > Settings > Look & Feel.
