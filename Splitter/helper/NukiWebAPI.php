<?php

declare(strict_types=1);

trait NukiWebAPI
{
    /**
     * Get a list of smartlocks
     * @return string
     */
    public function GetSmartLocks(): string
    {
        $endpoint = 'https://api.nuki.io/smartlock';
        return $this->SendDataToNukiWeb($endpoint, 'GET', '');
    }

    /**
     * Updates a smartlock config
     * @param string $SmartLockID
     * @param string $Config
     * @return string
     */
    public function UpdateSmartLockConfig(string $SmartLockID, string $Config): string
    {
        $this->SendDebug(__FUNCTION__, 'Config: ' . $Config, 0);
        $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/config';
        return $this->SendDataToNukiWeb($endpoint, 'POST', $Config);
    }

    /**
     * Updates an opener advanced config
     * @param string $SmartLockID
     * @param string $Config
     * @return string
     */
    public function UpdateOpenerAdvancedConfig(string $SmartLockID, string $Config): string
    {
        $this->SendDebug(__FUNCTION__, 'Config: ' . $Config, 0);
        $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/advanced/openerconfig';
        return $this->SendDataToNukiWeb($endpoint, 'POST', $Config);
    }

    /**
     * Lock & unlock a smartlock with options
     * @param string $SmartLockID
     * @param int $Action
     * @param int $Option
     * @return string
     */
    public function SetSmartLockAction(string $SmartLockID, int $Action, int $Option): string
    {
        $endpoint = 'https://api.nuki.io/smartlock/' . $SmartLockID . '/action';
        /*
         * action (integer):
         * The action:
         * type=0: 1 unlock, 2 lock, 3 unlatch, 4 lock 'n' go, 5 lock 'n' go with unlatch;
         * type=1: 1 unlock;
         * type=2: 1 activate ring to open, 2 deactivate ring to open, 3 open (electric strike actuation), 6 activate continuous mode, 7 deactivate continuous mode
         *
         * option (integer, optional):
         * The option mask: 0 none, 2 force, 4 full lock
         */
        $postfields = '{"action": ' . $Action . ',"option": ' . $Option . '}';
        return $this->SendDataToNukiWeb($endpoint, 'POST', $postfields);
    }

    #################### Private

    public function SendDataToNukiWeb(string $Endpoint, string $CustomRequest, string $Postfields): string
    {
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $Endpoint, 0);
        $this->SendDebug(__FUNCTION__, 'CustomRequest: ' . $CustomRequest, 0);
        $body = '';
        $accessToken = $this->ReadPropertyString('APIToken');
        if (empty($accessToken)) {
            return $body;
        }
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
        if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:  # OK
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($response, 0, $header_size);
                    $body = substr($response, $header_size);
                    $this->SendDebug(__FUNCTION__, 'Header: ' . $header, 0);
                    $this->SendDebug(__FUNCTION__, 'Body: ' . $body, 0);
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'HTTP Code: ' . $http_code, 0);
            }
        } else {
            $error_msg = curl_error($ch);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($ch);
        return $body;
    }
}