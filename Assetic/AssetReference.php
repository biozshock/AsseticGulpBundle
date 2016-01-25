<?php

namespace Biozshock\AsseticGulpBundle\Assetic;

use Assetic\Asset\AssetReference as BaseAssetReference;
use Assetic\AssetManager;

class AssetReference extends BaseAssetReference
{
    /**
     * @var string
     */
    private $assetName;

    public function __construct(AssetManager $am, $name)
    {
        parent::__construct($am, $name);
        $this->assetName = $name;
    }

    /**
     * @return string
     */
    public function getAssetName()
    {
        return $this->assetName;
    }

}
