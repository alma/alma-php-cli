<?php

namespace App\Command;

use Alma\API\Entities\Instalment;
use Alma\API\Entities\Order;
use Alma\API\RequestError;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaPaymentCreateCommand extends AbstractAlmaCommand
{
    const REQUIRED_ARRAY = InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED;
    protected static $defaultName = 'alma:payment:create';
    protected static $defaultDescription = 'Create payment with given informations then output informations about payload & created payment';

    const SHIPPING_ADDRESS_TYPE = 'shipping';
    const BILLING_ADDRESS_TYPE  = 'billing';
    const CUSTOMER_ADDRESS_TYPE = 'customer';
    const ADDRESSES_TYPES       = [
        self::CUSTOMER_ADDRESS_TYPE,
        self::BILLING_ADDRESS_TYPE,
        self::SHIPPING_ADDRESS_TYPE,
    ];
    const ADDRESSES_KEYS        = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'line1',
        'line2',
        'postal_code',
        'city',
        'country',
    ];

    /**
     * @param string         $type
     * @param InputInterface $input
     *
     * @return array
     */
    protected function buildAddressFrom(string $type, InputInterface $input): array
    {
        $address = [];
        foreach (self::ADDRESSES_KEYS as $key) {
            $value = $input->getOption($this->getAddressOptionName($type, $key));
            if ($value) {
                $address[$key] = $value;
            }
        }

        return $address;
    }

    private function checkArrayValues(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        foreach ($array as $value) {
            if (is_null($value)) {
                return false;
            }
        }

        return true;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'A valid amount to perform payment (give it in cents)')
            ->addOption('output-payload', 'p', InputOption::VALUE_NONE, 'should I output payload before create payment')
            ->addOption(
                'output-customer',
                'c',
                InputOption::VALUE_NONE,
                'should I output customer after create payment'
            )
            ->addOption('output-orders', 'o', InputOption::VALUE_NONE, 'should I output orders after create payment')
            ->addOption(
                'output-addresses',
                'a',
                InputOption::VALUE_NONE,
                'should I output addresses after create payment'
            )
            ->addOption(
                'format-payload',
                'f',
                InputOption::VALUE_OPTIONAL,
                'should I format output payload (works with --output-payload) - possible values are table, dump, json, var_dump, var_export',
                'table'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'do not perform really the create payment action (useful if you just want to see the payload)'
            )
            ->addOption('installments', 'i', InputOption::VALUE_REQUIRED, 'pnx installments count', 3)
            ->addOption('payment-locale', 'l', InputOption::VALUE_REQUIRED, 'locale for payement', 'fr')
            ->addOption(
                'force-empty-payment-address',
                'F',
                InputOption::VALUE_NONE,
                'provide this flag if you don\'t want provide payment billing nor shipping address informations (empty billing billing address will be set in payload)'
            )
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'customer email')
            ->addOption('phone', null, InputOption::VALUE_REQUIRED, 'customer phone')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'customer first_name')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'customer last_name')
            ->addOption('return-url', null, InputOption::VALUE_REQUIRED, 'a website return URL after alma checkout')
            ->addOption(
                'cancel-url',
                null,
                InputOption::VALUE_REQUIRED,
                'a website return URL on customer cancel alma checkout'
            )
            ->addOption(
                'ipn-url',
                null,
                InputOption::VALUE_REQUIRED,
                'a website IPN callback URL for Alma events after checkout'
            )
            ->addOption(
                'custom-data',
                null,
                self::REQUIRED_ARRAY,
                'custom data for payment formatted as key:value',
                ['client:alma-cli']
            )
            ->addOption('order-reference', null, InputOption::VALUE_REQUIRED, 'unique identifier of your order')
            ->addOption('order-edit-url', null, InputOption::VALUE_REQUIRED, 'your website edit url for order')
            ->addOption('customer-edit-url', null, InputOption::VALUE_REQUIRED, 'you website edit url for customer')
        ;
        foreach (self::ADDRESSES_TYPES as $type) {
            foreach (self::ADDRESSES_KEYS as $key) {
                $this->addOption(
                    $this->getAddressOptionName($type, $key),
                    null,
                    InputOption::VALUE_REQUIRED,
                    sprintf('%s address "%s" info', $type, $key)
                );
            }
        }
    }

    /**
     * @param int            $amount
     * @param int            $installmentsCount
     * @param InputInterface $input
     *
     * @return array[]
     * @throws Exception
     */
    protected function defaultPayload(int $amount, int $installmentsCount, InputInterface $input): array
    {
        $customer = [
            'first_name' => $input->getOption('first-name'),
            'last_name'  => $input->getOption('last-name'),
            'email'      => $input->getOption('email'),
            'phone'      => $input->getOption('phone'),
        ];

        $order = [
            'merchant_reference' => $input->getOption('order-reference'),
            'merchant_url'       => $input->getOption('order-edit-url'),
            'customer_url'       => $input->getOption('customer-edit-url'),
        ];

        $returnUrl  = $input->getOption('return-url');
        $ipnUrl     = $input->getOption('ipn-url');
        $cancelUrl  = $input->getOption('cancel-url');
        $customData = $input->getOption('custom-data');
        $locale     = $input->getOption('payment-locale');
        $data       = [
            'payment' => [
                'purchase_amount'    => $amount,
                'installments_count' => $installmentsCount,
            ],
        ];
        if ($returnUrl) {
            $data['payment']['return_url'] = $returnUrl;
        }
        if ($ipnUrl) {
            $data['payment']['ipn_callback_url'] = $ipnUrl;
        }
        if ($cancelUrl) {
            $data['payment']['customer_cancel_url'] = $cancelUrl;
        }
        if ($customData) {
            $data['payment']['custom_data'] = $this->formatCustomData($customData);
        }
        if ($locale) {
            $data['payment']['locale'] = $locale;
        }
        if ($this->checkArrayValues($order)) {
            $data['order'] = $order;
        }
        if ($this->checkArrayValues($customer)) {
            $data['customer'] = $customer;
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $amount = intval($input->getArgument('amount'));
        if ($amount <= 0) {
            $this->io->error('Amount must be > 0');

            return self::INVALID;
        }
        $installmentsCount = intval($input->getOption('installments'));
        if ($installmentsCount <= 0) {
            $this->io->error('Installments count must be > 0');

            return self::INVALID;
        }

        $data = $this->defaultPayload($amount, $installmentsCount, $input);

        $customerAddress   = $this->buildAddressFrom(self::CUSTOMER_ADDRESS_TYPE, $input);
        $billingAddress    = $this->buildAddressFrom(self::BILLING_ADDRESS_TYPE, $input);
        $shippingAddress   = $this->buildAddressFrom(self::SHIPPING_ADDRESS_TYPE, $input);
        $payloadAddresses  = [];
        $customerAddresses = [];
        if ($this->checkArrayValues($customerAddress)) {
            $customerAddresses[]                  = $customerAddress;
            $payloadAddresses['customer-address'] = $customerAddress;
        }
        if ($this->checkArrayValues($billingAddress)) {
            $data['payment']['billing_address'] = $billingAddress;
//            $customerAddresses[]                         = $billingAddress;
            $payloadAddresses['payment-billing-address'] = $billingAddress;
        }
        if ($this->checkArrayValues($shippingAddress)) {
            $data['payment']['shipping_address'] = $shippingAddress;
//            $customerAddresses[]                          = $shippingAddress;
            $payloadAddresses['payment-shipping-address'] = $shippingAddress;
        }
        if (!empty($customerAddresses)) {
            $data['customer']['addresses'] = $customerAddresses;
        }
        if (!$this->checkArrayValues($shippingAddress) && !$this->checkArrayValues(
                $shippingAddress
            ) && $input->getOption('force-empty-payment-address')) {
            // force an empty billing address
            $data['payment']['billing_address'] = [];
            $payloadAddresses['payment-billing-address'] = [];
        }

        if ($input->getOption('output-payload')) {
            switch ($input->getOption('format-payload')) {
                case 'var_export':
                    var_export($data);
                    break;
                case 'var_dump':
                    var_dump($data);
                    break;
                case 'dump':
                    dump($data);
                    break;
                case 'json':
                    print(json_encode($data, JSON_PRETTY_PRINT));
                    break;
                case 'table':
                    $this->io->title('Payload Payment');
                    $this->outputKeyValueTable($data['payment'], ['billing_address', 'shipping_address']);
                    $this->io->title('Payload Addresses');
                    $this->outputAddresses($payloadAddresses);
                    $this->io->title('Payload Customer');
                    $this->outputKeyValueTable($data['customer'], ['addresses']);
                    $this->io->title('Payload Order');
                    $this->outputKeyValueTable($data['order']);
                    break;
                default:
                    $this->io->error(sprintf('%s: not a valid payload format', $input->getOption('format-payload')));

                    return self::INVALID;
            }
        }

        if ($input->getOption('dry-run')) {
            $this->io->info('Command called with dry run ... we will not perform the payment creation');

            return self::SUCCESS;
        }
        try {
            $payment = $this->alma->payments->create($data);
        } catch (RequestError $e) {
            return $this->outputRequestError($e);

        }

        $this->io->title('Alma Payment from API');
        $this->outputKeyValueTable(
            get_object_vars($payment),
            ['customer', 'billing_address', 'orders', 'payment_plan']
        );
        if ($input->getOption('output-customer')) {
            $this->io->title('Alma Payment.customer from API');
            $this->outputKeyValueTable($payment->customer, ['addresses']);
        }
        if ($input->getOption('output-addresses')) {
            $this->io->title('Alma Payment.customer.addresses from API');
            $this->outputAddresses($payment->customer['addresses'], ['id', 'created']);
            $this->io->title('Alma Payment.billing_address from API');
            $this->outputAddresses([$payment->billing_address], ['id', 'created']);
        }
        if ($input->getOption('output-orders')) {
            $this->io->title('Alma Payment.orders from API');
            $this->outputOrders($payment->orders);
        }
        $this->io->title('Alma Payment.payment_plan from API');
        $this->outputPaymentPlans($payment->payment_plan);

        return self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function formatCustomData(array $customData): array
    {
        $formattedData = [];
        foreach ($customData as $data) {
            $split = explode(":", $data);
            if (sizeof($split) !== 2) {
                throw new Exception("bad custom data format ($data) => this must be formatted as 'key:value'");
            }
            $formattedData[$split[0]] = $split[1];
        }

        return $formattedData;
    }

    private function getAddressOptionName(string $type, string $key): string
    {
        if (!in_array($type, self::ADDRESSES_TYPES)) {
            throw new InvalidArgumentException(sprintf('%s: BAD ADDRESS TYPE', $type));
        }

        return sprintf('%s-%s', $type, str_replace("_", "-", $key));
    }

    /**
     * @param array|Order[] $orders
     */
    private function outputOrders(array $orders)
    {
        $headers = [
            'payment',
            'merchant_reference',
            'merchant_url',
            'data',
            'id',
            'comment',
            'created',
            'customer_url',
        ];
        $rows    = [];
        foreach ($orders as $order) {
            $rows[] = [
                $order->payment,
                $order->merchant_reference,
                $order->merchant_url,
                $this->implodeWithKeys($order->data),
                $order->id,
                $order->comment,
                $this->formatTimestamp($order->created),
                $order->customer_url,
            ];
        }
        $this->io->table($headers, $rows);
    }

}
