<?php

/** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

trait Helper_webOAuth
{
    private string $oauthIdentifier = 'nuki_web';
    private string $oauthServer = 'oauth.ipmagic.de';

    ########## Public

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

    public function RequestStatus(): void
    {
        echo $this->FetchData('https://' . $this->oauthServer . '/forward');
    }

    ########## Protected

    /**
     * This function will be called by the OAuth control.
     *
     * @throws Exception
     */
    protected function ProcessOAuthData(): void
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

    ########## Private

    /**
     * Fetches the Refresh Token.
     *
     * @param $code
     * @return string
     * @throws Exception
     */
    private function FetchRefreshToken($code): string
    {
        $refreshToken = '';
        $this->SendDebug(__FUNCTION__, 'Use Authentication Code to get our precious Refresh Token!', 0);
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
        $result = @file_get_contents('https://' . $this->oauthServer . '/access_token/' . $this->oauthIdentifier, false, $context);
        //Check result, must be a json encoded string
        if ($result === false) {
            $error = error_get_last();
            $this->SendDebug(__FUNCTION__, 'HTTP request failed. Error: ' . $error['message'], 0);
            $this->LogMessage('ID: ' . $this->InstanceID . ', Fetch refresh token, HTTP request failed. Error: ' . $error['message'], KL_ERROR);
            die();
        }
        if (is_string($result)) {
            if ($this->CheckJson($result)) {
                //We got a json string, so lets decode it
                $this->SendDebug(__FUNCTION__, $result, 0);
                $data = json_decode($result);
                if (!isset($data->token_type) || $data->token_type != 'bearer') {
                    $this->SendDebug(__FUNCTION__, 'Abort, Bearer Token expected!', 0);
                    $this->LogMessage('ID: ' . $this->InstanceID . ', Fetch refresh token. Abort, Bearer Token expected!', KL_WARNING);
                    die();
                }
                //Save temporary access token
                if (property_exists($data, 'access_token')) {
                    $this->FetchAccessToken($data->access_token, time() + $data->expires_in);
                }
                if (property_exists($data, 'refresh_token')) {
                    $refreshToken = $data->refresh_token;
                    $this->UpdateFormField('Token', 'caption', $refreshToken);
                }
            }
        }
        return $refreshToken;
    }

    /**
     * Fetches the Access Token.
     *
     * @param string $Token
     * @param int $Expires
     * @return mixed
     */
    private function FetchAccessToken(string $Token = '', int $Expires = 0): mixed
    {
        //Exchange our Refresh Token for temporary Access Token
        if ($Token == '' && $Expires == 0) {
            //Check if we already have a valid Access Token in cache
            $data = $this->GetBuffer('AccessToken');
            if ($data != '') {
                $data = json_decode($data);
                if (time() < $data->Expires) {
                    $this->SendDebug(__FUNCTION__, 'OK! Access Token is valid until: ' . date('d.m.y H:i:s', $data->Expires), 0);
                    return $data->Token;
                }
            }
            //If we slipped here we need to fetch the new Access Token via the Refresh Token
            $this->SendDebug(__FUNCTION__, 'Use Refresh Token to get a new Access Token!', 0);
            //Check for an existing Refresh Token
            if (empty($this->ReadAttributeString('Token'))) {
                //Abort, we have no Refresh Token yet, please register first
                die();
            }
            //Get new tokens
            $options = [
                'http' => [
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query(['refresh_token' => $this->ReadAttributeString('Token')])
                ]
            ];
            $context = stream_context_create($options);
            $result = @file_get_contents('https://' . $this->oauthServer . '/access_token/' . $this->oauthIdentifier, false, $context);
            //Check result, must be a json encoded string
            if ($result === false) {
                $error = error_get_last();
                $this->SendDebug(__FUNCTION__, 'HTTP request failed. Error: ' . $error['message'], 0);
                $this->LogMessage('ID: ' . $this->InstanceID . ', Fetch access token, HTTP request failed. Error: ' . $error['message'], KL_ERROR);
                die();
            }
            if (is_string($result)) {
                if ($this->CheckJson($result)) {
                    //We got a json string, so lets decode it
                    $this->SendDebug(__FUNCTION__, $result, 0);
                    $data = json_decode($result);
                    if (!isset($data->token_type) || $data->token_type != 'bearer') {
                        $this->SendDebug(__FUNCTION__, 'Abort, Bearer Token expected!', 0);
                        $this->LogMessage('ID: ' . $this->InstanceID . ', Fetch access token. Abort, Bearer Token expected!', KL_WARNING);
                        die();
                    }
                    //Update parameters to properly cache it in the next step
                    //Update Access Token
                    if (isset($data->access_token) && isset($data->expires_in)) {
                        $Token = $data->access_token;
                        $Expires = time() + $data->expires_in;
                        $this->SetBuffer('AccessToken', json_encode(['Token' => $Token, 'Expires' => $Expires]));
                        $this->SendDebug(__FUNCTION__, 'CACHE! New Access Token is valid until: ' . date('d.m.y H:i:s', $Expires), 0);
                        $this->UpdateFormField('AccessToken', 'caption', 'Access Token: ' . substr($data->access_token, 0, 16) . '...');
                        $this->UpdateFormField('TokenValidUntil', 'caption', $this->Translate('Valid until') . ': ' . date('d.m.y H:i:s', $Expires));
                    }
                    //Update Refresh Token
                    if (isset($data->refresh_token)) {
                        $this->SendDebug(__FUNCTION__, "NEW! Let's save the updated Refresh Token permanently", 0);
                        $this->WriteAttributeString('Token', $data->refresh_token);
                        $this->UpdateFormField('Token', 'caption', 'Refresh Token: ' . substr($data->refresh_token, 0, 16) . '...');
                    }
                }
            }
            $this->ValidateConfiguration();
        }
        //Return current Access Token
        return $Token;
    }

    private function FetchData($url): false|string
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
        if ((!str_contains($http_response_header[0], '200'))) {
            echo $http_response_header[0] . PHP_EOL . $result;
            return false;
        }
        return $result;
    }

    private function RegisterWebOAuth($WebOAuth): void
    {
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

    private function UnregisterWebOAuth($WebOAuth): void
    {
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
                $this->SendDebug(__FUNCTION__, 'WebOAuth was successfully unregistered', 0);
            }
        }
    }
}
