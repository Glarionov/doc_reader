<?php

namespace App\Http\Controllers;

use App\Http\Services\SavingProcessService;
use Exception;
use Illuminate\Http\Request;

class SavingProcessController extends Controller
{
    public function __construct(protected SavingProcessService $service)
    {
    }

    /**
     * Get information about reading status
     *
     * @return array
     * @throws Exception
     */
    public function getReadingStatus(Request $request)
    {
        $request->validate(['code' => 'required']);
        return $this->service->getReadingStatus($request->input('code'));
    }
}
