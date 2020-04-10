<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\AmazonPay\Dependency\Client;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;

class AmazonPayToShipmentBridge implements AmazonPayToShipmentInterface
{
    /**
     * @var \Spryker\Client\Shipment\ShipmentClientInterface
     */
    protected $shipmentClient;

    /**
     * @param \Spryker\Client\Shipment\ShipmentClientInterface $shipmentClient
     */
    public function __construct($shipmentClient)
    {
        $this->shipmentClient = $shipmentClient;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\ShipmentMethodsTransfer
     */
    public function getAvailableMethods(QuoteTransfer $quoteTransfer)
    {
        if (method_exists($this->shipmentClient, 'getAvailableMethodsByShipment') === true) {

            foreach ($quoteTransfer->getItems() as $itemTransfer) {
                $itemTransfer->setShipment(new ShipmentTransfer());
                $itemTransfer->getShipment()->setShippingAddress(new AddressTransfer());
            }

            $shipmentMethodsCollectionTransfer = $this->shipmentClient->getAvailableMethodsByShipment($quoteTransfer);

            if ($shipmentMethodsCollectionTransfer->getShipmentMethods()->count() > 1) {
                throw new \RuntimeException('Split shipping is not supported');
            }

            foreach ($quoteTransfer->getItems() as $itemTransfer) {
                $itemTransfer->setShipment(null);
            }

            $shipmentMethodsTransfer = $shipmentMethodsCollectionTransfer
                ->getShipmentMethods()
                ->getIterator()
                ->current();

            return $shipmentMethodsTransfer;
        }

        return $this->shipmentClient->getAvailableMethods($quoteTransfer);
    }
}
