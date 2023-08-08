<?php

namespace App\Jobs;

use App\Helpers\TableReaders\ConcreteTableReaders\DataRowTableReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SaveDataFromFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $filePath -            path to file, from storage/uploads
     * @param string $savingProcessCode
     */
    public function __construct(public string $filePath, public string $savingProcessCode)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dataSaveHelper = new DataRowTableReader();
        try {
            Log::info("Start processing file by path $this->filePath");
            $dataSaveHelper->saveDataFromTable($this->filePath, $this->savingProcessCode);
            Log::info("Stopped processing file by path $this->filePath");
        } catch (\Exception $exception) {
            Log::info("Error while processing file by path $this->filePath, message: " . $exception->getMessage());
        }
    }
}
