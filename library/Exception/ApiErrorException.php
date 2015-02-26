<?php
namespace Backlog\Exception;

use RuntimeException;

class ApiErrorException extends RuntimeException
{
    /**
     * @var array
     */
    protected $errors = null;

    /**
     * @param array $errors
     *
     * @return ApiErrorException
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
