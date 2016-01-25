<?php

namespace Biozshock\AsseticGulpBundle;

class GulpAsset
{
    /**
     * @var array source files
     */
    private $sources = [];

    /**
     * @var string source path
     */
    private $destination;

    /**
     * @var array types (extensions) of files
     */
    private $types = [];

    public function addRootSource($root, $path)
    {
        // if path do not starts with /  and root do not have / at the end
        if (strpos($path, '/') === 0 || strrpos($root, '/') !== strlen($root) - 1) {
            $root .= '/';
        }

        $this->addSource($root . $path);
    }

    /**
     * @param string $source
     */
    public function addSource($source)
    {
        $this->types[pathinfo($source, \PATHINFO_EXTENSION)] = true;
        $this->sources[] = $source;
    }

    /**
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    public function getArrayConfig()
    {
        $pathInfo = pathinfo($this->destination);
        return [
            'sources' => array_unique($this->sources),
            'destination' => [
                'path' => $pathInfo['dirname'],
                'file' => $pathInfo['basename'],
            ],
            'types' => array_keys($this->types),
        ];
    }

    /**
     * @return string JSON representation of asset config
     */
    public function __toString()
    {
        $assetConfig = $this->getArrayConfig();

        return json_encode($assetConfig);
    }


}
