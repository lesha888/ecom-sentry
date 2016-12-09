<?php
/**
 * SentryErrorHandler class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-sentry.components
 */

namespace ecom\sentry;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\Application;

/**
 * Error handler that allows for sending errors to Sentry.
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @var string component ID for the sentry client.
     */
    public $clientId = 'sentry';

    /**
     * Initializes the error handler.
     */
    public function init()
    {
        Yii::$app->on(Application::EVENT_BEFORE_REQUEST, [$this, 'onShutdown']);
    }

    /**
     * Invoked on shutdown to attempt to capture any unhandled errors.
     */
    public function onShutdown()
    {
        $error = error_get_last();
        if ($error !== null) {
            $errors = array(
                E_ERROR,
                E_PARSE,
                E_CORE_ERROR,
                E_CORE_WARNING,
                E_COMPILE_ERROR,
                E_COMPILE_WARNING,
                E_STRICT
            );
            if (in_array($error['type'], $errors)) {
                $this->getSentryClient()->captureException(
                    $this->createErrorException($error['message'], $error['type'], $error['file'], $error['line'])
                );
            }
        }
    }

    public function logException($exception)
    {
        $this->getSentryClient()->captureException($exception);

        parent::logException($exception);
    }

    /**
     * Creates an error exception.
     * @param string $message error message.
     * @param int $code error code.
     * @param string $file file in which the error occurred.
     * @param int $line line number on which the error occurred.
     * @return \ErrorException exception instance.
     */
    protected function createErrorException($message, $code, $file, $line)
    {
        return new \ErrorException($message, $code, 0/* will be resolved */, $file, $line);
    }

    /**
     * Returns the Sentry client component.
     * @return Sentry client instance.
     * @throws InvalidConfigException if the component id is invalid.
     */
    public function getSentryClient()
    {
        if (!Yii::$app->has($this->clientId)) {
            throw new InvalidConfigException('SentryErrorHandler.componentID "%s" is invalid.', $this->clientId);
        }

        return Yii::$app->get($this->clientId);
    }
}
