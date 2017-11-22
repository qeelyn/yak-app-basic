<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/5/25
 * Time: 下午5:20
 */

namespace app\components;

use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\web\Response;

class ErrorAction extends Action
{
    /**
     * @var string the view file to be rendered. If not set, it will take the value of [[id]].
     * That means, if you name the action as "error" in "SiteController", then the view name
     * would be "error", and the corresponding view file would be "views/site/error.php".
     */
    public $view;
    /**
     * @var string the name of the error when the exception name cannot be determined.
     * Defaults to "Error".
     */
    public $defaultName;
    /**
     * @var string the message to be displayed when the exception message contains sensitive information.
     * Defaults to "An internal server error occurred.".
     */
    public $defaultMessage;

    /**
     * @var \Exception the exception object, normally is filled on [[init()]] method call.
     * @see [[findException()]] to know default way of obtaining exception.
     * @since 2.0.11
     */
    protected $exception;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->exception = $this->findException();

        if ($this->defaultMessage === null) {
            $this->defaultMessage = Yii::t('yii', 'An internal server error occurred.');
        }

        if ($this->defaultName === null) {
            $this->defaultName = Yii::t('yii', 'Error');
        }
    }

    /**
     * Runs the action.
     *
     * @return string|Response result content
     */
    public function run()
    {
        Yii::$app->getResponse()->setStatusCodeByException($this->exception);
        if(Yii::$app->response->format == Response::FORMAT_JSON){
            return $this->renderJsonResponse();
        }elseif (Yii::$app->response->format == Response::FORMAT_HTML){
            return $this->renderHtmlResponse();
        }else{
            return $this->renderJsonResponse();
        }
    }

    /**
     * Builds string that represents the exception.
     * Normally used to generate a response to AJAX request.
     * @return Response
     * @since 2.0.11
     */
    protected function renderJsonResponse()
    {
        $current = [
            'code' => $this->getExceptionCode(),
            'message' => $this->getExceptionMessage(),
        ];
        $result = [
            'errors' => [$current],
        ];
        Yii::$app->response->data = $result;

        return Yii::$app->response;
    }

    /**
     * Renders a view that represents the exception.
     * @return string
     * @since 2.0.11
     */
    protected function renderHtmlResponse()
    {
        return $this->controller->render($this->view ?: $this->id, $this->getViewRenderParams());
    }

    /**
     * Builds array of parameters that will be passed to the view.
     * @return array
     * @since 2.0.11
     */
    protected function getViewRenderParams()
    {
        return [
            'name' => $this->getExceptionName(),
            'message' => $this->getExceptionMessage(),
            'exception' => $this->exception,
        ];
    }

    /**
     * Gets exception from the [[yii\web\ErrorHandler|ErrorHandler]] component.
     * In case there is no exception in the component, treat as the action has been invoked
     * not from error handler, but by direct route, so '404 Not Found' error will be displayed.
     * @return \Exception
     * @since 2.0.11
     */
    protected function findException()
    {
        if (($exception = Yii::$app->getErrorHandler()->exception) === null) {
            $exception = new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }

        return $exception;
    }

    /**
     * Gets the code from the [[exception]].
     * @return mixed
     * @since 2.0.11
     */
    protected function getExceptionCode()
    {
        if ($this->exception instanceof HttpException) {
            return $this->exception->statusCode;
        }

        return $this->exception->getCode();
    }

    /**
     * Returns the exception name, followed by the code (if present).
     *
     * @return string
     * @since 2.0.11
     */
    protected function getExceptionName()
    {
        if ($this->exception instanceof Exception) {
            $name = $this->exception->getName();
        } else {
            $name = $this->defaultName;
        }

        if ($code = $this->getExceptionCode()) {
            $name .= " (#$code)";
        }

        return $name;
    }

    /**
     * Returns the [[exception]] message for [[yii\base\UserException]] only.
     * For other cases [[defaultMessage]] will be returned.
     * @return string
     * @since 2.0.11
     */
    protected function getExceptionMessage()
    {
        if ($this->exception instanceof UserException) {
            return $this->exception->getMessage();
        }

        return $this->defaultMessage;
    }

    /**
     * 异常转化
     * @param \Exception $exception
     * @return array
     */
    private function formatAjaxException(\Exception $exception)
    {
        $current = [
            'code' => $this->getExceptionCode(),
            'message' => $this->getExceptionMessage(),
        ];
        $result = [
            'errors' => [$current],
        ];

        return $result;
    }
}