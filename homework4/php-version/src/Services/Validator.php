<?php

namespace TodoApi\Services;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule => $ruleValue) {
                $this->applyRule($field, $value, $rule, $ruleValue);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, mixed $ruleValue): void
    {
        switch ($rule) {
            case 'required':
                if ($ruleValue && ($value === null || $value === '')) {
                    $this->errors[$field][] = "$field is required";
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->errors[$field][] = "$field must be a string";
                }
                break;

            case 'boolean':
                if ($value !== null && !is_bool($value)) {
                    $this->errors[$field][] = "$field must be a boolean";
                }
                break;

            case 'array':
                if ($value !== null && !is_array($value)) {
                    $this->errors[$field][] = "$field must be an array";
                }
                break;

            case 'maxLength':
                if ($value !== null && is_string($value) && strlen($value) > $ruleValue) {
                    $this->errors[$field][] = "$field must not exceed $ruleValue characters";
                }
                break;

            case 'minLength':
                if ($value !== null && is_string($value) && strlen($value) < $ruleValue) {
                    $this->errors[$field][] = "$field must be at least $ruleValue characters";
                }
                break;

            case 'notEmpty':
                if ($ruleValue && $value !== null && is_string($value) && trim($value) === '') {
                    $this->errors[$field][] = "$field cannot be empty or whitespace only";
                }
                break;

            case 'uuid':
                if ($value !== null && !$this->isValidUuid($value)) {
                    $this->errors[$field][] = "$field must be a valid UUID";
                }
                break;

            case 'datetime':
                if ($value !== null && !$this->isValidDatetime($value)) {
                    $this->errors[$field][] = "$field must be a valid ISO 8601 datetime";
                }
                break;

            case 'enum':
                if ($value !== null && !in_array($value, $ruleValue, true)) {
                    $allowed = implode(', ', $ruleValue);
                    $this->errors[$field][] = "$field must be one of: $allowed";
                }
                break;

            case 'maxItems':
                if ($value !== null && is_array($value) && count($value) > $ruleValue) {
                    $this->errors[$field][] = "$field must not exceed $ruleValue items";
                }
                break;

            case 'arrayItemMaxLength':
                if ($value !== null && is_array($value)) {
                    foreach ($value as $item) {
                        if (is_string($item) && strlen($item) > $ruleValue) {
                            $this->errors[$field][] = "$field items must not exceed $ruleValue characters";
                            break;
                        }
                    }
                }
                break;
        }
    }

    private function isValidUuid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }

    private function isValidDatetime(string $datetime): bool
    {
        try {
            $dt = new \DateTime($datetime);
            return $dt->format(\DateTime::ATOM) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    public static function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Trim whitespace
        $value = trim($value);

        // Escape HTML entities to prevent XSS
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return $value;
    }

    public static function sanitizeArray(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return array_map(function ($item) {
            return is_string($item) ? self::sanitizeString($item) : $item;
        }, $values);
    }
}
