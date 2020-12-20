<?php
namespace Onestepcheckout\Iosc\Api;

/**
 * ExtensionInterface class for @see \Magento\Quote\Api\Data\AddressInterface
 */
interface DobAssignmentInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    /**
     *
     * @return string|null
     */
    public function getDob();

    /**
     *
     * @param string $value
     * @return $this
     */
    public function setDob(
        $value
    );
}
