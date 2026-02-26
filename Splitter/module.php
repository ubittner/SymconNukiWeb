<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class NukiSplitterWebAPI extends IPSModuleStrict
{
    //Helper
    use NukiWebAPI;
    use Helper_webHook;
    use Helper_webOAuth;

    //Constants
    private const string LIBRARY_GUID = '{8CDE2F20-ECBF-F12E-45AC-B8A7F36CBBFC}';
    private const string MODULE_PREFIX = 'NUKISW';
    private const string CORE_CONNECT_GUID = '{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}';
    private const string CORE_WEBHOOK_GUID = '{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}';
    private const string CORE_WEBOAUTH_GUID = '{F99BF07D-CECA-438B-A497-E4B55F139D37}';
    private const string NUKI_DEVICE_DATA_GUID = '{6BD0D007-1A06-4F3F-1896-84E2BBFB4B09}';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyBoolean('Active', true);
        $this->RegisterPropertyInteger('Timeout', 5000);
        $this->RegisterPropertyBoolean('UseDeviceStateUpdates', true);

        //Attributes
        $this->RegisterAttributeString('Token', ''); /* This is the Refresh Token! */
        $this->RegisterAttributeString('WebhookURL', '');
        $this->RegisterAttributeString('WebhookSecret', '');
    }

    public function Destroy(): void
    {
        //Unregister WebOAuth
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterWebOAuth($this->oauthIdentifier);
        }

        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges(): void
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Register WebOAuth
        $this->RegisterWebOAuth($this->oauthIdentifier);

        //Register WebHook
        // old $this->RegisterWebHook('/hook/' . $this->oauthIdentifier);
        $this->RegisterHook($this->oauthIdentifier);

        //Check configuration
        if (!$this->ValidateConfiguration()) {
            return;
        }

        $this->PrepareNukiWebHook();
        $this->ManageWebhook();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    public function GetConfigurationForm(): string
    {
        $data = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $data['elements'][2]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date']);
        $data['actions'][2]['items'][0]['caption'] = $this->GetAccessToken() ? 'Access Token: ' . substr($this->GetAccessToken(), 0, 16) . ' ...' : 'Access Token: ' . $this->Translate('not available') . '!';
        $data['actions'][2]['items'][1]['caption'] = $this->Translate('Valid until') . ': ' . $this->GetAccessTokenExpires();
        $data['actions'][2]['items'][2]['caption'] = $this->ReadAttributeString('Token') ? 'Refresh Token: ' . substr($this->ReadAttributeString('Token'), 0, 16) . ' ...' : 'Refresh Token: ' . $this->Translate('not available') . '!';
        $data['actions'][2]['items'][3]['caption'] = 'Webhook URL: ' . $this->ReadAttributeString('WebhookURL');
        $data['actions'][2]['items'][4]['caption'] = 'Webhook Secret: ' . $this->ReadAttributeString('WebhookSecret');
        return json_encode($data);
    }

    public function ForwardData($JSONString): string
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'GetSmartLocks':
                $response = $this->GetSmartLocks();
                break;

            case 'GetSmartLock':
                $params = (array) $data->Buffer->Params;
                $response = $this->GetSmartLock($params['smartlockId']);
                break;

            case 'UpdateSmartLockConfig':
                $params = (array) $data->Buffer->Params;
                $response = $this->UpdateSmartLockConfig($params['smartlockId'], $params['config']);
                break;

            case 'UpdateOpenerAdvancedConfig':
                $params = (array) $data->Buffer->Params;
                $response = $this->UpdateOpenerAdvancedConfig($params['smartlockId'], $params['openerAdvancedConfig']);
                break;

            case 'SetSmartLockAction':
                $params = (array) $data->Buffer->Params;
                $response = $this->SetSmartLockAction($params['smartlockId'], $params['action'], $params['option']);
                break;

            case 'GetSmartLockLog':
                $params = (array) $data->Buffer->Params;
                $response = $this->GetSmartLockLog($params['smartlockId'], $params['parameter']);
                break;

            case 'SendDataToNukiWeb':
                $params = (array) $data->Buffer->Params;
                $response = $this->SendDataToNukiWeb($params['endpoint'], $params['customRequest'], $params['postfields']);
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                $response = '';
        }
        return $response;
    }

    ########## Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): bool
    {
        $result = true;
        $status = 102;
        if (empty($this->ReadAttributeString('Token'))) {
            $this->SendDebug(__FUNCTION__, 'Refresh Token is missing, please register first!', 0);
            $status = 201;
            $result = false;
        }
        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SendDebug(__FUNCTION__, 'Instance is inactive!', 0);
            $result = false;
            $status = 104;
        }
        $this->SetStatus($status);
        return $result;
    }

    private function GetAccessToken(): string
    {
        $buffer = $this->GetBuffer('AccessToken');
        if ($this->CheckJson($buffer)) {
            $data = json_decode($buffer);
            if (property_exists($data, 'Token')) {
                return $data->Token;
            }
        }
        return '';
    }

    private function GetAccessTokenExpires(): string
    {
        $buffer = $this->GetBuffer('AccessToken');
        if ($this->CheckJson($buffer)) {
            $data = json_decode($buffer);
            if (property_exists($data, 'Expires')) {
                return date('d.m.y H:i:s', $data->Expires);
            }
        }
        return '-';
    }

    /**
     * Checks if a sting is json encoded.
     *
     * @param string $String
     * @return bool
     * false:   no json string
     * true:    json string
     */
    private function CheckJson(string $String): bool
    {
        return json_validate($String);
    }
}