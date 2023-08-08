<?php

namespace App\Helpers\TableReaders;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use App\Helpers\TableReaders\Filters\BaseSpreadSheetFilter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use \Matex\Evaluator;

class ExcelTableReader
{
    /**
     * File from where there will be reading
     * @var string
     */
    public string $source;

    /**
     * Type of reader
     * @var mixed
     */
    public mixed $reader;

    /**
     * Spreadsheet of current file
     * @var
     */
    public $spreadsheet;

    /**
     * Amount of rows in currently reading file
     * @var
     */
    public $rowsAmount;

    /**
     * First column in reading sequence
     * @var string
     */
    public $firstColumnLetter = 'A';

    /**
     * Last column in reading sequence
     * @var
     */
    public $lastColumnLetter;

    public function __construct(
        public Evaluator $evaluator = new Evaluator(),
        private array $savedValues = []
    )
    {
    }

    /**
     * @param string $inputFileName
     * @return void
     * @throws Exception
     */
    public function setSource(string $inputFileName)
    {
        $this->source = $inputFileName;

        $testAgainstFormats = [
            IOFactory::READER_XLS,
            IOFactory::READER_XLSX,
            IOFactory::READER_HTML,
        ];

        $inputFileType = IOFactory::identify($inputFileName, $testAgainstFormats);

        $this->reader = IOFactory::createReader($inputFileType);
        $this->rowsAmount = $this->reader->listWorksheetInfo($inputFileName)[0]['totalRows'];
        $this->lastColumnLetter = $this->reader->listWorksheetInfo($inputFileName)[0]['lastColumnLetter'];
    }

    /**
     * @param $minRow
     * @param $maxRow
     * @return void
     */
    public function setFilter($minRow, $maxRow)
    {
        $filter = new BaseSpreadSheetFilter($minRow, $maxRow);
        $this->reader->setReadFilter($filter);
    }

    /**
     * @return mixed
     */
    public function getRowsAmount()
    {
        return $this->rowsAmount;
    }

    /**
     * Calculate values of cells when needed
     *
     * @param $minRow
     * @param $maxRow
     * @param $calculatedColumns
     * @return array
     */
    protected function handleExpressions($minRow, $maxRow, $calculatedColumns)
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $savedValuesForResult = [];
        $resultIndex = 0;

        for ($row = $minRow; $row <= $maxRow; $row ++) {
            foreach ($calculatedColumns as $calculatedColumn) {
                $cell = "{$calculatedColumn}{$row}";
                $keyOfColumn = ord($calculatedColumn) - 65;
                $value = $sheet->getCell($cell)->getValue();

                preg_match('#[A-z]\d+#', $value, $matches);

                if (empty($matches)) {
                    $resultValue = $value;
                } else {
                    $expression = $value;

                    foreach ($matches as $match) {
                        if (isset($this->savedValues[$match])) {
                            $expression = str_replace($match, $this->savedValues[$match], $expression);
                        } else {
                            Log::warning("Absent expression '$match' in saved values");
                            continue 2;
                        }
                    }

                    try {
                        $expression = str_replace('=', '', $expression);
                        $resultValue = $this->evaluator->execute($expression);
                    } catch (\Exception $exception) {
                        Log::warning("'$expression' can't be calculated");
                        continue;
                    }
                }

                $this->savedValues[$cell] = $resultValue;
                $savedValuesForResult[$resultIndex][$keyOfColumn] = $resultValue;
                $resultIndex++;
            }
        }

        return $savedValuesForResult;
    }

    /**
     * Reads data from table as array
     *
     * @param $minRow
     * @param $maxRow
     * @param array $calculatedColumns - столбцы, значения в которых используются для вычислений других значений того же столбца
     * @return array
     * @throws Exception
     */
    public function readAsArray($minRow, $maxRow, array $calculatedColumns = []): array
    {
        $this->setFilter($minRow, $maxRow);
        $this->spreadsheet = $this->reader->load($this->source);
        $range = "{$this->firstColumnLetter}$minRow:{$this->lastColumnLetter}$maxRow";

        $savedValuesForResult = $this->handleExpressions($minRow, $maxRow, $calculatedColumns);
        $arrayOfValues = $this->spreadsheet->getActiveSheet()->rangeToArray($range);

        foreach ($savedValuesForResult as $resultIndex => $columnValues) {
            foreach ($columnValues as $keyOfColumn => $resultValue) {
                $arrayOfValues[$resultIndex][$keyOfColumn] = $resultValue;
            }
        }
        return $arrayOfValues;
    }
}