<?php
namespace Meetanshi\DistanceBasedShipping\Helper;

use Magento\Framework\Unserialize\Unserialize;
use Magento\Framework\Serialize\Serializer\Json;

class Data
{
    protected $unserialize;
    protected $json;

    public function __construct(
        Unserialize $unserialize,
        Json $json
    ) {
        $this->unserialize=$unserialize;
        $this->json=$json;
    }

    public function getSerializedConfigValue($value)
    {
        if (empty($value)) {
            return false;
        }

        if ($this->isSerialized($value)) {
            $unserializer = $this->unserialize;
        } else {
            $unserializer = $this->json;
        }

        return $unserializer->unserialize($value);
    }
    private function isSerialized($value)
    {
        return (boolean) preg_match('/^((s|i|d|b|a|O|C):|N;)/', $value);
    }
}
