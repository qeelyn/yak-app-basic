yak-app-basic
============

YAK项目是基于YII2框架开发的通用平台项目,类似yii-app-basic项目,本项目用于快速构建YAK的应用.

### 安装

```
composer create-project yak/yak-app-basic --prefer-dist --stability=dev
```

### 开始
项目结构采用与YII高级应用类似的结构,具体不多说,请参考Yii-app-advance
- 1.项目初始化,构建本地环境
```php
init --env=[Development|Production|...] --overwrite=[y|n|q]
```

> 在执行完后,在配置文件目录会生成*-local.php文件

### API

* 数据封装

    yak的项目有数据返回约定,
    ```json
    {
      "data":{
        "user":{}
      },
      //errors段为当存在错误时,才会出现.
      "errors":[
        {
          "code":401,
          "message":"授权失败"
        }
      ]
    }
    ```
    通过Host项目返回的数据由各子项目自己控制,建议的写法为在模块的module文件中增加afterAction事件
    ```php
    //在init方法添加事件
    $this->on('afterAction',[$this,'onAfterAction']);
    //以下为
    public function onAfterAction($event)
        {
            if($event->action instanceof GraphQLAction || $event->result instanceof Response){
                return;
            }elseif (Yii::$app->response->format == Response::FORMAT_JSON){
                $result['data'] = $event->result;
                $event->result = $result;
            }
        }
    ```
### 异常

    异常由于将展示给用户,要求全站点具有统一的处理方式,因此由host项目控制,通过配置errorHandle的errorAction,统一返回给用户.
    当然子项目也可以自己接管异常.
    
    为了区分异常与普通数据,非View的请求异常处理后的统一返回Response对象,非view请求是指json,xml,jsonp等这类的请求.
    调试阶段,可以不配置errorAction,这样异常会直接输出html格式的信息.


    
* TODO
  
  * 异常只处理了json格式的封装,其他格式的请求还未处理.
  
### 资源发布

yak的资源管理是基于Yii的资源管理,但又可独立作用于静态资源,这点对于子项目集成方式的目录组织很重要.
当我们基于Yii PHP的开发的子项目来说,只需要采用Yii的资源发布就可以了.假设我们采用前后端分离的方式进行开发,前端项目采用vuejs等其他框架方式开发.
有如下问题点.
1. 站点的安全权限,一般不可访问子项目的目录,因此子项目的资源依然需要发布.
2. 不同框架采用不同的路由方式,yii的资源管理发布目录的随机性使资源路径不确定.造成url不可用.

针对这些问题点,可采用yii console方式对资源进行发布,同时我们在发布资源时采用固定目录存在子项目资源,在资源加上时间戳来避免资源缓存.

具体执行如下.

* 环境依赖:nodejs,请先install

* 在yak-host\build目录中,AssetController是在controller模式下的资源发布,设置好配置文件与输出AssetManager的bundle,执行
```cmd
    php yii publish build/assets-config.php config/assets-bundle.php
```


* 子项目按照yii的资源方式进行组织.如在web目录下建立资源,建立AssetBundle文件.这时需要注意不必要的文件需要排除掉
```php
class UcenterVueAsset extends AssetBundle
{
    //建议不要采用alias方式进行,省得console模式下去设置别名
    public $sourcePath = __DIR__ . '/../web/';
    public $css = [
        'styles/main.css'
    ];
    public $js = [
        'scripts/manifest.js',
        'scripts/vendor.js',
        'scripts/main.js',
    ];

    public $depends = [
    ];

    public $jsOptions = [
    ];
    //有必要排除,因为在web目录下,具有可能很多其他的文件
    public $publishOptions = [
        'only' => [
            'fonts/*',
            'images/*',
            'scripts/*',
            'styles/*'
        ]
    ];

}
```
* 子项目按自己的项目形式发布到本项目的web目录中,如采用webpack进行打包处理,注意需要设置在主项目中的发布路径,并将资源文件提交到CVS
* 主项目针对子项目进行配置,在build目录的assets-config.php文件中进行配置,重点配置如下
```php    
    'assetManager' => [
        'basePath' => '@web/assets',
        'baseUrl' => '@web/assets',
        'bundles' => [
            'yii\web\JqueryAsset' => [
                'sourcePath' => null,   // do not publish the bundle
                'js' => [
                    'https://cdn.bootcss.com/jquery/3.2.1/jquery.js',
                ]
            ],
            //当资源与static中的对应时,该资源会当成静态资源,根据路径设置发布到对应目录
            'yak\ucenter\assets\UcenterVueAsset' =>[
                'basePath' =>'@web/assets/ucenter',
                //该路径需要与子项目发布的根路径一致
                'baseUrl' =>'/assets/ucenter',
            ],
        ],
    ],
    //statics配置中表示资源都为静态资源.
    'statics'=>[
        'yak\ucenter\assets\UcenterVueAsset'
    ],    
```
