<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AmazonPay\Business\Api\Converter;

use Generated\Shared\Transfer\AmazonpayPriceTransfer;
use Generated\Shared\Transfer\AmazonpayStatusTransfer;
use SprykerEco\Shared\AmazonPay\AmazonPayConfig;

abstract class AbstractConverter
{
    public const STATUS_DECLINED = 'Declined';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_OPEN = 'Open';
    public const STATUS_CLOSED = 'Closed';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_SUSPENDED = 'Suspended';
    public const STATUS_CANCELLED = 'Canceled';

    public const FIELD_LAST_UPDATE_TIMESTAMP = 'LastUpdateTimestamp';
    public const FIELD_REASON_CODE = 'ReasonCode';
    public const FIELD_STATE = 'State';
    public const FIELD_AMOUNT = 'Amount';
    public const FIELD_CURRENCY_CODE = 'CurrencyCode';

    /**
     * @var array
     */
    protected $fieldToTransferMap = [
        self::FIELD_STATE => AmazonpayStatusTransfer::STATE,
        self::FIELD_REASON_CODE => AmazonpayStatusTransfer::REASON_CODE,
        self::FIELD_LAST_UPDATE_TIMESTAMP => AmazonpayStatusTransfer::LAST_UPDATE_TIMESTAMP,
    ];

    /**
     * @var array
     */
    protected $statusMap = [
        self::STATUS_DECLINED => AmazonPayConfig::STATUS_DECLINED,
        self::STATUS_SUSPENDED => AmazonPayConfig::STATUS_SUSPENDED,
        self::STATUS_PENDING => AmazonPayConfig::STATUS_PENDING,
        self::STATUS_OPEN => AmazonPayConfig::STATUS_OPEN,
        self::STATUS_CLOSED => AmazonPayConfig::STATUS_CLOSED,
        self::STATUS_COMPLETED => AmazonPayConfig::STATUS_COMPLETED,
        self::STATUS_CANCELLED => AmazonPayConfig::STATUS_CANCELLED,
    ];

    /**
     * @var array
     */
    protected $reasonToStatusMap = [
        AmazonPayConfig::REASON_CODE_AMAZON_CLOSED => AmazonPayConfig::STATUS_AMAZON_CLOSED,
        AmazonPayConfig::REASON_CODE_PAYMENT_METHOD_INVALID => AmazonPayConfig::STATUS_PAYMENT_METHOD_INVALID,
        AmazonPayConfig::REASON_CODE_TRANSACTION_TIMED_OUT => AmazonPayConfig::STATUS_TRANSACTION_TIMED_OUT,
        AmazonPayConfig::REASON_CODE_SELLER_CLOSED => AmazonPayConfig::STATUS_EXPIRED,
        AmazonPayConfig::REASON_CODE_EXPIRED_UNUSED => AmazonPayConfig::STATUS_EXPIRED,
    ];

    /**
     * @param array $priceData
     *
     * @return \Generated\Shared\Transfer\AmazonpayPriceTransfer
     */
    protected function convertPriceToTransfer(array $priceData)
    {
        $priceTransfer = new AmazonpayPriceTransfer();

        $priceTransfer->setAmount($priceData[static::FIELD_AMOUNT]);
        $priceTransfer->setCurrencyCode($priceData[static::FIELD_CURRENCY_CODE]);

        return $priceTransfer;
    }

    /**
     * @param array $statusData
     *
     * @return \Generated\Shared\Transfer\AmazonpayStatusTransfer
     */
    protected function convertStatusToTransfer(array $statusData)
    {
        $statusTransfer = new AmazonpayStatusTransfer();

        $mappedStatusData = $this->mapStatusToTransferProperties($statusData);
        $statusTransfer->fromArray($mappedStatusData, true);

        $statusName = $this->getStatusName($statusData);
        if ($statusName !== null) {
            $statusTransfer->setState($statusName);
        }

        $statusNameByReasonCode = $this->getStatusNameByReasonCode($statusData);
        if ($statusNameByReasonCode !== null) {
            $statusTransfer->setState($statusNameByReasonCode);
        }

        return $statusTransfer;
    }

    /**
     * @param array $statusData
     *
     * @return array
     */
    protected function mapStatusToTransferProperties(array $statusData)
    {
        $result = [];
        foreach ($this->fieldToTransferMap as $statusField => $propertyName) {
            $result[$propertyName] = $statusData[$statusField] ?? null;
        }

        return $result;
    }

    /**
     * @param array $statusData
     *
     * @return string|null
     */
    protected function getStatusName(array $statusData)
    {
        return $this->getValueByKeyFromMap($this->statusMap, $statusData, static::FIELD_STATE);
    }

    /**
     * @param array $statusData
     *
     * @return string|null
     */
    protected function getStatusNameByReasonCode(array $statusData)
    {
        return $this->getValueByKeyFromMap($this->reasonToStatusMap, $statusData, static::FIELD_REASON_CODE);
    }

    /**
     * @param array $map
     * @param array $statusData
     * @param string $key
     *
     * @return string|null
     */
    protected function getValueByKeyFromMap(array $map, array $statusData, $key)
    {
        if (!empty($statusData[$key])) {
            return $map[$statusData[$key]] ?? null;
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    protected function getNameData($name)
    {
        $names = explode(' ', $name, 2);

        if (count($names) === 2) {
            return $names;
        }

        return [$name, $name];
    }
}
