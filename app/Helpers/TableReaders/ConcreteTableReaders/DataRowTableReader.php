<?php

namespace App\Helpers\TableReaders\ConcreteTableReaders;

use App\Models\DataRow;

class DataRowTableReader extends AbstractConcreteTableReader
{
    /**
     * Key for redis to access status of currently saving process
     */
    const LAST_SAVED_ROW_KEY = 'last_saved_data_row';

    /**
     * Key for redis (or other progress logger) to access status of currently saving process
     */
    const IS_SAVING_NOW_KEY = 'is_saving_data_row_now';

    /**
     * String that helps TableReaderFactory class do define which reader to use
     * @var string
     */
    protected string $sourceType = 'xlsx';

    /**
     * Sources with values that will be calculated based on previous values of same column
     * @var array
     */
    protected array $calculatedColumns = ['A'];

    /**
     * For function convertDataForSaving, to map indexes to column names, like ['first_name', 'last_name']
     * @var array
     */
    protected array $columnIndexes = ['doc_id', 'name', 'date'];

    /**
     * Rows without values in this columns wont be added in innsert array
     * @var array|int[]
     */
    protected array $requiredColumnIndexes = [0];

    protected mixed $model = DataRow::class;
}
