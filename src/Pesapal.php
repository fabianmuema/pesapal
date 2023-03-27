<?php

namespace Fabian\Pesapal;

include 'OAuth.php';

use Session;

class Pesapal
{
    public function makePayment($params): string
    {
        if (!array_key_exists('currency', $params)) {
            if (config('pesapal.currency') != null) {
                $params['currency'] = config('pesapal.currency');
            }
        }

        Session::put('pesapal_callback_route', $params['callback_url'] ?? '');
        Session::put('pesapal_success_controller_method', $params['success_url'] ?? '');
        Session::put('pesapal_is_live', config('pesapal.live', env('PESAPAL_LIVE')));

        $token = null;

        $consumer_key = config('pesapal.consumer_key', env('PESAPAL_CONSUMER_KEY'));

        $consumer_secret = config('pesapal.consumer_secret', env('PESAPAL_CONSUMER_KEY'));

        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

        $iframelink = config(
            'pesapal.live',
            false
        ) ? 'https://www.pesapal.com/API/PostPesapalDirectOrderV4' : 'http://demo.pesapal.com/api/PostPesapalDirectOrderV4';

        $post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
                        <PesapalDirectOrderInfo
                            xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\"
                            xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"
                            Amount=\"".$params['amount']."\"
                            Description=\"".$params['description']."\"
                            Type=\"".$params['type']."\"
                            Reference=\"".$params['reference']."\"
                            FirstName=\"".$params['first_name']."\"
                            LastName=\"".$params['last_name']."\"
                            Currency=\"".$params['currency']."\"
                            Email=\"".$params['email']."\"
                            PhoneNumber=\"".$params['phonenumber']."\"
                            xmlns=\"http://www.pesapal.com\" />";

        $post_xml = htmlentities($post_xml);

        $consumer = new OAuthConsumer($consumer_key, $consumer_secret);

        $iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $iframelink, $params);

        $iframe_src->set_parameter("oauth_callback", $params['callback_url']);

        $iframe_src->set_parameter("pesapal_request_data", $post_xml);

        $iframe_src->sign_request($signature_method, $consumer, $token);

        return '<iframe src="'.$iframe_src.'" width="100%" height="720px" scrolling="auto" frameBorder="0"> <p>Unable to load the payment page</p> </iframe>';
    }

    function redirectToIPN($pesapalNotification, $pesapal_merchant_reference, $pesapalTrackingId)
    {
        $consumer_key = config('pesapal.consumer_key', env('PESAPAL_CONSUMER_KEY'));
        $consumer_secret = config('pesapal.consumer_secret', env('PESAPAL_CONSUMER_SECRET'));

        $statusrequestAPI = Session::get(
            'pesapal_is_live'
        ) ? 'https://www.pesapal.com/api/querypaymentstatus' : 'http://demo.pesapal.com/api/querypaymentstatus';

        \Log::error($statusrequestAPI);

        if ($pesapalNotification == "CHANGE" && $pesapalTrackingId != '') {
            $token = $params = null;
            $consumer = new OAuthConsumer($consumer_key, $consumer_secret);
            $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

            //get transaction status
            $request_status = OAuthRequest::from_consumer_and_token(
                $consumer,
                $token,
                "GET",
                $statusrequestAPI,
                $params
            );
            $request_status->set_parameter("pesapal_merchant_reference", $pesapal_merchant_reference);
            $request_status->set_parameter("pesapal_transaction_tracking_id", $pesapalTrackingId);
            $request_status->sign_request($signature_method, $consumer, $token);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $request_status);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $response = curl_exec($ch);

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $raw_header = substr($response, 0, $header_size - 4);
            $headerArray = explode("\r\n\r\n", $raw_header);
            $header = $headerArray[count($headerArray) - 1];

            //transaction status
            $elements = preg_split("/=/", substr($response, $header_size));
            $status = $elements[1];

            curl_close($ch);

            //UPDATE YOUR DB TABLE WITH NEW STATUS FOR TRANSACTION WITH pesapal_transaction_tracking_id $pesapalTrackingId
            $separator = explode('@', Session::get('pesapal_success_controller_method'));
            $controller = $separator[0];
            $method = $separator[1];
            $class = '\App\Http\Controllers\\'.$separator[0];
            $payment = new $class();
            $payment->$method();

            if ($status != "PENDING") {
                $resp = "pesapal_notification_type=$pesapalNotification&pesapal_transaction_tracking_id=$pesapalTrackingId&pesapal_merchant_reference=$pesapal_merchant_reference";
                ob_start();
                echo $resp;
                ob_flush();
                exit;
            }
        }
    }


    public function random_reference(): string
    {
        $length = 15;

        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $str = '';

        $max = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }

        return 'PESAPAL'.$str;
    }

}
