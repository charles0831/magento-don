<?php

namespace Meetanshi\DistanceBasedShipping\Model;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Config\Model\Config\CommentInterface;

/**
 * Class Comment
 * @package Meetanshi\WhatsappContact\Model
 */
class GoogleApiComment extends AbstractBlock implements CommentInterface
{
    /**
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue)
    {
        $url = 'https://cloud.google.com/console/google/maps-apis/overview?authuser=1';
        return "Go to the <a href='$url'>Google Cloud Platform Console</a>. Click the menu button and <b>select APIs & Services > Credentials</b>. On the <b>Credentials</b> page, click <b>Create credentials > API key</b>.";
    }
}
