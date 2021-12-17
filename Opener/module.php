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

        $this->RegisterPropertyString('SmartLockID', '');
        $this->RegisterPropertyString('AccountID', '');
        $this->RegisterPropertyString('AuthID', '');
        $this->RegisterPropertyString('Type', '');
        $this->RegisterPropertyString('Name', '');
        $this->RegisterPropertyInteger('UpdateInterval', 300);

        ##### Variables

        //Label ring suppression
        $this->RegisterVariableString('LabelRingSuppression', $this->Translate('Ring suppression'), '', 300);

        //Ring suppression ring
        $id = @$this->GetIDForIdent('RingSuppressionRing');
        $this->RegisterVariableBoolean('RingSuppressionRing', $this->Translate('Ring'), '~Switch', 310);
        $this->EnableAction('RingSuppressionRing');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingSuppressionRing'), 'Alert');
        }

        //Ring suppression ring to open
        $id = @$this->GetIDForIdent('RingSuppressionRingToOpen');
        $this->RegisterVariableBoolean('RingSuppressionRingToOpen', $this->Translate('Ring to Open'), '~Switch', 320);
        $this->EnableAction('RingSuppressionRingToOpen');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingSuppressionRingToOpen'), 'Alert');
        }

        //Ring suppression continous mode
        $id = @$this->GetIDForIdent('RingSuppressionContinuousMode');
        $this->RegisterVariableBoolean('RingSuppressionContinuousMode', $this->Translate('Continuous mode'), '~Switch', 330);
        $this->EnableAction('RingSuppressionContinuousMode');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingSuppressionContinuousMode'), 'Alert');
        }

        //Label sounds
        $this->RegisterVariableString('LabelSounds', $this->Translate('Sounds'), '', 400);

        //Sound doorbell rings
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SoundDoorbellRings';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Melody');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('No sound'), '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'Sound 1', '', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 2, 'Sound 2', '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Sound 3', '', 0x0000FF);
        $this->RegisterVariableInteger('SoundDoorbellRings', $this->Translate('Doorbell rings'), $profile, 410);
        $this->EnableAction('SoundDoorbellRings');

        //Sound open via app
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SoundOpenViaApp';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Melody');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('No sound'), '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'Sound 1', '', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 2, 'Sound 2', '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Sound 3', '', 0x0000FF);
        $this->RegisterVariableInteger('SoundOpenViaApp', $this->Translate('Open via app'), $profile, 420);
        $this->EnableAction('SoundOpenViaApp');

        //Sound ring to open
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SoundRingToOpen';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Melody');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('No sound'), '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'Sound 1', '', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 2, 'Sound 2', '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Sound 3', '', 0x0000FF);
        $this->RegisterVariableInteger('SoundRingToOpen', $this->Translate('Ring to Open'), $profile, 430);
        $this->EnableAction('SoundRingToOpen');

        //Sound continuous mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SoundContinuousMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Melody');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('No sound'), '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'Sound 1', '', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 2, 'Sound 2', '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Sound 3', '', 0x0000FF);
        $this->RegisterVariableInteger('SoundContinuousMode', $this->Translate('Continuous mode'), $profile, 440);
        $this->EnableAction('SoundContinuousMode');

        //Volume slider
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Volume';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 255, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Speaker');
        $this->RegisterVariableInteger('Volume', $this->Translate('Volume'), $profile, 450);
        $this->EnableAction('Volume');

        ##### Timer

        $this->RegisterTimer('Update', 0, self::MODULE_PREFIX . '_GetOpenerConfiguration(' . $this->InstanceID . ', true);');

        ##### Splitter

        //Connect to parent (Nuki Web Splitter)
        $this->ConnectParent('{DA16C1AA-0AFE-65B6-1A0C-5761A08A0FF8}');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['SoundDoorbellRings', 'SoundOpenViaApp', 'SoundRingToOpen', 'SoundContinuousMode', 'Volume'];
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

        $this->GetOpenerConfiguration(true);
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
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

    public function GetOpenerConfiguration(bool $Update): string
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
                    $openerConfig = $device;
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Actual config: ' . json_encode($openerConfig), 0);
        //Update values
        if ($Update) {
            if (!empty($openerConfig)) {
                if (array_key_exists('openerAdvancedConfig', $openerConfig)) {
                    if (is_array($openerConfig['openerAdvancedConfig'])) {
                        $doorbellSuppression = $openerConfig['openerAdvancedConfig']['doorbellSuppression'];
                        switch ($doorbellSuppression) {
                            case 0: //All off
                                $this->SetValue('RingSuppressionRing', false);
                                $this->SetValue('RingSuppressionRingToOpen', false);
                                $this->SetValue('RingSuppressionContinuousMode', false);
                                break;

                            case 1: //CM on
                                $this->SetValue('RingSuppressionRing', false);
                                $this->SetValue('RingSuppressionRingToOpen', false);
                                $this->SetValue('RingSuppressionContinuousMode', true);
                                break;

                            case 2: //RTO on
                                $this->SetValue('RingSuppressionRing', false);
                                $this->SetValue('RingSuppressionRingToOpen', true);
                                $this->SetValue('RingSuppressionContinuousMode', false);
                                break;

                            case 3: //RTO, CM on
                                $this->SetValue('RingSuppressionRing', false);
                                $this->SetValue('RingSuppressionRingToOpen', true);
                                $this->SetValue('RingSuppressionContinuousMode', true);
                                break;

                            case 4: //Ring on
                                $this->SetValue('RingSuppressionRing', true);
                                $this->SetValue('RingSuppressionRingToOpen', false);
                                $this->SetValue('RingSuppressionContinuousMode', false);
                                break;

                            case 5: //Ring, CM on
                                $this->SetValue('RingSuppressionRing', true);
                                $this->SetValue('RingSuppressionRingToOpen', false);
                                $this->SetValue('RingSuppressionContinuousMode', true);
                                break;

                            case 6: //Ring, RTO on
                                $this->SetValue('RingSuppressionRing', true);
                                $this->SetValue('RingSuppressionRingToOpen', true);
                                $this->SetValue('RingSuppressionContinuousMode', false);
                                break;

                            case 7: //All on
                                $this->SetValue('RingSuppressionRing', true);
                                $this->SetValue('RingSuppressionRingToOpen', true);
                                $this->SetValue('RingSuppressionContinuousMode', true);
                                break;
                        }
                        //Sounds
                        $this->SetValue('SoundDoorbellRings', $openerConfig['openerAdvancedConfig']['soundRing']);
                        $this->SetValue('SoundOpenViaApp', $openerConfig['openerAdvancedConfig']['soundOpen']);
                        $this->SetValue('SoundRingToOpen', $openerConfig['openerAdvancedConfig']['soundRto']);
                        $this->SetValue('SoundContinuousMode', $openerConfig['openerAdvancedConfig']['soundCm']);
                        $this->SetValue('Volume', $openerConfig['openerAdvancedConfig']['soundLevel']);
                    }
                }
            }
        }
        return json_encode($openerConfig);
    }

    ##### Request Action

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'RingSuppressionRing':
            case 'RingSuppressionRingToOpen':
            case 'RingSuppressionContinuousMode':
            case 'SoundDoorbellRings':
            case 'SoundOpenViaApp':
            case 'SoundRingToOpen':
            case 'SoundContinuousMode':
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

        //Get configuration
        $config = json_decode($this->GetOpenerConfiguration(false), true);

        // Prepare data
        $doorbellSuppression = 0;
        if ($this->GetValue('RingSuppressionRing')) {
            $doorbellSuppression += 4;
        }
        if ($this->GetValue('RingSuppressionRingToOpen')) {
            $doorbellSuppression += 2;
        }
        if ($this->GetValue('RingSuppressionContinuousMode')) {
            $doorbellSuppression += 1;
        }
        $openerAdvancedConfig = [];
        if (array_key_exists('smartlockId', $config)) {
            if ($config['smartlockId'] == $smartLockID) {
                if (array_key_exists('openerAdvancedConfig', $config)) {
                    if (is_array($config['openerAdvancedConfig'])) {
                        $config['openerAdvancedConfig']['doorbellSuppression'] = $doorbellSuppression;
                        $config['openerAdvancedConfig']['soundRing'] = $this->GetValue('SoundDoorbellRings');
                        $config['openerAdvancedConfig']['soundOpen'] = $this->GetValue('SoundOpenViaApp');
                        $config['openerAdvancedConfig']['soundRto'] = $this->GetValue('SoundRingToOpen');
                        $config['openerAdvancedConfig']['soundCm'] = $this->GetValue('SoundContinuousMode');
                        $config['openerAdvancedConfig']['soundLevel'] = $this->GetValue('Volume');
                    }
                }
                $openerAdvancedConfig = $config['openerAdvancedConfig'];
            }
        }
        $this->SendDebug(__FUNCTION__, 'New advanced config: ' . json_encode($openerAdvancedConfig), 0);

        //Update data
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