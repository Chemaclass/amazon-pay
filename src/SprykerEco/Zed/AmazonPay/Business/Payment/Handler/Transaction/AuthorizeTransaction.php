<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Transaction;

use Generated\Shared\Transfer\AmazonpayCallTransfer;
use Orm\Zed\AmazonPay\Persistence\SpyPaymentAmazonpay;
use SprykerEco\Shared\AmazonPay\AmazonPayConfig;

class AuthorizeTransaction extends AbstractAmazonpayTransaction
{
    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function execute(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        $this->updateAuthorizationReferenceId($amazonPayCallTransfer);

        $amazonPayCallTransfer = parent::execute($amazonPayCallTransfer);

        $this->updatePaymentEntity($amazonPayCallTransfer);

        return $amazonPayCallTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return string
     */
    protected function buildErrorMessage(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return AmazonPayConfig::PREFIX_AMAZONPAY_PAYMENT_ERROR .
            $amazonpayCallTransfer->getAmazonpayPayment()
                ->getAuthorizationDetails()
                ->getAuthorizationStatus()
                ->getReasonCode();
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return void
     */
    protected function updateAuthorizationReferenceId(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        $authReferenceId = $this->generateOperationReferenceId($amazonPayCallTransfer);
        $amazonPayCallTransfer->getAmazonpayPayment()
            ->getAuthorizationDetails()
            ->setAuthorizationReferenceId($authReferenceId);
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return void
     */
    protected function updatePaymentEntity(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        if (!$this->isPaymentSuccess($amazonPayCallTransfer)) {
            $this->paymentEntity->setStatus(AmazonPayConfig::STATUS_DECLINED);
            $this->paymentEntity->save();

            return;
        }

        $isPartialProcessing = $this->paymentEntity !== null && $this->isPartialProcessing($this->paymentEntity, $amazonPayCallTransfer);

        if ($isPartialProcessing) {
            $this->paymentEntity = $this->paymentProcessor->duplicatePaymentEntity($this->paymentEntity);
        }

        $authorizationDetails = $amazonPayCallTransfer->getAmazonpayPayment()->getAuthorizationDetails();

        if ($this->isStateDeclined($authorizationDetails->getAuthorizationStatus()->getState())) {
            $amazonPayCallTransfer->getAmazonpayPayment()->getResponseHeader()
                ->setIsSuccess(false)
                ->setIsInvalidPaymentMethod($this->isInvalidPaymentMethod($amazonPayCallTransfer))
                ->setErrorMessage($this->buildErrorMessage($amazonPayCallTransfer));
        }

        if ($this->paymentEntity !== null) {
            $this->paymentEntity->setStatus($authorizationDetails->getAuthorizationStatus()->getState());
            $this->paymentEntity->setAuthorizationReferenceId($authorizationDetails->getAuthorizationReferenceId());
            $this->paymentEntity->setAmazonAuthorizationId($authorizationDetails->getAmazonAuthorizationId());
            $this->paymentEntity->save();
        }

        if ($isPartialProcessing) {
            $this->paymentProcessor->assignAmazonpayPaymentToItems(
                $this->paymentEntity,
                $amazonPayCallTransfer
            );
        }
    }

    /**
     * @param string $stateName
     *
     * @return bool
     */
    protected function isStateDeclined($stateName)
    {
        return in_array($stateName, [
            AmazonPayConfig::STATUS_DECLINED,
            AmazonPayConfig::STATUS_TRANSACTION_TIMED_OUT,
            AmazonPayConfig::STATUS_CANCELLED,
            AmazonPayConfig::STATUS_SUSPENDED,
            AmazonPayConfig::STATUS_EXPIRED,
            AmazonPayConfig::STATUS_PAYMENT_METHOD_INVALID,
        ], true);
    }

    /**
     * @param \Orm\Zed\AmazonPay\Persistence\SpyPaymentAmazonpay $paymentAmazonPayEntity
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return bool
     */
    protected function isPartialProcessing(SpyPaymentAmazonpay $paymentAmazonPayEntity, AmazonpayCallTransfer $amazonPayCallTransfer): bool
    {
        return false;
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return bool
     */
    protected function isInvalidPaymentMethod(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        return ($amazonPayCallTransfer->getAmazonpayPayment()
                ->getAuthorizationDetails()
                ->getAuthorizationStatus()
                ->getReasonCode() === AmazonPayConfig::REASON_CODE_PAYMENT_METHOD_INVALID);
    }
}
