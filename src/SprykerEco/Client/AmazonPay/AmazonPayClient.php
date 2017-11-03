<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\AmazonPay;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerEco\Client\AmazonPay\AmazonPayFactory getFactory()
 */
class AmazonPayClient extends AbstractClient implements AmazonPayClientInterface
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
        return $this->getFactory()->createZedStub()->handleCartWithAmazonPay($quoteTransfer);
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
        return $this->getFactory()->createZedStub()->addSelectedAddressToQuote($quoteTransfer);
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
        return $this->getFactory()->createZedStub()->addSelectedShipmentMethodToQuote($quoteTransfer);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Spryker\Shared\Kernel\Transfer\TransferInterface
     */
    public function confirmPurchase(QuoteTransfer $quoteTransfer)
    {
        return $this->getFactory()->createZedStub()->confirmPurchase($quoteTransfer);
    }
}
