<?php

namespace TkhConsult\KinaPg\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\File;

class CustomFileType extends File
{
    /**
     * @return string[]
     */
    public function _getAllowedExtensions() {
        return ['key'];
    }
}
