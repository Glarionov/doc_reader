<?php

namespace App\Helpers\TableReaders\Filters;
use \PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class BaseSpreadSheetFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow   = 100;

    /**  Get the list of rows and columns to read  */
    public function __construct($startRow, $endRow) {
        $this->startRow = $startRow;
        $this->endRow   = $endRow;
    }

    public function readCell($columnAddress, $row, $worksheetName = '') {
        if ($row >= $this->startRow && $row <= $this->endRow) {
            return true;
        }
        return false;
    }
}