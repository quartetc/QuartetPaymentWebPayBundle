<?php

namespace Quartet\Payment\WebPayBundle\Controller;

use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WebPayController extends Controller
{
    /**
     * @param PaymentInstructionInterface $instruction
     *
     * @return Result
     */
    protected function completePayment(PaymentInstructionInterface $instruction)
    {
        $controller = $this->getPluginController();

        /**
         * @var $pendingTransaction FinancialTransactionInterface
         * @var $payment PaymentInterface
         */
        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
            $payment = $controller->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
        } else {
            $payment = $pendingTransaction->getPayment();
        }

        /* @var $result Result */
        $result = $controller->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

        return $result;
    }

    /**
     * @param number $amount
     * @param string $currency
     * @param string $method
     * @param array  $extendedData
     *
     * @return PaymentInstruction
     */
    protected function createPaymentInstruction($amount, $currency, $method = 'quartet_payment_webpay', array $extendedDataArray = null)
    {
        $extendedData = null;
        if (!empty($extendedDataArray)) {
            $extendedData = new ExtendedData();
            foreach ($extendedDataArray as $k => $v) {
                $extendedData->set($k, $v);
            }
        }

        $instruction = new PaymentInstruction($amount, $currency, $method, $extendedData);

        $this->getPluginController()->createPaymentInstruction($instruction);

        return $instruction;
    }

    /**
     * @return \JMS\Payment\CoreBundle\PluginController\EntityPluginController
     */
    protected function getPluginController()
    {
        return $this->get('payment.plugin_controller');
    }

}
