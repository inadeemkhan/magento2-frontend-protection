<?php

namespace DevScripts\Password\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Image as MagentoImage;
use Magento\Framework\UrlInterface;

class Image extends MagentoImage
{
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath('password_page');
    }

    protected function _addWhetherScopeInfo()
    {
        return false;
    }

    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'];
    }

    protected function _getUrl()
    {
        if (!$this->getValue()) {
            return '';
        }

        return $this->_urlBuilder->getBaseUrl(
            UrlInterface::URL_TYPE_MEDIA
        ) . 'password_page/' . ltrim(str_replace('default/', '', $this->getValue()), '/');
    }
}