<?php

namespace Xcentia\Coster\Model\ResourceModel\Product;

use Magento\Framework\Authorization;
use Magento\Framework\ObjectManagerInterface;

class Factory
{
    /**
     * Entity class name
     */
    const CLASS_NAME = \Magento\Framework\Authorization::class;

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return Authorization
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create(self::CLASS_NAME, $data);
    }
}
