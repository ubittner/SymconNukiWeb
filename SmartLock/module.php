<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

class NukiSmartLockWebAPI extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{8CDE2F20-ECBF-F12E-45AC-B8A7F36CBBFC}';
    private const MODULE_PREFIX = 'NUKISLW';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ##### Properties

        $this->RegisterPropertyString('SmartLockID', '');
        $this->RegisterPropertyString('AccountID', '');
        $this->RegisterPropertyString('AuthID', '');
        $this->RegisterPropertyString('Name', '');
        $this->RegisterPropertyInteger('UpdateInterval', 300);
        $this->RegisterPropertyBoolean('UseDoorSensor', false);
        $this->RegisterPropertyBoolean('UseKeypad', false);
        $this->RegisterPropertyBoolean('UseActivityLog', true);
        $this->RegisterPropertyInteger('ActivityLogPeriodLastDays', 7);
        $this->RegisterPropertyInteger('ActivityLogMaximumEntries', 50);

        ##### Variables

        //Smart Lock
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SmartLock';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Lock'), 'LockClosed', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Unlock'), 'LockOpen', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Unlatch'), 'Door', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, $this->Translate("Lock 'n' Go"), 'Lock', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 4, $this->Translate("Lock 'n' Go with unlatch"), 'Door', 0x00FF00);
        $this->RegisterVariableInteger('SmartLock', 'Smart Lock', $profile, 10);
        $this->EnableAction('SmartLock');

        //Device state
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DeviceState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Uncalibrated'), 'Warning', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Locked'), 'LockClosed', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Unlocking'), 'LockOpen', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 3, $this->Translate('Unlocked'), 'LockOpen', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 4, $this->Translate('Locking'), 'LockClosed', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 5, $this->Translate('Unlatched'), 'Door', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 6, $this->Translate("Unlocked (Lock 'n' Go)"), 'LockOpen', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 7, $this->Translate('Unlatching'), 'Door', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 254, $this->Translate('Motor blocked'), 'Warning', 0xFF0000);
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

        //Battery charge
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryCharge';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 100, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Battery');
        $this->RegisterVariableInteger('BatteryCharge', $this->Translate('Battery charge'), $profile, 120);

        //Battery charging
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryCharging';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, 'Battery');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Inactive'), '', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Active'), '', 0x00FF00);
        $this->RegisterVariableBoolean('BatteryCharging', $this->Translate('Battery charging'), $profile, 130);

        //Smart lock LED
        $id = @$this->GetIDForIdent('SmartLockLED');
        $this->RegisterVariableBoolean('SmartLockLED', $this->Translate('LED signal on the Smart Lock'), '~Switch', 200);
        $this->EnableAction('SmartLockLED');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('SmartLockLED'), 'Bulb');
        }

        //LED brightness
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Brightness';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 5, 1);
        IPS_SetVariableProfileText($profile, '', '');
        IPS_SetVariableProfileIcon($profile, 'Sun');
        $this->RegisterVariableInteger('Brightness', $this->Translate('LED Brightness'), $profile, 210);
        $this->EnableAction('Brightness');

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
        $profiles = ['SmartLock', 'DeviceState', 'BatteryState', 'BatteryCharge', 'BatteryCharging', 'Brightness', 'DoorState', 'DoorSensorBatteryState', 'KeypadBatteryState'];
        foreach ($profiles as $profile) {
            $this->DeleteProfile($profile);
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

        //Door sensor

        if ($this->ReadPropertyBoolean('UseDoorSensor')) {
            //Door state
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DoorState';
            if (!IPS_VariableProfileExists($profile)) {
                IPS_CreateVariableProfile($profile, 1);
            }
            IPS_SetVariableProfileIcon($profile, '');
            IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Unavailable'), 'Warning', -1);
            IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Deactivated'), 'Warning', 0x0000FF);
            IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Door closed'), 'Door', 0xFF0000);
            IPS_SetVariableProfileAssociation($profile, 3, $this->Translate('Door opened'), 'Door', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 4, $this->Translate('Door state unknown'), 'Warning', -1);
            IPS_SetVariableProfileAssociation($profile, 5, $this->Translate('Calibrating'), 'Gear', 0xFFFF00);
            $id = @$this->GetIDForIdent('DoorState');
            $this->MaintainVariable('DoorState', $this->Translate('Door state'), 1, $profile, 300, true);
            if ($id == false) {
                $this->SetValue('DoorState', 4);
            }

            //Door sensor battery state
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DoorSensorBatteryState';
            if (!IPS_VariableProfileExists($profile)) {
                IPS_CreateVariableProfile($profile, 0);
            }
            IPS_SetVariableProfileIcon($profile, 'Battery');
            IPS_SetVariableProfileAssociation($profile, false, 'OK', '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, true, $this->Translate('Low battery'), '', 0xFF0000);
            $this->MaintainVariable('DoorSensorBatteryState', $this->Translate('Door sensor battery state'), 0, $profile, 310, true);
        } else {
            $this->MaintainVariable('DoorState', $this->Translate('Door state'), 1, '', 0, false);
            $this->DeleteProfile('DoorState');
            $this->MaintainVariable('DoorSensorBatteryState', $this->Translate('Door sensor battery state'), 0, '', 0, false);
            $this->DeleteProfile('DoorSensorBatteryState');
        }

        //Keypad
        if ($this->ReadPropertyBoolean('UseKeypad')) {
            //Keypad battery state
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.KeypadBatteryState';
            if (!IPS_VariableProfileExists($profile)) {
                IPS_CreateVariableProfile($profile, 0);
            }
            IPS_SetVariableProfileIcon($profile, 'Battery');
            IPS_SetVariableProfileAssociation($profile, false, 'OK', '', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, true, $this->Translate('Low battery'), '', 0xFF0000);
            $this->MaintainVariable('KeypadBatteryState', $this->Translate('Keypad battery state'), 0, $profile, 400, true);
        } else {
            $this->MaintainVariable('KeypadBatteryState', $this->Translate('Keypad battery state'), 0, '', 0, false);
            $this->DeleteProfile('KeypadBatteryState');
        }

        //Activity log

        if ($this->ReadPropertyBoolean('UseActivityLog')) {
            $id = @$this->GetIDForIdent('ActivityLog');
            $this->MaintainVariable('ActivityLog', $this->Translate('Activity log'), 3, 'HTMLBox', 500, true);
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
        //Received data from splitter, not used at the moment
        $data = json_decode($JSONString);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
    }

    #################### Public methods

    public function GetDeviceType(): int
    {
        return $this->ReadAttributeInteger('Type');
    }

    public function UpdateData(): void
    {
        $this->SetTimerInterval('Update', 0);
        $this->GetSmartLockData(true);
        $this->GetActivityLog(true);
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
    }

    public function GetSmartLockData(bool $Update): string
    {
        $smartLockData = '';
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return $smartLockData;
        }
        if (!$this->HasActiveParent()) {
            return $smartLockData;
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
            return $smartLockData;
        }
        $this->SendDebug(__FUNCTION__, 'Body: ' . $result['body'], 0);
        foreach (json_decode($result['body'], true) as $device) {
            if (array_key_exists('smartlockId', $device)) {
                if ($device['smartlockId'] == $smartLockID) {
                    $smartLockData = $device;
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Actual data: ' . json_encode($smartLockData), 0);
        if ($Update) {
            if (!empty($smartLockData)) {
                //Type
                if (array_key_exists('type', $smartLockData)) {
                    if ($this->ReadAttributeInteger('Type') == -1) {
                        $this->WriteAttributeInteger('Type', $smartLockData['type']);
                    }
                }
                //State
                $deviceState = 256;
                $batteryState = false;
                $batteryCharge = 0;
                $batteryCharging = 0;
                if (array_key_exists('state', $smartLockData)) {
                    if (array_key_exists('state', $smartLockData['state'])) {
                        $deviceState = $smartLockData['state']['state'];
                        $this->SetValue('DeviceState', $deviceState);
                    }
                    if (array_key_exists('batteryCritical', $smartLockData['state'])) {
                        $batteryState = (bool) $smartLockData['state']['batteryCritical'];
                    }
                    if (array_key_exists('batteryCharge', $smartLockData['state'])) {
                        $batteryCharge = $smartLockData['state']['batteryCharge'];
                    }
                    if (array_key_exists('batteryCharging', $smartLockData['state'])) {
                        $batteryCharging = $smartLockData['state']['batteryCharging'];
                    }
                }
                $this->SetValue('DeviceState', $deviceState);
                $this->SetValue('BatteryState', $batteryState);
                $this->SetValue('BatteryCharge', $batteryCharge);
                $this->SetValue('BatteryCharging', $batteryCharging);
                //Door sensor
                if ($this->ReadPropertyBoolean('UseDoorSensor')) {
                    if (array_key_exists('state', $smartLockData)) {
                        if (array_key_exists('doorState', $smartLockData['state'])) {
                            $doorState = $smartLockData['state']['doorState'];
                            $this->SetValue('DoorState', $doorState);
                        }
                        $doorSensorBatteryState = false;
                        if (array_key_exists('doorsensorBatteryCritical', $smartLockData['state'])) {
                            $doorSensorBatteryState = (bool) $smartLockData['state']['doorsensorBatteryCritical'];
                        }
                        $this->SetValue('DoorSensorBatteryState', $doorSensorBatteryState);
                    }
                }
                //Keypad
                if ($this->ReadPropertyBoolean('UseKeypad')) {
                    if (array_key_exists('state', $smartLockData)) {
                        $keypadBatteryState = false;
                        if (array_key_exists('keypadBatteryCritical', $smartLockData['state'])) {
                            $keypadBatteryState = (bool) $smartLockData['state']['keypadBatteryCritical'];
                        }
                        $this->SetValue('KeypadBatteryState', $keypadBatteryState);
                    }
                }
                //Config
                if (array_key_exists('config', $smartLockData)) {
                    //Smart lock LED
                    if (array_key_exists('ledEnabled', $smartLockData['config'])) {
                        $this->SetValue('SmartLockLED', (bool) $smartLockData['config']['ledEnabled']);
                    }
                    //LED brighntess
                    if (array_key_exists('ledBrightness', $smartLockData['config'])) {
                        $this->SetValue('Brightness', (int) $smartLockData['config']['ledBrightness']);
                    }
                }
            }
        }
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
        return json_encode($smartLockData);
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
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 200) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
            return '';
        }
        //Header
        $string = "<table style='width: 100%; border-collapse: collapse;'>";
        $string .= '<tr> <td><b> ' . $this->Translate('Date') . '</b></td> <td><b>' . $this->Translate('Action') . '</b></td> <td><b>Name</b></td> <td><b> ' . $this->Translate('Trigger') . '</b></td> </tr>';
        //Log entries
        $log = [];
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
        return json_encode($log);
    }

    public function SetSmartLockAction(int $Action): void
    {
        $smartLockID = $this->ReadPropertyString('SmartLockID');
        if (empty($smartLockID)) {
            return;
        }
        if (!$this->HasActiveParent()) {
            return;
        }
        $this->SetTimerInterval('Update', 0);
        $this->SetValue('SmartLock', $Action);
        /*
         * API action:
         * 1    unlock
         * 2    lock
         * 3    unlatch
         * 4    lock 'n' go
         * 5    lock 'n' go with unlatch
         */
        switch ($Action) {
            case 0: # Lock
                $smartLockAction = 2;
                break;

            case 1: # Unlock
                $smartLockAction = 1;
                break;

            case 2: # Unlatch
                $smartLockAction = 3;
                break;

            case 3: # Lock 'n' Go
                $smartLockAction = 4;
                break;

            case 4: # Lock 'n' Go with unlatch
                $smartLockAction = 5;
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Unknown action: ', 0);
                return;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'SetSmartLockAction';
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'action' => $smartLockAction, 'option' => 0];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 204) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
        }
        $this->SetTimerInterval('Update', 10000);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'SmartLock':
                $this->SetSmartLockAction($Value);
                break;

            case 'SmartLockLED':
            case 'Brightness':
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

    private function DeleteProfile(string $ProfileName): void
    {
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $ProfileName;
        if (@IPS_VariableProfileExists($profile)) {
            IPS_DeleteVariableProfile($profile);
        }
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
        $config = json_decode($this->GetSmartLockData(false), true);
        if (empty($config)) {
            return;
        }

        //Prepare data
        $smartLockConfig = [];
        if (array_key_exists('config', $config)) {
            if (is_array($config['config'])) {
                $config['config']['ledEnabled'] = $this->GetValue('SmartLockLED');
                $config['config']['ledBrightness'] = $this->GetValue('Brightness');
            }
            $smartLockConfig = $config['config'];
        }
        $this->SendDebug(__FUNCTION__, 'New config: ' . json_encode($smartLockConfig), 0);

        //Update data
        if (empty($smartLockConfig)) {
            return;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
        $buffer['Command'] = 'UpdateSmartLockConfig';
        $buffer['Params'] = ['smartlockId' => $smartLockID, 'config' => json_encode($smartLockConfig)];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        $httpCode = $result['httpCode'];
        $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
        if ($httpCode != 204) {
            $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 204!', 0);
        }
        $this->SetTimerInterval('Update', 10000);
    }
}