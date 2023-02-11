<?php


namespace modmore\Commerce_Payrexx\Gateways;

use Commerce;
use comOrder;
use comPaymentMethod;
use comTransaction;
use Exception;
use modmore\Commerce\Adapter\AdapterInterface;
use modmore\Commerce\Admin\Widgets\Form\DescriptionField;
use modmore\Commerce\Admin\Widgets\Form\PasswordField;
use modmore\Commerce\Admin\Widgets\Form\TextField;
use modmore\Commerce\Admin\Widgets\Form\Validation\Required;
use modmore\Commerce\Gateways\Exceptions\TransactionException;
use modmore\Commerce\Gateways\Helpers\GatewayHelper;
use modmore\Commerce\Gateways\Interfaces\GatewayInterface;
use modmore\Commerce\Gateways\Interfaces\SharedWebhookGatewayInterface;
use modmore\Commerce\Gateways\Interfaces\WebhookGatewayInterface;
use Payrexx\Models\Request\PaymentProvider;
use Payrexx\Models\Request\SignatureCheck;
use Payrexx\Payrexx;
use Payrexx\PayrexxException;

class Gateway implements GatewayInterface, WebhookGatewayInterface, SharedWebhookGatewayInterface
{
    private Commerce $commerce;
    private comPaymentMethod $method;
    private AdapterInterface $adapter;

    public function __construct(Commerce $commerce, comPaymentMethod $method)
    {
        $this->commerce = $commerce;
        $this->adapter = $commerce->adapter;
        $this->method = $method;
    }

    public function view(comOrder $order): string
    {
        return '';
    }

    public function submit(comTransaction $transaction, array $data): Transaction
    {
        $order = $transaction->getOrder();
        if (!$order) {
            throw new TransactionException('No order provided.');
        }

        $instance = $this->method->getProperty('instance');
        $apiKey = $this->method->getProperty('apiKey');
        if (empty($instance) || empty($apiKey)) {
            throw new TransactionException('Missing required credentials to create transaction.');
        }
        $client = new Payrexx($instance, $apiKey);

        $payment = new \Payrexx\Models\Request\Gateway();
        $payment->setAmount($transaction->get('amount')); // gateway expects cents
        $payment->setCurrency($transaction->getCurrency()->get('alpha_code'));
        // Ignore VAT
        $payment->setVatRate(null);

        $payment->setSku($order->setReference());
//        $payment->setPurpose([$order->get('reference')]);
        // @todo It looks like we may need to use the reference id for webhooks later, to match the
        // Transaction on Payrex's side to the transaction inside Commerce.
        $payment->setReferenceId($order->get('reference') . ' / ' . $transaction->get('id'));

        $payment->setSuccessRedirectUrl(GatewayHelper::getReturnUrl($transaction));
        $payment->setFailedRedirectUrl(GatewayHelper::getReturnUrl($transaction));
        $payment->setCancelRedirectUrl(GatewayHelper::getCancelUrl($transaction));

        // empty array = all available PSPs
        // @todo pre-select PSP
        $payment->setPsp([]);
        //$payment->setPsp(array(4));
        //$payment->setPm(['mastercard']);

        // Various options related to auth flows, which we don't support for now
        $payment->setPreAuthorization(false);
        $payment->setReservation(false);

        // Expiration
        $payment->setValidity(15);

        // @todo allow selecting a profile
        // $payment->setLookAndFeelProfile('144be481');

        $this->addBasket($payment, $order);

        // optional: add contact information which should be stored along with payment
        if ($billing = $order->getBillingAddress()) {
//            $payment->addField('title', 'mister');
            $firstName = $billing->get('firstname');
            $lastName = $billing->get('lastname');
            $fullName = $billing->get('fullname');
            GatewayHelper::normalizeNames($firstName, $lastName, $fullName);

            $payment->addField('forename', $firstName);
            $payment->addField('surname', $lastName);
            $payment->addField('company', $billing->get('company') ?: '');
            $payment->addField('street', $billing->get('address1') ?: '');
            $payment->addField('postcode', $billing->get('zip') ?: '');
            $payment->addField('place', $billing->get('city') ?: '');
            $payment->addField('country', $billing->get('country'));
            $payment->addField('phone', $billing->get('phone') ?: '');
            $payment->addField('email', $billing->get('email') ?: '');
        }

        $payment->setSkipResultPage(true);
/*
 *
    1 => 'Benutzerdefiniertes Feld (DE)',
    2 => 'Benutzerdefiniertes Feld (EN)',
    3 => 'Benutzerdefiniertes Feld (FR)',
    4 => 'Benutzerdefiniertes Feld (IT)',
 */
        // Store some custom info
        $payment->addField('custom_field_1', $order->get('reference'), [
            1 => 'Commerce ' . $this->adapter->lexicon('commerce.reference', [], 'de'),
            2 => 'Commerce ' . $this->adapter->lexicon('commerce.reference', [], 'en'),
            3 => 'Commerce ' . $this->adapter->lexicon('commerce.reference', [], 'fr'),
            4 => 'Commerce ' . $this->adapter->lexicon('commerce.reference', [], 'it'),
        ]);
        $payment->addField('custom_field_2', $order->get('id'), [
            1 => 'Commerce ' . $this->adapter->lexicon('commerce.order_id', [], 'de'),
            2 => 'Commerce ' . $this->adapter->lexicon('commerce.order_id', [], 'en'),
            3 => 'Commerce ' . $this->adapter->lexicon('commerce.order_id', [], 'fr'),
            4 => 'Commerce ' . $this->adapter->lexicon('commerce.order_id', [], 'it'),
        ]);
        $payment->addField('custom_field_3', $transaction->get('id'), [
            1 => 'Commerce ' . $this->adapter->lexicon('commerce.transaction', [], 'de'),
            2 => 'Commerce ' . $this->adapter->lexicon('commerce.transaction', [], 'en'),
            3 => 'Commerce ' . $this->adapter->lexicon('commerce.transaction', [], 'fr'),
            4 => 'Commerce ' . $this->adapter->lexicon('commerce.transaction', [], 'it'),
        ]);
        if ($user = $order->getUser()) {
            $payment->addField('custom_field_4', $user->get('username'), [
                1 => $this->adapter->lexicon('username', [], 'de'),
                2 => $this->adapter->lexicon('username', [], 'en'),
                3 => $this->adapter->lexicon('username', [], 'fr'),
                4 => $this->adapter->lexicon('username', [], 'it'),
            ]);
        }

        try {
            /** @var \Payrexx\Models\Response\Gateway $response */
            $response = $client->create($payment);
        } catch (PayrexxException $e) {
            throw new TransactionException(get_class($e) . ': ' . $e->getMessage());
        }

        return new Transaction($response);
    }

    public function returned(comTransaction $transaction, array $data): Transaction
    {
        $order = $transaction->getOrder();
        if (!$order) {
            throw new TransactionException('No order provided.');
        }
        $ref = $transaction->get('reference');
        if (empty($ref)) {
            throw new TransactionException('No transaction reference found.');
        }

        $instance = $this->method->getProperty('instance');
        $apiKey = $this->method->getProperty('apiKey');
        if (empty($instance) || empty($apiKey)) {
            throw new TransactionException('Missing required credentials to create transaction.');
        }
        $client = new Payrexx($instance, $apiKey);

        $payment = new \Payrexx\Models\Request\Gateway();
        $payment->setId($ref);

        try {
            /** @var \Payrexx\Models\Response\Gateway $response */
            $response = $client->getOne($payment);
        } catch (PayrexxException $e) {
            throw new TransactionException(get_class($e) . ': ' . $e->getMessage());
        }

        return new Transaction($response);
    }

    public function getGatewayProperties(comPaymentMethod $method): array
    {
        $fields = [];


        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[instance]',
            'label' => 'Instance',
            'description' => 'The instance is your Payrexx subdomain.',
            'value' => $method->getProperty('instance', ''),
            'validation' => [
                new Required(),
            ]
        ]);
        $fields[] = new PasswordField($this->commerce, [
            'name' => 'properties[apiKey]',
            'label' => 'API Key',
            'description' => 'Add an API Key to your account by logging in to the Payrexx dashboard, and navigating to Integrations > API & Plugins. ',
            'value' => $method->getProperty('apiKey', ''),
            'validation' => [
                new Required(),
            ]
        ]);

        $instance = $method->getProperty('instance');
        $apiKey = $method->getProperty('apiKey');
        if (empty($instance) || empty($apiKey)) {
            $fields[] = new DescriptionField($this->commerce, [
                'label' => 'Connection',
                'description' => 'Enter an instance and API Key, and then save the payment method to check your connection to Payrexx.',
            ]);

            return $fields;
        }

        $client = new Payrexx($instance, $apiKey);

        try {
            /** @var \Payrexx\Models\Response\SignatureCheck $response */
            $client->getOne(new SignatureCheck());
        } catch (Exception $e) {
            $fields[] = new DescriptionField($this->commerce, [
                'label' => get_class($e),
                'description' => $e->getMessage(),
            ]);

            return $fields;
        }

        $fields[] = new DescriptionField($this->commerce, [
            'label' => 'Connection',
            'description' => '✔ Successfully connected',
        ]);

        if ($method->get('id') > 0) {
            $fields[] = new TextField($this->commerce, [
                'label' => 'Webhook',
                'description' => 'Using the webhook is recommended <b>on Commerce 1.3+</b> to ensure payment status updates are pushed to Commerce regardless of the customers\' return to the checkout.<br><br>Based on your current configuration, the field above contains the webhook URL you would need to configure in the Payrexx dashboard, under Integrations > Webhooks.<br><br>When adding the webhook, enable only <code>Transaction</code> events end set the Webhook Type to <code>Normal (PHP-Post)</code>. Use the latest Webhook Version unless otherwise instructed. Note that the <code>Send Test Data</code> button will return a 400 error with response <em>Unable to identify transaction</em>; that is expected.',
                'value' => GatewayHelper::getSharedNotifyURL($method),
            ]);
        }

        try {
            /** @var \Payrexx\Models\Response\PaymentProvider $response */
            $paymentProviders = $client->getAll(new PaymentProvider());
        } catch (Exception $e) {
            $fields[] = new DescriptionField($this->commerce, [
                'label' => get_class($e),
                'description' => $e->getMessage(),
            ]);

            return $fields;
        }

        $list = [];
        /** @var PaymentProvider $paymentProvider */
        foreach ($paymentProviders as $paymentProvider) {
            $options = implode(', ', $paymentProvider->getActivePaymentMethods());
            if (!empty($options)) {
                $options = '<br><span class="help">' . $options . '</span>';
            }
            $list[] = '<li>' . $paymentProvider->getName() . $options . '</li>';
        }

        $fields[] = new DescriptionField($this->commerce, [
            'label' => 'Payment providers',
            'description' => '<ul class="c ui bulleted list">' . implode('', $list) . '</ul>',
        ]);


        return $fields;
    }

    /**
     * @param \Payrexx\Models\Request\Gateway $payment
     * @param comOrder $order
     * @return void
     */
    private function addBasket(
        \Payrexx\Models\Request\Gateway $payment,
        comOrder $order
    ): void {
        $basket = [];
        foreach ($order->getItems() as $item) {
            // We give the full row as one quantity regardless of the actual quantity to avoid
            // rounding errors. This is because Payrexx IGNORES the AMOUNT we set, but rather bills
            // the total of the basket. That means they may charge a different amount then we do
            // if the calculation is different due to taxes or quantities.
            $basket[] = [
                'name' => [$item->get('quantity') . 'x ' . $item->getName()],
                'description' => [$item->getDescription()],
                'quantity' => 1,
                'amount' => $item->get('total'),
            ];
        }
        foreach ($order->getShipments() as $shipment) {
            $method = $shipment->getShippingMethod();
            $basket[] = [
                'name' => [$method ? $method->get('name') : $this->adapter->lexicon('commerce.shipping')],
                'description' => [''],
                'quantity' => 1,
                'amount' => $shipment->get('fee_incl_tax'),
            ];
        }
        foreach ($order->getTransactions() as $t) {
            $method = $t->getMethod();
            if ($t->get('amount') > 0 && ($t->isCompleted() || $t->isProcessing())) {
                $basket[] = [
                    'name' => [($method ? $method->get('name') : $this->adapter->lexicon('commerce.paid')) . ' (fee)'],
                    'description' => [''],
                    'quantity' => 1,
                    'amount' => -$t->get('amount'),
                ];
            }

            if ($t->isCompleted()) {
                $basket[] = [
                    'name' => [$method ? $method->get('name') : $this->adapter->lexicon('commerce.paid')],
                    'description' => [''],
                    'quantity' => 1,
                    'amount' => -$t->get('amount'),
                ];
            }
        }
        $payment->setBasket($basket);
    }

    public function identifyWebhookTransaction()
    {
        $postedTransaction = !empty($_POST['transaction']) ? $_POST['transaction'] : [];
        if (empty($postedTransaction) || !is_array($postedTransaction)) {
            return false;
        }

        $ref = $postedTransaction['referenceId'] ?? '';
        if (empty($ref) || strpos($ref, '/') === false) {
            return false;
        }

        $refBits = explode('/', $ref);
        $transId = (int)trim(end($refBits));
        $transaction = $this->adapter->getObject(comTransaction::class, [
            'id' => $transId,
            'method' => $this->method->get('id'),
        ]);
        if ($transaction instanceof comTransaction) {
            return $transaction;
        }

        return false;
    }

    public function webhook(comTransaction $transaction, array $data): Transaction
    {
        return $this->returned($transaction, $data);
    }
}
