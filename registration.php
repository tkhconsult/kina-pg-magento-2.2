<?php
/**
* Copyright © 2020 TkhConsult. All rights reserved.
* See COPYING.txt for license details.
*/
require_once(__DIR__ . '/vendor/autoload.php');

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'TkhConsult_KinaPg',
    __DIR__
);
