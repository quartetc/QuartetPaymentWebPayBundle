<?php

namespace Quartet\Payment\WebPayBundle\Plugin;

use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\ErrorBuilder;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use WebPay\Api\Charges;
use WebPay\Exception\APIConnectionException;
use WebPay\Exception\APIException;
use WebPay\Exception\CardException;
use WebPay\Exception\InvalidRequestException;

class WebPayPlugin extends AbstractPlugin
{
    const ATTR_CHARGE_CARD        = 'card';
    const ATTR_CHARGE_CUSTOMER    = 'customer';
    const ATTR_CHARGE_DESCRIPTION = 'charge';

    /**
     * @var \WebPay\Api\Charges
     */
    private $charges;

    /**
     * @param Charges $charges
     */
    public function __construct(Charges $charges)
    {
        $this->charges = $charges;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $errorBuilder = new ErrorBuilder();

        /* @var $data \JMS\Payment\CoreBundle\Entity\ExtendedData */
        $data = $instruction->getExtendedData();

        if (!$data->has(self::ATTR_CHARGE_CARD) && !$data->has(self::ATTR_CHARGE_CUSTOMER)) {
            $errorBuilder->addDataError('card', 'form.error.required');
        }

        if ($errorBuilder->hasErrors()) {
            throw $errorBuilder->getException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        /* @var $data \JMS\Payment\CoreBundle\Entity\ExtendedData */
        $data = $transaction->getExtendedData();

        /* @var $payment Payment */
        $payment = $transaction->getPayment();

        try {

            $chargeArguments = array(
                'amount'        => (int) $transaction->getRequestedAmount(),
                'currency'      => $payment->getPaymentInstruction()->getCurrency(),
            );

            if ($data->has(self::ATTR_CHARGE_CARD)) {
                $chargeArguments[self::ATTR_CHARGE_CARD] = $data->get(self::ATTR_CHARGE_CARD);
            }

            if ($data->has(self::ATTR_CHARGE_CUSTOMER)) {
                $chargeArguments[self::ATTR_CHARGE_CUSTOMER] = $data->get(self::ATTR_CHARGE_CUSTOMER);
            }

            if ($data->has(self::ATTR_CHARGE_DESCRIPTION)) {
                $chargeArguments[self::ATTR_CHARGE_DESCRIPTION] = $data->get(self::ATTR_CHARGE_DESCRIPTION);
            }

            $charge = $this->charges->create($chargeArguments);

            $transaction->setReferenceNumber($charge->id);
            $transaction->setProcessedAmount($charge->amount);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
            $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);

        } catch (CardException $e) {

            throw $this->createFinancialException($e, $transaction);
        } catch (InvalidRequestException $e) {

            throw $this->createFinancialException($e, $transaction);
        } catch (APIConnectionException $e) {

            throw $this->createFinancialException($e, $transaction);
        } catch (APIException $e) {

            throw $this->createFinancialException($e, $transaction);
        }
    }

    /**
     * @param \Exception                    $e
     * @param FinancialTransactionInterface $transaction
     *
     * @return FinancialException
     */
    private function createFinancialException(\Exception $e, FinancialTransactionInterface $transaction = null)
    {
        $exception = new FinancialException($e->getMessage(), $e->getCode(), $e);

        if ($transaction) {
            $exception->setFinancialTransaction($transaction);
        }

        return $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function processes($name)
    {
        return $name === 'quartet_payment_webpay';
    }
}
