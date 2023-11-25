# GenericWebhook PHP Class

## Abstract

This white paper introduces the `GenericWebhook` PHP class, designed to facilitate the implementation of webhooks in PHP applications. Webhooks are a mechanism for real-time communication between different systems, allowing one system to notify another about specific events. The `GenericWebhook` class provides a flexible and easy-to-use framework for handling various webhook scenarios, including create, update, and delete events.

## 1. Introduction

The `GenericWebhook` class is authored by Eugen Mihailescu and is distributed under the MIT License. This class is intended to be a generic and extensible solution for implementing webhook functionality in PHP applications.

### 1.1 Purpose

The primary purpose of the `GenericWebhook` class is to simplify the integration of webhook support into PHP applications. It offers a clean and organized structure for handling different types of webhooks, such as create, update, and delete events. Additionally, the class allows for easy customization and extension to meet specific application requirements.

### 1.2 Key Features

- Flexible Webhook Subscription:
  - Developers can easily subscribe to CREATE, UPDATE, and DELETE webhooks by specifying the type, URL, and optional HTTP method.
- Asynchronous Requests:
  - The class utilizes asynchronous requests, improving the efficiency and responsiveness of webhook triggers.
- Error Handling:
  - Robust error handling ensures that exceptions are caught and reported appropriately, preventing disruptions in application functionality.
- Customizable Callbacks:
  - Users can define custom callback functions to handle webhook responses, allowing for tailored post-webhook processing.
- Payload Validation:
  - The mayTriggerWebhook method allows developers to define conditions under which webhooks may be triggered, enhancing security and control.
- Dynamic Initialization:
  - The class dynamically initializes webhooks based on the getWebhooks method, providing a clean and organized way to set up default webhooks.

## 2. Class Overview

### 2.1 Constants

The class defines three constants representing different webhook types:

- `WEBHOOK_TYPE_CREATE`: Represents a create webhook.
- `WEBHOOK_TYPE_UPDATE`: Represents an update webhook.
- `WEBHOOK_TYPE_DELETE`: Represents a delete webhook.

### 2.2 Methods

#### 2.2.1 Webhook Initialization

The webhooks are initialized automatically. All that's needed is implementing the two functions in the extended PHP class. See [2.2.2](#2.2.2-customization-hooks).

#### 2.2.2 Customization Hooks

- `getWebhooks`: Retrieves the class default webhooks. Subclasses can override this method to define custom webhooks.
- `mayTriggerWebhook`: Determines if a given action may trigger a webhook based on type, method, URL, and payload.

#### 2.2.3 Type-Specific Triggers

- `triggerCreateWebhook`, `triggerUpdateWebhook`, `triggerDeleteWebhook`: Triggers webhooks of specific types with the provided payload and optional callback.

## 3. Usage

To use the `GenericWebhook` class, follow these steps:

1. Optionally customize webhooks using the `getWebhooks` function.
2. Optionally specify conditions the webhook may trigger using the `mayTriggerWebhook` function.
3. Trigger webhooks using the `triggerCreateWebhook`, `triggerUpdateWebhook`, `triggerDeleteWebhook` functions.

```php
// Example Usage
class MyWebhookAwareClass extends GenericWebhook {
    // ... (class implementation)

    // Get the default webhooks
    protected function getWebhooks(){
        return [
            self::WEBHOOK_TYPE_CREATE => ['tcp://localhost:3000/create'],
            self::WEBHOOK_TYPE_UPDATE => [['url' => 'tcp://localhost:3000/update', 'method' => 'PATCH']],
            self::WEBHOOK_TYPE_DELETE => [['url' => 'tcp://localhost:3000/delete', 'method' => 'DELETE']]
        ];
    }

    // Define the condition the webhook may trigger
    protected function mayTriggerWebhook($type, $method, $url, $payload)
    {
        // Define allowed tables for each webhook type
        $allowedTables = [
            self::WEBHOOK_TYPE_CREATE => ['this_table', 'other_table'],
            self::WEBHOOK_TYPE_UPDATE => ['some_table', 'any_table'],
            self::WEBHOOK_TYPE_DELETE => ['this_table', 'not_that_table'],
        ];

        // Check if the webhook type and table are allowed
        return isset($allowedTables[$type]) && in_array($payload['table'], $allowedTables[$type]);
    }

    public function insert($table, $data)
    {
        // after a successfully insert operation
        if ($success === true && $new_id) {
            // notify all create subscribers about this event
            $this->triggerCreateWebhook(['entity' => $table, 'data' => $data, 'newId' => $new_id], function ($res, $headers) {
                // your post create webhook handler (if any!) goes here
            });
        }
    }

    public function update($table, $data, $criteria)
    {
        // after a successfully update operation
        if ($success === true) {
            // notify all update subscribers about this event
            $this->triggerUpdateWebhook(['entity' => $table, 'data' => $data, 'criteria' => $criteria], function ($res, $headers) {
                // your post update webhook handler (if any!) goes here
            });
        }
    }

    public function delete($table, $criteria)
    {
        // after a successfully delete operation
        if ($success === true) {
            // notify all delete subscribers about this event
            $this->triggerDeleteWebhook(['entity' => $table, 'criteria' => $criteria], function ($res, $headers) {
                // your post delete webhook handler (if any!) goes here
            });
        }
    }
}
```

A complete working example can be found in the `example` directory. It includes two PHP applications that can simply be tested on the local system's PHP engine:

- a `listener` web application which is the webhook server
- a `subscriber` console application which is the webhook subscribed client

To run the `listener` server just run the following command in the project's directory: `php -S localhost:3000 -t ./example/listener`.

To run the `subscriber` application just run the following command in the project's directory: `php -f example/subscriber/index.php`.

## 4. Conclusion

The `GenericWebhook` PHP class provides a robust foundation for implementing webhook functionality in PHP applications. Its flexibility, extensibility, and support for various webhook types make it a valuable tool for developers seeking an efficient and organized way to handle real-time communication between systems.

The class's open-source nature and the MIT License encourage collaboration and adaptation to specific project requirements. As technology evolves, the `GenericWebhook` class can serve as a reliable solution for integrating webhooks into PHP applications.
