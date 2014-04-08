<?php

namespace Quartet\Payment\WebPayBundle;

use Quartet\Payment\WebPayBundle\DependencyInjection\QuartetPaymentWebPayExtension;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class QuartetPaymentWebPayBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new QuartetPaymentWebPayExtension();
    }
}
