<?php

namespace app\build;

use yii\helpers\FileHelper;
use yii\web\AssetBundle;
use yii\console\Exception;
use Yii;
use yii\helpers\Console;
use yii\helpers\VarDumper;

/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/6/14
 * Time: 下午5:00
 */
class AssetController extends \yii\console\controllers\AssetController
{
    public $defaultAction = 'publish';
    /**
     * @var array 静态资源
     */
    public $statics = [];

    function actionPublish($configFile,$bundleFile)
    {
        $this->loadConfiguration($configFile);
        $am = $this->getAssetManager();
        $published = [];
        foreach ($this->statics as $name){
            $bundle = $am->getBundle($name,false);
            if(isset($bundle->basePath,$bundle->baseUrl)){
                $published[$name] = $this->publishStaticDirectory($bundle);
                //已经copy的,不需要sourcePath
                $bundle->sourcePath = null;
            }
        }
        $class = new \ReflectionClass($am);
        $prop = $class->getProperty('_published');
        $prop->setAccessible(true);
        $prop->setValue($am,$published);
        $this->compress($bundleFile);
    }

    /**
     * @param AssetBundle $bundle
     * @return bool whether asset bundle external or not.
     */
    protected function isBundleExternal($bundle)
    {
        if(in_array($bundle::classname(),$this->statics)){
            return empty($bundle->sourcePath);
        }
        return (empty($bundle->sourcePath) && empty($bundle->basePath));
    }

    /**
     * @param $bundle AssetBundle
     */
    private function publishStaticDirectory($bundle)
    {
        $am = $this->getAssetManager();
        $src = $bundle->sourcePath;
        $dstDir = $bundle->basePath;
        $options = $bundle->publishOptions;
        $this->stdout("Starting copy static assets  from {$src} to {$dstDir} \n");
        if ($am->linkAssets) {
            if (!is_dir($bundle->basePath)) {
                FileHelper::createDirectory(dirname($bundle->basePath), $am->dirMode, true);
                symlink($src, $dstDir);
            }
        } elseif (!empty($options['forceCopy']) || ($am->forceCopy && !isset($options['forceCopy'])) || !is_dir($dstDir)) {
            $opts = array_merge(
                $options,
                [
                    'dirMode' => $am->dirMode,
                    'fileMode' => $am->fileMode,
                    'copyEmptyDirectories' => false,
                ]
            );
            if (!isset($opts['beforeCopy'])) {
                if ($am->beforeCopy !== null) {
                    $opts['beforeCopy'] = $am->beforeCopy;
                } else {
                    $opts['beforeCopy'] = function ($from, $to) {
                        return strncmp(basename($from), '.', 1) !== 0;
                    };
                }
            }
            if (!isset($opts['afterCopy']) && $am->afterCopy !== null) {
                $opts['afterCopy'] = $am->afterCopy;
            }
            FileHelper::copyDirectory($src, $dstDir, $opts);
        }
        $this->stdout("Finished copy static assets  from {$src} to {$dstDir} \n");
        return [$dstDir, $am->baseUrl . '/' . $bundle->baseUrl];
    }

    private function compress($bundleFile)
    {
        $bundles = $this->loadBundles($this->bundles);
        $targets = $this->loadTargets($this->targets, $bundles);
        foreach ($targets as $name => $target) {
            $this->stdout("Creating output bundle '{$name}':\n");
            if (!empty($target->js)) {
                $this->buildTarget($target, 'js', $bundles);
            }
            if (!empty($target->css)) {
                $this->buildTarget($target, 'css', $bundles);
            }
            $this->stdout("\n");
        }

        $targets = $this->adjustDependency($targets, $bundles);
        $this->saveTargets($targets, $bundleFile);

//        if ($this->deleteSource) {
//            $this->deletePublishedAssets($bundles);
//        }
    }

    /**
     * Builds output asset bundle.
     * @param \yii\web\AssetBundle $target output asset bundle
     * @param string $type either 'js' or 'css'.
     * @param \yii\web\AssetBundle[] $bundles source asset bundles.
     * @throws Exception on failure.
     */
    protected function buildTarget($target, $type, $bundles)
    {
        $inputFiles = [];
        foreach ($target->depends as $name) {
            if (isset($bundles[$name])) {
                if (!$this->isBundleExternal($bundles[$name])) {
                    foreach ($bundles[$name]->$type as $file) {
                        if (is_array($file)) {
                            $inputFiles[] = $bundles[$name]->basePath . '/' . $file[0];
                        } else {
                            $inputFiles[] = $bundles[$name]->basePath . '/' . $file;
                        }
                    }
                }
            } else {
                throw new Exception("Unknown bundle: '{$name}'");
            }
        }

        if (empty($inputFiles)) {
            $target->$type = [];
        } else {
            FileHelper::createDirectory($target->basePath, $this->getAssetManager()->dirMode);
            $tempFile = $target->basePath . '/' . strtr($target->$type, ['{hash}' => 'temp']);

            if ($type === 'js') {
                $this->compressJsFiles($inputFiles, $tempFile);
            } else {
                $this->compressCssFiles($inputFiles, $tempFile);
            }

            $targetFile = strtr($target->$type, ['{hash}' => md5_file($tempFile)]);
            $outputFile = $target->basePath . '/' . $targetFile;
            rename($tempFile, $outputFile);
            $target->$type = [$targetFile];
        }
    }

    /**
     * Adjust dependencies between asset bundles in the way source bundles begin to depend on output ones.
     * @param \yii\web\AssetBundle[] $targets output asset bundles.
     * @param \yii\web\AssetBundle[] $bundles source asset bundles.
     * @return \yii\web\AssetBundle[] output asset bundles.
     */
    protected function adjustDependency($targets, $bundles)
    {
        $this->stdout("Creating new bundle configuration...\n");

        $map = [];
        foreach ($targets as $name => $target) {
            foreach ($target->depends as $bundle) {
                $map[$bundle] = $name;
            }
        }

        foreach ($targets as $name => $target) {
            $depends = [];
            foreach ($target->depends as $bn) {
                foreach ($bundles[$bn]->depends as $bundle) {
                    $depends[$map[$bundle]] = true;
                }
            }
            unset($depends[$name]);
            $target->depends = array_keys($depends);
        }

        // detect possible circular dependencies
        foreach ($targets as $name => $target) {
            $registered = [];
            $this->registerBundle($targets, $name, $registered);
        }

        foreach ($map as $bundle => $target) {
            $sourceBundle = $bundles[$bundle];
            $depends = $sourceBundle->depends;
            if (!$this->isBundleExternal($sourceBundle)) {
                $depends[] = $target;
            }
            $targetBundle = clone $sourceBundle;
            $targetBundle->depends = $depends;
            $targets[$bundle] = $targetBundle;
        }

        return $targets;
    }

    /**
     * Saves new asset bundles configuration.
     * @param \yii\web\AssetBundle[] $targets list of asset bundles to be saved.
     * @param string $bundleFile output file name.
     * @throws \yii\console\Exception on failure.
     */
    protected function saveTargets($targets, $bundleFile)
    {
        $array = [];
        foreach ($targets as $name => $target) {
            if (isset($this->targets[$name])) {
                $array[$name] = array_merge($this->targets[$name], [
                    'class' => get_class($target),
                    'sourcePath' => null,
                    'basePath' => $this->targets[$name]['basePath'],
                    'baseUrl' => $this->targets[$name]['baseUrl'],
                    'js' => $target->js,
                    'css' => $target->css,
                    'depends' => [],
                ]);
            } else {
                if ($this->isBundleExternal($target)) {
                    $array[$name] = $this->composeBundleConfig($target);
                } else {
                    $array[$name] = [
                        'sourcePath' => null,
                        'js' => [],
                        'css' => [],
                        'depends' => $target->depends,
                    ];
                }
            }
        }
        $array = VarDumper::export($array);
        $version = date('Y-m-d H:i:s', time());
        $bundleFileContent = <<<EOD
<?php
/**
 * This file is generated by the "yii {$this->id}" command.
 * DO NOT MODIFY THIS FILE DIRECTLY.
 * @version {$version}
 */
return {$array};
EOD;
        if (!file_put_contents($bundleFile, $bundleFileContent)) {
            throw new Exception("Unable to write output bundle configuration at '{$bundleFile}'.");
        }
        $this->stdout("Output bundle configuration created at '{$bundleFile}'.\n", Console::FG_GREEN);
    }

    /**
     * @param AssetBundle $bundle asset bundle instance.
     * @return array bundle configuration.
     */
    private function composeBundleConfig($bundle)
    {
        $config = Yii::getObjectVars($bundle);
        $config['class'] = get_class($bundle);
        return $config;
    }

}