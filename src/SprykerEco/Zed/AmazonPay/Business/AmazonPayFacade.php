<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Business;

use Generated\Shared\Transfer\AmazonpayCallTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @api
 *
 * @method \SprykerEco\Zed\AmazonPay\Business\AmazonPayBusinessFactory getFactory()
 */
class AmazonPayFacade extends AbstractFacade implements AmazonPayFacadeInterface
{
    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function handleCartWithAmazonPay(QuoteTransfer $quoteTransfer)
    {
        return $this->getFactory()
            ->createQuoteUpdateFactory()
            ->createQuoteUpdaterCollection()
            ->update($quoteTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addSelectedAddressToQuote(QuoteTransfer $quoteTransfer)
    {
        return $this->getFactory()
            ->createQuoteUpdateFactory()
            ->createShippingAddressQuoteDataUpdater()
            ->update($quoteTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addSelectedShipmentMethodToQuote(QuoteTransfer $quoteTransfer)
    {
        return $this->getFactory()
            ->createQuoteUpdateFactory()
            ->createShipmentDataQuoteUpdater()
            ->update($quoteTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function confirmPurchase(QuoteTransfer $quoteTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createConfirmPurchaseTransaction()
            ->execute($quoteTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function captureOrder(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createCaptureAuthorizedTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function cancelOrder(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createCancelOrderTransactionSequence()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function closeOrder(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createCloseCapturedOrderTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function refundOrder(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createRefundOrderTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function reauthorizeExpiredOrder(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createReauthorizeExpiredOrderTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     */
    public function authorizeOrderItems(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createAuthorizeTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function reauthorizeSuspendedOrder(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createReauthorizeOrderTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function updateAuthorizationStatus(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createUpdateOrderAuthorizationStatusTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function updateCaptureStatus(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createUpdateOrderCaptureStatusHandler()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     *
     * @return \Generated\Shared\Transfer\AmazonpayCallTransfer
     */
    public function updateRefundStatus(AmazonpayCallTransfer $amazonpayCallTransfer)
    {
        return $this->getFactory()
            ->createTransactionFactory()
            ->createUpdateOrderRefundStatusTransaction()
            ->execute($amazonpayCallTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CheckoutResponseTransfer $checkoutResponseTransfer
     *
     * @return void
     */
    public function saveOrderPayment(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer
    ) {
        $this->getFactory()
            ->createOrderSaver()
            ->saveOrderPayment($quoteTransfer, $checkoutResponseTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param array $headers
     * @param string $body
     *
     * @return \Spryker\Shared\Kernel\Transfer\AbstractTransfer
     */
    public function convertAmazonPayIpnRequest(array $headers, $body)
    {
        return $this->getFactory()
            ->createAdapterFactory()
            ->createIpnRequestAdapter($headers, $body)
            ->getIpnRequest();
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer $ipnRequestTransfer
     *
     * @return void
     */
    public function handleAmazonPayIpnRequest(AbstractTransfer $ipnRequestTransfer)
    {
        $this->getFactory()
            ->createIpnFactory()
            ->createIpnRequestFactory()
            ->createConcreteIpnRequestHandler($ipnRequestTransfer)
            ->handle($ipnRequestTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    public function hydrateOrderInfo(OrderTransfer $orderTransfer)
    {
        return $this->getFactory()
            ->createAmazonpayOrderInfoHydrator()
            ->hydrateOrderInfo($orderTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayCallTransfer $amazonpayCallTransfer
     * @param int[] $alreadyAffectedItems
     * @param string $eventName
     *
     * @return void
     */
    public function triggerEventForRelatedItems(AmazonpayCallTransfer $amazonpayCallTransfer, array $alreadyAffectedItems, $eventName)
    {
        $this->getFactory()
            ->createRelatedItemsUpdateModel()
            ->triggerEvent($amazonpayCallTransfer, $alreadyAffectedItems, $eventName);
    }

    /**
     * @param string $orderReferenceId
     * @param string $status
     *
     * @return void
     */
    public function updateStatus($orderReferenceId, $status)
    {
        $this->getFactory()
            ->createPaymentProcessorModel()
            ->updateStatus($orderReferenceId, $status);
    }
}
