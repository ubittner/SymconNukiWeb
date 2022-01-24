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
        $this->RegisterPropertyString('Name', '');
        $this->RegisterPropertyBoolean('UseAutomaticUpdate', true);
        $this->RegisterPropertyInteger('UpdateInterval', 0);
        $this->RegisterPropertyBoolean('UseActivityLog', true);
        $this->RegisterPropertyInteger('ActivityLogPeriodLastDays', 7);
        $this->RegisterPropertyInteger('ActivityLogMaximumEntries', 50);

        ########## Variables

        //Door
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Door';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Door');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Open'), '', 0x00FF00);
        $this->RegisterVariableInteger('Door', $this->Translate('Door'), $profile, 10);
        $this->EnableAction('Door');

        ##### State

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
        $this->RegisterVariableInteger('DeviceState', $this->Translate('Device state'), $profile, 100);
        if ($id == false) {
            $this->SetValue('DeviceState', 256);
        }

        //Battery state
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, 'Battery');
        IPS_SetVariableProfileAssociation($profile, false, 'OK', '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, true, $this->Translate('Low battery'), '', 0xFF0000);
        $this->RegisterVariableBoolean('BatteryState', $this->Translate('Battery state'), $profile, 110);

        ##### Ring to Open

        //Ring to open
        $id = @$this->GetIDForIdent('RingToOpen');
        $this->RegisterVariableBoolean('RingToOpen', 'Ring to Open', '~Switch', 200);
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
        $this->RegisterVariableInteger('RingToOpenTimeout', $this->Translate('Ring to Open timeout'), $profile, 210);
        $this->EnableAction('RingToOpenTimeout');

        //One time access
        $id = @$this->GetIDForIdent('OneTimeAccess');
        $this->RegisterVariableBoolean('OneTimeAccess', $this->Translate('One time access'), '~Switch', 220);
        $this->EnableAction('OneTimeAccess');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('OneTimeAccess'), 'Door');
        }

        //Continous mode
        $id = @$this->GetIDForIdent('ContinuousMode');
        $this->RegisterVariableBoolean('ContinuousMode', $this->Translate('Continuous mode'), '~Switch', 230);
        $this->EnableAction('ContinuousMode');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('ContinuousMode'), 'Door');
        }

        ##### Ring suppression

        //Ring suppression ring
        $id = @$this->GetIDForIdent('RingSuppressionRing');
        $this->RegisterVariableBoolean('RingSuppressionRing', $this->Translate('Ring suppression Ring'), '~Switch', 300);
        $this->EnableAction('RingSuppressionRing');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingSuppressionRing'), 'Alert');
        }

        //Ring suppression ring to open
        $id = @$this->GetIDForIdent('RingSuppressionRingToOpen');
        $this->RegisterVariableBoolean('RingSuppressionRingToOpen', $this->Translate('Ring suppression Ring to Open'), '~Switch', 310);
        $this->EnableAction('RingSuppressionRingToOpen');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingSuppressionRingToOpen'), 'Alert');
        }

        //Ring suppression continous mode
        $id = @$this->GetIDForIdent('RingSuppressionContinuousMode');
        $this->RegisterVariableBoolean('RingSuppressionContinuousMode', $this->Translate('Ring suppression continuous mode'), '~Switch', 320);
        $this->EnableAction('RingSuppressionContinuousMode');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('RingSuppressionContinuousMode'), 'Alert');
        }

        ##### Sounds

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
        $this->RegisterVariableInteger('SoundDoorbellRings', $this->Translate('Sound doorbell rings'), $profile, 400);
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
        $this->RegisterVariableInteger('SoundOpenViaApp', $this->Translate('Sound open via app'), $profile, 410);
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
        $this->RegisterVariableInteger('SoundRingToOpen', $this->Translate('Sound Ring to Open'), $profile, 420);
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
        $this->RegisterVariableInteger('SoundContinuousMode', $this->Translate('Sound continuous mode'), $profile, 430);
        $this->EnableAction('SoundContinuousMode');

        //Volume slider
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Volume';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 255, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Speaker');
        $this->RegisterVariableInteger('Volume', $this->Translate('Volume'), $profile, 440);
        $this->EnableAction('Volume');

        ##### LED

        //Opener LED
        $id = @$this->GetIDForIdent('OpenerLED');
        $this->RegisterVariableBoolean('OpenerLED', $this->Translate('LED signal on the Opener'), '~Switch', 500);
        $this->EnableAction('OpenerLED');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('OpenerLED'), 'Bulb');
        }

        ##### Attributes

        $this->RegisterAttributeInteger('Type', -1);

        ##### Timer

        $this->RegisterTimer('Update', 0, self::MODULE_PREFIX . '_UpdateData(' . $this->InstanceID . ');');

        ##### Splitter

        //Connect to parent (Nuki Web Splitter)
        $this->ConnectParent('{DA16C1AA-0AFE-65B6-1A0C-5761A08A0FF8}');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Door', 'DeviceState', 'BatteryState', 'RingToOpenTimeout', 'SoundDoorbellRings', 'SoundOpenViaApp', 'SoundRingToOpen', 'SoundContinuousMode', 'Volume'];
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

        ########## Maintain variable

        //Activity log
        if ($this->ReadPropertyBoolean('UseActivityLog')) {
            $id = @$this->GetIDForIdent('ActivityLog');
            $this->MaintainVariable('ActivityLog', $this->Translate('Activity log'), 3, 'HTMLBox', 600, true);
            if ($id == false) {
                IPS_SetIcon($this->GetIDForIdent('ActivityLog'), 'Database');
            }
        } else {
            $this->MaintainVariable('ActivityLog', $this->Translate('Activity log'), 3, '', 0, false);
        }

        $this->UpdateData();
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
        //Received data from splitter
        $this->SendDebug(__FUNCTION__, 'Incoming data: ' . $JSONString, 0);
        if (!$this->ReadPropertyBoolean('UseAutomaticUpdate')) {
            $this->SendDebug(__FUNCTION__, 'Abort, automatic update is disabled!', 0);
            return;
        }
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, 'Buffer data:  ' . json_encode($data->Buffer), 0);
        $buffer = json_decode(json_encode($data->Buffer), true);
        // Check feature first
        if (array_key_exists('feature', $buffer)) {
            switch ($buffer['feature']) {
                case 'DEVICE_STATUS':
                case 'DEVICE_CONFIG':
                    if (array_key_exists('smartlockId', $buffer)) {
                        $smartlockID = $buffer['smartlockId'];
                        if ($smartlockID == $this->ReadPropertyString('SmartLockID')) {
                            $this->UpdateData();
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Abort, data is not for this device!', 0);
                        }
                    }
                    break;

                case 'DEVICE_LOGS':
                    if (array_key_exists('smartlockLog', $buffer)) {
                        $log = $buffer['smartlockLog'];
                        if (array_key_exists('smartlockId', $log)) {
                            if ($log['smartlockId'] == $this->ReadPropertyString('SmartLockID')) {
                                $this->UpdateData();
                            } else {
                                $this->SendDebug(__FUNCTION__, 'Abort, data is not for this device!', 0);
                            }
                        }
                    }
                    break;

                default:
                    $this->SendDebug(__FUNCTION__, 'Abort, unknown Parameter!', 0);
            }
        }
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

    #################### Public methods

    public function GetDeviceType(): int
    {
        return $this->ReadAttributeInteger('Type');
    }

    public function UpdateData(): void
    {
        $this->SetTimerInterval('Update', 0);
        $this->GetOpenerData(true);
        $this->GetActivityLog(true);
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
    }

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
        $buffer['Command'] = 'GetSmartLock';
        $buffer['Params'] = ['smartlockId' => $smartLockID];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 200) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                return $openerData;
            }
        }
        $this->SendDebug(__FUNCTION__, 'Actual data: ' . $result['body'], 0);
        if (array_key_exists('body', $result)) {
            $openerData = json_decode($result['body'], true);
            if ($Update) {
                if (!empty($openerData)) {
                    //Type
                    if (array_key_exists('type', $openerData)) {
                        if ($this->ReadAttributeInteger('Type') == -1) {
                            $this->WriteAttributeInteger('Type', $openerData['type']);
                        }
                    }
                    //State
                    $ringToOpenState = false;
                    $continousModeState = false;
                    $deviceState = 0;
                    $batteryState = false;
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
                            $batteryState = (bool) $openerData['state']['batteryCritical'];
                        }
                    }
                    $this->SetValue('DeviceState', $deviceState);
                    $this->SetValue('BatteryState', $batteryState);
                    $this->SetValue('RingToOpen', $ringToOpenState);
                    $this->SetValue('ContinuousMode', $continousModeState);
                    //Advanced config
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
        }
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
        return json_encode($openerData);
    }

    public function GetActivityLog(bool $Update): string
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return '';
        }
        if (!$this->HasActiveParent()) {
            return '';
        }
        if (!$this->ReadPropertyBoolean('UseActivityLog')) {
            return '';
        }
        $periodLastDays = $this->ReadPropertyInteger('ActivityLogPeriodLastDays');
        $limit = $this->ReadPropertyInteger('ActivityLogMaximumEntries');
        if ($periodLastDays == 0) {
            $parameter = 'limit=' . $limit;
        } else {
            $datetime = urlencode(date('c', strtotime('-' . $this->ReadPropertyInteger('ActivityLogPeriodLastDays') . ' day')));
            $parameter = 'fromDate=' . $datetime . '&limit=' . $limit;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'GetSmartLockLog';
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'parameter' => $parameter];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 200) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                return '';
            }
        }
        $log = [];
        if (array_key_exists('body', $result)) {
            $this->SendDebug(__FUNCTION__, 'Body: ' . $result['body'], 0);
            //Header
            $string = "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr> <td><b> ' . $this->Translate('Date') . '</b></td> <td><b>' . $this->Translate('Action') . '</b></td> <td><b>Name</b></td> <td><b> ' . $this->Translate('Trigger') . '</b></td> </tr>';
            //Log entries
            $logEntries = json_decode($result['body'], true);
            foreach ($logEntries as $logEntry) {
                if (array_key_exists('smartlockId', $logEntry)) {
                    if ($logEntry['smartlockId'] != $smartLockID) {
                        continue;
                    } else {
                        $log[] = $logEntry;
                    }
                }
                //Date
                if (array_key_exists('date', $logEntry)) {
                    $date = $logEntry['date'];
                    $date = new DateTime($date);
                    $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                    $date = $date->format('d.m.Y H:i:s');
                }
                //Action
                if (array_key_exists('action', $logEntry)) {
                    $action = $logEntry['action'];
                    /*
                     * API action:
                     * 1    unlock
                     * 2    lock
                     * 3    unlatch
                     * 4    lock'n'go
                     * 5    lock'n'go with unlatch
                     * 208  door warning ajar
                     * 209  door warning status mismatch
                     * 224  doorbell recognition (only Opener)
                     * 240  door opened
                     * 241  door closed
                     * 242  door sensor jammed
                     * 243  firmware update
                     * 250  door log enabled
                     * 251  door log disabled
                     * 252  initialization
                     * 253  calibration
                     * 254  log enabled
                     * 255  log disabled
                     */
                    switch ($action) {
                        case 1:
                            $action = $this->Translate('unlock');
                            break;

                        case 2:
                            $action = $this->Translate('lock');
                            break;

                        case 3:
                            $action = $this->Translate('unlatch');
                            break;

                        case 4:
                            $action = $this->Translate("lock'n'go");
                            break;

                        case 5:
                            $action = $this->Translate("lock'n'go with unlatch");
                            break;

                        case 208:
                            $action = $this->Translate('door warning ajar');
                            break;

                        case 209:
                            $action = $this->Translate('door warning status mismatch');
                            break;

                        case 224:
                            $action = $this->Translate('doorbell recognition');
                            break;

                        case 240:
                            $action = $this->Translate('door opened');
                            break;

                        case 241:
                            $action = $this->Translate('door closed');
                            break;

                        case 242:
                            $action = $this->Translate('door sensor jammed');
                            break;

                        case 243:
                            $action = $this->Translate('firmware update');
                            break;

                        case 250:
                            $action = $this->Translate('door log enabled');
                            break;

                        case 251:
                            $action = $this->Translate('door log disabled');
                            break;

                        case 252:
                            $action = $this->Translate('initialization');
                            break;

                        case 253:
                            $action = $this->Translate('calibration');
                            break;

                        case 254:
                            $action = $this->Translate('log enabled');
                            break;

                        case 255:
                            $action = $this->Translate('log disabled');
                            break;

                        default:
                            $action = $action . ' ' . $this->Translate('Unknown');
                    }
                }
                //Name
                if (array_key_exists('name', $logEntry)) {
                    $name = $logEntry['name'];
                    if (empty($name)) {
                        $name = $this->Translate('Unknown');
                    }
                }
                //Trigger
                if (array_key_exists('trigger', $logEntry)) {
                    $trigger = $logEntry['trigger'];
                    /*
                     * API trigger:
                     * 0    system
                     * 1    manual
                     * 2    button
                     * 3    automatic
                     * 4    web
                     * 5    app
                     * 6    auto lock
                     * 7    accessory
                     * 255  keypad
                     */
                    switch ($trigger) {
                        case 0:
                            $trigger = $this->Translate('system');
                            break;

                        case 1:
                            $trigger = $this->Translate('manual');
                            break;

                        case 2:
                            $trigger = $this->Translate('button');
                            break;

                        case 3:
                            $trigger = $this->Translate('automatic');
                            break;

                        case 4:
                            $trigger = $this->Translate('web');
                            break;

                        case 5:
                            $trigger = $this->Translate('app');
                            break;

                        case 6:
                            $trigger = $this->Translate('auto lock');
                            break;

                        case 7:
                            $trigger = $this->Translate('accessory');
                            break;

                        case 255:
                            $trigger = $this->Translate('keypad');
                            break;

                        default:
                            $trigger = $this->Translate('Unknown');
                    }
                }
                if (isset($date) && isset($action) && isset($name) && isset($trigger)) {
                    $string .= '<tr><td>' . $date . '</td><td>' . $action . '</td><td>' . $name . '</td><td>' . $trigger . '</td></tr>';
                }
            }
            $string .= '</table>';
            if ($Update) {
                $this->SetValue('ActivityLog', $string);
            }
        }
        return json_encode($log);
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
        /*
         * API action:
         * 3    open (electric strike actuation)
         */
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'action' => 3, 'option' => 0];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $result = json_decode($this->SendDataToParent($data), true);
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 204) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
            }
        }
        if (!$this->ReadPropertyBoolean('UseAutomaticUpdate')) {
            $this->SetTimerInterval('Update', 10000);
        }
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
        /*
         * API action:
         * 1    activate ring to open
         * 2    deactivate ring to open
         */
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
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 204) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
                //Revert
                $this->SetValue('RingToOpen', !$State);
                $this->SetValue('DeviceState', $deviceState);
            }
        }
        if (!$this->ReadPropertyBoolean('UseAutomaticUpdate')) {
            $this->SetTimerInterval('Update', 10000);
        }
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
        /*
         * API action:
         * 6    activate continuous mode
         * 7    deactivate continuous mode
         */
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
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 204) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
                //Revert
                $this->SetValue('ContinuousMode', !$State);
                $this->SetValue('DeviceState', $deviceState);
            }
        }
        if (!$this->ReadPropertyBoolean('UseAutomaticUpdate')) {
            $this->SetTimerInterval('Update', 10000);
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

        //Prepare data
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
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 204) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
            }
        }
        if (!$this->ReadPropertyBoolean('UseAutomaticUpdate')) {
            $this->SetTimerInterval('Update', 10000);
        }
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
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 204) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
            }
        }
        if (!$this->ReadPropertyBoolean('UseAutomaticUpdate')) {
            $this->SetTimerInterval('Update', 10000);
        }
    }
}