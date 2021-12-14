<?php

class DroidSendApi
{
    private $api_key;
    private $api_url  = 'https://app.droidsend.com/';
    private $device_id;




    public function __construct($api_key, $device_id)
    {
        $this->api_key = $api_key;
        $this->device_id = $device_id;
    }



    /**
 * @param string     $number      The mobile number where you want to send message.
 * @param string     $message     The message you want to send.
 * @param int|string $device      The ID of a device you want to use to send this message.
 * @param int        $schedule    Set it to timestamp when you want to send this message.
 * @param bool       $isMMS       Set it to true if you want to send MMS message instead of SMS.
 * @param string     $attachments Comma separated list of image links you want to attach to the message. Only works for MMS messages.
 * @param bool       $prioritize  Set it to true if you want to prioritize this message.
 *
 * @return array     Returns The array containing information about the message.
 * @throws Exception If there is an error while sending a message.
 */
public function sendMessage($number, $message, $schedule = null, $isMMS = false, $attachments = null, $prioritize = false)
{
    $url = $this->api_url . "/services/send.php";
    $postData = array(
        'number' => $number,
        'message' => $message,
        'schedule' => $schedule,
        'key' => $this->api_key,
        'devices' => $this->device_id,
        'type' => $isMMS ? "mms" : "sms",
        'attachments' => $attachments,
        'prioritize' => $prioritize ? 1 : 0
    );
    return $this->sendRequest($url, $postData)["messages"][0];
}

    
        /**
        * @param string $url
        * @param array  $postData
        *
        * @return array
        * @throws Exception
        */
 private  function sendRequest($url, $postData)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            curl_close($ch);
            if ($httpCode == 200) {
                $json = json_decode($response, true);
                if ($json == false) {
                    if (empty($response)) {
                        throw new Exception("Missing data in request. Please provide all the required information to send messages.");
                    } else {
                        throw new Exception($response);
                    }
                } else {
                    if ($json["success"]) {
                        return $json["data"];
                    } else {
                        throw new Exception($json["error"]["message"]);
                    }
                }
            } else {
                throw new Exception("HTTP Error Code : {$httpCode}");
            }
        }
}
