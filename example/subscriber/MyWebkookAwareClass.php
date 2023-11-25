<?php
include_once __DIR__ . '/../../lib/GenericWebhook.php';

class MyWebkookAwareClass extends GenericWebhook
{
    /**
     * @inheritdoc
     */
    protected function getWebhooks()
    {
        return [
            self::WEBHOOK_TYPE_CREATE => ['tcp://localhost:3000/create'],
            self::WEBHOOK_TYPE_UPDATE => [['url' => 'tcp://localhost:3000/update', 'method' => 'PATCH']],
            self::WEBHOOK_TYPE_DELETE => [['url' => 'tcp://localhost:3000/delete', 'method' => 'DELETE']]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function mayTriggerWebhook($type, $method, $url, $payload)
    {
        // Define allowed tables for each webhook type
        $allowedTables = [
            self::WEBHOOK_TYPE_CREATE => ['this_table', 'other_table'],
            self::WEBHOOK_TYPE_UPDATE => ['some_table', 'any_table'],
            self::WEBHOOK_TYPE_DELETE => ['this_table', 'not_that_table'],
        ];

        // Extract table from payload (assuming $payload is an array)
        $tableArray = $payload; // See create|update|delete functions
        $table = reset($tableArray); // or just $payload['table']

        // Check if the webhook type and table are allowed
        return isset($allowedTables[$type]) && in_array($table, $allowedTables[$type]);
    }

    public function insert($table, $data)
    {
        // insert $data into $table goes here
        $success = true;
        $last_id = rand(1, 1000);

        if ($success === true) {
            // notify all create subscribers webhooks about this event
            // see also: getWebhooks and mayTriggerWebhook
            $this->triggerCreateWebhook(['entity' => $table, 'data' => $data, 'newId' => $last_id], function ($res, $headers) {
                print_r(['res' => $res, 'headers' => $headers]);
                // your post create webhook handler (if any!) goes here
            });
        }
    }

    public function update($table, $data, $criteria)
    {
        // update $data to $table goes here
        $success = true;

        if ($success === true) {
            // notify all update subscribers webhooks about this event
            // see also: getWebhooks and mayTriggerWebhook
            $this->triggerUpdateWebhook(['entity' => $table, 'data' => $data, 'criteria' => $criteria], function ($res, $headers) {
                print_r(['res' => $res, 'headers' => $headers]);
                // your post update webhook handler (if any!) goes here
            });
        }
    }

    public function delete($table, $criteria)
    {
        // delete $data from $table goes here
        $success = true;

        if ($success === true) {
            // notify all delete subscribers webhooks about this event
            // see also: getWebhooks and mayTriggerWebhook
            $this->triggerDeleteWebhook(['entity' => $table, 'criteria' => $criteria], function ($res, $headers) {
                print_r(['res' => $res, 'headers' => $headers]);
                // your post delete webhook handler (if any!) goes here
            });
        }
    }
}
