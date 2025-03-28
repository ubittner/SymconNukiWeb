<?php

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class NukiConfiguratorWebAPI extends IPSModuleStrict
{
    //Constants
    private const LIBRARY_GUID = '{8CDE2F20-ECBF-F12E-45AC-B8A7F36CBBFC}';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        //Connect to parent (Nuki Web Splitter)
        $this->ConnectParent('{DA16C1AA-0AFE-65B6-1A0C-5761A08A0FF8}');
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
        $data = [];
        $buffer = [];
        $data['DataID'] = '{7F9C82E4-FF89-7856-2F13-E5A1992167D6}';
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
                            $instanceID = $this->GetDeviceInstances($smartLockID, 0);
                            $values[] = [
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
                            break;

                        case 1: # Box
                            $instanceID = $this->GetDeviceInstances($smartLockID, 1);
                            $values[] = [
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
                            break;

                        case 2: # Opener
                            $instanceID = $this->GetDeviceInstances($smartLockID, 2);
                            $values[] = [
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
                            break;

                        case 3: # Door
                            $instanceID = $this->GetDeviceInstances($smartLockID, 3);
                            $values[] = [
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
                            break;
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
                $moduleID = '{48C163A9-C871-88EB-2717-26A195E3E476}';
                break;

            case 1: # Box
                $moduleID = '{5C79FC64-46D3-1EF9-3C72-3137275CB34C}';
                break;

            case 2: # Opener
                $moduleID = '{41271F9F-1DB0-CB78-93BD-1361A6C5C058}';
                break;

            case 3: # Door
                $moduleID = '{8A30A6FD-A027-95E0-2DB2-F4B4F50E4EEA}';
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