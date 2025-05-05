<?php

/** @noinspection PhpMissingReturnTypeInspection */
/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait Helper_webHook
{
    #################### Protected

    /**
     * This function will be called by the hook control and data will be forwarded to the devices.
     *
     * @throws Exception
     */
    protected function ProcessHookData()
    {
        //Get incoming data from server
        $this->SendDebug(__FUNCTION__, 'Incoming data: ' . print_r($_SERVER, true), 0);
        //Get content
        $data = file_get_contents('php://input');
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        //Check HMAC SHA256 signature
        $secret = $this->ReadAttributeString('WebhookSecret');
        if (empty($secret)) {
            $this->SendDebug(__FUNCTION__, 'We have no webhook secret!', 0);
            return;
        }
        $expectedSignature = hash_hmac('sha256', $data, $secret);
        $this->SendDebug(__FUNCTION__, 'Expected signature: ' . $expectedSignature, 0);
        $receivedSignature = $_SERVER['HTTP_X_NUKI_SIGNATURE_SHA256'];
        $this->SendDebug(__FUNCTION__, 'Received signature: ' . $receivedSignature, 0);
        if ($expectedSignature != $receivedSignature) {
            $this->SendDebug(__FUNCTION__, 'Signature is invalid!', 0);
            header('WWW-Authenticate: Basic Realm="Nuki Web WebHook"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authorization required';
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Signature is valid!', 0);
        //Send data to children
        $forwardData = [];
        $forwardData['DataID'] = self::NUKI_DEVICE_DATA_GUID;
        $forwardData['Buffer'] = json_decode($data);
        $forwardData = json_encode($forwardData);
        $this->SendDebug(__FUNCTION__, 'Forward Data: ' . $forwardData, 0);
        $this->SendDataToChildren($forwardData);
    }

    #################### Private

    /**
     * Registers a webhook to the WebHook Control.
     *
     * @param $WebHook
     */
    private function RegisterWebHook($WebHook): void
    {
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBHOOK_GUID);
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
                $this->SendDebug(__FUNCTION__, 'WebHook was successfully registered.', 0);
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    /**
     * Unregisters a webhook from the WebHook Control.
     *
     * @param $WebHook
     */
    private function UnregisterWebHook($WebHook): void
    {
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBHOOK_GUID);
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            $index = null;
            foreach ($hooks as $key => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        $found = true;
                        $index = $key;
                        break;
                    }
                }
            }
            if ($found === true && !is_null($index)) {
                array_splice($hooks, $index, 1);
                IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
                IPS_ApplyChanges($ids[0]);
                $this->SendDebug(__FUNCTION__, 'WebHook was successfully unregistered.', 0);
            }
        }
    }

    /**
     * Prepares the webhook url, username and password for Nuki Web.
     *
     * @throws Exception
     */
    private function PrepareNukiwebHook(): void
    {
        $webhookURL = $this->ReadAttributeString('WebhookURL');
        $this->SendDebug(__FUNCTION__, 'Saved Webhook URL: ' . $webhookURL, 0);
        if (!empty($webhookURL)) {
            return;
        }
        // Get ipmagic address and add webhook credentials
        $ids = IPS_GetInstanceListByModuleID(self::CORE_CONNECT_GUID);
        if (count($ids) > 0) {
            $url = CC_GetURL($ids[0]) . '/hook/' . $this->oauthIdentifier;
            $this->WriteAttributeString('WebhookURL', $url);
            $this->SendDebug(__FUNCTION__, 'Webhook URL: ' . $url, 0);
        }
    }

    /**
     * Manages the webhook for Nuki Web.
     *
     * @throws Exception
     */
    private function ManageWebhook(): void
    {
        //Webhook for device state updates
        if ($this->ReadPropertyBoolean('UseDeviceStateUpdates')) {
            $this->CreateNukiWebhook();
        } else {
            $this->DeleteNukiWebhook();
        }
    }

    /**
     * Creates the decental webhook on Nuki Web.
     *
     * @throws Exception
     */
    private function CreateNukiWebhook(): void
    {
        if ($this->GetStatus() != 102) {
            return;
        }
        if (empty($this->ReadAttributeString('Token'))) {
            return;
        }
        $ids = IPS_GetInstanceListByModuleID(self::CORE_CONNECT_GUID);
        if (count($ids) > 0) {
            if (IPS_GetInstance($ids[0])['InstanceStatus'] == 102) {
                if ($this->ReadPropertyBoolean('UseDeviceStateUpdates')) {
                    //Check for existing webhook
                    $existing = false;
                    $webhookURL = $this->ReadAttributeString('WebhookURL');
                    if (!empty($webhookURL)) {
                        //Identifiy webhook id
                        $decentralWebhooks = json_decode($this->GetDecentralWebHooks(), true);
                        if (array_key_exists('body', $decentralWebhooks)) {
                            $webhooks = json_decode($decentralWebhooks['body'], true);
                            foreach ($webhooks as $webhook) {
                                if (array_key_exists('webhookUrl', $webhook)) {
                                    if ($this->ReadAttributeString('WebhookURL') == $webhook['webhookUrl']) {
                                        if (array_key_exists('id', $webhook)) {
                                            $existing = true;
                                            $this->SendDebug(__FUNCTION__, 'Webhook ID: ' . $webhook['id'], 0);
                                        }
                                        if (array_key_exists('secret', $webhook)) {
                                            $secret = $webhook['secret'];
                                            $this->WriteAttributeString('WebhookSecret', $secret);
                                            $this->UpdateFormField('WebhookSecret', 'caption', 'Webhook Secret: ' . $secret);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!$existing) {
                        //Used features
                        $webhookFeatures = ['DEVICE_STATUS', 'DEVICE_CONFIG', 'DEVICE_LOGS'];
                        //Create webhook on Nuki Web
                        $result = json_decode($this->CreateDecentralWebhook($this->ReadAttributeString('WebhookURL'), json_encode($webhookFeatures)), true);
                        if (array_key_exists('httpCode', $result)) {
                            $httpCode = $result['httpCode'];
                            if ($httpCode == 200) {
                                $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
                                if (array_key_exists('body', $result)) {
                                    $body = json_decode($result['body'], true);
                                    if (array_key_exists('secret', $body)) {
                                        $secret = $body['secret'];
                                        $this->WriteAttributeString('WebhookSecret', $secret);
                                        $this->UpdateFormField('WebhookSecret', 'caption', 'Webhook Secret: ' . $secret);
                                    }
                                }
                            } else {
                                $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode . ', something went wrong!', 0);
                            }
                        }
                    }
                }
            } else {
                $this->SendDebug(__FUNCTION__, 'Connect Control is deactivated!', 0);
                $this->LogMessage('Connect Control is deactivated!', KL_NOTIFY);
            }
        }
    }

    /**
     * Deletes the decentral webhook from Nuki Web.
     *
     * @throws Exception
     */
    private function DeleteNukiWebhook(): void
    {
        if (empty($this->ReadAttributeString('Token'))) {
            return;
        }
        $webhookURL = $this->ReadAttributeString('WebhookURL');
        $this->SendDebug(__FUNCTION__, 'Actual Webhook URL: ' . $webhookURL, 0);
        if (!empty($webhookURL)) {
            //Identifiy webhook id
            $decentralWebhooks = json_decode($this->GetDecentralWebHooks(), true);
            if (array_key_exists('body', $decentralWebhooks)) {
                $webhooks = json_decode($decentralWebhooks['body'], true);
                foreach ($webhooks as $webhook) {
                    if (array_key_exists('webhookUrl', $webhook)) {
                        if ($this->ReadAttributeString('WebhookURL') == $webhook['webhookUrl']) {
                            if (array_key_exists('id', $webhook)) {
                                $id = $webhook['id'];
                                $this->SendDebug(__FUNCTION__, 'Webhook ID: ' . $id, 0);
                                //Delete webhook on Nuki Web
                                $result = json_decode($this->DeleteDecentralWebhook($id), true);
                                if (array_key_exists('httpCode', $result)) {
                                    $httpCode = $result['httpCode'];
                                    if ($httpCode == 204) {
                                        $this->SendDebug(__FUNCTION__, 'Decentral webhook was deleted successfully.', 0);
                                        $this->WriteAttributeString('WebhookSecret', '');
                                    } else {
                                        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode . ', something went wrong!', 0);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->WriteAttributeString('WebhookURL', '');
        $this->UpdateFormField('WebhookURL', 'caption', 'Webhook URL: ');
        $webhookURL = $this->ReadAttributeString('WebhookURL');
        $this->SendDebug(__FUNCTION__, 'New Webhook URL: ' . $webhookURL, 0);
        $this->WriteAttributeString('WebhookSecret', '');
        $this->UpdateFormField('WebhookSecret', 'caption', 'Webhook Secret: ');
    }
}