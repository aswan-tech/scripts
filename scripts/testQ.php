<?php
$url = "https://sqs.ap-southeast-1.amazonaws.com/444226616090/testOrder_Q";
while(true) {
    $res = $client->receiveMessage(array(
        'QueueUrl'          => $url,
        'WaitTimeSeconds'   => 1
    ));
    if ($res->getPath('Messages')) {

        foreach ($res->getPath('Messages') as $msg) {
            echo "Received Msg: ".$msg['Body'];
        }
        // Do something useful with $msg['Body'] here
        $res = $client->deleteMessage(array(
            'QueueUrl'      => $url,
            'ReceiptHandle' => $msg['ReceiptHandle']
        ));
    }
}
?>
