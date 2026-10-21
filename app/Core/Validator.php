<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Declarative validation rule engine. Validate on input, escape on output —
 * this is the "validate" half (docs/05-security.md §4). Rules are checked
 * even when a form's client-side validation would also have caught them:
 * client-side validation is a UX feature only, never the authority.
 */
final class Validator
{
    /** @var array<string,string[]> */
    private array $errors = [];

    public function __construct(
        private readonly array $data,
        private readonly array $rules
    ) {
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data, $rules);
        $validator->run();

        return $validator;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    /** @return array<string,string[]> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $name, $param);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        $label = ucwords(str_replace('_', ' ', $field));

        match ($rule) {
            'required' => $this->check($field, $value !== null && $value !== '', "{$label} is required."),
            'email'    => $this->check(
                $field,
                $value === null || $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                "{$label} must be a valid email address."
            ),
            'min' => $this->check(
                $field,
                $value === null || mb_strlen((string) $value) >= (int) $param,
                "{$label} must be at least {$param} characters."
            ),
            'max' => $this->check(
                $field,
                $value === null || mb_strlen((string) $value) <= (int) $param,
                "{$label} may not be more than {$param} characters."
            ),
            'confirmed' => $this->check(
                $field,
                $value === ($this->data[$field . '_confirmation'] ?? null),
                "{$label} confirmation does not match."
            ),
            'strong_password' => $this->check(
                $field,
                $value === null || $value === '' || self::isStrongPassword((string) $value),
                "{$label} must contain at least 3 of: uppercase, lowercase, numbers, symbols."
            ),
            default => null,
        };
    }

    private static function isStrongPassword(string $value): bool
    {
        $classes = 0;
        if (preg_match('/[a-z]/', $value) === 1) {
            ++$classes;
        }
        if (preg_match('/[A-Z]/', $value) === 1) {
            ++$classes;
        }
        if (preg_match('/[0-9]/', $value) === 1) {
            ++$classes;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $value) === 1) {
            ++$classes;
        }

        return $classes >= 3;
    }

    private function check(string $field, bool $condition, string $message): void
    {
        if (!$condition) {
            $this->errors[$field][] = $message;
        }
    }
}
