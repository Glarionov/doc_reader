<?php

namespace App\Http\Controllers;

use App\Helpers\TableReaders\ConcreteTableReaders\DataRowTableReader;
use App\Http\Requests\SaveDataRowRequest;
use App\Http\Services\DataRowService;
use App\Http\Services\SavingProcessService;
use App\Jobs\SaveDataFromFile;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataRowController extends Controller
{

    public function __construct(protected DataRowService $service)
    {
    }

    /**
     * Get list of last read data from file, only from fully successfully ended attempts (status=2)
     *
     * @return array
     */
    public function index(): array
    {
        return $this->service->list();
    }

    /**
     * Uploads file and goes back to dashboard page
     *
     * @param SaveDataRowRequest $request
     * @return Application|Factory|View|\Illuminate\Foundation\Application
     * @throws Exception
     */
    public function upload(SaveDataRowRequest $request)
    {
        $path = $request->file('file')->store('uploads');

        $savingProcessService = new SavingProcessService();
        $savingProcess = $savingProcessService->create();
        $savingProcessCode = $savingProcess->code;

        SaveDataFromFile::dispatch($path, $savingProcessCode);
        return view('dashboard', ['uploaded' => true, 'savingProcessCode' => $savingProcessCode]);
    }
}
