<?php

namespace Biozshock\AsseticGulpBundle\Assetic;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory as BaseAssetFactory;

class AssetFactory extends BaseAssetFactory
{
    protected function createAssetReference($name)
    {
        // set asset manager
        parent::createAssetReference($name);

        return new AssetReference($this->getAssetManager(), $name);
    }
}
