<?php

namespace App\Helpers\TableReaders\ConcreteTableReaders;

use App\Helpers\Loggers\LoggerFactory;
use App\Helpers\Loggers\RedisLogger;
use App\Helpers\TableReaders\TableReaderFactory;
use App\Http\Services\SavingProcessService;
use App\Models\DataRow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

abstract class AbstractConcreteTableReader
{
    /**
     * Key for redis (or other progress logger) to access status of currently saving process
     */
    const LAST_SAVED_ROW_KEY = 'last_saved_row';

    /**
     * Key for redis (or other progress logger) to access status of currently saving process
     */
    const IS_SAVING_NOW_KEY = 'is_saving_now';

    /**
     * Key for currently executing saving process
     */
    const CURRENT_SAVING_PROCESS_ID_KEY = 'current_saving_process_id';

    /**
     * Status for old data that was successfully fully saved
     */
    const OLD_DATA_STATUS = 0;

    /**
     * Status for previously read data, but still not saved fully
     */
    const CURRENTLY_SAVING_STATUS = 1;

    /**
     * Status for current data, fully successfully saved
     */
    const ACTUAL_DATA_STATUS = 2;

    /**
     * Status for old data that wasn't fully saved
     */
    const OLD_FAILED_DATA_STATUS = 3;

    /**
     * String that helps TableReaderFactory class do define which reader to use
     * @var string
     */
    protected string $sourceType = 'xlsx';

    /**
     * @var string
     */
    protected string $progressLoggerType = 'redis';

    /**
     * Sources with values that will be calculated based on previous values of same column
     * @var array
     */
    protected array $calculatedColumns;

    /**
     * Rows without values in this columns wont be added in innsert array
     * @var array|int[]
     */
    protected array $requiredColumnIndexes = [0];

    /**
     * For function convertDataForSaving, to map indexes to column names, like ['first_name', 'last_name']
     * @var array
     */
    protected array $columnIndexes;

    /**
     * How many rows will be read and saved at ounce
     * @var int
     */
    protected int $chunkSize = 1000;

    /**
     * If true - adds 'created_at' and 'updated_at' while converting
     * @var bool
     */
    protected bool $useTimestamps = true;

    /**
     * The saving data can be divided by "status" value
     * 0 - old data that was successfully fully saved
     * 1 - previously read data, but still not saved fully
     * 2 - current data, fully successfully saved
     * 3 - old data that wasn't fully saved
     * @var bool
     */
    protected bool $useStatus = true;

    /**
     * Eloquent Model that helps execute database functions
     * @var
     */
    protected mixed $model;

    /**
     * This variable makes code "sleep" (stop working) between reading chunks, so you can watch the process more precisely
     * @var int
     */
    protected int $sleepTimeBetweenReadingChunks = 0;

    /**
     * Getter function for variable above, preventing delay in production
     * If you actually need delay, you can redefine this method in some classes
     *
     * @return int
     */
    protected function getSleepTimeBetweenReadingChunks(): int
    {
        if (App::hasDebugModeEnabled()) {
            return $this->sleepTimeBetweenReadingChunks;
        }
        return 0;
    }

    /**
     * Convert from arrays for inserting in DB, like ['John', 'Oliver'] to ['first_name' => 'John', 'last_name' => 'Oliver']
     *
     * @param $initialData
     * @return array
     */
    protected function convertDataForSaving($initialData = [])
    {
        $result = [];

        foreach ($initialData as $rowIndex => $row) {
            foreach ($this->requiredColumnIndexes as $requiredIndex) {
                if (empty($row[$requiredIndex])) {
                    continue 2;
                }
            }
            foreach ($this->columnIndexes as $columnIndex => $columnName) {
                $result[$rowIndex][$columnName] = $row[$columnIndex];
            }

            if ($this->useTimestamps) {
                $result[$rowIndex]['created_at'] = Carbon::now();
                $result[$rowIndex]['updated_at'] = Carbon::now();
            }

            if ($this->useStatus) {
                $result[$rowIndex]['status'] = static::CURRENTLY_SAVING_STATUS;
            }
        }

        return $result;
    }

    /**
     * @param $fileName
     * @return string
     */
    protected function getFullFilePath($fileName)
    {
        return Storage::disk('local')->path("$fileName");
    }

    /**
     * @return RedisLogger|null
     * @throws \Exception
     */
    public function getProgressLogger(): ?RedisLogger
    {
        if (!empty($this->progressLoggerType)) {
            return LoggerFactory::getLogger($this->progressLoggerType);
        }
        return null;
    }

    /**
     * Reads source and saves in to database
     *
     * @param $source
     * @param $savingProcessCode
     * @return bool|string
     * @throws Exception
     */
    public function saveDataFromTable($source, $savingProcessCode): bool|string
    {
        $progressLogger = $this->getProgressLogger();
        $reader = TableReaderFactory::getReader($this->sourceType);

        $fullPath = $this->getFullFilePath($source);

        $reader->setSource($fullPath);

        $lastRow = $reader->getRowsAmount();
        $minRow = 2;
        $maxRow = $minRow + $this->chunkSize - 1;

        $result = [];

        $progressLogger->set($savingProcessCode, 0);

        if ($this->useStatus) {
            $this->model::where('status', static::CURRENTLY_SAVING_STATUS)->update(['status' => static::OLD_FAILED_DATA_STATUS]);
        }

        while ($minRow < $lastRow) {
            try {
                $arrayFromChunk = $reader->readAsArray($minRow, $maxRow, $this->calculatedColumns);

                $minRow = $maxRow + 1;
                $maxRow = $minRow + $this->chunkSize - 1;

                $insertData = $this->convertDataForSaving($arrayFromChunk);

                $this->model::insert($insertData);

                if (!empty($progressLogger)) {
                    $savedRowsAmount = (int) $progressLogger->get($savingProcessCode);
                    $savedRowsAmount += count($insertData);
                    $progressLogger->set($savingProcessCode, $savedRowsAmount);
                }
            } catch (\Exception $exception) {
                Log::error("Error while saving data from file, message: " . $exception->getMessage());
                return false;
            }

            $result = array_merge($result, $arrayFromChunk);

            if (!empty($this->getSleepTimeBetweenReadingChunks())) {
                sleep($this->getSleepTimeBetweenReadingChunks());
            }
        }

        if ($this->useStatus) {
            DB::transaction(function () {
                $this->model::where('status', static::ACTUAL_DATA_STATUS)->update(['status' => static::OLD_DATA_STATUS]);
                $this->model::where('status', static::CURRENTLY_SAVING_STATUS)->update(['status' => static::ACTUAL_DATA_STATUS]);
            });
        }

        return $savingProcessCode;
    }
}