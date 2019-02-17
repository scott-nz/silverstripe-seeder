<?php
namespace Seeder\Extensions;

use SilverStripe\Core\Extension;

/**
 * Class IsSeededExtension
 */
class IsSeededExtension extends Extension
{
    /**
     * @var bool
     */
    private $isSeeded = false;

    /**
     * @return bool
     */
    public function isSeeded()
    {
        return $this->isSeeded;
    }

    /**
     * @param bool $bool
     */
    public function setIsSeeded($bool = true)
    {
        $this->isSeeded = $bool;
    }
}
