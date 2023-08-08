<?php

namespace App\Http\Services;

use App\Helpers\TableReaders\ConcreteTableReaders\DataRowTableReader;
use App\Http\Resources\DataRowResource;
use App\Models\DataRow;
use App\Models\SavingProcess;
use Exception;
use Illuminate\Support\Str;

class SavingProcessService
{
    /**
     * @return string
     * @throws Exception
     */
    public function create()
    {
        for ($try = 0; $try < 1000000; $try++) {
            $code = Str::random(20);
            $processAlreadyInDB = SavingProcess::query()->firstWhere('code', $code);
            if ($processAlreadyInDB) {
                continue;
            } else {
                $savingProcess = new SavingProcess();
                $savingProcess->code = $code;
                $savingProcess->save();
                return $savingProcess;
            }
        }
        throw new Exception("Cannot make unique code for process");
    }

    /**
     * Get information about reading status
     *
     * @return array
     * @throws Exception
     */
    public function getReadingStatus($code)
    {
        $reader = new DataRowTableReader();
        $logger = $reader->getProgressLogger();

        $rowsSaved = $logger->get($code);
        $success = is_null($rowsSaved) ? false: true;
        return ['success' => $success, 'rows_saved' => $rowsSaved];
    }
}