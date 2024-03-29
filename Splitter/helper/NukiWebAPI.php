<?php

declare(strict_types=1);

trait NukiWebAPI
{
    /**
     * Get a list of smartlocks
     * @return string
     * @throws Exception
     */
    public function GetSmartLocks(): string
    {
        $endpoint = 'https://api.nuki.io/smartlock';
        $result = $this->SendDataToNukiWeb($endpoint, 'GET', '');
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    /**
     * Get information about a specific smartlock
     * @param string $SmartLockID
     * @return string
     * @throws Exception
     */
    public function GetSmartLock(string $SmartLockID): string
    {
        $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID;
        $result = $this->SendDataToNukiWeb($endpoint, 'GET', '');
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    /**
     * Updates a smartlock config
     * @param string $SmartLockID
     * @param string $Config
     * @return string
     * @throws Exception
     */
    public function UpdateSmartLockConfig(string $SmartLockID, string $Config): string
    {
        $this->SendDebug(__FUNCTION__, 'Config: ' . $Config, 0);
        $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/config';
        $result = $this->SendDataToNukiWeb($endpoint, 'POST', $Config);
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    /**
     * Updates an opener advanced config
     * @param string $SmartLockID
     * @param string $Config
     * @return string
     * @throws Exception
     */
    public function UpdateOpenerAdvancedConfig(string $SmartLockID, string $Config): string
    {
        $this->SendDebug(__FUNCTION__, 'Config: ' . $Config, 0);
        $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/advanced/openerconfig';
        $result = $this->SendDataToNukiWeb($endpoint, 'POST', $Config);
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    /**
     * Lock & unlock a smartlock with options
     * @param string $SmartLockID
     * @param int $Action
     * @param int $Option
     * @return string
     * @throws Exception
     */
    public function SetSmartLockAction(string $SmartLockID, int $Action, int $Option): string
    {
        $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/action';
        /*
         * action (integer):
         * The action:
         * type=0: 1 unlock, 2 lock, 3 unlatch, 4 lock 'n' go, 5 lock 'n' go with unlatch
         * type=1: 1 unlock
         * type=2: 1 activate ring to open, 2 deactivate ring to open, 3 open (electric strike actuation), 6 activate continuous mode, 7 deactivate continuous mode
         *
         * option (integer, optional):
         * The option mask: 0 none, 2 force, 4 full lock
         */
        $postfields = '{"action": ' . $Action . ',"option": ' . $Option . '}';
        $result = $this->SendDataToNukiWeb($endpoint, 'POST', $postfields);
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    /**
     * Get a list of smartlock logs
     * @param string $SmartLockID
     * @param string $Parameter
     * @return string
     * @throws Exception
     */
    public function GetSmartLockLog(string $SmartLockID, string $Parameter): string
    {
        if (empty($Parameter)) {
            $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/log';
        } else {
            $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/log?' . $Parameter;
        }
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $result = $this->SendDataToNukiWeb($endpoint, 'GET', '');
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    ########## Webhook

    /**
     * Get all registered decentral webhooks from nuki web
     * @return string
     * @throws Exception
     */
    public function GetDecentralWebHooks(): string
    {
        $endpoint = 'https://api.nuki.io/api/decentralWebhook';
        $result = $this->SendDataToNukiWeb($endpoint, 'GET', '');
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    /**
     * Creates a decentral webhook on nuki web
     * @param string $WebhookURL
     * @param string $WebhookFeatures (must be json_encoded)
     * @return string
     * @throws Exception
     */
    public function CreateDecentralWebhook(string $WebhookURL, string $WebhookFeatures): string
    {
        $this->SendDebug(__FUNCTION__, 'Webhook URL: ' . $WebhookURL, 0);
        $this->SendDebug(__FUNCTION__, 'Webhook Features: ' . $WebhookFeatures, 0);
        $endpoint = 'https://api.nuki.io/api/decentralWebhook';
        $postfields = json_encode(['webhookUrl' => $WebhookURL, 'webhookFeatures' => json_decode($WebhookFeatures)]);
        $result = $this->SendDataToNukiWeb($endpoint, 'PUT', $postfields);
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    /**
     * Deletes a decentral webhook from nuki web
     * @param int $WebhookID
     * @return string
     * @throws Exception
     */
    public function DeleteDecentralWebhook(int $WebhookID): string
    {
        $this->SendDebug(__FUNCTION__, 'Webhook ID: ' . $WebhookID, 0);
        $endpoint = 'https://api.nuki.io/api/decentralWebhook/' . $WebhookID;
        $result = $this->SendDataToNukiWeb($endpoint, 'DELETE', '');
        $this->SendDebug(__FUNCTION__, 'Result: ' . $result, 0);
        return $result;
    }

    #################### Private

    /**
     * @throws Exception
     */
    public function SendDataToNukiWeb(string $Endpoint, string $CustomRequest, string $Postfields): string
    {
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $Endpoint, 0);
        $this->SendDebug(__FUNCTION__, 'CustomRequest: ' . $CustomRequest, 0);
        $result = [];
        $accessToken = $this->FetchAccessToken();
        if (empty($accessToken)) {
            return json_encode($result);
        }
        $body = '';
        $timeout = round($this->ReadPropertyInteger('Timeout') / 1000);
        //Send data to endpoint
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST   => $CustomRequest,
            CURLOPT_URL             => $Endpoint,
            CURLOPT_HEADER          => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_POSTFIELDS      => $Postfields,
            CURLOPT_HTTPHEADER      => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json']]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if (!curl_errno($ch)) {
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            # OK
            if ($httpCode == 200) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $header_size);
                $body = substr($response, $header_size);
                $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
                $this->SendDebug(__FUNCTION__, 'Response body: ' . $body, 0);
            }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($ch);
        $result = ['httpCode' => $httpCode, 'body' => $body];
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        return json_encode($result);
    }
}