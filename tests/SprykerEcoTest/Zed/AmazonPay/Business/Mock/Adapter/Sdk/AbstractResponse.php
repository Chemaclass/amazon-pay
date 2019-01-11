<?php

/**
 * Apache OSL-2
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\AmazonPay\Business\Mock\Adapter\Sdk;

use PayWithAmazon\ResponseParser;
use SprykerEco\Zed\AmazonPay\Business\Api\Adapter\AuthorizeAdapter;

class AbstractResponse
{
    /**
     * @var array
     */
    protected $responseData;

    /**
     * @var \PayWithAmazon\ResponseParser $responseParser
     */
    protected $responseParser;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $responseBodyXml;

    /**
     * @var string
     */
    protected $orderReferenceId;

    public const ORDER_REFERENCE_ID_1 = 'S02-5989383-0864061-0000001';
    public const ORDER_REFERENCE_ID_2 = 'S02-5989383-0864061-0000002';
    public const ORDER_REFERENCE_ID_3 = 'S02-5989383-0864061-0000003';
    public const ORDER_REFERENCE_ID_4 = 'S02-5989383-0864061-0000004';
    public const ORDER_REFERENCE_ID_5 = 'S02-5989383-0864061-0000005';
    public const ORDER_REFERENCE_ID_6 = 'S02-5989383-0864061-0000006';

    /**
     * @param array $requestParameters
     */
    public function __construct(array $requestParameters)
    {
        if (isset($requestParameters[AuthorizeAdapter::AMAZON_ORDER_REFERENCE_ID])) {
            $this->orderReferenceId = $requestParameters[AuthorizeAdapter::AMAZON_ORDER_REFERENCE_ID];
        }

        $this->statusCode = 200;
    }

    /**
     * @return \PayWithAmazon\ResponseParser
     */
    public function convertToResponseParser()
    {
        return new ResponseParser([
            'Status' => $this->statusCode,
            'ResponseBody' => $this->responseBodyXml,
        ]);
    }
}
