<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class SymconNukiBridgeValidationTest extends TestCaseSymconValidation
{
    public function testValidateSymconNukiWeb(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateConfiguratorModule(): void
    {
        $this->validateModule(__DIR__ . '/../Configurator');
    }

    public function testValidateOpenerModule(): void
    {
        $this->validateModule(__DIR__ . '/../Opener');
    }

    public function testValidateSplitterModule(): void
    {
        $this->validateModule(__DIR__ . '/../Splitter');
    }
}