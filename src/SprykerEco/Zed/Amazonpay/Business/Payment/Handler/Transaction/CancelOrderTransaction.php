<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Amazonpay\Business\Payment\Handler\Transaction;

use Generated\Shared\Transfer\AmazonpayCallTransfer;
use SprykerEco\Shared\Amazonpay\AmazonpayConstants;

class CancelOrderTransaction extends AbstractAmazonpayTransaction
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
        $amazonpayCallTransfer = parent::execute($amazonpayCallTransfer);
        $isPartialProcessing = $this->isPartialProcessing($this->paymentEntity, $amazonpayCallTransfer);

        if ($this->apiResponse->getHeader()->getIsSuccess()) {
            if ($isPartialProcessing) {
                $this->paymentEntity = $this->duplicatePaymentEntity($this->paymentEntity);
            }

            $this->paymentEntity->setStatus(AmazonpayConstants::OMS_STATUS_CANCELLED);
            $this->paymentEntity->save();

            if ($isPartialProcessing) {
                $this->assignAmazonpayPaymentToItemsIfNew($this->paymentEntity, $amazonpayCallTransfer);
            }
        }

        return $amazonpayCallTransfer;
    }

}
