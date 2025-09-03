<?php

namespace App\Utils;

use MongoDB\Client;
use MongoDB\Model\BSONDocument;

class Validator
{
    private $data;
    private $errors = [];
    private static $db;
    private $customMessages = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
        
        // Initialize database connection for unique validation
        if (!self::$db) {
            $config = require __DIR__ . '/../../config/db.php';
            $client = new Client($config['mongodb']['dsn'], $config['mongodb']['options']);
            self::$db = $client->selectDatabase($config['mongodb']['database']);
        }
    }

    /**
     * Set custom error messages
     */
    public function setCustomMessages(array $messages): self
    {
        $this->customMessages = $messages;
        return $this;
    }

    /**
     * Validate data against rules
     */
    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleSet) {
            $rules = explode('|', $ruleSet);
            $value = $this->getValue($field);
            
            foreach ($rules as $rule) {
                $params = [];
                
                // Check for parameters in rule (e.g., max:255)
                if (strpos($rule, ':') !== false) {
                    [$rule, $param] = explode(':', $rule, 2);
                    $params = explode(',', $param);
                }
                
                $method = 'validate' . ucfirst($rule);
                
                if (method_exists($this, $method)) {
                    if (!$this->$method($field, $value, $params)) {
                        $this->addError($field, $rule, $params);
                        break; // Stop checking more rules for this field if one fails
                    }
                }
            }
        }
        
        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function firstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors)[0] : null;
    }

    /**
     * Validate required field
     */
    protected function validateRequired(string $field, $value): bool
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof \Countable) && count($value) < 1) {
            return false;
        }
        return true;
    }

    /**
     * Validate email
     */
    protected function validateEmail(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate minimum length
     */
    protected function validateMin(string $field, $value, array $params): bool
    {
        $min = (int) $params[0];
        
        if (is_string($value)) {
            return mb_strlen(trim($value)) >= $min;
        }
        
        if (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }

    /**
     * Validate maximum length
     */
    protected function validateMax(string $field, $value, array $params): bool
    {
        $max = (int) $params[0];
        
        if (is_string($value)) {
            return mb_strlen(trim($value)) <= $max;
        }
        
        if (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }

    /**
     * Validate string
     */
    protected function validateString(string $field, $value): bool
    {
        return is_string($value);
    }

    /**
     * Validate numeric
     */
    protected function validateNumeric(string $field, $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Validate integer
     */
    protected function validateInteger(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate boolean
     */
    protected function validateBoolean(string $field, $value): bool
    {
        $acceptable = [true, false, 0, 1, '0', '1'];
        return in_array($value, $acceptable, true);
    }

    /**
     * Validate array
     */
    protected function validateArray(string $field, $value): bool
    {
        return is_array($value);
    }

    /**
     * Validate date format
     */
    protected function validateDate(string $field, $value, array $params): bool
    {
        $format = $params[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    /**
     * Validate date after a given date
     */
    protected function validateAfter(string $field, $value, array $params): bool
    {
        $date = $params[0] ?? 'now';
        $format = $params[1] ?? 'Y-m-d';
        
        $date1 = \DateTime::createFromFormat($format, $value);
        $date2 = \DateTime::createFromFormat($format, $date);
        
        return $date1 > $date2;
    }

    /**
     * Validate date before a given date
     */
    protected function validateBefore(string $field, $value, array $params): bool
    {
        $date = $params[0] ?? 'now';
        $format = $params[1] ?? 'Y-m-d';
        
        $date1 = \DateTime::createFromFormat($format, $value);
        $date2 = \DateTime::createFromFormat($format, $date);
        
        return $date1 < $date2;
    }

    /**
     * Validate field exists in given array
     */
    protected function validateIn(string $field, $value, array $params): bool
    {
        return in_array($value, $params, true);
    }

    /**
     * Validate field does not exist in given array
     */
    protected function validateNotIn(string $field, $value, array $params): bool
    {
        return !in_array($value, $params, true);
    }

    /**
     * Validate field is a valid URL
     */
    protected function validateUrl(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate field is a valid IP address
     */
    protected function validateIp(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate field is a valid JSON string
     */
    protected function validateJson(string $field, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate field is a valid UUID
     */
    protected function validateUuid(string $field, $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        ) === 1;
    }

    /**
     * Validate field is a valid MongoDB ObjectId
     */
    protected function validateObjectId(string $field, $value): bool
    {
        return preg_match('/^[0-9a-f]{24}$/i', $value) === 1;
    }

    /**
     * Validate field is unique in the database
     */
    protected function validateUnique(string $field, $value, array $params): bool
    {
        $collection = $params[0];
        $ignoreId = $params[1] ?? null;
        
        $query = [$field => $value];
        
        if ($ignoreId) {
            $query['_id'] = ['$ne' => new \MongoDB\BSON\ObjectId($ignoreId)];
        }
        
        $count = self::$db->$collection->countDocuments($query);
        
        return $count === 0;
    }

    /**
     * Validate field exists in the database
     */
    protected function validateExists(string $field, $value, array $params): bool
    {
        $collection = $params[0];
        $count = self::$db->$collection->countDocuments([$field => $value]);
        return $count > 0;
    }

    /**
     * Validate field matches another field
     */
    protected function validateSame(string $field, $value, array $params): bool
    {
        $otherField = $params[0];
        return isset($this->data[$otherField]) && $value === $this->data[$otherField];
    }

    /**
     * Validate field is different from another field
     */
    protected function validateDifferent(string $field, $value, array $params): bool
    {
        $otherField = $params[0];
        return !isset($this->data[$otherField]) || $value !== $this->data[$otherField];
    }

    /**
     * Validate field matches a regular expression
     */
    protected function validateRegex(string $field, $value, array $params): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        
        return preg_match($params[0], (string) $value) === 1;
    }

    /**
     * Validate field is a valid timezone
     */
    protected function validateTimezone(string $field, $value): bool
    {
        try {
            new \DateTimeZone($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get field value from data array
     */
    private function getValue(string $field)
    {
        // Handle dot notation for nested arrays (e.g., 'user.name')
        if (strpos($field, '.') !== false) {
            return $this->getNestedValue($field);
        }
        
        return $this->data[$field] ?? null;
    }

    /**
     * Get nested value using dot notation
     */
    private function getNestedValue(string $key)
    {
        $data = $this->data;
        $keys = explode('.', $key);
        
        foreach ($keys as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } elseif (is_object($data) && isset($data->$segment)) {
                $data = $data->$segment;
            } elseif ($data instanceof BSONDocument && isset($data[$segment])) {
                $data = $data[$segment];
            } else {
                return null;
            }
        }
        
        return $data;
    }

    /**
     * Add error message
     */
    private function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->getErrorMessage($field, $rule, $params);
        $this->errors[$field] = [$message];
    }

    /**
     * Get error message for a rule
     */
    private function getErrorMessage(string $field, string $rule, array $params = []): string
    {
        // Check for custom message first
        $customKey = "{$field}.{$rule}";
        if (isset($this->customMessages[$customKey])) {
            return $this->customMessages[$customKey];
        }

        // Default error messages
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$params[0]} characters.",
            'max' => "The {$field} may not be greater than {$params[0]} characters.",
            'string' => "The {$field} must be a string.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'boolean' => "The {$field} field must be true or false.",
            'array' => "The {$field} must be an array.",
            'date' => "The {$field} is not a valid date.",
            'after' => "The {$field} must be a date after {$params[0]}.",
            'before' => "The {$field} must be a date before {$params[0]}.",
            'in' => "The selected {$field} is invalid.",
            'not_in' => "The selected {$field} is invalid.",
            'url' => "The {$field} format is invalid.",
            'ip' => "The {$field} must be a valid IP address.",
            'json' => "The {$field} must be a valid JSON string.",
            'uuid' => "The {$field} must be a valid UUID.",
            'object_id' => "The {$field} must be a valid MongoDB ObjectId.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
            'same' => "The {$field} and {$params[0]} must match.",
            'different' => "The {$field} and {$params[0]} must be different.",
            'regex' => "The {$field} format is invalid.",
            'timezone' => "The {$field} must be a valid timezone.",
        ];

        return $messages[$rule] ?? "The {$field} field is invalid.";
    }

    /**
     * Static method for quick validation
     */
    public static function make(array $data, array $rules, array $messages = []): array
    {
        $validator = new self($data);
        $validator->setCustomMessages($messages);
        
        $isValid = $validator->validate($rules);
        
        return [
            'valid' => $isValid,
            'errors' => $validator->getErrors(),
            'firstError' => $validator->firstError()
        ];
    }
}
