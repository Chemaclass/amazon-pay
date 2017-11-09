<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\AmazonPay\Controller;

use Generated\Shared\Transfer\AmazonpayPaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Config\Config;
use Spryker\Yves\Kernel\Controller\AbstractController;
use SprykerEco\Shared\AmazonPay\AmazonPayConfig;
use SprykerEco\Shared\AmazonPay\AmazonPayConstants;
use SprykerEco\Yves\AmazonPay\Plugin\Provider\AmazonPayControllerProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Client\AmazonPay\AmazonPayClientInterface getClient()
 * @method \SprykerEco\Yves\AmazonPay\AmazonPayFactory getFactory()
 */
class PaymentController extends AbstractController
{
    const URL_PARAM_REFERENCE_ID = 'reference_id';
    const URL_PARAM_ACCESS_TOKEN = 'access_token';
    const URL_PARAM_SHIPMENT_METHOD_ID = 'shipment_method_id';
    const QUOTE_TRANSFER = 'quoteTransfer';
    const SHIPMENT_METHODS = 'shipmentMethods';
    const AMAZONPAY_CONFIG = 'amazonpayConfig';
    const IS_ASYNCHRONOUS = 'isAsynchronous';
    const CART_ITEMS = 'cartItems';
    const SUCCESS = 'success';
    const ERROR_AMAZONPAY_PAYMENT_FAILED = 'amazonpay.payment.failed';

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function checkoutAction(Request $request)
    {
        $quoteTransfer = $this->getFactory()
            ->getQuoteClient()
            ->getQuote();

        if (!$this->isAllowedCheckout($quoteTransfer) || !$this->isRequestComplete($request)) {
            return $this->getFailedRedirectResponse();
        }

        $amazonPaymentTransfer = $this->buildAmazonPaymentTransfer($request);

        $quoteTransfer->setAmazonpayPayment($amazonPaymentTransfer);
        $quoteTransfer = $this->getClient()
            ->handleCartWithAmazonPay($quoteTransfer);
        $this->getFactory()
            ->getQuoteClient()
            ->setQuote($quoteTransfer);

        return [
            static::QUOTE_TRANSFER => $quoteTransfer,
            static::CART_ITEMS => $this->getCartItems($quoteTransfer),
            static::AMAZONPAY_CONFIG => $this->getAmazonPayConfig(),
        ];
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setOrderReferenceAction(Request $request)
    {
        $quoteTransfer = $this->getFactory()
            ->getQuoteClient()
            ->getQuote();

        if (!$this->isAmazonPayment($quoteTransfer)) {
            return $this->getFailedRedirectResponse();
        }

        $quoteTransfer->getAmazonpayPayment()
            ->setOrderReferenceId(
                $request->request->get(static::URL_PARAM_REFERENCE_ID)
            );

        return new JsonResponse([static::SUCCESS => true]);
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function getShipmentMethodsAction()
    {
        $quoteTransfer = $this->getFactory()
            ->getQuoteClient()
            ->getQuote();

        if (!$this->isAmazonPayment($quoteTransfer)) {
            return $this->getFailedRedirectResponse();
        }

        $quoteTransfer = $this->getClient()
            ->addSelectedAddressToQuote($quoteTransfer);
        $this->getFactory()
            ->getQuoteClient()
            ->setQuote($quoteTransfer);
        $shipmentMethods = $this->getFactory()
            ->getShipmentClient()
            ->getAvailableMethods($quoteTransfer);

        return [
            static::SHIPMENT_METHODS => $shipmentMethods->getMethods(),
        ];
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateShipmentMethodAction(Request $request)
    {
        $quoteTransfer = $this->getFactory()->getQuoteClient()->getQuote();

        if (!$this->isAmazonPayment($quoteTransfer)) {
            return $this->getFailedRedirectResponse();
        }

        $quoteTransfer->getShipment()->setShipmentSelection(
            $request->request->get(static::URL_PARAM_SHIPMENT_METHOD_ID)
        );
        $quoteTransfer = $this->getClient()
            ->addSelectedShipmentMethodToQuote($quoteTransfer);
        $quoteTransfer = $this->getFactory()
            ->getCalculationClient()->recalculate($quoteTransfer);
        $this->getFactory()
            ->getQuoteClient()
            ->setQuote($quoteTransfer);

        return [
            static::QUOTE_TRANSFER => $quoteTransfer,
        ];
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function confirmPurchaseAction(Request $request)
    {
        $quoteTransfer = $this->getFactory()->getQuoteClient()->getQuote();

        if (!$this->isAmazonPayment($quoteTransfer)) {
            return $this->getFailedRedirectResponse();
        }

        $quoteTransfer = $this->getClient()->confirmPurchase($quoteTransfer);

        if ($quoteTransfer->getAmazonpayPayment()
            ->getAuthorizationDetails()
            ->getAuthorizationStatus()
            ->getState() === AmazonPayConfig::STATUS_PAYMENT_METHOD_INVALID
        ) {
            return $this->redirectResponseInternal(AmazonPayControllerProvider::CHECKOUT);
        }

        if (!$quoteTransfer->getAmazonpayPayment()->getResponseHeader()->getIsSuccess()) {
            return $this->getRedirectForError($quoteTransfer, $request);
        }

        $quoteTransfer = $this->getFactory()->getCalculationClient()->recalculate($quoteTransfer);
        $this->getFactory()->getQuoteClient()->setQuote($quoteTransfer);

        $checkoutResponseTransfer = $this->getFactory()->getCheckoutClient()->placeOrder($quoteTransfer);

        if ($checkoutResponseTransfer->getIsSuccess()) {
            return $this->redirectResponseInternal(AmazonPayControllerProvider::SUCCESS);
        }

        return $this->getFailedRedirectResponse();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function successAction(Request $request)
    {
        $this->getFactory()->getQuoteClient()->clearQuote();

        return [
            static::IS_ASYNCHRONOUS => $this->isAsynchronous(),
            static::AMAZONPAY_CONFIG => $this->getAmazonPayConfig(),
        ];
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \ArrayObject|\Generated\Shared\Transfer\ItemTransfer[]
     */
    protected function getCartItems(QuoteTransfer $quoteTransfer)
    {
        return $quoteTransfer->getItems();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return bool
     */
    protected function isRequestComplete(Request $request)
    {
        return $request->query->get(static::URL_PARAM_REFERENCE_ID) !== null &&
        $request->query->get(static::URL_PARAM_ACCESS_TOKEN) !== null;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Generated\Shared\Transfer\AmazonpayPaymentTransfer
     */
    protected function buildAmazonPaymentTransfer(Request $request)
    {
        $amazonPaymentTransfer = new AmazonpayPaymentTransfer();
        $amazonPaymentTransfer->setOrderReferenceId($request->query->get(static::URL_PARAM_REFERENCE_ID));
        $amazonPaymentTransfer->setAddressConsentToken($request->query->get(static::URL_PARAM_ACCESS_TOKEN));

        return $amazonPaymentTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return bool
     */
    protected function isAllowedCheckout(QuoteTransfer $quoteTransfer)
    {
        return $quoteTransfer->getTotals() !== null;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getRedirectForError(QuoteTransfer $quoteTransfer, Request $request)
    {
        $this->addErrorMessage(
            $this->getErrorMessageFromQuote($quoteTransfer)
        );

        return $this->redirectResponseExternal($request->headers->get('Referer'));
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return bool
     */
    protected function isAmazonPayment(QuoteTransfer $quoteTransfer)
    {
        return $quoteTransfer->getAmazonpayPayment() !== null;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return string
     */
    protected function getErrorMessageFromQuote(QuoteTransfer $quoteTransfer)
    {
        return $quoteTransfer->getAmazonpayPayment()->getResponseHeader()->getErrorMessage()
                ?? static::ERROR_AMAZONPAY_PAYMENT_FAILED;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getFailedRedirectResponse()
    {
        $this->addErrorMessage(static::ERROR_AMAZONPAY_PAYMENT_FAILED);

        return $this->redirectResponseInternal($this->getPaymentRejectRoute());
    }

    /**
     * @return string
     */
    protected function getPaymentRejectRoute()
    {
        return Config::get(AmazonPayConstants::PAYMENT_REJECT_ROUTE);
    }

    protected function isAsynchronous()
    {
        return $this->getAmazonPayConfig()->getAuthTransactionTimeout() > 0
            && !$this->getAmazonPayConfig()->getCaptureNow();
    }

    /**
     * @return \SprykerEco\Shared\AmazonPay\AmazonPayConfigInterface
     */
    protected function getAmazonPayConfig()
    {
        return $this->getFactory()->createAmazonPayConfig();
    }
}
