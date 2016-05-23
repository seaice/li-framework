<?php
namespace Li;

class Validator {
    public $rules = [];
    public $dataWrap = 'data';
    public $errors = [];
    protected $_data;
    protected $_dataType;
    protected $_customMessages;
    protected $_customAttributes;

    public static function make($data, $rules, $customMessages, $customAttributes) {
        return new Validator($data, $rules, $customMessages, $customAttributes);
    }

    public function __construct($data, $rules, $customMessages = [], $customAttributes = []) {
        $this->_data = $data;
        $this->setRules($rules);
        $this->_customMessages = $customMessages;
        $this->_customAttributes = $customAttributes;
    }

    public function setRules($rules) {
        $rules = $this->_explodeRules($rules);
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }
    public function fails() {
        return !$this->passes();
    }

    public function test() {
        return false;
    }

    public function passes() {
        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                if (false === $this->_validate($attribute, $rule)) {
                    break;
                }
            }
        }

        return count($this->errors) === 0;
    }

    protected function _validate($attribute, $rule) {
        list($rule, $parameters) = $this->_parseRule($rule);

        if ($rule == '') {
            return true;
        }

        $method = '_validate' . Str::studly($rule);
        $value = $this->_getValue($attribute);

        if (!$this->$method($attribute, $value, $parameters, $this)) {
            $this->_addFailure($attribute, $rule, $parameters);
            return false;
        }

        return true;
    }

    protected function _parseRule($rules) {
        $parameters = [];

        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);
            $parameters = $this->_parseParameters($rules, $parameter);
        }
        $rules = $this->_normalizeRule($rules);

        return [$rules, $parameters];
    }

    protected function _normalizeRule($rule) {
        switch ($rule) {
            case 'int':
                return 'Integer';
            case 'bool':
                return 'Boolean';
            default:
                return $rule;
        }
    }

    protected function _explodeRules($rules) {
        foreach ($rules as $key => $rule) {
            $rules[$key] = (is_string($rule)) ? explode('|', $rule) : $rule;
        }

        return $rules;
    }

    protected function _parseParameters($rule, $parameter) {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    protected function _addFailure($attribute, $rule, $parameters) {

        list($id, $parameters) = $this->_transParameters($attribute, $rule, $parameters);
        
        $this->errors[$attribute] = App::app()->t($id, $parameters);
    }

    protected function _transParameters($attribute, $rule, $parameters) {
        $id = 'validation.' . $rule;

        if ($rule == 'between') {
            $id .= '.' . $this->_getType($attribute);
            $parameters = array_combine([':min', ':max'], $parameters);
        } else if ($rule == 'after') {
            $parameters = array_combine([':date'], $parameters);
        } else if ($rule == 'date_format') {
            $parameters = array_combine([':format'], $parameters);
        } else if ($rule == 'before') {
            $parameters = array_combine([':date'], $parameters);
        } else if ($rule == 'digits') {
            $parameters = array_combine([':digits'], $parameters);
        } else if ($rule == 'digits_between') {
            $parameters = array_combine([':min', ':max'], $parameters);
        } else if ($rule == 'max') {
            $id .= '.' . $this->_getType($attribute);
            $parameters = array_combine([':max'], $parameters);
        } else if ($rule == 'min') {
            $id .= '.' . $this->_getType($attribute);
            $parameters = array_combine([':min'], $parameters);
        }

        $parameters[':attribute'] = isset($this->_customAttributes[$attribute]) ? $this->_customAttributes[$attribute] : $attribute;

        return [$id, $parameters];
    }

    protected function _getType($attribute) {
        if (empty($this->_dataType[$attribute])) {

            if ($this->_hasRule($attribute, ['numeric', 'integer'])) {
                $this->_dataType[$attribute] = 'numeric';
            } else if ($this->_hasRule($attribute, ['array'])) {
                $this->_dataType[$attribute] = 'array';
            }
            $this->_dataType[$attribute] = 'string';

        }
        return $this->_dataType[$attribute];
    }

    protected function _getValue($attribute) {
        if (array_key_exists($attribute, $this->_data)) {
            return $this->_data[$attribute];
        }
    }

    protected function _requireParameterCount($count, $parameters, $rule) {
        if (count($parameters) < $count) {
            throw new Exception("Validation rule $rule requires at least $count parameters.");
        }
    }

    protected function _validateAccepted($attribute, $value) {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];

        return $this->_validateRequired($attribute, $value) && in_array($value, $acceptable, true);
    }

    protected function _validateActiveUrl($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            return count(dns_get_record($url, DNS_A | DNS_AAAA)) > 0;
        }

        return false;
    }

    /**
     * Validate the date is after a given date.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function _validateAfter($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'after');

        if (! is_string($value)) {
            return false;
        }

        if ($format = $this->_getDateFormat($attribute)) {
            return $this->_validateAfterWithFormat($format, $value, $parameters);
        }

        if (! ($date = strtotime($parameters[0]))) {
            return strtotime($value) > strtotime($this->_getValue($parameters[0]));
        }

        return strtotime($value) > $date;
    }

    /**
     * Validate the date is after a given date with a given format.
     *
     * @param  string  $format
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function _validateAfterWithFormat($format, $value, $parameters)
    {
        $param = $this->_getValue($parameters[0]) ?: $parameters[0];

        return $this->_checkDateTimeOrder($format, $param, $value);
    }

    protected function _checkDateTimeOrder($format, $before, $after)
    {
        $before = $this->_getDateTimeWithOptionalFormat($format, $before);

        $after = $this->_getDateTimeWithOptionalFormat($format, $after);

        return ($before && $after) && ($after > $before);
    }

    protected function _getDateTimeWithOptionalFormat($format, $value)
    {
        $date = \DateTime::createFromFormat($format, $value);

        if ($date) {
            return $date;
        }

        try {
            return new \DateTime($value);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Validate that an attribute matches a date format.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function _validateDateFormat($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'date_format');

        if (! is_string($value)) {
            return false;
        }

        $parsed = date_parse_from_format($parameters[0], $value);

        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }

    /**
     * Get the date format for an attribute if it has one.
     *
     * @param  string  $attribute
     * @return string|null
     */
    protected function _getDateFormat($attribute)
    {
        if ($result = $this->_getRule($attribute, 'date_format')) {
            return $result[1][0];
        }
    }

    protected function _validateAlpha($attribute, $value)
    {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    protected function _validateAlphaDash($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value);
    }

    protected function _validateAlphaNum($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value);
    }

    protected function _validateArray($attribute, $value)
    {
        return is_null($value) || is_array($value);
    }

    protected function _validateBefore($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'before');

        if (! is_string($value)) {
            return false;
        }

        if ($format = $this->_getDateFormat($attribute)) {
            return $this->_validateBeforeWithFormat($format, $value, $parameters);
        }

        if (! ($date = strtotime($parameters[0]))) {
            return strtotime($value) < strtotime($this->getValue($parameters[0]));
        }

        return strtotime($value) < $date;
    }

    protected function _validateBeforeWithFormat($format, $value, $parameters)
    {
        $param = $this->_getValue($parameters[0]) ?: $parameters[0];

        return $this->_checkDateTimeOrder($format, $value, $param);
    }

    protected function _validateBetween($attribute, $value, $parameters) {
        $this->_requireParameterCount(2, $parameters, 'between');
        $size = $this->_getSize($attribute, $value);
        return $size >= $parameters[0] && $size <= $parameters[1];
    }

    protected function _validateBoolean($attribute, $value)
    {
        $acceptable = [true, false, 0, 1, '0', '1'];

        return is_null($value) || in_array($value, $acceptable, true);
    }

    protected function _validateConfirmed($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'confirmed');
        $other = $this->_getValue($parameters[0]);
        return isset($other) && $value === $other;
    }

    protected function _validateDate($attribute, $value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if (! is_string($value) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    protected function _validateDifferent($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'different');
        $other = $this->_getValue($parameters[0]);
        return isset($other) && $value !== $other;
    }

    protected function _validateDigits($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'digits');

        return $this->_validateNumeric($attribute, $value)
            && strlen((string) $value) == $parameters[0];
    }

    protected function _validateDigitsBetween($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string) $value);

        return $this->_validateNumeric($attribute, $value)
          && $length >= $parameters[0] && $length <= $parameters[1];
    }


    protected function _validateEmail($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }


    protected function _validateIn($attribute, $value, $parameters)
    {
        if (is_array($value) && $this->_hasRule($attribute, 'Array')) {
            return count(array_diff($value, $parameters)) == 0;
        }

        return ! is_array($value) && in_array((string) $value, $parameters);
    }

    protected function _validateInteger($attribute, $value)
    {
        return is_null($value) || filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function _validateIp($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function _validateJson($attribute, $value)
    {
        if (! is_scalar($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function _validateMax($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'max');
        return $this->_getSize($attribute, $value) <= $parameters[0];
    }

    protected function _validateMin($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'min');
        return $this->_getSize($attribute, $value) >= $parameters[0];
    }
    
    protected function _validateNotIn($attribute, $value, $parameters)
    {
        return ! $this->_validateIn($attribute, $value, $parameters);
    }

    protected function _validateNumeric($attribute, $value, $parameters) {
        return is_null($value) || is_numeric($value);
    }


    protected function _validateRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->_requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value);
    }

    protected function _validateRequired($attribute, $value) {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    protected function _validateSize($attribute, $value, $parameters)
    {
        $this->_requireParameterCount(1, $parameters, 'size');

        return $this->_getSize($attribute, $value) == $parameters[0];
    }

    protected function _validateString($attribute, $value)
    {
        return is_null($value) || is_string($value);
    }

    protected function _validateTimezone($attribute, $value)
    {
        try {
            new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    protected function _getSize($attribute, $value) {
        $type = $this->_getType($attribute);

        if ('numeric' == $type) {
            return $value;
        } else if ('array' == $type) {
            return count($value);
        }

        return mb_strlen($value);
    }

    protected function _hasRule($attribute, $rules) {
        return !is_null($this->_getRule($attribute, $rules));
    }

    protected function _getRule($attribute, $rules) {
        if (!array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            list($rule, $parameters) = $this->_parseRule($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }

    /**
     * Validate that an attribute was "accepted".
     *
     * This validation rule implies the attribute is "required".
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAccepted($attribute, $value) {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];

        return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
    }

    public function getValidator() {
        echo json_encode($this->errors);
    }

    /**
     * 验证
     */
    /*
    public function validate_old($data, $rules, $message = [], $attributes = []) {
        //解析rule
        foreach ($this->rule as $key => $rule) {
            foreach ($rule as $r) {
                if ($r[0] == 'required') {
                    if (!isset($data[$key]) || $data[$key] === '') {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'email') {
                    if (!isset($data[$key]) || !$this->checkEmail($data[$key])) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'number') {
                    if (!isset($data[$key]) || !is_numeric($data[$key])) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'digits') {
                    if (!isset($data[$key]) && !is_numeric($data[$key]) && !is_int($data[$key] + 0)) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'date') {
                    debug($data[$key]);

                    if (!isset($data[$key]) || !$this->checkDate(($data[$key]), $r[2])) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'url') {
                    if (!isset($data[$key]) || !$this->checkUrl($data[$key])) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'minlength') {
                    if (!isset($data[$key]) || strlen($data[$key]) < $r[2]) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'maxlength') {
                    if (!isset($data[$key]) || strlen($data[$key]) > $r[2]) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'equalTo') {
                    if (!isset($data[$key]) || $data[$key] != $r[2]) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'rangelength') {
                    $len = strlen($data[$key]);
                    if (!isset($data[$key]) || ($len < $r[2] || $len > $r[3])) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'range') {
                    if (!isset($data[$key]) || ($data[$key] < $r[2] || $data[$key] > $r[3])) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'max') {
                    if (!isset($data[$key]) || $data[$key] > $r[2]) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                } else if ($r[0] == 'min') {
                    if (!isset($data[$key]) || $data[$key] < $r[2]) {
                        $this->error[$key] = $r[1];
                        break;
                    }
                }
            }
        }

        return $this->error;
    }

    public function checkEmail($email) {
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";

        if (preg_match($pattern, $email)) {
            return true;
        }

        return false;
    }
    public function checkUrl($url) {
        $pattern = "|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i";

        if (preg_match($pattern, $url)) {
            return true;
        }

        return false;
    }

    public function checkDate($date, $format) {
        $format = str_replace('YYYY', 'Y', $format);
        $format = str_replace('YY', 'y', $format);

        $format = str_replace('MM', 'm', $format);

        $format = str_replace('DD', 'd', $format);

        $format = str_replace('HH', 'H', $format);
        $format = str_replace('mm', 'i', $format);
        $format = str_replace('ss', 's', $format);

        if (empty($format)) {
            $format = 'Y-m-d';
        }
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt !== false && !array_sum($dt->getLastErrors());
    }

    public function getError() {
        $error = '';
        $errorCount = count($this->error);
        $i = 0;
        foreach ($this->error as $key => $value) {
            $error .= '"' . $this->dataWrap . '[' . $key . ']":"' . $value . '"';

            if ($i < $errorCount - 1) {
                $error .= ',';
            }
            $i++;
        }

        $js = <<< EOF
    {$this->dataWrap}_validate.showErrors({
        {$error}
    });
EOF;
        echo $js;
    }
    */
}
