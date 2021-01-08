<?php
/**
 * Copyright © 2020 TkhConsult. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TkhConsult\KinaPg\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository;
use TkhConsult\KinaPg\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Repository
     */
    protected $assetRepo;

    const CODE = 'kinabank_gateway';
    const SUPPORT_CURRENCY = 'PGK';

    public function __construct(RequestInterface $request, Repository $assetRepo)
    {
        $this->request = $request;
        $this->assetRepo = $assetRepo;

    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ],
                    'logoImageUrl' => $this->getLogoImageUrl(),
                ]
            ]
        ];
    }

    /**
     * Retrieve CVV tooltip image url
     *
     * @return string
     */
    public function getLogoImageUrl()
    {
        return $this->getViewFileUrl('TkhConsult_KinaPg::logo.png');
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }
}
