<?php

namespace Omnipay\EFT;

use Omnipay\Manual\Gateway as ManualGateway;

/**
 * Store Cheque payment gateway
 *
 * This is an example of a custom gateway. It simply extends the existing
 * Omnipay Manual payment gateway.
 *
 * For more information about developing custom gateways, please see
 * https://github.com/omnipay/omnipay
 */
class Gateway extends ManualGateway
{
    public function getName()
    {
        return 'EFT';
    }
}
