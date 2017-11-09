<?php
/**
 * LogTarget class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-sentry.components
 */

namespace lesha888\sentry;

use Yii;
use yii\base\InvalidConfigException;
use yii\log\Logger;
use yii\log\Target;

/**
 * Log route that allows for sending messages to Sentry.
 */
class LogTarget extends Target
{
    /**
     * @var string component ID for the sentry client.
     */
    public $clientId = 'sentry';

    /**
     * Processes log messages and sends them to specific destination.
     * Derived child classes must implement this method.
     * @param array $logs list of messages. Each array element represents one message
     * with the following structure:
     * array(
     *   [0] => message (string)
     *   [1] => level (string)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true)
     * );
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     */
    protected function processLogs($logs)
    {
        foreach ($logs as $log) {
            $this->getSentryClient()->captureMessage(
                $log[0],
                [],
                [
                    'extra' => [
                        'message' => $log[0],
                        'level' => $log[1],
                        'log_time' => date('Y-m-d H:i:s', $log[3]),
                    ],
                    'tags' => [
                        'category' => $log[2],
                    ],
                ]
            );
        }
    }

    public function export()
    {
        $client = $this->getSentryClient();

        foreach($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;

            $client->captureMessage($text, [],
                [
                    'extra' => [
                        'message' => $text,
                        'level' => Logger::getLevelName($level),
                        'log_time' => date('Y-m-d H:i:s', $timestamp),

                    ],
                    'tags' => [
                        'category' => $category
                    ]
                ]
            );
        }
    }

    /**
     * Returns the Sentry client component.
     * @return \Raven_Client client instance.
     * @throws InvalidConfigException if the component id is invalid.
     */
    public function getSentryClient()
    {
        if (!Yii::$app->has($this->clientId)) {
            throw new InvalidConfigException(sprintf('SentryLogRoute.componentID "%s" is invalid.', $this->clientId));
        }

        return Yii::$app->get($this->clientId);
    }
}