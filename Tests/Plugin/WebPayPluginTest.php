<?php

namespace Quartet\Payment\WebPayBundle\Tests\Plugin;

use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use Quartet\Payment\WebPayBundle\Plugin\WebPayPlugin;
use WebPay\Api\Charges;

class WebPayPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebPayPlugin
     */
    private $plugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $chargeApi;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->plugin = new WebPayPlugin(
            $this->chargeApi = $this->getChargeApi()
        );
    }

    /**
     * @test
     * @dataProvider provideTestCheckPaymentInstructionData
     */
    public function testCheckPaymentInstruction($hasError, array $data)
    {
        $paymentInstruction = $this->getPaymentInstruction();

        $paymentInstruction
            ->expects($this->once())
            ->method('getExtendedData')
            ->will($this->returnValue($extendedData = $this->getExtendedData()));

        $extendedData
            ->expects($this->atLeastOnce())
            ->method('has')
            ->with(call_user_func_array(array($this, 'logicalOr'), array_keys($data)))
            ->will(call_user_func_array(array($this, 'onConsecutiveCalls'), array_values($data)));

        if ($hasError) {
            $this->setExpectedException('JMS\Payment\CoreBundle\Plugin\Exception\InvalidPaymentInstructionException');
        }

        $this->plugin->checkPaymentInstruction($paymentInstruction);
    }

    /**
     * @return array
     */
    public function provideTestCheckPaymentInstructionData()
    {
        return array(
            array(false, array(WebPayPlugin::ATTR_CHARGE_CARD => true, WebPayPlugin::ATTR_CHARGE_CUSTOMER => true)),
            array(false, array(WebPayPlugin::ATTR_CHARGE_CARD => true, WebPayPlugin::ATTR_CHARGE_CUSTOMER => false)),
            array(false, array(WebPayPlugin::ATTR_CHARGE_CARD => false, WebPayPlugin::ATTR_CHARGE_CUSTOMER => true)),
            array(true,  array(WebPayPlugin::ATTR_CHARGE_CARD => false, WebPayPlugin::ATTR_CHARGE_CUSTOMER => false)),
        );
    }

    /**
     * @test
     */
    public function testApproveAndDeposit()
    {
        $transaction = $this->getMock('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');

        $transaction
            ->expects($this->once())
            ->method('getExtendedData')
            ->will($this->returnValue($extendedData = $this->getExtendedData()));

        $transaction
            ->expects($this->once())
            ->method('getPayment')
            ->will($this->returnValue($payment = $this->getMock('JMS\Payment\CoreBundle\Model\PaymentInterface')));

        $transaction
            ->expects($this->once())
            ->method('getRequestedAmount')
            ->will($this->returnValue($requestedAmount = 9600));

        $payment
            ->expects($this->once())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($paymentInstruction = $this->getPaymentInstruction()));

        $paymentInstruction
            ->expects($this->once())
            ->method('getCurrency')
            ->will($this->returnValue($currency = 'jpy'));

        $extendedData
            ->expects($this->exactly(3))
            ->method('has')
            ->with($this->logicalOr(WebPayPlugin::ATTR_CHARGE_CARD, WebPayPlugin::ATTR_CHARGE_CUSTOMER, WebPayPlugin::ATTR_CHARGE_DESCRIPTION))
            ->will($this->returnValue(true));

        $extendedData
            ->expects($this->exactly(3))
            ->method('get')
            ->with($this->logicalOr(WebPayPlugin::ATTR_CHARGE_CARD, WebPayPlugin::ATTR_CHARGE_CUSTOMER, WebPayPlugin::ATTR_CHARGE_DESCRIPTION))
            ->will($this->onConsecutiveCalls('card', 'customer', 'description'));

        $that = $this;

        $this
            ->chargeApi
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($that, $requestedAmount, $currency) {
                $that->assertInternalType('array', $data);
                $that->assertEquals($requestedAmount, $data['amount']);
                $that->assertEquals($currency, $data['currency']);
                $that->assertEquals('card', $data[WebPayPlugin::ATTR_CHARGE_CARD]);
                $that->assertEquals('customer', $data[WebPayPlugin::ATTR_CHARGE_CUSTOMER]);
                $that->assertEquals('description', $data[WebPayPlugin::ATTR_CHARGE_DESCRIPTION]);

                return true;
            }))
            ->will($this->returnValue($charge = new \stdClass()));

        $charge->id = $chargeId = 1020;
        $charge->amount = $amount = 3090;

        $transaction
            ->expects($this->once())
            ->method('setReferenceNumber')
            ->with($chargeId);

        $transaction
            ->expects($this->once())
            ->method('setProcessedAmount')
            ->with($amount);

        $transaction
            ->expects($this->once())
            ->method('setResponseCode')
            ->with(PluginInterface::RESPONSE_CODE_SUCCESS);

        $transaction
            ->expects($this->once())
            ->method('setReasonCode')
            ->with(PluginInterface::REASON_CODE_SUCCESS);

        $this->plugin->approveAndDeposit($transaction, false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getChargeApi()
    {
        return $this->getMockBuilder('WebPay\Api\Charges')->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentInstruction()
    {
        return $this->getMock('JMS\Payment\CoreBundle\Model\PaymentInstructionInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getExtendedData()
    {
        return $this->getMock('JMS\Payment\CoreBundle\Model\ExtendedDataInterface');
    }
}
