<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Amazonpay\Business\Payment\Handler\Transaction;

use Generated\Shared\Transfer\AmazonpayCallTransfer;
use SprykerEco\Shared\Amazonpay\AmazonpayConstants;

class ReauthorizeOrderTransaction extends AbstractAmazonpayTransaction
{

    /**
     * @var \Generated\Shared\Transfer\AmazonpayResponseTransfer
     */
    protected $apiResponse;

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function execute(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        $amazonpayCallTransfer->getAmazonpayPayment()
            ->getAuthorizationDetails()
            ->setAuthorizationReferenceId(
                $this->generateOperationReferenceId($amazonpayCallTransfer)
            );

        $amazonpayCallTransfer = parent::execute($amazonpayCallTransfer);
        $this->paymentEntity = $this->duplicatePaymentEntity($this->paymentEntity);

        if ($this->apiResponse->getHeader()->getIsSuccess()) {
            $amazonpayCallTransfer->getAmazonpayPayment()->setAuthorizationDetails(
                $this->apiResponse->getAuthorizationDetails()
            );

            $this->paymentEntity->setAmazonAuthorizationId(
                $this->apiResponse->getAuthorizationDetails()->getAmazonAuthorizationId()
            );

            $this->paymentEntity->setAuthorizationReferenceId(
                $this->apiResponse->getAuthorizationDetails()->getAuthorizationReferenceId()
            );

            $this->paymentEntity->save();
        }

        $this->paymentEntity->setStatus(AmazonpayConstants::OMS_STATUS_AUTH_PENDING);
        $this->paymentEntity->save();
        $this->assignAmazonpayPaymentToItems($this->paymentEntity, $amazonpayCallTransfer);

        return $amazonpayCallTransfer;
    }

}
