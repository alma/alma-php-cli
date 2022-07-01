<?php

namespace App\Formatter;

use Alma\API\RequestError;
use DateTime;

/**
 * Trait FormatterTrait
 *
 * @package App\Formatter
 */
trait FormatterTrait
{
    protected function formatDouble($amount, string $sign): string
    {
        return sprintf("%.2f %s", round(intval($amount) / 100, 2), $sign);
    }

    protected function formatMoney($amount = 0): string
    {
        return $this->formatDouble($amount, '€');
    }

    protected function formatPercent($amount = 0): string
    {
        return $this->formatDouble($amount, '%');
    }

    /**
     * Format bool / null or empty values
     *
     * @param $value
     *
     * @return string
     */
    protected function formatPrimitive($value): string
    {
        if (is_bool($value)) {
            return $value ? '<fg=cyan>TRUE</>' : '<fg=cyan>FALSE</>';
        }
        if (is_null($value)) {
            return '<fg=cyan>NULL</>';
        }
        if (empty($value) && !is_int($value)) { // 0 is empty => :facepalm:
            return '<fg=cyan>EMPTY</>';
        }

        return $value;
    }

    /**
     * @param RequestError $exception
     *
     * @return string
     */
    protected function formatRequestError(RequestError $exception): string
    {
        $stringErrors = "";
        if (isset($exception->response->json['errors'])) {

            foreach ($exception->response->json['errors'] as $error) {
                $stringErrors .= $this->implodeWithKeys($error);
            }
        }

        return sprintf(
            "(%s) : %s (%s)\n%s",
            $exception->response->errorMessage,
            $exception->response->responseCode,
            $exception->response->json['error_code'] ?? 'No error code',
            $stringErrors ?: "No json errors"
        );
    }

    /**
     * @param int $timestamp
     *
     * @return string
     */
    protected function formatTimestamp(int $timestamp): string
    {
        return (new DateTime())->setTimestamp($timestamp)->format('Y-m-d');
    }

    /**
     * @param array  $array
     * @param string $separator
     * @param string $keySeparator
     * @param int    $keyLength
     *
     * @return string
     */
    protected function implodeWithKeys(
        array $array,
        string $separator = "\n",
        string $keySeparator = ": ",
        int $keyLength = 15
    ): string {
        return implode(
            $separator,
            array_map(
                function ($value, $key) use ($keySeparator, $keyLength) {
                    if (is_iterable($value)) {
                        return $this->implodeWithKeys($value);
                    }

                    return sprintf("%{$keyLength}s%s%s", $key, $keySeparator, $value);
                },
                $array,
                array_keys($array)
            )
        );
    }
}
