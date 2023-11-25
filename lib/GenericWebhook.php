<?php

include_once 'AsyncRequest.php';

/**
 * Generic webhook class
 *
 * @author   Eugen Mihailescu <eugen@lsbolagen.se>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Licence
 */
class GenericWebhook
{

    const WEBHOOK_TYPE_CREATE = 'create';
    const WEBHOOK_TYPE_UPDATE = 'update';
    const WEBHOOK_TYPE_DELETE = 'delete';

    /**
     * Disable entirely the webhook function
     * 
     * @var bool
     */
    private $_webhook_disabled = false;

    /**
     * Subscribed clients URLs for CREATE webhooks
     * This is an array containing
     * the subscribed clients URLs webhooks for inserts to model's table.
     *
     * @var array
     */
    private $_create_hooks = array();

    /**
     * Subscribed clients URLs for UPDATE webhooks
     * This is an array containing
     * the subscribed clients URLs webhooks for updates to model's table.
     *
     * @var array
     */
    private $_update_hooks = array();


    /**
     * Subscribed clients URLs for DELETE webhooks
     * This is an array containing
     * the subscribed clients URLs webhooks for deletes from model's table.
     *
     * @var array
     */
    private $_delete_hooks = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        if ($this->_webhook_disabled) {
            return;
        }

        $this->initWebhooks();
    }

    /**
     * Trigger the given webhool
     * 
     * @param string $type Any of WEBHOOK_TYPE_* constant
     * @param string $method The HTTP method
     * @param string $url The request URL address
     * @param array|object $payload The payload ({entity,data,criteria}) that would be sent to the subscribed webhook
     * @param callable $callback A callback with prototype ($res, $headers). $res may be either the HTTP response on success, false otherwise. It can be a \Exception error in case of exception.
     */
    private function triggerWebhook($type, $method, $url, $payload, $callback = null)
    {
        $callback = is_callable($callback) ? $callback : function ($response, $headers) {
        };

        if (!$this->mayTriggerWebhook($type, $method, $url, $payload)) {
            call_user_func($callback, false, []);
            return;
        }

        try {
            $async = new AsyncRequestHandler();
            $async->makeAsyncRequest($method, $url, $payload, $callback);
        } catch (\Exception $e) {
            call_user_func($callback, $e, []);
        }
    }

    /**
     * Subscribe a webhook
     * 
     * @param string $type Any of WEBHOOK_TYPE_* constant
     * @param string $url The subscribing URL address
     * @param string $method The HTTP method
     */
    private function webhookSubscribe($type, $url, $method)
    {
        switch ($type) {
            case self::WEBHOOK_TYPE_CREATE:
                $_hooks = &$this->_create_hooks;
                $method = isset($method) ? $method : 'POST';
                break;
            case self::WEBHOOK_TYPE_UPDATE:
                $_hooks = &$this->_update_hooks;
                $method = isset($method) ? $method : 'PATCH';
                break;
            case self::WEBHOOK_TYPE_DELETE:
                $_hooks = &$this->_delete_hooks;
                $method = isset($method) ? $method : 'DELETE';
                break;
            default:
                throw new \Exception(sprintf('Unexpected webhook type "%s"', $type));
        }

        if (!in_array($method, ['POST', 'GET', 'PATCH', 'PUT', 'DELETE'])) {
            throw new \Exception(sprintf('Unexpected webhook method "%s"', $method));
        }

        $_hooks[] = [$url, $method];
    }

    /**
     * Disable webhook support
     * 
     * @param bool $disabled When true disable the webhook support, otherwise enable the webhook support
     */
    protected function disableWebhooks($disabled)
    {
        $this->_webhook_disabled = $disabled;
    }

    /**
     * Initialize the class webhooks
     */
    protected function initWebhooks()
    {
        try {
            foreach ($this->getWebhooks() as $type => $webhooks) {
                foreach ($webhooks as $value) {
                    $method = null;

                    if (is_array($value)) {
                        $url = $value['url'];
                        $method = $value['method'];
                    } else {
                        $url = $value;
                    }
                    $this->webhookSubscribe($type, $url, $method);
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Get the class default webhooks
     * @return array Returns an array of URL|array. When array the first element is the URL, the second the method for triggering the respective URL.
     */
    protected function getWebhooks()
    {
        return [];
    }

    /**
     * * Define the condition the webhook may trigger
     * 
     * @param string $type Any of WEBHOOK_TYPE_* constant
     * @param string $method The HTTP method
     * @param string $url The request URL address
     * @param array|object $payload The payload ({entity,data,criteria}) that would be sent to the subscribed webhook
     * @return bool Returns true if the given action may trigger the webhook, false otherwise
     */
    protected function mayTriggerWebhook($type, $method, $url, $payload)
    {
        return true;
    }

    /**
     * Trigger the INSERT webhook(s) for the given payload
     * 
     * @param array|object $payload The payload ({entity,data,criteria}) that would be sent to the subscribed webhook
     * @param callable $callback A callback where the request's response is sent
     */
    protected function triggerCreateWebhook($payload, $callback = null)
    {
        foreach ($this->_create_hooks as $tuple) {
            list($url, $method) = $tuple;
            $this->triggerWebhook(self::WEBHOOK_TYPE_CREATE, $method, $url, $payload, $callback);
        }
    }

    /**
     * Trigger the UPDATE webhook(s) for the given payload
     * 
     * @param array|object $payload The payload ({entity,data,criteria}) that would be sent to the subscribed webhook
     * @param callable $callback A callback where the request's response is sent
     */
    protected function triggerUpdateWebhook($payload, $callback = null)
    {
        foreach ($this->_update_hooks as $tuple) {
            list($url, $method) = $tuple;
            $this->triggerWebhook(self::WEBHOOK_TYPE_UPDATE, $method, $url, $payload, $callback);
        }
    }

    /**
     * Trigger the DELETE webhook(s) for the given payload
     * 
     * @param array|object $payload The payload ({entity,data,criteria}) that would be sent to the subscribed webhook
     * @param callable $callback A callback where the request's response is sent
     */
    protected function triggerDeleteWebhook($payload, $callback = null)
    {
        foreach ($this->_delete_hooks as $tuple) {
            list($url, $method) = $tuple;
            $this->triggerWebhook(self::WEBHOOK_TYPE_DELETE, $method, $url, $payload, $callback);
        }
    }
}
