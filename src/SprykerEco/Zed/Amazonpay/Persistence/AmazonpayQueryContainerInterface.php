<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Amazonpay\Persistence;

use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Spryker\Zed\Kernel\Persistence\QueryContainer\QueryContainerInterface;

interface AmazonpayQueryContainerInterface extends QueryContainerInterface
{

    /**
     * @api
     *
     * @param string $orderReferenceId
     *
     * @return \Orm\Zed\Amazonpay\Persistence\SpyPaymentAmazonpayQuery
     */
    public function queryPaymentByOrderReferenceId($orderReferenceId);

    /**
     * @api
     *
     * @param string $authorizationReferenceId
     *
     * @return \Orm\Zed\Amazonpay\Persistence\SpyPaymentAmazonpayQuery
     */
    public function queryPaymentByAuthorizationReferenceId($authorizationReferenceId);

    /**
     * @api
     *
     * @param string $captureReferenceId
     *
     * @return \Orm\Zed\Amazonpay\Persistence\SpyPaymentAmazonpayQuery
     */
    public function queryPaymentByCaptureReferenceId($captureReferenceId);

    /**
     * @api
     *
     * @param string $refundReferenceId
     *
     * @return \Orm\Zed\Amazonpay\Persistence\SpyPaymentAmazonpayQuery
     */
    public function queryPaymentByRefundReferenceId($refundReferenceId);

    /**
     * @api
     *
     * @param string $authorizationReferenceId
     * @param SpySalesOrderItem[] $excludeItems
     *
     * @return \Orm\Zed\Amazonpay\Persistence\SpyPaymentAmazonpayQuery
     */
    public function querySalesOrderItemsByPaymentReferenceId($authorizationReferenceId, $excludeItems = []);

}
