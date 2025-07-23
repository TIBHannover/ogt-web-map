<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelExportController extends ExportController
{
    /**
     * Export data as Excel file
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportExcel(Request $request)
    {
        $type = $request->input("type", "all");
        $filename = "wikidata_export_" . date("Y-m-d_His") . ".xlsx";
        
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Use a temporary in-memory stream to collect CSV data
        $tempStream = fopen('php://temp', 'r+');
        
        // Get the CSV data using the parent class methods
        if ($type == "places" || $type == "all") {
            $this->exportPlaces($tempStream, ';');
        } elseif ($type == "places_no_events") {
            $this->exportPlacesWithoutEvents($tempStream, ';');
        } elseif ($type == "places_minimal") {
            $this->exportPlacesMinimal($tempStream, ';');
        } elseif ($type == "staatspolizeileitstellen") {
            $this->exportStaatspolizeileitstellen($tempStream, ';');
        } elseif ($type == "staatspolizeistellen") {
            $this->exportStaatspolizeistellen($tempStream, ';');
        } elseif ($type == "aussendienststellen") {
            $this->exportAussendienststellen($tempStream, ';');
        } elseif ($type == "gefaengnisse") {
            $this->exportGefaengnisse($tempStream, ';');
        } elseif ($type == "erweitertes_polizeigefaengnis") {
            $this->exportErweitertesPolizeigefaengnis($tempStream, ';');
        } elseif ($type == "arbeitserziehungslager") {
            $this->exportArbeitserziehungslager($tempStream, ';');
        } elseif ($type == "ereignisse") {
            $this->exportEvents($tempStream, ';');
        } elseif ($type == "erinnerungsorte") {
            $this->exportErinnerungsorte($tempStream, ';');
        } elseif ($type == "persons" || $type == "all") {
            $this->exportPersons($tempStream, ';');
        } elseif ($type == "persons_perpetrators") {
            $this->exportPersonsPerpetrators($tempStream, ';');
        } elseif ($type == "persons_victims") {
            $this->exportPersonsVictims($tempStream, ';');
        }
        
        // Reset the stream pointer to the beginning
        rewind($tempStream);
        
        // Read the CSV data and populate the spreadsheet
        $row = 1;
        while (($data = fgetcsv($tempStream, 0, ';')) !== false) {
            $col = 1;
            foreach ($data as $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Auto-size columns for better readability
        foreach (range(1, $col-1) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }
        
        // Close the temporary stream
        fclose($tempStream);
        
        // Set up the response headers
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ];
        
        // Create Excel file and send it to the browser
        return response()->stream(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            $headers
        );
    }
}