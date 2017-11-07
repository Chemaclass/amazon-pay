<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn;

use Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;
use SprykerEco\Shared\AmazonPay\AmazonPayConfig;
use SprykerEco\Zed\AmazonPay\Business\Order\RefundOrderInterface;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Authorize\IpnPaymentAuthorizeClosedHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Authorize\IpnPaymentAuthorizeDeclineHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Authorize\IpnPaymentAuthorizeOpenHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Authorize\IpnPaymentAuthorizeSuspendedHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Logger\IpnRequestLoggerInterface;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\OrderReference\IpnOrderReferenceCancelledHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\OrderReference\IpnOrderReferenceClosedHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\OrderReference\IpnOrderReferenceOpenHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\OrderReference\IpnOrderReferenceSuspendedHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Capture\IpnPaymentCaptureCompletedHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Capture\IpnPaymentCaptureDeclineHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Refund\IpnPaymentRefundCompletedHandler;
use SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Refund\IpnPaymentRefundDeclineHandler;
use SprykerEco\Zed\AmazonPay\Dependency\Facade\AmazonPayToOmsInterface;
use SprykerEco\Zed\AmazonPay\Persistence\AmazonPayQueryContainerInterface;

class IpnRequestFactory implements IpnRequestFactoryInterface
{
    /**
     * @var \SprykerEco\Zed\AmazonPay\Dependency\Facade\AmazonPayToOmsInterface
     */
    protected $omsFacade;

    /**
     * @var \SprykerEco\Zed\AmazonPay\Persistence\AmazonPayQueryContainerInterface
     */
    protected $amazonpayQueryContainer;

    /**
     * @var \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Logger\IpnRequestLoggerInterface
     */
    protected $ipnRequestLogger;

    /**
     * @var \SprykerEco\Zed\AmazonPay\Business\Order\RefundOrderInterface
     */
    protected $refundOrderModel;

    /**
     * @param \SprykerEco\Zed\AmazonPay\Dependency\Facade\AmazonPayToOmsInterface $omsFacade
     * @param \SprykerEco\Zed\AmazonPay\Persistence\AmazonPayQueryContainerInterface $amazonpayQueryContainer
     * @param \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\Logger\IpnRequestLoggerInterface $ipnRequestLogger
     * @param \SprykerEco\Zed\AmazonPay\Business\Order\RefundOrderInterface $refundOrderModel
     */
    public function __construct(
        AmazonPayToOmsInterface $omsFacade,
        AmazonPayQueryContainerInterface $amazonpayQueryContainer,
        IpnRequestLoggerInterface $ipnRequestLogger,
        RefundOrderInterface $refundOrderModel
    ) {
        $this->omsFacade = $omsFacade;
        $this->amazonpayQueryContainer = $amazonpayQueryContainer;
        $this->ipnRequestLogger = $ipnRequestLogger;
        $this->refundOrderModel = $refundOrderModel;
    }

    /**
     * @param \Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer $paymentRequestTransfer
     *
     * @throws \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnHandlerNotFoundException
     *
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    public function createConcreteIpnRequestHandler(AmazonpayIpnPaymentRequestTransfer $paymentRequestTransfer)
    {
        $map = $this->getNotificationTypeToHandlerMap();

        if (isset($map[$paymentRequestTransfer->getMessage()->getNotificationType()])) {
            return $map[$paymentRequestTransfer->getMessage()->getNotificationType()]($paymentRequestTransfer);
        }

        throw new IpnHandlerNotFoundException('Unknown IPN Notification type: ' .
            $paymentRequestTransfer->getMessage()->getNotificationType());
    }

    /**
     * @return array
     */
    protected function getNotificationTypeToHandlerMap()
    {
        return [
            AmazonPayConfig::IPN_REQUEST_TYPE_PAYMENT_AUTHORIZE => function (AbstractTransfer $ipnRequest) {
                return $this->createIpnPaymentAuthorizeHandler($ipnRequest);
            },
            AmazonPayConfig::IPN_REQUEST_TYPE_PAYMENT_CAPTURE => function (AbstractTransfer $ipnRequest) {
                return $this->createIpnPaymentCaptureHandler($ipnRequest);
            },
            AmazonPayConfig::IPN_REQUEST_TYPE_PAYMENT_REFUND => function (AbstractTransfer $ipnRequest) {
                return $this->createIpnPaymentRefundHandler($ipnRequest);
            },
            AmazonPayConfig::IPN_REQUEST_TYPE_ORDER_REFERENCE_NOTIFICATION => function (AbstractTransfer $ipnRequest) {
                return $this->createIpnOrderReferenceHandler($ipnRequest);
            },
        ];
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer | \Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer $ipnRequest
     *
     * @throws \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnHandlerNotFoundException
     *
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentAuthorizeHandler(AbstractTransfer $ipnRequest)
    {
        if ($ipnRequest->getAuthorizationDetails()->getAuthorizationStatus()->getIsSuspended()) {
            return $this->createIpnPaymentAuthorizeSuspendedHandler();
        }

        if ($ipnRequest->getAuthorizationDetails()->getAuthorizationStatus()->getIsDeclined()) {
            return $this->createIpnPaymentAuthorizeDeclineHandler();
        }

        if ($ipnRequest->getAuthorizationDetails()->getAuthorizationStatus()->getIsOpen()) {
            return $this->createIpnPaymentAuthorizeOpenHandler();
        }

        if ($ipnRequest->getAuthorizationDetails()->getAuthorizationStatus()->getIsClosed()) {
            return $this->createIpnPaymentAuthorizeClosedHandler();
        }

        throw new IpnHandlerNotFoundException('No IPN handler for auth payment and status ' .
            $ipnRequest->getAuthorizationDetails()->getAuthorizationStatus()->getState());
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentAuthorizeSuspendedHandler()
    {
        return new IpnPaymentAuthorizeSuspendedHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentAuthorizeDeclineHandler()
    {
            return new IpnPaymentAuthorizeDeclineHandler(
                $this->omsFacade,
                $this->amazonpayQueryContainer,
                $this->ipnRequestLogger
            );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentAuthorizeOpenHandler()
    {
        return new IpnPaymentAuthorizeOpenHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentAuthorizeClosedHandler()
    {
        return new IpnPaymentAuthorizeClosedHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer | \Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer $ipnRequest
     *
     * @throws \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnHandlerNotFoundException
     *
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentCaptureHandler(AbstractTransfer $ipnRequest)
    {
        if ($ipnRequest->getCaptureDetails()->getCaptureStatus()->getIsDeclined()) {
            return $this->createIpnPaymentCaptureDeclineHandler();
        }

        if ($ipnRequest->getCaptureDetails()->getCaptureStatus()->getIsCompleted()) {
            return $this->createIpnPaymentCaptureCompletedHandler();
        }

        if ($ipnRequest->getCaptureDetails()->getCaptureStatus()->getIsClosed()) {
            return $this->createIpnEmptyHandler();
        }

        throw new IpnHandlerNotFoundException('No IPN handler for capture and status ' .
            $ipnRequest->getCaptureDetails()->getCaptureStatus()->getState());
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentCaptureDeclineHandler()
    {
        return new IpnPaymentCaptureDeclineHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentCaptureCompletedHandler()
    {
        return new IpnPaymentCaptureCompletedHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnEmptyHandler()
    {
        return new IpnEmptyHandler();
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer | \Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer $ipnRequest
     *
     * @throws \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnHandlerNotFoundException
     *
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentRefundHandler(AbstractTransfer $ipnRequest)
    {
        if ($ipnRequest->getRefundDetails()->getRefundStatus()->getIsDeclined()) {
            return $this->createIpnPaymentRefundDeclineHandler();
        }

        if ($ipnRequest->getRefundDetails()->getRefundStatus()->getIsCompleted()) {
            return $this->createIpnPaymentRefundCompletedHandler();
        }

        throw new IpnHandlerNotFoundException('No IPN handler for payment refund and status ' .
            $ipnRequest->getRefundDetails()->getRefundStatus()->getState());
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentRefundDeclineHandler()
    {
        return new IpnPaymentRefundDeclineHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnPaymentRefundCompletedHandler()
    {
        return new IpnPaymentRefundCompletedHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger,
            $this->refundOrderModel
        );
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer | \Generated\Shared\Transfer\AmazonpayIpnPaymentRequestTransfer $ipnRequest
     *
     * @throws \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnHandlerNotFoundException
     *
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnOrderReferenceHandler(AbstractTransfer $ipnRequest)
    {
        if ($ipnRequest->getOrderReferenceStatus()->getIsOpen()) {
            return $this->createIpnOrderReferenceOpenHandler();
        }

        if ($ipnRequest->getOrderReferenceStatus()->getIsClosed()) {
            if ($ipnRequest->getOrderReferenceStatus()->getIsClosedByAmazon()) {
                return $this->createIpnOrderReferenceClosedHandler();
            }

            return $this->createIpnEmptyHandler();
        }

        if ($ipnRequest->getOrderReferenceStatus()->getIsSuspended()) {
            return $this->createIpnOrderReferenceSuspendedHandler();
        }

        if ($ipnRequest->getOrderReferenceStatus()->getIsCancelled()) {
            return $this->createIpnOrderReferenceCancelledHandler();
        }

        throw new IpnHandlerNotFoundException('No IPN handler for order reference and status ' .
            $ipnRequest->getOrderReferenceStatus()->getState());
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnOrderReferenceOpenHandler()
    {
        return new IpnOrderReferenceOpenHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnOrderReferenceClosedHandler()
    {
        return new IpnOrderReferenceClosedHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnOrderReferenceSuspendedHandler()
    {
        return new IpnOrderReferenceSuspendedHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }

    /**
     * @return \SprykerEco\Zed\AmazonPay\Business\Payment\Handler\Ipn\IpnRequestHandlerInterface
     */
    protected function createIpnOrderReferenceCancelledHandler()
    {
        return new IpnOrderReferenceCancelledHandler(
            $this->omsFacade,
            $this->amazonpayQueryContainer,
            $this->ipnRequestLogger
        );
    }
}
