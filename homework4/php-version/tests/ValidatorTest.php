<?php

namespace TodoApi\Tests;

use PHPUnit\Framework\TestCase;
use TodoApi\Services\Validator;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testRequiredValidation(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => ['required' => true]];

        $this->assertFalse($this->validator->validate($data, $rules));
        $this->assertNotEmpty($this->validator->getErrors());
    }

    public function testStringValidation(): void
    {
        $data = ['name' => 123];
        $rules = ['name' => ['string' => true]];

        $this->assertFalse($this->validator->validate($data, $rules));
        $errors = $this->validator->getErrors();
        $this->assertArrayHasKey('name', $errors);
    }

    public function testMaxLengthValidation(): void
    {
        $data = ['name' => 'This is a very long string'];
        $rules = ['name' => ['maxLength' => 10]];

        $this->assertFalse($this->validator->validate($data, $rules));
    }

    public function testNotEmptyValidation(): void
    {
        $data = ['name' => '   '];
        $rules = ['name' => ['notEmpty' => true]];

        $this->assertFalse($this->validator->validate($data, $rules));
    }

    public function testUuidValidation(): void
    {
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $invalidUuid = 'not-a-uuid';

        $dataValid = ['id' => $validUuid];
        $dataInvalid = ['id' => $invalidUuid];
        $rules = ['id' => ['uuid' => true]];

        $this->assertTrue($this->validator->validate($dataValid, $rules));
        $this->assertFalse($this->validator->validate($dataInvalid, $rules));
    }

    public function testDatetimeValidation(): void
    {
        $validDate = '2025-11-06T10:00:00Z';
        $invalidDate = 'not-a-date';

        $dataValid = ['dueDate' => $validDate];
        $dataInvalid = ['dueDate' => $invalidDate];
        $rules = ['dueDate' => ['datetime' => true]];

        $this->assertTrue($this->validator->validate($dataValid, $rules));
        $this->assertFalse($this->validator->validate($dataInvalid, $rules));
    }

    public function testEnumValidation(): void
    {
        $dataValid = ['priority' => 'high'];
        $dataInvalid = ['priority' => 'urgent'];
        $rules = ['priority' => ['enum' => ['low', 'medium', 'high']]];

        $this->assertTrue($this->validator->validate($dataValid, $rules));
        $this->assertFalse($this->validator->validate($dataInvalid, $rules));
    }

    public function testArrayValidation(): void
    {
        $dataValid = ['categories' => ['tag1', 'tag2']];
        $dataInvalid = ['categories' => 'not-an-array'];
        $rules = ['categories' => ['array' => true]];

        $this->assertTrue($this->validator->validate($dataValid, $rules));
        $this->assertFalse($this->validator->validate($dataInvalid, $rules));
    }

    public function testMaxItemsValidation(): void
    {
        $data = ['categories' => ['tag1', 'tag2', 'tag3']];
        $rules = ['categories' => ['array' => true, 'maxItems' => 2]];

        $this->assertFalse($this->validator->validate($data, $rules));
    }

    public function testSanitizeString(): void
    {
        $input = '<script>alert("xss")</script>';
        $sanitized = Validator::sanitizeString($input);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('&lt;script&gt;', $sanitized);
    }

    public function testSanitizeStringTrimsWhitespace(): void
    {
        $input = '  Test String  ';
        $sanitized = Validator::sanitizeString($input);

        $this->assertEquals('Test String', $sanitized);
    }

    public function testSanitizeArray(): void
    {
        $input = ['<b>tag1</b>', 'tag2'];
        $sanitized = Validator::sanitizeArray($input);

        $this->assertStringContainsString('&lt;b&gt;', $sanitized[0]);
        $this->assertEquals('tag2', $sanitized[1]);
    }

    public function testGetFirstError(): void
    {
        $data = ['name' => '', 'description' => ''];
        $rules = [
            'name' => ['required' => true],
            'description' => ['required' => true],
        ];

        $this->validator->validate($data, $rules);
        $firstError = $this->validator->getFirstError();

        $this->assertIsString($firstError);
        $this->assertNotEmpty($firstError);
    }

    public function testValidDataPassesValidation(): void
    {
        $data = [
            'name' => 'Test List',
            'description' => 'A test description',
        ];
        $rules = [
            'name' => ['required' => true, 'string' => true, 'maxLength' => 255],
            'description' => ['string' => true, 'maxLength' => 1000],
        ];

        $this->assertTrue($this->validator->validate($data, $rules));
        $this->assertEmpty($this->validator->getErrors());
    }
}
