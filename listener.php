<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('impact.ccat.eu', 5672, 'myjar', 'myjar');
$channel = $connection->channel(1);
$channel->queue_declare('interest-queue', false, false, false, false);
$channelSent = $connection->channel(2);
$channelSent->queue_declare('solved-interest-queue', false, false, false, false);
$callback = function ($msg)
//use ($channelSent)
{
    echo " [x] Received ", $msg->body, "\n";
    $data = json_decode($msg->body, true);
    if (empty($data)) {
        return;
    }
    $days = $data['days'];
    $sum = $data['sum'];
    $divisibleByBoth = floor($days / 15);
    $divisibleByThree = floor($days / 3) - $divisibleByBoth;
    $divisibleByFive = floor($days / 5) - $divisibleByBoth;
    $notDivisible = $days - $divisibleByBoth - $divisibleByThree - $divisibleByFive;

    $interestForThree = round($sum * 0.01, 2) * $divisibleByThree;
    $interestForFive = round($sum * 0.02, 2) * $divisibleByFive;
    $interestForBoth = round($sum * 0.03, 2) * $divisibleByBoth;
    $interestForNon = round($sum * 0.04, 2) * $notDivisible;

    $totalInterest = $interestForThree + $interestForFive + $interestForBoth + $interestForNon;
    $totalSum = $sum + $totalInterest;

    $data['interest'] = $totalInterest;
    $data['totalSum'] = $totalSum;
    $data['token'] = 'kamil';
    echo json_encode($data) . "\n";
//    $msg = new AMQPMessage(json_encode($data));
//    $channelSent->basic_publish($msg, '', 'solved-interest-queue');
};
$channel->basic_consume('interest-queue', '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();