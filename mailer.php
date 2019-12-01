<?php
/**
 * @author      Brynjar Ingimarsson
 * @copyright   2019 Brynjar Ingimarsson
 * @link        https://github.com/Ingimarsson/smlymailer
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/settings.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Denpa\Bitcoin\Client as BitcoinClient;

// Initiate the mail interface

$mail = new PHPMailer;

$mail->isSMTP();
$mail->SMTPDebug = SMTP::DEBUG_SERVER;
$mail->Host = $settings['smtp']['host'];
$mail->Port = $settings['smtp']['port'];
$mail->SMTPAuth = true;
$mail->Username = $settings['smtp']['username'];
$mail->Password = $settings['smtp']['password'];

// Initiate the wallet RPC interface

$bitcoind = new BitcoinClient([
    'scheme' => 'http',
    'host' => $settings['rpc']['host'],
    'port' => $settings['rpc']['port'],
    'user' => $settings['rpc']['username'],
    'password' => $settings['rpc']['password'],
]);

// Continue from latest block of last run

$lastblock = file_get_contents(__DIR__ . '/lastblock.txt');

$result = $bitcoind->listSinceBlock($lastblock);
$txs = $result->get();

// Update latest block

file_put_contents(__DIR__ . '/lastblock.txt', $txs['lastblock']);

// Email the results

$mail->setFrom($settings['smtp']['from']);
$mail->addAddress($settings['smtp']['to']);

$mail->Subject = 'SMLY Wallet Notification';

$body = "<h1>SMLY Wallet Notification</h1>";
$body .= "<h2>".sizeof($txs['transactions'])." transactions</h2>";

foreach ($txs['transactions'] as $tx) {
    $body .= "<h3>".date("d M Y H:i:s", $tx['blocktime'])."</h3>";
    $body .= "<p>Type: ".$tx['category']."</p>";
    $body .= "<p>Address: ".$tx['address']."</p>";
    $body .= "<p>Amount: ".$tx['amount']." SMLY</p>";
}

$mail->msgHTML($body);

// Send email if there are any new transactions

if (sizeof($txs['transactions']) > 0) {
    $mail->send();
}
