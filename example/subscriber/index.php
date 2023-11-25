<?php

include_once "MyWebkookAwareClass.php";

$my_table = new MyWebkookAwareClass();

readline("[1] Testing the INSERT trigger. Press any key to continue...");
// simulate an insert request that would trigger the insert webhook (see mayTriggerWebhook)
$my_table->insert("this_table", ['field_1' => 100, 'field_2' => 'some string', 'field_3' => true]);
// simulate an insert request that would trigger the insert webhook (see mayTriggerWebhook)
$my_table->insert("other_table", ['field_a' => 'lorem ipsum text', 'field_b' => 99.99]);
// simulate an insert request that would NOT trigger the insert webhook (see mayTriggerWebhook)
$my_table->insert("some_table", ['field_x' => -1, 'field_y' => 'qwerty', 'field_z' => null]);
echo (" => sent 2 valid request and one that should not trigger the webhook.\n\n");


readline("[2] Testing the UPDATE trigger. Press any key to continue...");
// simulate an update request that would trigger the update webhook (see mayTriggerWebhook)
$my_table->update("some_table", ['field_x' => 200, 'field_y' => 'other string', 'field_z' => false], [['name' => 'field_z', 'operator' => 'in', 'operand' => [1, 2, 3]]]);
// simulate an update request that would trigger the update webhook (see mayTriggerWebhook)
$my_table->update("any_table", ['f1' => 1, 'f2' => 2], [['name' => 'f3', 'operator' => '!=', 'operand' => 'test']]);
// simulate an update request that would NOT trigger the update webhook (see mayTriggerWebhook)
$my_table->update("not_that_table", ['field_x' => -1, 'field_y' => 'qwerty', 'field_z' => null], [['name' => 'field_y', 'operator' => '!=', 'operand' => null]]);
echo (" => sent 2 valid request and one that should not trigger the webhook.\n\n");

readline("[3] Testing the DELETE trigger. Press any key to continue...");
// simulate an delete request that would trigger the delete webhook (see mayTriggerWebhook)
$my_table->delete("this_table", [['name' => 'field_x', 'operator' => 'not in', 'operand' => [1, 2, 3]]]);
// simulate an delete request that would trigger the delete webhook (see mayTriggerWebhook)
$my_table->delete("not_that_table", [['name' => 'f1', 'operator' => '=', 'operand' => 1]]);
// simulate an update request that would NOT trigger the delete webhook (see mayTriggerWebhook)
$my_table->delete("any_table", [['name' => 'field_x', 'operator' => '<=', 'operand' => "test"]]);
echo (" => sent 2 valid request and one that should not trigger the webhook.\n\n");
