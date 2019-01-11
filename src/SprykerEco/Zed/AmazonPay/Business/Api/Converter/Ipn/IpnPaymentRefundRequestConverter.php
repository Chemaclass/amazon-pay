<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Business\Api\Converter\Ipn;

use Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer;
use SprykerEco\Zed\AmazonPay\Business\Api\Converter\ArrayConverterInterface;

class IpnPaymentRefundRequestConverter extends IpnPaymentAbstractRequestConverter
{
    public const REFUND_DETAILS = 'RefundDetails';
    /**
     * @var \SprykerEco\Zed\AmazonPay\Business\Api\Converter\ArrayConverterInterface $refundDetailsConverter
     */
    protected $refundDetailsConverter;

    /**
     * @param \SprykerEco\Zed\AmazonPay\Business\Api\Converter\ArrayConverterInterface $refundDetailsConverter
     */
    public function __construct(ArrayConverterInterface $refundDetailsConverter)
    {
        $this->refundDetailsConverter = $refundDetailsConverter;
    }

    /**
     * @param array $request
     * @param string $body
     *
     * @return \Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer
     */
    public function convert(array $request, $body)
    {
        $ipnPaymentRequestTransfer = new AmazonpayIpnPaymentRequestTransfer();
        $ipnPaymentRequestTransfer->setMessage($this->extractMessage($request));
        $ipnPaymentRequestTransfer->setRefundDetails(
            $this->refundDetailsConverter->convert($request[static::REFUND_DETAILS])
        );
        $ipnPaymentRequestTransfer->setRawMessage($body);

        return $ipnPaymentRequestTransfer;
    }
}
