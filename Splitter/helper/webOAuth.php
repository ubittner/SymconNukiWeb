<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait Helper_webOAuth
{
    private $oauthIdentifier = 'nuki_web';
    private $oauthServer = 'oauth.ipmagic.de';

    #################### Public

    /**
     * This function will be called by the register button on the property page!
     *
     * @return string
     */
    public function Register(): string
    {
        //Return everything which will open the browser
        return 'https://' . $this->oauthServer . '/authorize/' . $this->oauthIdentifier . '?username=' . urlencode(IPS_GetLicensee());
    }

    public function RequestStatus()
    {
        echo $this->FetchData('https://' . $this->oauthServer . '/forward');
    }

    #################### Protected

    /**
     * This function will be called by the OAuth control.
     *
     * @throws Exception
     */
    protected function ProcessOAuthData()
    {
        //Let's assume requests via GET are for code exchange.
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!isset($_GET['code'])) {
                die('Authorization Code expected');
            }
            $token = $this->FetchRefreshToken($_GET['code']);
            $this->SendDebug('ProcessOAuthData', "OK! Let's save the Refresh Token permanently", 0);
            $this->WriteAttributeString('Token', $token);
            $this->UpdateFormField('Token', 'caption', 'Token: ' . substr($token, 0, 16) . ' ...');
            $this->ManageWebhook();
        } else {
            //Just print raw post data!
            echo file_get_contents('php://input');
        }
    }

    #################### Private

    private function FetchRefreshToken($code): string
    {
        $this->SendDebug('FetchRefreshToken', 'Use Authentication Code to get our precious Refresh Token!', 0);
        $this->SendDebug(__FUNCTION__, json_encode($code), 0);
        //Exchange our Authentication Code for a permanent Refresh Token and temporary Access Token
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query(['code' => $code])
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents('https://' . $this->oauthServer . '/access_token/' . $this->oauthIdentifier, false, $context);
        $this->SendDebug(__FUNCTION__, $result, 0);
        $data = json_decode($result);
        if (!isset($data->token_type) || $data->token_type != 'bearer') {
            die('Bearer Token expected');
        }
        //Save temporary access token
        $this->FetchAccessToken($data->access_token, time() + $data->expires_in);
        //Return RefreshToken
        return $data->refresh_token;
    }

    private function FetchAccessToken($Token = '', $Expires = 0)
    {
        //Exchange our Refresh Token for temporary Access Token
        if ($Token == '' && $Expires == 0) {
            //Check if we already have a valid Token in cache
            $data = $this->GetBuffer('AccessToken');
            if ($data != '') {
                $data = json_decode($data);
                if (time() < $data->Expires) {
                    $this->SendDebug('FetchAccessToken', 'OK! Access Token is valid until ' . date('d.m.y H:i:s', $data->Expires), 0);
                    return $data->Token;
                }
            }
            $this->SendDebug('FetchAccessToken', 'Use Refresh Token to get new Access Token!', 0);
            //If we slipped here we need to fetch the access token
            if (empty($this->ReadAttributeString('Token'))) {
                //Abort, we have no refresh token
                return '';
            }
            $options = [
                'http' => [
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query(['refresh_token' => $this->ReadAttributeString('Token')])
                ]
            ];
            $context = stream_context_create($options);
            $result = file_get_contents('https://' . $this->oauthServer . '/access_token/' . $this->oauthIdentifier, false, $context);
            $this->SendDebug(__FUNCTION__, $result, 0);
            $data = json_decode($result);
            if (!isset($data->token_type) || $data->token_type != 'bearer') {
                die('Bearer Token expected');
            }
            //Update parameters to properly cache it in the next step
            $Token = $data->access_token;
            $Expires = time() + $data->expires_in;
            //Update Refresh Token
            if (isset($data->refresh_token)) {
                $this->SendDebug('FetchAccessToken', "NEW! Let's save the updated Refresh Token permanently", 0);
                $this->WriteAttributeString('Token', $data->refresh_token);
                $this->UpdateFormField('Token', 'caption', 'Token: ' . substr($data->refresh_token, 0, 16) . '...');
                $this->ValidateConfiguration();
            }
        }
        $this->SendDebug('FetchAccessToken', 'CACHE! New Access Token is valid until ' . date('d.m.y H:i:s', $Expires), 0);
        //Save current Token
        $this->SetBuffer('AccessToken', json_encode(['Token' => $Token, 'Expires' => $Expires]));
        //Return current Token
        return $Token;
    }

    private function FetchData($url)
    {
        $opts = [
            'http'=> [
                'method'        => 'POST',
                'header'        => 'Authorization: Bearer ' . $this->FetchAccessToken() . "\r\n" . 'Content-Type: application/json' . "\r\n",
                'content'       => '{"JSON-KEY":"THIS WILL BE LOOPED BACK AS RESPONSE!"}',
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);
        $this->SendDebug(__FUNCTION__, 'Context: ' . $context, 0);
        $result = file_get_contents($url, false, $context);
        if ((strpos($http_response_header[0], '200') === false)) {
            echo $http_response_header[0] . PHP_EOL . $result;
            return false;
        }
        return $result;
    }

    private function RegisterWebOAuth($WebOAuth)
    {
        $this->SendDebug(__FUNCTION__, 'Method was executed.', 0);
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBOAUTH_GUID);
        if (count($ids) > 0) {
            $clientIDs = json_decode(IPS_GetProperty($ids[0], 'ClientIDs'), true);
            $found = false;
            foreach ($clientIDs as $index => $clientID) {
                if ($clientID['ClientID'] == $WebOAuth) {
                    if ($clientID['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $clientIDs[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $clientIDs[] = ['ClientID' => $WebOAuth, 'TargetID' => $this->InstanceID];
                $this->SendDebug(__FUNCTION__, 'WebOAuth was successfully registered', 0);
            }
            IPS_SetProperty($ids[0], 'ClientIDs', json_encode($clientIDs));
            IPS_ApplyChanges($ids[0]);
        }
    }

    private function UnregisterWebOAuth($WebOAuth)
    {
        $this->SendDebug(__FUNCTION__, 'Method was executed.', 0);
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBOAUTH_GUID);
        if (count($ids) > 0) {
            $clientIDs = json_decode(IPS_GetProperty($ids[0], 'ClientIDs'), true);
            $found = false;
            $index = null;
            foreach ($clientIDs as $key => $clientID) {
                if ($clientID['ClientID'] == $WebOAuth) {
                    if ($clientID['TargetID'] == $this->InstanceID) {
                        $found = true;
                        $index = $key;
                        break;
                    }
                }
            }
            if ($found === true && !is_null($index)) {
                array_splice($clientIDs, $index, 1);
                IPS_SetProperty($ids[0], 'ClientIDs', json_encode($clientIDs));
                IPS_ApplyChanges($ids[0]);
                $this->SendDebug(__FUNCTION__, 'WebOAuth was successfully registered', 0);
            }
        }
    }
}
