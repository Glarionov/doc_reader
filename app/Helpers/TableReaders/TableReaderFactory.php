<?php

namespace App\Helpers\TableReaders;

use Exception;

class TableReaderFactory
{
    /**
     * Возвращает инструмент чтения информации
     *
     * @param string $readerType
     * @return ExcelTableReader
     * @throws Exception
     */
    public static function getReader(string $readerType = 'xlsx'): mixed
    {
        switch ($readerType) {
            case 'xlsx':
                return new ExcelTableReader();
            default:
                throw new Exception("Unknown reader type");
        }
    }
}