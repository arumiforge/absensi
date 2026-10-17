<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Built-in validation messages are never shown in English.
 *
 * @internal
 */
final class ValidationLanguageTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        $this->resetServices();

        parent::tearDown();
    }

    public function testEveryBuiltInRuleHasIndonesianMessage(): void
    {
        $english    = require SYSTEMPATH . 'Language/en/Validation.php';
        $indonesian = require APPPATH . 'Language/id/Validation.php';
        $core       = ['noRuleSets', 'ruleNotFound', 'groupNotFound', 'groupNotArray', 'invalidTemplate'];

        $this->assertSame([], array_diff(array_keys($english), $core, array_keys($indonesian)));
    }

    public function testMessageUsesScreenLabel(): void
    {
        service('language')->setLocale('id');
        $validation = service('validation', null, false);

        $validation->setRules(['alasan' => ['label' => 'Alasan koreksi', 'rules' => 'required|min_length[5]']]);

        $this->assertFalse($validation->run(['alasan' => 'ok']));
        $this->assertSame('Alasan koreksi paling sedikit 5 karakter.', $validation->getError('alasan'));
    }
}
