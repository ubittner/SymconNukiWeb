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

        //Door
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Door';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Door');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Open'), '', 0x00FF00);
        $this->RegisterVariableInteger('Door', $this->Translate('Door'), $profile, 10);
        $this->EnableAction('Door');

        //Label state
        $this->RegisterVariableString('LabelState', $this->Translate('State'), '', 100);

        //Device state
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DeviceState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Untrained'), 'Execute', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Online'), 'Network', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, $this->Translate('Ring to Open active'), 'Alert', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 5, $this->Translate('Open'), 'Door', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 7, $this->Translate('Opening'), 'Door', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 253, 'Boot Run', 'Repeat', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 255, $this->Translate('Undefined'), 'Warning', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 256, $this->Translate('Unknown'), 'Information', -1);
        $id = @$this->GetIDForIdent('DeviceState');
        $this->RegisterVariableInteger('DeviceState', $this->Translate('Device state'), $profile, 110);
        if ($id == false) {
            $this->SetValue('DeviceState', 256);
        }

        //Battery state
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Battery');
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Low battery'), '', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Unknown'), '', -1);
        $this->RegisterVariableInteger('BatteryState', $this->Translate('Battery state'), $profile, 120);

        //Label Ring to open
        $this->RegisterVariableString('LabelRingToOpen', 'Ring to Open', '', 200);

        //Ring to open
        $id = @$this->GetIDForIdent('RingToOpen');
        $this->RegisterVariableBoolean('RingToOpen', 'Ring to Open', '~Switch', 210);
        $this->EnableAction('RingToOpen');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingToOpen'), 'Alert');
        }

        //Ring to open timeout
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.RingToOpenTimeout';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Clock');
        IPS_SetVariableProfileAssociation($profile, 5, '5 min', '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 10, '10 min', '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 15, '15 min', '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 20, $this->Translate('20 min (default)'), '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 30, '30 min', '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 45, '45 min', '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 60, '60 min', '', 0x0000FF);
        $this->RegisterVariableInteger('RingToOpenTimeout', $this->Translate('Ring to Open timeout'), $profile, 220);
        $this->EnableAction('RingToOpenTimeout');

        //One time access
        $id = @$this->GetIDForIdent('OneTimeAccess');
        $this->RegisterVariableBoolean('OneTimeAccess', $this->Translate('One time access'), '~Switch', 230);
        $this->EnableAction('OneTimeAccess');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('OneTimeAccess'), 'Door');
        }

        //Continous mode
        $id = @$this->GetIDForIdent('ContinuousMode');
        $this->RegisterVariableBoolean('ContinuousMode', $this->Translate('Continuous mode'), '~Switch', 240);
        $this->EnableAction('ContinuousMode');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('ContinuousMode'), 'Door');
        }

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

        //Label LED
        $this->RegisterVariableString('LabelOpenerLED', $this->Translate('LED'), '', 500);

        //Opener LED
        $id = @$this->GetIDForIdent('OpenerLED');
        $this->RegisterVariableBoolean('OpenerLED', $this->Translate('LED signal on the Opener'), '~Switch', 510);
        $this->EnableAction('OpenerLED');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('OpenerLED'), 'Bulb');
        }

        ##### Timer

        $this->RegisterTimer('Update', 0, self::MODULE_PREFIX . '_GetOpenerData(' . $this->InstanceID . ', true);');

        ##### Splitter

        //Connect to parent (Nuki Web Splitter)
        $this->ConnectParent('{DA16C1AA-0AFE-65B6-1A0C-5761A08A0FF8}');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Door', 'DeviceState', 'BatteryState', 'SoundDoorbellRings', 'SoundOpenViaApp', 'SoundRingToOpen', 'SoundContinuousMode', 'Volume'];
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

        $this->GetOpenerData(true);
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
        //Version info
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

    #################### Public methods

    public function GetOpenerData(bool $Update): string
    {
        $openerData = '';
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return $openerData;
        }
        if (!$this->HasActiveParent()) {
            return $openerData;
        }
        $this->SetTimerInterval('Update', 0);
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'GetSmartLocks';
        $buffer['Params'] = '';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 200) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
            return $openerData;
        }
        $this->SendDebug(__FUNCTION__, 'Body: ' . $result['body'], 0);
        foreach (json_decode($result['body'], true) as $device) {
            if (array_key_exists('smartlockId', $device)) {
                if ($device['smartlockId'] == $smartLockID) {
                    $openerData = $device;
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($openerData), 0);
        if ($Update) {
            if (!empty($openerData)) {
                //State
                $ringToOpenState = false;
                $continousModeState = false;
                $deviceState = 0;
                $batteryState = 2;
                if (array_key_exists('state', $openerData)) {
                    if (array_key_exists('state', $openerData['state'])) {
                        $deviceState = $openerData['state']['state'];
                        if ($deviceState == 3) {
                            $ringToOpenState = true;
                        }
                    }
                    if (array_key_exists('mode', $openerData['state'])) {
                        if ($openerData['state']['mode'] == 3) {
                            $continousModeState = true;
                            $deviceState = 3;
                        }
                    }
                    if (array_key_exists('batteryCritical', $openerData['state'])) {
                        $batteryState = $openerData['state']['batteryCritical'];
                    }
                    if (array_key_exists('batteryCritical', $openerData['state'])) {
                        $batteryState = $openerData['state']['batteryCritical'];
                    }
                }
                $this->SetValue('DeviceState', $deviceState);
                $this->SetValue('BatteryState', $batteryState);
                $this->SetValue('RingToOpen', $ringToOpenState);
                $this->SetValue('ContinuousMode', $continousModeState);
                // Advanced config
                if (array_key_exists('openerAdvancedConfig', $openerData)) {
                    if (is_array($openerData['openerAdvancedConfig'])) {
                        //Ring to open
                        $oneTimeAccessState = false;
                        if ($openerData['openerAdvancedConfig']['disableRtoAfterRing']) {
                            $oneTimeAccessState = true;
                        }
                        $this->SetValue('OneTimeAccess', $oneTimeAccessState);
                        $this->SetValue('RingToOpenTimeout', $openerData['openerAdvancedConfig']['rtoTimeout']);
                        //Doorbell suppression
                        $doorbellSuppression = $openerData['openerAdvancedConfig']['doorbellSuppression'];
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
                        //Sounds & volume
                        $this->SetValue('SoundDoorbellRings', $openerData['openerAdvancedConfig']['soundRing']);
                        $this->SetValue('SoundOpenViaApp', $openerData['openerAdvancedConfig']['soundOpen']);
                        $this->SetValue('SoundRingToOpen', $openerData['openerAdvancedConfig']['soundRto']);
                        $this->SetValue('SoundContinuousMode', $openerData['openerAdvancedConfig']['soundCm']);
                        $this->SetValue('Volume', $openerData['openerAdvancedConfig']['soundLevel']);
                    }
                }
                //Config
                if (array_key_exists('config', $openerData)) {
                    //Opener LED
                    if (array_key_exists('ledEnabled', $openerData['config'])) {
                        $this->SetValue('OpenerLED', (bool) $openerData['config']['ledEnabled']);
                    }
                }
            }
        }
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
        return json_encode($openerData);
    }

    public function OpenDoor(): void
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return;
        }
        if (!$this->HasActiveParent()) {
            return;
        }
        $this->SetTimerInterval('Update', 0);
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'SetSmartLockAction';
        // action: 3 open (electric strike actuation)
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'action' => 3, 'option' => 0];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 204) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
        }
        $this->SetTimerInterval('Update', 5000);
    }

    public function ToggleRingToOpen(bool $State): void
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return;
        }
        if (!$this->HasActiveParent()) {
            return;
        }
        $this->SetTimerInterval('Update', 0);
        $this->SetValue('RingToOpen', $State);
        $deviceState = $this->GetValue('DeviceState');
        if ($State) {
            $this->SetValue('DeviceState', 3);
        }
        // action: 1 activate ring to open, 2 deactivate ring to open
        $action = 1;
        if (!$State) {
            $action = 2;
            if (!$this->GetValue('ContinuousMode')) {
                $this->SetValue('DeviceState', 1);
            }
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'SetSmartLockAction';
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'action' => $action, 'option' => 0];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 204) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
            //Revert
            $this->SetValue('RingToOpen', !$State);
            $this->SetValue('DeviceState', $deviceState);
        }
        $this->SetTimerInterval('Update', 5000);
    }

    public function ToggleContinuousMode(bool $State): void
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return;
        }
        if (!$this->HasActiveParent()) {
            return;
        }
        $this->SetTimerInterval('Update', 0);
        $this->SetValue('ContinuousMode', $State);
        $deviceState = $this->GetValue('DeviceState');
        if ($State) {
            $this->SetValue('DeviceState', 3);
        }
        // action: 6 activate continuous mode, 7 deactivate continuous mode
        $action = 6;
        if (!$State) {
            $action = 7;
            if (!$this->GetValue('RingToOpen')) {
                $this->SetValue('DeviceState', 1);
            }
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'SetSmartLockAction';
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'action' => $action, 'option' => 0];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 204) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
            //Revert
            $this->SetValue('ContinuousMode', !$State);
            $this->SetValue('DeviceState', $deviceState);
        }
        $this->SetTimerInterval('Update', 5000);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'Door':
                $this->OpenDoor();
                break;

            case 'RingToOpen':
                $this->ToggleRingToOpen($Value);
                break;

            case 'ContinuousMode':
                $this->ToggleContinuousMode($Value);
                break;

            case 'RingToOpenTimeout':
            case 'OneTimeAccess':
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

            case 'OpenerLED':
                $this->SetValue($Ident, $Value);
                $this->UpdateConfig();
                break;

        }
    }

    #################### Private methods

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function UpdateConfig(): void
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return;
        }
        if (!$this->HasActiveParent()) {
            return;
        }
        $this->SetTimerInterval('Update', 0);

        //Get configuration
        $config = json_decode($this->GetOpenerData(false), true);
        if (empty($config)) {
            return;
        }

        // Prepare data
        $openerConfig = [];
        if (array_key_exists('config', $config)) {
            if (is_array($config['config'])) {
                $config['config']['ledEnabled'] = $this->GetValue('OpenerLED');
            }
            $openerConfig = $config['config'];
        }
        $this->SendDebug(__FUNCTION__, 'New config: ' . json_encode($openerConfig), 0);

        //Update data
        if (empty($openerConfig)) {
            return;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'UpdateSmartLockConfig';
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'config' => json_encode($openerConfig)];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 204) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
        }
        $this->SetTimerInterval('Update', 5000);
    }

    private function UpdateAdvanceConfig(): void
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return;
        }
        if (!$this->HasActiveParent()) {
            return;
        }

        $this->SetTimerInterval('Update', 0);

        //Get configuration
        $config = json_decode($this->GetOpenerData(false), true);
        if (empty($config)) {
            return;
        }

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
        if (array_key_exists('openerAdvancedConfig', $config)) {
            if (is_array($config['openerAdvancedConfig'])) {
                $config['openerAdvancedConfig']['disableRtoAfterRing'] = $this->GetValue('OneTimeAccess');
                $config['openerAdvancedConfig']['rtoTimeout'] = $this->GetValue('RingToOpenTimeout');
                $config['openerAdvancedConfig']['doorbellSuppression'] = $doorbellSuppression;
                $config['openerAdvancedConfig']['soundRing'] = $this->GetValue('SoundDoorbellRings');
                $config['openerAdvancedConfig']['soundOpen'] = $this->GetValue('SoundOpenViaApp');
                $config['openerAdvancedConfig']['soundRto'] = $this->GetValue('SoundRingToOpen');
                $config['openerAdvancedConfig']['soundCm'] = $this->GetValue('SoundContinuousMode');
                $config['openerAdvancedConfig']['soundLevel'] = $this->GetValue('Volume');
            }
            $openerAdvancedConfig = $config['openerAdvancedConfig'];
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
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 204) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
        }
        $this->SetTimerInterval('Update', 5000);
    }
}