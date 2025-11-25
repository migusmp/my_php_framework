<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private bool $validated = false;

    private function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function passes(): bool
    {
        if (! $this->validated) {
            $this->validate();
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function errors(): array
    {
        if (! $this->validated) {
            $this->validate();
        }

        return $this->errors;
    }

    /**
     * Devuelve únicamente los campos validados (útil para limpiar $_POST).
     */
    public function validated(): array
    {
        if (! $this->validated) {
            $this->validate();
        }

        $result = [];
        foreach ($this->rules as $field => $_) {
            if (\array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }

        return $result;
    }

    private function validate(): void
    {
        $this->validated = true;

        foreach ($this->rules as $field => $ruleString) {
            $rules = \explode('|', $ruleString);

            foreach ($rules as $rule) {
                [$name, $param] = $this->parseRule($rule);

                $value = $this->data[$field] ?? null;

                switch ($name) {
                    case 'required':
                        $this->validateRequired($field, $value);
                        break;

                    case 'email':
                        $this->validateEmail($field, $value);
                        break;

                    case 'min':
                        $this->validateMin($field, $value, (int) $param);
                        break;

                    case 'max':
                        $this->validateMax($field, $value, (int) $param);
                        break;

                        // aquí podrías ir añadiendo más reglas (numeric, same, regex, etc.)
                }
            }
        }
    }

    private function parseRule(string $rule): array
    {
        if (\str_contains($rule, ':')) {
            [$name, $param] = \explode(':', $rule, 2);
            return [\trim($name), \trim($param)];
        }

        return [\trim($rule), null];
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function validateRequired(string $field, $value): void
    {
        if ($value === null || $value === '' || (is_string($value) && \trim($value) === '')) {
            $this->addError($field, "El campo {$field} es obligatorio.");
        }
    }

    private function validateEmail(string $field, $value): void
    {
        if ($value === null || $value === '') {
            return; // lo maneja required, si existe
        }

        if (! \filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "El campo {$field} debe ser un email válido.");
        }
    }

    private function validateMin(string $field, $value, int $min): void
    {
        if ($value === null || $value === '') {
            return; // lo maneja required
        }

        $length = \mb_strlen((string) $value);
        if ($length < $min) {
            $this->addError($field, "El campo {$field} debe tener al menos {$min} caracteres.");
        }
    }

    private function validateMax(string $field, $value, int $max): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $length = \mb_strlen((string) $value);
        if ($length > $max) {
            $this->addError($field, "El campo {$field} no puede tener más de {$max} caracteres.");
        }
    }
}
