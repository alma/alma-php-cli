<?php

namespace App\Command\Meta;

use Alma\API\Entities\Order;
use Alma\API\Entities\Payment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Trait AlmaPaymentOutputTrait
 *
 * @package App\Command\Meta
 */
trait DisplayOutputPaymentTrait
{
    protected function configureOutputPaymentOptions()
    {
        $this->addOption(
            'output-customer',
            'c',
            InputOption::VALUE_NONE,
            'should I output customer after create payment'
        )
            ->addOption('output-orders', null, InputOption::VALUE_NONE, 'should I output orders after create payment')
            ->addOption(
                'output-addresses',
                'a',
                InputOption::VALUE_NONE,
                'should I output addresses after create payment'
            )
        ;
    }

    /**
     * @param array $data
     *
     * @override
     */
    public function outputFormatTable(array ...$data): void
    {
        if (empty($data)) {
            return;
        }
        $this->io->title('Payload Payment');
        $this->outputKeyValueTable($data[0]['payment'], ['billing_address', 'shipping_address']);
        // $payloadAddresses
        if (isset($data[1])) {
            $this->io->title('Payload Addresses');
            $this->outputAddresses($data[1]);
        }
        $this->io->title('Payload Customer');
        $this->outputKeyValueTable($data[0]['customer'], ['addresses']);
        $this->io->title('Payload Order');
        $this->outputKeyValueTable($data[0]['order']);
    }

    /**
     * @param array|Order[] $orders
     */
    protected function outputOrders(array $orders)
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

    /**
     * @param Payment $payment
     * @param InputInterface $input
     *
     * @return void
     */
    protected function outputPayment(Payment $payment, InputInterface $input): void
    {
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
    }
}
