<?php
namespace App\Validator;

use \Illuminate\Validation\Validator;

class CustomValidation extends Validator
{
    /**
     * 指定の拡張子が含まれていないか
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    public function validateMimesExcept($attribute, $value, $parameters, $validator)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        $tmpVals = explode('.', $value->getClientOriginalName());
        $ext = '';
        if (count($tmpVals) >= 1) {
            $ext = $tmpVals[count($tmpVals) - 1];
        }

        return $value->getPath() !== '' && !in_array($value->guessExtension(), $parameters) && !in_array($ext, $parameters);
    }

    /**
     * Replace all place-holders for the mimes_except rule.
     * :valuesに指定した拡張子文字列を入れる
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    public function replaceMimesExcept($message, $attribute, $rule, $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }
}
