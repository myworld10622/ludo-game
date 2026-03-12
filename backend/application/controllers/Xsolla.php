<?php

use Xsolla\SDK\API\XsollaClient;

class Xsolla extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function payment_token()
    {

        $client = XsollaClient::factory(array(
            'merchant_id' => XSOLLA_MERCHANT_ID,
            'api_key' => XSOLLA_API_KEY
        ));
        $paymentUIToken = $client->createCommonPaymentUIToken(XSOLLA_PROJECT_ID, XSOLLA_USER_ID, $sandboxMode = true);
        print_r($paymentUIToken);
    }

    public function xsolla_server_token()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://login.xsolla.com/api/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials&client_id=' . XSOLLA_CLIENT_ID . '&client_secret=' . XSOLLA_CLIENT_SECRET_KEY,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }
    public function xsolla_payment_token()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://store.xsolla.com/api/v2/project/252327/admin/payment/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "user": {
            "id": {
                "value": "1"
            }
            },
            "sandbox":true,
            "purchase": {
                
            "items": [
                {
                "sku": "RoyalGold1",
                "quantity": 1
                }
            ]
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic MjUyMzI3OmUxOGJjOWIyZjcxOTk3MDc4OTc5ZGRjZTk4MjdjOGJlZTRlMzQ2YjY='
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;


    }
}