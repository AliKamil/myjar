<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('impact.ccat.eu', 5672, 'myjar', 'myjar');
$channel = $connection->channel();

$callback = function ($msg) use ($channel) {
    echo " [x] Received ", $msg->body, "\n";
    $data = json_decode($msg->body, true);
    if (empty($data)) {
        return;
    }
    $days = $data['days'];
    $sum = $data['sum'];
    if (!is_int($days) || $days < 0 || !is_int($sum) || $sum < 0) {
        return;
    }

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
    $data['token'] = 'arthur';
    $string = json_encode($data);
    echo "Sent: " . $string . "\n";
    $msg = new AMQPMessage($string, ['content_type' => 'text/json', 'delivery_mode' => 2]);
    $channel->basic_publish($msg, '', 'solved-interest-queue');
};
$channel->basic_consume('interest-queue', '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();