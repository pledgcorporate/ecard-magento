<?php

namespace Pledg\PledgPaymentGateway\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Pledg\PledgPaymentGateway\Helper\Crypto;
use Psr\Log\LoggerInterface;

class RefundHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $refund_url = $response['GATEWAY_REFUND_GATEWAY_URL'];
        $merchant_number = $response['GATEWAY_MERCHANT_ID'];
        $apiKey = $response['GATEWAY_API_KEY'];

        $refund_amount = $handlingSubject['amount'];
        $payment = $handlingSubject['payment']->getPayment();

        if (empty($payment) || empty($payment->getData('creditmemo'))) {
            throw new LocalizedException(
                __('We can\'t issue a refund transaction because there is no capture transaction.')
            );
        }

        $transaction_id = $payment->getData()['creditmemo']->getData('invoice')->getData('transaction_id');
        $refund_details = array(
            "x_merchant_number" => $merchant_number,
            "x_purchase_number" => $transaction_id,
            "x_amount" => $refund_amount,
            "x_reason" => "Refund"
        );

        $refund_signature = Crypto::generateSignature($refund_details, $apiKey);
        $refund_details['signature'] = $refund_signature;

        $json = json_encode($refund_details);

        // Do refunding POST request using curl
        $curl = curl_init($refund_url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        $response = curl_exec($curl);

        // split and parse header and body
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header_string = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $header_rows = explode(PHP_EOL, $header_string);
        $header_rows_trimmed = array_map('trim', $header_rows);
        $parsed_header = ($this->parseHeaders($header_rows_trimmed));

        curl_close($curl);

        if ($parsed_header['response_code'] == '204') {
            return $this;
        } elseif ($parsed_header['response_code'] == '401') {
            $error_message = 'Pledg refunding error: Failed Signature Check when communicating with the Pledg gateway.';
        } elseif ($parsed_header['response_code'] == '400') {
            $return_message = json_decode($body, true)['Message'];
            $return_message_explain = '';
            if ($return_message == "MERR0001") {
                $return_message_explain = ' (API Key Not found)';
            } elseif ($return_message == "MERR0003") {
                $return_message_explain = ' (Refund Failed)';
            } elseif ($return_message == "MERR0004") {
                $return_message_explain = ' (Invalid Request)';
            }
            $error_message = 'Pledg refunding error with returned message from gateway: '.$return_message.$return_message_explain;
        } else {
            $error_message = "Pledg refunding failed with unknown error.";
        }
        $this->logger->error(__($error_message));

        throw new LocalizedException(__($error_message));
    }

    private function parseHeaders($headers)
    {
        $head = array();
        foreach ($headers as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1])) {
                $head[trim($t[0])] = trim($t[1]);
            } else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                    $head['response_code'] = intval($out[1]);
                }
            }
        }
        return $head;
    }
}
