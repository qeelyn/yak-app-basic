<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/6/15
 * Time: 下午3:38
 */

namespace yakunit\build;

use app\build\AssetController;
use yakunit\TestCase;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\helpers\VarDumper;
use yii\helpers\ArrayHelper;

class AssetControllerTest extends TestCase
{
    /**
     * @var string path for the test files.
     */
    protected $testFilePath = '';
    /**
     * @var string test assets path.
     */
    protected $testAssetsBasePath = '';

    public function setUp()
    {
        $this->mockApplication();
        $this->testFilePath = Yii::getAlias('@yakunit/runtime') . DIRECTORY_SEPARATOR . str_replace('\\', '_', get_class($this)) . uniqid();
        $this->createDir($this->testFilePath);
        $this->testAssetsBasePath = $this->testFilePath . DIRECTORY_SEPARATOR . 'assets';
        $this->createDir($this->testAssetsBasePath);
//        Yii::setAlias('@web', $this->testFilePath);
    }

    public function tearDown()
    {
        $this->removeDir($this->testFilePath);
    }

    public function testActionCompress()
    {
        $bundleFile = $this->testFilePath . DIRECTORY_SEPARATOR . 'bundle.php';

        // When :
        $configFile = __DIR__.'/../../build/assets-config.php';
        $this->runAssetControllerAction('compress', [$configFile, $bundleFile]);

        // Then :
        $this->assertFileExists($bundleFile, 'Unable to create output bundle file!');
        $compressedBundleConfig = require($bundleFile);
        $this->assertTrue(is_array($compressedBundleConfig), 'Output bundle file has incorrect format!');
        $this->assertCount(3, $compressedBundleConfig, 'Output bundle config contains wrong bundle count!');

    }

    function testCopyDirectory()
    {
        $src = "/Users/tsingsun/workspace/Git/yak-host/vendor/yak/ucenter/assets/../web";
        $dstDir = "/Users/tsingsun/workspace/Git/yak-host/build/../web/assets/ucenter";
        $opts = [
            'only'=>[
//                'fonts/','images/','scripts/','styles/'
                'scripts/*'
            ],
            'copyEmptyDirectories'=>false,
        ];
        FileHelper::copyDirectory($src, $dstDir, $opts);
    }

    public function testActionPublish()
    {
        $bundleFile = $this->testFilePath . DIRECTORY_SEPARATOR . 'bundle.php';
        $configFile = __DIR__.'/../../build/assets-config.php';
        $this->runAssetControllerAction('publish', [$configFile,$bundleFile]);
    }

    /**
     * Creates directory.
     * @param string $dirName directory full name.
     */
    protected function createDir($dirName)
    {
        FileHelper::createDirectory($dirName);
    }

    /**
     * Removes directory.
     * @param string $dirName directory full name
     */
    protected function removeDir($dirName)
    {
        if (!empty($dirName)) {
            FileHelper::removeDirectory($dirName);
        }
    }

    /**
     * Creates a list of asset source files.
     * @param array $files assert source files in format: file/relative/name => fileContent
     * @param string $fileBasePath base path for the created files, if not set [[testFilePath]]
     */
    protected function createAssetSourceFiles(array $files, $fileBasePath = null)
    {
        foreach ($files as $name => $content) {
            $this->createAssetSourceFile($name, $content, $fileBasePath);
        }
    }

    /**
     * Creates test asset file.
     * @param string $fileRelativeName file name relative to [[testFilePath]]
     * @param string $content file content
     * @param string $fileBasePath base path for the created files, if not set [[testFilePath]] is used.
     * @throws \Exception on failure.
     */
    protected function createAssetSourceFile($fileRelativeName, $content, $fileBasePath = null)
    {
        if ($fileBasePath === null) {
            $fileBasePath = $this->testFilePath;
        }
        $fileFullName = $fileBasePath . DIRECTORY_SEPARATOR . $fileRelativeName;
        $this->createDir(dirname($fileFullName));
        if (file_put_contents($fileFullName, $content) <= 0) {
            throw new \Exception("Unable to create file '{$fileFullName}'!");
        }
    }

    /**
     * Declares asset bundle class according to given configuration.
     * @param  array  $config asset bundle config.
     * @return string new class full name.
     */
    protected function declareAssetBundleClass(array $config)
    {
        $sourceCode = $this->composeAssetBundleClassSource($config);
        eval($sourceCode);

        return $config['namespace'] . '\\' . $config['class'];
    }

    /**
     * Composes asset bundle class source code.
     * @param  array  $config asset bundle config.
     * @return string class source code.
     */
    protected function composeAssetBundleClassSource(array &$config)
    {
        $config = array_merge(
            [
                'namespace' => StringHelper::dirname(get_class($this)),
                'class' => 'AppAsset',
                'sourcePath' => null,
                'basePath' => $this->testFilePath,
                'baseUrl' => '',
                'css' => [],
                'js' => [],
                'depends' => [],
            ],
            $config
        );
        foreach ($config as $name => $value) {
            if (!in_array($name, ['namespace', 'class'])) {
                $config[$name] = VarDumper::export($value);
            }
        }

        $source = <<<EOL
namespace {$config['namespace']};

use yii\web\AssetBundle;

class {$config['class']} extends AssetBundle
{
    public \$sourcePath = {$config['sourcePath']};
    public \$basePath = {$config['basePath']};
    public \$baseUrl = {$config['baseUrl']};
    public \$css = {$config['css']};
    public \$js = {$config['js']};
    public \$depends = {$config['depends']};
}
EOL;

        return $source;
    }

    /**
     * Creates test compress config file.
     * @param string $fileName output file name.
     * @param array[] $bundles asset bundles config.
     * @param array $config additional config parameters.
     * @throws \Exception on failure.
     */
    protected function createCompressConfigFile($fileName, array $bundles, array $config = [])
    {
        $content = '<?php return ' . VarDumper::export($this->createCompressConfig($bundles, $config)) . ';';
        if (file_put_contents($fileName, $content) <= 0) {
            throw new \Exception("Unable to create file '{$fileName}'!");
        }
    }

    /**
     * Creates test compress config.
     * @param array[] $bundles asset bundles config.
     * @param array $config additional config.
     * @return array config array.
     */
    protected function createCompressConfig(array $bundles, array $config = [])
    {
        static $classNumber = 0;
        $classNumber++;
        $className = $this->declareAssetBundleClass(['class' => 'AssetBundleAll' . $classNumber]);
        $baseUrl = '/test';
        $config = ArrayHelper::merge($config, [
            'bundles' => $bundles,
            'targets' => [
                $className => [
                    'basePath' => $this->testAssetsBasePath,
                    'baseUrl' => $baseUrl,
                    'js' => 'all.js',
                    'css' => 'all.css',
                ],
            ],
            'assetManager' => [
                'basePath' => $this->testAssetsBasePath,
                'baseUrl' => '',
                'hashCallback' => function($path){
                    return $path;
                },
            ],
        ]);

        return $config;
    }

    /**
     * Emulates running of the asset controller action.
     * @param  string $actionID id of action to be run.
     * @param  array  $args     action arguments.
     * @return string command output.
     */
    protected function runAssetControllerAction($actionID, array $args = [])
    {
        $controller = $this->createAssetController();
        $controller->run($actionID, $args);
//        return $controller->flushStdOutBuffer();
    }

    /**
     * Creates test asset controller instance.
     * @return AssetController
     */
    protected function createAssetController()
    {
        $module = $this->getMockBuilder('yii\\base\\Module')
            ->setMethods(['fake'])
            ->setConstructorArgs(['console'])
            ->getMock();
        $assetController = new AssetController('asset', $module);
        $assetController->interactive = false;
        $assetController->jsCompressor = 'cp {from} {to}';
        $assetController->cssCompressor = 'cp {from} {to}';

        return $assetController;
    }
}