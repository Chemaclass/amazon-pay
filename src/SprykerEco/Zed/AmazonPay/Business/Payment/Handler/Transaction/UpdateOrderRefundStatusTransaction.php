<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Transaction;

use Generated\Shared\Transfer\AmazonpayCallTransfer;
use Generated\Shared\Transfer\AmazonpayStatusTransfer;
use SprykerEco\Shared\AmazonPay\AmazonPayConfig;
use SprykerEco\Shared\AmazonPay\AmazonPayConfigInterface;
use SprykerEco\Zed\AmazonPay\Business\Api\Adapter\CallAdapterInterface;
use SprykerEco\Zed\AmazonPay\Business\Order\PaymentProcessorInterface;
use SprykerEco\Zed\AmazonPay\Business\Order\RefundOrderInterface;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Transaction\Logger\TransactionLoggerInterface;

class UpdateOrderRefundStatusTransaction extends AbstractAmazonpayTransaction
{
    /**
     * @var \SprykerEco\Zed\AmazonPay\Business\Order\RefundOrderInterface
     */
    protected $refundOrderModel;

    /**
     * @param \SprykerEco\Zed\AmazonPay\Business\Api\Adapter\CallAdapterInterface $executionAdapter
     * @param \SprykerEco\Shared\AmazonPay\AmazonPayConfigInterface $config
     * @param \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Transaction\Logger\TransactionLoggerInterface $transactionLogger
     * @param \SprykerEco\Zed\AmazonPay\Business\Order\PaymentProcessorInterface $paymentProcessor
     * @param \SprykerEco\Zed\AmazonPay\Business\Order\RefundOrderInterface $refundOrderModel
     */
    public function __construct(
        CallAdapterInterface $executionAdapter,
        AmazonPayConfigInterface $config,
        TransactionLoggerInterface $transactionLogger,
        PaymentProcessorInterface $paymentProcessor,
        RefundOrderInterface $refundOrderModel
    ) {

        parent::__construct($executionAdapter, $config, $transactionLogger, $paymentProcessor);

        $this->refundOrderModel = $refundOrderModel;
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonPayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function execute(AmazonpayCallTransfer $amazonPayCallTransfer)
    {
        if (!$amazonPayCallTransfer->getAmazonpayPayment()->getRefundDetails()->getAmazonRefundId()) {
            return $amazonPayCallTransfer;
        }

        $amazonPayCallTransfer = parent::execute($amazonPayCallTransfer);

        if (!$this->apiResponse->getHeader()->getIsSuccess()) {
            return $amazonPayCallTransfer;
        }

        $isPartialProcessing = $this->isPartialProcessing($this->paymentEntity, $amazonPayCallTransfer);

        if ($isPartialProcessing) {
            $this->paymentEntity = $this->paymentProcessor->duplicatePaymentEntity($this->paymentEntity);
        }

        $status = $this->getPaymentStatus($this->apiResponse->getRefundDetails()->getRefundStatus());

        $refundIsRequired = ($status === AmazonPayConfig::OMS_STATUS_REFUND_COMPLETED
            && $this->paymentEntity->getStatus() !== $status);

        $this->paymentEntity->setStatus($status);
        $this->paymentEntity->save();

        if ($isPartialProcessing) {
            $this->paymentProcessor->assignAmazonpayPaymentToItems($this->paymentEntity, $amazonPayCallTransfer);
        }

        if ($refundIsRequired) {
            $this->refundOrderModel->refundPayment($this->paymentEntity);
        }

        return $amazonPayCallTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayStatusTransfer $status
     *
     * @return string
     */
    protected function getPaymentStatus(AmazonpayStatusTransfer $status)
    {
        if ($status->getIsPending()) {
            return AmazonPayConfig::OMS_STATUS_REFUND_PENDING;
        }

        if ($status->getIsDeclined()) {
            return AmazonPayConfig::OMS_STATUS_REFUND_DECLINED;
        }

        if ($status->getIsCompleted()) {
            return AmazonPayConfig::OMS_STATUS_REFUND_COMPLETED;
        }

        return AmazonPayConfig::OMS_STATUS_CANCELLED;
    }
}
