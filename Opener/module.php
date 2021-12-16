<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class NukiOpenerWebAPI extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{8CDE2F20-ECBF-F12E-45AC-B8A7F36CBBFC}';
    private const MODULE_PREFIX = 'NUKIOW';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ##### Properties

        //Opener
        $this->RegisterPropertyString('SmartLockID', '');
        $this->RegisterPropertyString('AccountID', '');
        $this->RegisterPropertyString('AuthID', '');
        $this->RegisterPropertyString('Type', '');
        $this->RegisterPropertyString('Name', '');
        //Sound
        $this->RegisterPropertyBoolean('RingSuppressionRing', false);
        $this->RegisterPropertyBoolean('RingSuppressionRingToOpen', false);
        $this->RegisterPropertyBoolean('RingSuppressionContinuousMode', false);
        $this->RegisterPropertyInteger('SoundDoorbellRings', 0);
        $this->RegisterPropertyInteger('SoundOpenViaApp', 0);
        $this->RegisterPropertyInteger('SoundRingToOpen', 0);
        $this->RegisterPropertyInteger('SoundContinuousMode', 0);

        ##### Variables

        //Ring suppression
        $id = @$this->GetIDForIdent('RingSuppression');
        $this->RegisterVariableBoolean('RingSuppression', $this->Translate('Ring suppression'), '~Switch', 300);
        $this->EnableAction('RingSuppression');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingSuppression'), 'Alert');
        }

        //Volume slider
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Volume';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 255, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Speaker');
        $this->RegisterVariableInteger('Volume', $this->Translate('Volume'), $profile, 310);
        $this->EnableAction('Volume');

        ##### Splitter

        //Connect to parent (Nuki Web Splitter)
        $this->ConnectParent('{DA16C1AA-0AFE-65B6-1A0C-5761A08A0FF8}');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Volume'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
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

        $this->UpdateAdvanceConfig();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
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

    public function ReceiveData($JSONString)
    {
        //Received data from splitter, not used at the moment
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    public function GetOpenerConfiguration(): string
    {
        $openerConfig = '';
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return $openerConfig;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'GetSmartLocks';
        $buffer['Params'] = '';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $devices = json_decode($this->SendDataToParent($data), true);
        foreach ($devices as $device) {
            if (array_key_exists('smartlockId', $device)) {
                if ($device['smartlockId'] == $smartLockID) {
                    $openerConfig = json_encode($device);
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Config: ' . $openerConfig, 0);
        return $openerConfig;
    }

    ##### Request Action

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'RingSuppression':
            case 'Volume':
                $this->SetValue($Ident, $Value);
                $this->UpdateAdvanceConfig();
                break;

        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function UpdateAdvanceConfig(): void
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return;
        }

        ##### Get configuration

        $config = json_decode($this->GetOpenerConfiguration(), true);
        $this->SendDebug(__FUNCTION__, 'Devices: ' . json_encode($config), 0);

        ##### Prepare data

        //Dorbell suppression
        $doorbellSuppression = 0;
        if ($this->GetValue('RingSuppression')) {
            if ($this->ReadPropertyBoolean('RingSuppressionRing')) {
                $doorbellSuppression += 4;
            }
            if ($this->ReadPropertyBoolean('RingSuppressionRingToOpen')) {
                $doorbellSuppression += 2;
            }
            if ($this->ReadPropertyBoolean('RingSuppressionContinuousMode')) {
                $doorbellSuppression += 1;
            }
        }

        //Sounds
        $soundRing = $this->ReadPropertyInteger('SoundDoorbellRings');
        $soundOpen = $this->ReadPropertyInteger('SoundOpenViaApp');
        $soundRto = $this->ReadPropertyInteger('SoundRingToOpen');
        $soundCm = $this->ReadPropertyInteger('SoundContinuousMode');

        //Volume
        $soundLevel = $this->GetValue('Volume');

        //Update config
        $openerAdvancedConfig = [];
        if (array_key_exists('smartlockId', $config)) {
            if ($config['smartlockId'] == $smartLockID) {
                if (array_key_exists('openerAdvancedConfig', $config)) {
                    if (is_array($config['openerAdvancedConfig'])) {
                        $config['openerAdvancedConfig']['doorbellSuppression'] = $doorbellSuppression;
                        $config['openerAdvancedConfig']['soundRing'] = $soundRing;
                        $config['openerAdvancedConfig']['soundOpen'] = $soundOpen;
                        $config['openerAdvancedConfig']['soundRto'] = $soundRto;
                        $config['openerAdvancedConfig']['soundCm'] = $soundCm;
                        $config['openerAdvancedConfig']['soundLevel'] = $soundLevel;
                    }
                }
                $openerAdvancedConfig = $config['openerAdvancedConfig'];
            }
        }

        ##### Update new advanced config

        if (empty($openerAdvancedConfig)) {
            return;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'UpdateOpenerAdvancedConfig';
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'openerAdvancedConfig' => json_encode($openerAdvancedConfig)];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDataToParent($data);
    }
}