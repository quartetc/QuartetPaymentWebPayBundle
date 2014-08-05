<?php

namespace Quartet\Payment\WebPayBundle;

use Quartet\Payment\WebPayBundle\DependencyInjection\QuartetPaymentWebPayExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class QuartetPaymentWebPayBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new QuartetPaymentWebPayExtension();
    }
}
