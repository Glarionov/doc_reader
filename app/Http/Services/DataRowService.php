<?php

namespace App\Http\Services;

use App\Helpers\TableReaders\ConcreteTableReaders\DataRowTableReader;
use App\Http\Resources\DataRowResource;
use App\Models\DataRow;
use Exception;

class DataRowService
{
    /**
     * Get list of last read data from file, only from fully successfully ended attempts (status=2)
     *
     * @return array
     */
    public function list()
    {
        $dataRows = DataRow::where('status', 2)->get();
        $result = [];

        foreach ($dataRows as $dataRow) {
            $result[$dataRow->date][] = new DataRowResource($dataRow);
        }

        return $result;
    }
}