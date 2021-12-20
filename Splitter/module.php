<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class NukiSplitterWebAPI extends IPSModule
{
    //Helper
    use NukiWebAPI;

    //Constants
    private const LIBRARY_GUID = '{8CDE2F20-ECBF-F12E-45AC-B8A7F36CBBFC}';
    private const MODULE_PREFIX = 'NUKISW';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyBoolean('Active', false);
        $this->RegisterPropertyString('APIToken', '');
        $this->RegisterPropertyInteger('Timeout', 5000);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->ValidateConfiguration();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $version = 'Version: ' . $library['Version'] . '-' . $library['Build'] . ' vom ' . date('d.m.Y', $library['Date']);
        $formData['elements'][2]['caption'] = $version;
        return json_encode($formData);
    }

    public function ForwardData($JSONString): string
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'GetSmartLocks':
                $response = $this->GetSmartLocks();
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
        $this->SendDebug(__FUNCTION__, $response, 0);
        return $response;
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): void
    {
        $status = 102;
        if (empty($this->ReadPropertyString('APIToken'))) {
            $this->SendDebug(__FUNCTION__, 'API Token is missing!', 0);
            $status = 200;
        }
        $active = $this->ReadPropertyBoolean('Active');
        if (!$active) {
            $this->SendDebug(__FUNCTION__, 'Instance is inactive!', 0);
            $status = 104;
        }
        $this->SetStatus($status);
    }
}