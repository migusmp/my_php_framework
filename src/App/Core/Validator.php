<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validator simple tipo "Laravel-like".
 *
 * Uso:
 *
 *   $validator = Validator::make($_POST, [
 *       'name'                  => 'required|min:3|max:50',
 *       'email'                 => 'required|email',
 *       'password'              => 'required|min:8',
 *       'password_confirmation' => 'required|same:password',
 *   ])->setCustomMessages([
 *       'email.required' => 'Por favor, indica tu correo electrónico.',
 *       'email.email'    => 'El formato del correo no es válido.',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       $errors = $validator->errors();      // array por campo
 *       $first  = $validator->first('email'); // primer error de un campo
 *       // Guardar en Flash + redirigir, etc.
 *   }
 *
 *   $dataLimpia = $validator->validated();
 */
final class Validator
{
    /**
     * Mensajes por defecto (puedes ampliarlos).
     *
     * Claves: nombre de la regla (required, email, min, max, numeric, same, confirmed...)
     */
    private const DEFAULT_MESSAGES = [
        'required'   => 'El campo :field es obligatorio.',
        'email'      => 'El campo :field debe ser un email válido.',
        'min'        => 'El campo :field debe tener al menos :min caracteres.',
        'max'        => 'El campo :field no puede tener más de :max caracteres.',
        'numeric'    => 'El campo :field debe ser numérico.',
        'same'       => 'El campo :field debe coincidir con :other.',
        'confirmed'  => 'La confirmación de :field no coincide.',
    ];

    /**
     * Datos originales (ej: $_POST).
     */
    private array $data;

    /**
     * Reglas por campo (ej: ['email' => 'required|email']).
     */
    private array $rules;

    /**
     * Mensajes personalizados.
     *
     * - 'field.rule' → 'email.required'
     * - 'rule'       → 'required'
     */
    private array $customMessages = [];

    /**
     * Errores acumulados por campo.
     *
     * @var array<string, string[]>
     */
    private array $errors = [];

    /**
     * Flag interno para no validar dos veces.
     */
    private bool $validated = false;

    private function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    /**
     * Crea una nueva instancia de Validator.
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    /**
     * Define mensajes personalizados.
     *
     * Ej:
     *   [
     *      'email.required' => 'Debes poner un email sí o sí',
     *      'required'       => 'Este campo es obligatorio.',
     *   ]
     */
    public function setCustomMessages(array $messages): self
    {
        $this->customMessages = $messages;

        return $this;
    }

    /**
     * Devuelve true si la validación pasó sin errores.
     */
    public function passes(): bool
    {
        if (! $this->validated) {
            $this->validate();
        }

        return $this->errors === [];
    }

    /**
     * Devuelve true si hubo errores de validación.
     */
    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * Devuelve todos los errores agrupados por campo.
     *
     * [
     *   'email' => ['El campo email es obligatorio.', '...'],
     *   'name'  => ['...'],
     * ]
     */
    public function errors(): array
    {
        if (! $this->validated) {
            $this->validate();
        }

        return $this->errors;
    }

    /**
     * Devuelve todos los errores de un campo concreto.
     */
    public function errorsFor(string $field): array
    {
        if (! $this->validated) {
            $this->validate();
        }

        return $this->errors[$field] ?? [];
    }

    /**
     * Devuelve el primer error de un campo o null.
     */
    public function first(string $field): ?string
    {
        $fieldErrors = $this->errorsFor($field);

        return $fieldErrors[0] ?? null;
    }

    /**
     * Devuelve únicamente los campos validados según las reglas definidas.
     *
     * Útil para "limpiar" $_POST y quedarte solo con lo que esperas.
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

    /**
     * Lógica principal de validación.
     */
    private function validate(): void
    {
        $this->validated = true;

        foreach ($this->rules as $field => $ruleString) {
            $rules = \explode('|', (string) $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$name, $param] = $this->parseRule($rule);

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

                    case 'numeric':
                        $this->validateNumeric($field, $value);
                        break;

                    case 'same':
                        $this->validateSame($field, $value, (string) $param);
                        break;

                    case 'confirmed':
                        $this->validateConfirmed($field, $value);
                        break;

                        // aquí puedes ir añadiendo más reglas:
                        // case 'regex':
                        // case 'in':
                        // case 'boolean':
                        // etc.
                }
            }
        }
    }

    /**
     * Parsea "min:3" → ['min', '3'] o "required" → ['required', null].
     */
    private function parseRule(string $rule): array
    {
        $rule = \trim($rule);

        if ($rule === '') {
            return ['', null];
        }

        if (\str_contains($rule, ':')) {
            [$name, $param] = \explode(':', $rule, 2);

            return [\trim($name), \trim($param)];
        }

        return [$rule, null];
    }

    /**
     * Añade un error de validación para un campo.
     */
    private function addError(string $field, string $rule, array $replacements = []): void
    {
        $message = $this->resolveMessage($field, $rule, $replacements);

        $this->errors[$field][] = $message;
    }

    /**
     * Resuelve el mensaje de error:
     *
     * 1. Busca 'field.rule' en customMessages
     * 2. Busca 'rule' en customMessages
     * 3. Usa DEFAULT_MESSAGES[rule]
     */
    private function resolveMessage(string $field, string $rule, array $replacements): string
    {
        $keyFieldRule = $field . '.' . $rule;

        if (isset($this->customMessages[$keyFieldRule])) {
            $template = $this->customMessages[$keyFieldRule];
        } elseif (isset($this->customMessages[$rule])) {
            $template = $this->customMessages[$rule];
        } else {
            $template = self::DEFAULT_MESSAGES[$rule] ?? 'El campo :field no es válido.';
        }

        // Reemplazos básicos
        $replace = \array_merge([
            ':field' => $field,
        ], $replacements);

        return \strtr($template, $replace);
    }

    // ==========================================================
    //                  Reglas concretas
    // ==========================================================

    private function validateRequired(string $field, mixed $value): void
    {
        if (
            $value === null
            || $value === ''
            || (is_string($value) && \trim($value) === '')
        ) {
            $this->addError($field, 'required');
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return; // lo maneja 'required'
        }

        if (! \filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email');
        }
    }

    private function validateMin(string $field, mixed $value, int $min): void
    {
        if ($value === null || $value === '') {
            return; // lo maneja 'required'
        }

        $length = \mb_strlen((string) $value);

        if ($length < $min) {
            $this->addError($field, 'min', [
                ':min' => (string) $min,
            ]);
        }
    }

    private function validateMax(string $field, mixed $value, int $max): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $length = \mb_strlen((string) $value);

        if ($length > $max) {
            $this->addError($field, 'max', [
                ':max' => (string) $max,
            ]);
        }
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return; // lo maneja required si procede
        }

        if (! \is_numeric($value)) {
            $this->addError($field, 'numeric');
        }
    }

    /**
     * Regla "same:otherField"
     */
    private function validateSame(string $field, mixed $value, string $otherField): void
    {
        $otherValue = $this->data[$otherField] ?? null;

        if ($value !== $otherValue) {
            $this->addError($field, 'same', [
                ':other' => $otherField,
            ]);
        }
    }

    /**
     * Regla "confirmed":
     *
     * password + password_confirmation
     * email + email_confirmation, etc.
     */
    private function validateConfirmed(string $field, mixed $value): void
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->data[$confirmationField] ?? null;

        if ($value !== $confirmationValue) {
            $this->addError($field, 'confirmed');
        }
    }
}
