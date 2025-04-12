<?php

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class NukiConfiguratorWebAPI extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{8CDE2F20-ECBF-F12E-45AC-B8A7F36CBBFC}';
    private const NUKI_WEB_SPLITTER_GUID = '{DA16C1AA-0AFE-65B6-1A0C-5761A08A0FF8}';
    private const NUKI_WEB_SPLITTER_DATA_GUID = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
    private const NUKI_WEB_SMARTLOCK_GUID = '{48C163A9-C871-88EB-2717-26A195E3E476}';
    private const NUKI_WEB_BOX_GUID = '{5C79FC64-46D3-1EF9-3C72-3137275CB34C}';
    private const NUKI_WEB_OPENER_GUID = '{41271F9F-1DB0-CB78-93BD-1361A6C5C058}';
    private const NUKI_WEB_DOOR_GUID = '{8A30A6FD-A027-95E0-2DB2-F4B4F50E4EEA}';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        //Connect to parent (Nuki Web Splitter)
        $this->ConnectParent(self::NUKI_WEB_SPLITTER_GUID);
    }

    public function ApplyChanges(): void
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    /**
     * @throws Exception
     */
    public function GetConfigurationForm(): string
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $formData['elements'][2]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date']);
        $values = $this->GetDevices();
        $formData['actions'][0]['values'] = $values;
        return json_encode($formData);
    }

    /**
     * Gets all connected instances.
     *
     * @return string
     */
    public function GetConnectedInstances(): string
    {
        $instanceTypes[] = ['type' => 'smartlock', 'guid' => self::NUKI_WEB_SMARTLOCK_GUID];
        $instanceTypes[] = ['type' => 'box', 'guid' => self::NUKI_WEB_BOX_GUID];
        $instanceTypes[] = ['type' => 'opener', 'guid' => self::NUKI_WEB_OPENER_GUID];
        $instanceTypes[] = ['type' => 'door', 'guid' => self::NUKI_WEB_DOOR_GUID];
        $connectedInstanceIDs = [];
        foreach ($instanceTypes as $instanceType) {
            foreach (IPS_GetInstanceListByModuleID($instanceType['guid']) as $instanceID) {
                if (IPS_GetInstance($instanceID)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                    $connectedInstanceIDs[$instanceType['type']][] = ['smartlockID' => IPS_GetProperty($instanceID, 'SmartLockID'), 'accountID' => IPS_GetProperty($instanceID, 'AccountID'), 'authID' => IPS_GetProperty($instanceID, 'AuthID'), 'objectID' => $instanceID];
                }
            }
        }
        return json_encode($connectedInstanceIDs);
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    /**
     * @throws Exception
     */
    private function GetDevices(): array
    {
        $values = [];
        if (!$this->HasActiveParent()) {
            return $values;
        }

        //Init found devices
        $foundDevices['smartlock'] = [];
        $foundDevices['box'] = [];
        $foundDevices['opener'] = [];
        $foundDevices['door'] = [];

        //Get connected instances
        $connectedInstanceIDs = json_decode($this->GetConnectedInstances(), true);

        $serverConnection = true;

        $data = [];
        $buffer = [];
        $data['DataID'] = self::NUKI_WEB_SPLITTER_DATA_GUID;
        $buffer['Command'] = 'GetSmartLocks';
        $buffer['Params'] = '';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $result = json_decode($this->SendDataToParent($data), true);
        if (array_key_exists('httpCode', $result)) {
            $httpCode = $result['httpCode'];
            $this->SendDebug(__FUNCTION__, 'Result http code: ' . $httpCode, 0);
            if ($httpCode != 200) {
                $this->SendDebug(__FUNCTION__, 'Abort, result http code: ' . $httpCode . ', must be 200!', 0);
                return $values;
            }
        }
        if (array_key_exists('body', $result)) {
            foreach (json_decode($result['body'], true) as $device) {
                if (array_key_exists('type', $device)) {
                    $smartLockID = $device['smartlockId'];
                    $accountID = $device['accountId'];
                    $authID = $device['authId'];
                    $deviceType = $device['type'];
                    $name = $device['name'];
                    switch ($deviceType) {
                        case 0: # Smart Lock 1,2 (1. and 2. Generation)
                        case 4: # Smart Lock 3/4 (3. und 4. Generation)
                        case 5: # Smart Lock 5   (5. Genration: Smart Lock Ultra)
                            $foundDevices['smartlock'][] = ['smartlockID' => $smartLockID];
                            $instanceID = $this->GetDeviceInstances($smartLockID, 0);
                            $value = [
                                'SmartLockID'        => $smartLockID,
                                'AccountID'          => $accountID,
                                'AuthID'             => $authID,
                                'DeviceType'         => $deviceType,
                                'ProductDesignation' => 'Smart Lock',
                                'name'               => $name,
                                'instanceID'         => $instanceID,
                                'create'             => [
                                    'moduleID'      => '{48C163A9-C871-88EB-2717-26A195E3E476}',
                                    'name'          => $name,
                                    'configuration' => [
                                        'SmartLockID' => (string) $smartLockID,
                                        'AccountID'   => (string) $accountID,
                                        'AuthID'      => (string) $authID,
                                        'Name'        => (string) $name
                                    ]
                                ]
                            ];
                            if (array_key_exists('smartlock', $connectedInstanceIDs)) {
                                $connectedSmartLocks = $connectedInstanceIDs['smartlock'];
                                foreach ($connectedSmartLocks as $connectedSmartLock) {
                                    if ($connectedSmartLock['smartlockID'] == $smartLockID) {
                                        $value['name'] = IPS_GetName($connectedSmartLock['objectID']);
                                    }
                                }
                            }
                            $values[] = $value;
                            break;

                        case 1: # Box, prepared, but not used at the moment
                            $foundDevices['box'][] = ['smartlockID' => $smartLockID];
                            $instanceID = $this->GetDeviceInstances($smartLockID, 1);
                            $value = [
                                'SmartLockID'        => $smartLockID,
                                'AccountID'          => $accountID,
                                'AuthID'             => $authID,
                                'DeviceType'         => $deviceType,
                                'ProductDesignation' => 'Box',
                                'name'               => $name,
                                'instanceID'         => $instanceID
                                /*
                                'create' => [
                                    'moduleID' => "{5C79FC64-46D3-1EF9-3C72-3137275CB34C}",
                                    'name' => $name,
                                    'configuration' => [
                                        'SmartLockID'  => (string) $smartLockID,
                                        'AccountID' => (string) $accountID,
                                        'AuthID' => (string) $authID,
                                        'Name' => (string) $name
                                    ]
                                ]
                                 */
                            ];
                            if (array_key_exists('box', $connectedInstanceIDs)) {
                                $connectedBoxes = $connectedInstanceIDs['box'];
                                foreach ($connectedBoxes as $connectedBox) {
                                    if ($connectedBox['smartlockID'] == $smartLockID) {
                                        $value['name'] = IPS_GetName($connectedBox['objectID']);
                                    }
                                }
                            }
                            $values[] = $value;
                            break;

                        case 2: # Opener
                            $foundDevices['opener'][] = ['smartlockID' => $smartLockID];
                            $instanceID = $this->GetDeviceInstances($smartLockID, 2);
                            $value = [
                                'SmartLockID'        => $smartLockID,
                                'AccountID'          => $accountID,
                                'AuthID'             => $authID,
                                'DeviceType'         => $deviceType,
                                'ProductDesignation' => 'Opener',
                                'name'               => $name,
                                'instanceID'         => $instanceID,
                                'create'             => [
                                    'moduleID'      => '{41271F9F-1DB0-CB78-93BD-1361A6C5C058}',
                                    'name'          => $name,
                                    'configuration' => [
                                        'SmartLockID' => (string) $smartLockID,
                                        'AccountID'   => (string) $accountID,
                                        'AuthID'      => (string) $authID,
                                        'Name'        => (string) $name
                                    ]
                                ]
                            ];
                            if (array_key_exists('opener', $connectedInstanceIDs)) {
                                $connectedOpeners = $connectedInstanceIDs['opener'];
                                foreach ($connectedOpeners as $connectedOpener) {
                                    if ($connectedOpener['smartlockID'] == $smartLockID) {
                                        $value['name'] = IPS_GetName($connectedOpener['objectID']);
                                    }
                                }
                            }
                            $values[] = $value;
                            break;

                        case 3: # Door, prepared, not used at the moment
                            $foundDevices['door'][] = ['smartlockID' => $smartLockID];
                            $instanceID = $this->GetDeviceInstances($smartLockID, 3);
                            $value = [
                                'SmartLockID'        => $smartLockID,
                                'AccountID'          => $accountID,
                                'AuthID'             => $authID,
                                'DeviceType'         => $deviceType,
                                'ProductDesignation' => 'Door',
                                'name'               => $name,
                                'instanceID'         => $instanceID
                                /*
                                'create' => [
                                    'moduleID' => "{8A30A6FD-A027-95E0-2DB2-F4B4F50E4EEA}",
                                    'name' => $name,
                                    'configuration' => [
                                        'SmartLockID'  => (string) $smartLockID,
                                        'AccountID' => (string) $accountID,
                                        'AuthID' => (string) $authID,
                                        'Name' => (string) $name
                                    ]
                                ]
                                 */
                            ];
                            if (array_key_exists('door', $connectedInstanceIDs)) {
                                $connectedDoors = $connectedInstanceIDs['door'];
                                foreach ($connectedDoors as $connectedDoor) {
                                    if ($connectedDoor['smartlockID'] == $smartLockID) {
                                        $value['name'] = IPS_GetName($connectedDoor['objectID']);
                                    }
                                }
                            }
                            $values[] = $value;
                            break;
                    }
                }
            }
        } else {
            $serverConnection = false;
        }

        //Check if connected "devices" still exist in the Nuki account of the user
        $deviceTypes[] = ['type' => 'smartlock', 'firstCondition' => 'smartlockID'];
        $deviceTypes[] = ['type' => 'box', 'firstCondition' => 'smartlockID'];
        $deviceTypes[] = ['type' => 'opener', 'firstCondition' => 'smartlockID'];
        $deviceTypes[] = ['type' => 'door', 'firstCondition' => 'smartlockID'];
        foreach ($deviceTypes as $device) {
            if (array_key_exists($device['type'], $connectedInstanceIDs)) {
                $connectedDevices = $connectedInstanceIDs[$device['type']];
                foreach ($connectedDevices as $connectedDevice) {
                    if (array_key_exists('objectID', $connectedDevice)) {
                        $objectID = $connectedDevice['objectID'];
                        if (array_key_exists($device['firstCondition'], $connectedDevice)) {
                            $connectedFirstCondition = $connectedDevice[$device['firstCondition']];
                        }
                        $match = false;
                        foreach ($foundDevices[$device['type']] as $foundDevice) {
                            $foundFirstCondition = $foundDevice[$device['firstCondition']];
                            if (isset($connectedFirstCondition) && ($connectedFirstCondition == $foundFirstCondition)) {
                                $match = true;
                            }
                        }
                        if ($match) {
                            continue;
                        }
                        $description = $this->Translate('Does not exist anymore!');
                        if (!$serverConnection) {
                            $description = $this->Translate('Server not available!');
                        }
                        $values[] = [
                            'name'               => IPS_GetName($objectID),
                            'SmartLockID'        => $connectedDevice['smartlockID'],
                            'AccountID'          => $connectedDevice['accountID'],
                            'AuthID'             => $connectedDevice['authID'],
                            'ProductDesignation' => $description,
                            'instanceID'         => $objectID
                        ];
                    }
                }
            }
        }
        return $values;
    }

    private function GetDeviceInstances($DeviceUID, $DeviceType)
    {
        $instanceID = 0;
        $propertyName = 'SmartLockID';
        $moduleID = '';
        switch ($DeviceType) {
            case 0: # Smart Lock
                $moduleID = self::NUKI_WEB_SMARTLOCK_GUID;
                break;

            case 1: # Box, prepared, but not used at the moment
                $moduleID = self::NUKI_WEB_BOX_GUID;
                break;

            case 2: # Opener
                $moduleID = self::NUKI_WEB_OPENER_GUID;
                break;

            case 3: # Door, prepared, but not used at the moment
                $moduleID = self::NUKI_WEB_DOOR_GUID;
                break;
        }
        $instanceIDs = IPS_GetInstanceListByModuleID($moduleID);
        foreach ($instanceIDs as $id) {
            $currentStatus = @IPS_GetInstance($id)['InstanceStatus'];
            if ($currentStatus == 102) {
                if (IPS_GetProperty($id, $propertyName) == $DeviceUID) {
                    $instanceID = $id;
                }
            }
        }
        return $instanceID;
    }
}