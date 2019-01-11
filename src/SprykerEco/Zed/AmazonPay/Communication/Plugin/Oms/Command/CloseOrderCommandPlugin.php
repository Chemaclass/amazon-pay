<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Communication\Plugin\Oms\Command;

use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject;

/**
 * @method \SprykerEco\Zed\AmazonPay\Business\AmazonPayFacadeInterface getFacade()
 */
class CloseOrderCommandPlugin extends AbstractAmazonpayCommandPlugin
{
    /**
     * @api
     *
     * @param array $salesOrderItems
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject $data
     *
     * @return array
     */
    public function run(array $salesOrderItems, SpySalesOrder $orderEntity, ReadOnlyArrayObject $data)
    {
        $amazonpayCallTransfer = $this->createAmazonpayCallTransfer(
            $this->getPayment($salesOrderItems)
        );

        $this->getFacade()->closeOrder($amazonpayCallTransfer);

        return [];
    }
}
