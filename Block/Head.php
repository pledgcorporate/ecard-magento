<?php

namespace Pledg\PledgPaymentGateway\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;

class Head extends Template
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepository;

    protected $_scopeConfig;

    /**
     * Header constructor.
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->assetRepository = $context->getAssetRepository();
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getCustomJs()
    {
        if ($this->_scopeConfig->getValue('payment/pledg_gateway/staging')) {
            return 'https://s3-eu-west-1.amazonaws.com/pledg-assets/ecard-plugin/staging/plugin.min.js';
        }

        return 'https://s3-eu-west-1.amazonaws.com/pledg-assets/ecard-plugin/master/plugin.min.js';
    }
}
