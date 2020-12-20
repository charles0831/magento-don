<?php
namespace Onestepcheckout\Iosc\Api;

/**
 * ExtensionInterface class for @see \Magento\Quote\Api\Data\AddressInterface
 */
interface GenderAssignmentInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    /**
     *
     * @return string|null
     */
    public function getGender();

    /**
     *
     * @param string $value
     * @return $this
     */
    public function setGender(
        $value
    );
}
