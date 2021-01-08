<?php

namespace TkhConsult\KinaPg\Gateway\Validator;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CountryValidator extends AbstractValidator
{
    private $config;

    public function __construct(ResultInterfaceFactory $resultFactory, ConfigInterface $config)
    {
        $this->config = $config;
        parent::__construct($resultFactory);
    }

    public function validate(array $validationSubject)
    {
        $isValid = true;
        $storeId = $validationSubject['storeId'];

        if ((int)$this->config->getValue('allowspecific', $storeId) === 1) {
            $availableCountries = explode(
                ',',
                $this->config->getValue('specificcountry', $storeId)
            );

            if (!in_array($validationSubject['country'], $availableCountries)) {
                $isValid =  false;
            }
        }

        return $this->createResult($isValid);
    }
}
