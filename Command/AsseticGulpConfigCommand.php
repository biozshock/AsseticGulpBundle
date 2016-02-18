<?php

namespace Biozshock\AsseticGulpBundle\Command;

use Biozshock\AsseticGulpBundle\GulpAsset;
use Biozshock\AsseticGulpBundle\Assetic\AssetReference;
use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Util\VarUtils;
use Symfony\Bundle\AsseticBundle\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AsseticGulpConfigCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $configPath;

    /**
     * @var string
     */
    private $relative = null;

    protected function configure()
    {
        $this
            ->setName('assetic:gulp')
            ->setDescription('Dumps all assets to the gulp config file')
            ->addArgument('write_to', InputArgument::OPTIONAL, 'Override the configured asset root')
            ->addArgument('config_path', InputArgument::OPTIONAL, 'Override config path')
            ->addOption('absolute', 'a', null, 'Use absolute path in config')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $stdout)
    {
        parent::initialize($input, $stdout);

        if (!$input->getOption('absolute')) {
            $this->relative = getcwd();
        }

        if ($input->hasArgument('config_path') && $configPath = $input->getArgument('config_path')) {
            $this->configPath = $configPath;
        } else {
            $this->configPath = $this->getContainer()->getParameter('assetic.gulp.config_path');
        }
    }

    /**
     * @param mixed $configPath
     */
    public function setConfigPath($configPath)
    {
        $this->configPath = $configPath;
    }

    protected function execute(InputInterface $input, OutputInterface $stdout)
    {
        // print the header
        $stdout->writeln(sprintf('Dumping all <comment>%s</comment> assets.', $input->getOption('env')));
        $stdout->writeln(sprintf('Debug mode is <comment>%s</comment>.', $this->am->isDebug() ? 'on' : 'off'));
        $stdout->writeln('');

        $assets = [];
        foreach ($this->am->getNames() as $name) {
            $assets = array_merge($assets, $this->dumpAsset($name, $stdout));
        }

        $jsonFlags = 0;
        if ($this->getContainer()->getParameter('kernel.debug')) {
            $jsonFlags = JSON_PRETTY_PRINT;
        }

        $gulpConfig = json_encode($assets, $jsonFlags);

        if (false === @file_put_contents($this->configPath, $gulpConfig)) {
            throw new \RuntimeException('Unable to write file ' . $this->configPath);
        }
    }

    /**
     * Writes an asset.
     *
     * If the application or asset is in debug mode, each leaf asset will be
     * dumped as well.
     *
     * @param string          $name   An asset name
     * @param OutputInterface $stdout The command output
     */
    public function dumpAsset($name, OutputInterface $stdout)
    {
        $asset = $this->am->get($name);
        $formula = $this->am->hasFormula($name) ? $this->am->getFormula($name) : array();

        // start by dumping the main asset
        $assets = $this->getGulpConfig($asset, $stdout);

        $debug = isset($formula[2]['debug']) ? $formula[2]['debug'] : $this->am->isDebug();
        $combine = isset($formula[2]['combine']) ? $formula[2]['combine'] : !$debug;

        if (!$combine) {
            foreach ($asset as $leaf) {
                $assets = array_merge($assets, $this->getGulpConfig($leaf, $stdout));
            }
        }

        return $assets;
    }

    /**
     * Performs the asset dump.
     *
     * @param AssetInterface $asset An asset
     * @param OutputInterface $stdout The command output
     * @return array
     */
    private function getGulpConfig(AssetInterface $asset, OutputInterface $stdout)
    {
        $combinations = VarUtils::getCombinations(
            $asset->getVars(),
            $this->getContainer()->getParameter('assetic.variables')
        );

        // see http://stackoverflow.com/questions/30133597/is-there-a-way-to-merge-less-with-css-in-gulp
        // for a method of merging css and less

        $assets = [];
        foreach ($combinations as $combination) {
            $asset->setValues($combination);

            // resolve the target path
            $target = rtrim($this->basePath, '/').'/'.$asset->getTargetPath();
            $target = str_replace('_controller/', '', $target);
            $target = VarUtils::resolve($target, $asset->getVars(), $asset->getValues());

            if (!is_dir($dir = dirname($target))) {
                $stdout->writeln(sprintf(
                    '<comment>%s</comment> <info>[dir+]</info> %s',
                    date('H:i:s'),
                    $dir
                ));

                if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException('Unable to create directory '.$dir);
                }
            }

            $stdout->writeln(sprintf(
                '<comment>%s</comment> <info>[file+]</info> %s',
                date('H:i:s'),
                $target
            ));

            $gulpAsset = new GulpAsset();
            $gulpAsset->setDestination($this->getPath($target));
            $asset = $this->resolveAsset($asset);

            $this->gulpAsset($gulpAsset, $asset, $stdout);

            $assets[] = $gulpAsset->getArrayConfig();
        }

        return $assets;
    }

    private function outputVerbosity(OutputInterface $stdout, $root, $path)
    {
        if (OutputInterface::VERBOSITY_VERBOSE <= $stdout->getVerbosity()) {
            $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
        }
    }

    private function gulpAsset(GulpAsset $gulpAsset, AssetInterface $asset, OutputInterface $stdout)
    {
        if ($asset instanceof AssetCollectionInterface) {
            foreach ($asset as $leaf) {
                /** @var AssetInterface $leaf */

                $leaf = $this->resolveAsset($leaf);

                $this->gulpAsset($gulpAsset, $leaf, $stdout);
            }
        } else {
            $root = $asset->getSourceRoot();
            $path = $asset->getSourcePath();

            // if path do not starts with /  and root do not have / at the end
            if (strpos($path, '/') === 0 || strrpos($root, '/') !== strlen($root) - 1) {
                $root .= '/';
            }

            $source = $this->getPath($root . $path);

            $gulpAsset->addSource($source);
            $this->outputVerbosity($stdout, $root, $path);
        }
    }

    private function resolveAsset(AssetInterface $asset)
    {
        if ($asset instanceof AssetReference) {
            return $this->am->get($asset->getAssetName());
        }

        return $asset;
    }

    private function getPath($path)
    {
        $path = static::normalizeRelativePath($path);

        if ($this->relative) {
            $path = ltrim(str_replace($this->relative, '', $path), '/');
        }

        return $path;
    }

    /**
     * Normalize relative directories in a path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizeRelativePath($path)
    {
        // Path remove self referring paths ("/./").
        $path = preg_replace('#/\.(?=/)|^\./|/\./?$#', '', $path);

        // Regex for resolving relative paths
        $regex = '#/*[^/\.]+/\.\.#Uu';

        while (preg_match($regex, $path)) {
            $path = preg_replace($regex, '', $path);
        }

        return $path;
    }
}
