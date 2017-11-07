<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Transaction;

use Generated\Shared\Transfer\AmazonpayCallTransfer;
use Generated\Shared\Transfer\AmazonpayStatusTransfer;
use SprykerEco\Shared\AmazonPay\AmazonPayConfig;

class CaptureOrderTransaction extends AbstractAmazonpayTransaction
{
    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function execute(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        if (!$this->isAllowed($amazonPayCallTransfer)) {
            return $amazonPayCallTransfer;
        }

        $this->updateCaptureReferenceId($amazonPayCallTransfer);

        $amazonPayCallTransfer = parent::execute($amazonPayCallTransfer);

        return $amazonPayCallTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return bool
     */
    protected function isAllowed(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        if (!in_array($amazonPayCallTransfer->getAmazonpayPayment()->getStatus(), [
            AmazonPayConfig::OMS_STATUS_CAPTURE_PENDING,
            AmazonPayConfig::OMS_STATUS_AUTH_OPEN,
            AmazonPayConfig::OMS_STATUS_PAYMENT_METHOD_CHANGED,
        ], true)) {
            return false;
        }

        if ($amazonPayCallTransfer->getAmazonpayPayment()->getCaptureDetails()
            && $amazonPayCallTransfer->getAmazonpayPayment()->getCaptureDetails()->getAmazonCaptureId()) {
            return false;
        }

        return true;
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return void
     */
    protected function updatePaymentEntity(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        if (!$this->isPaymentSuccess($amazonPayCallTransfer)) {
            return;
        }

        $isPartialProcessing = $this->isPartialProcessing($this->paymentEntity, $amazonPayCallTransfer);

        if ($isPartialProcessing) {
            $this->paymentEntity = $this->duplicatePaymentEntity($this->paymentEntity);
        }

        $captureDetails = $this->apiResponse->getCaptureDetails();

        $amazonPayCallTransfer->getAmazonpayPayment()->setCaptureDetails($captureDetails);
        $this->paymentEntity->setAmazonCaptureId(
            $captureDetails->getAmazonCaptureId()
        );
        $this->paymentEntity->setCaptureReferenceId(
            $captureDetails->getCaptureReferenceId()
        );
        $newStatus = $this->getPaymentStatus($captureDetails->getCaptureStatus());

        if ($newStatus !== '') {
            $this->paymentEntity->setStatus($newStatus);
        }

        $this->paymentEntity->save();

        if ($isPartialProcessing) {
            $this->assignAmazonpayPaymentToItems($this->paymentEntity, $amazonPayCallTransfer);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayStatusTransfer $captureStatus
     *
     * @return string
     */
    protected function getPaymentStatus(AmazonpayStatusTransfer $captureStatus)
    {
        if ($captureStatus->getIsDeclined()) {
            return AmazonPayConfig::OMS_STATUS_CAPTURE_DECLINED;
        }

        if ($captureStatus->getIsPending()) {
            return AmazonPayConfig::OMS_STATUS_CAPTURE_PENDING;
        }

        if ($captureStatus->getIsCompleted()) {
            return AmazonPayConfig::OMS_STATUS_CAPTURE_COMPLETED;
        }

        return '';
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return void
     */
    protected function updateCaptureReferenceId(AmazonpayCallTransfer $amazonPayCallTransfer):void
    {
        $amazonPayCallTransfer->getAmazonpayPayment()->getCaptureDetails()->setCaptureReferenceId(
            $this->generateOperationReferenceId($amazonPayCallTransfer)
        );
    }
}
