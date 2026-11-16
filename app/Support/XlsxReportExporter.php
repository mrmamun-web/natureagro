<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

class XlsxReportExporter
{
    /**
     * @param  array<int, array<int, int|float|string|null>>  $rows
     */
    public function create(array $rows): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to export Excel workbooks.');
        }

        $documents = [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->packageRelationshipsXml(),
            'xl/workbook.xml' => $this->workbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationshipsXml(),
            'xl/worksheets/sheet1.xml' => $this->worksheetXml($rows),
        ];
        $path = tempnam(sys_get_temp_dir(), 'nature-agro-report-');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary Excel report file.');
        }

        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::OVERWRITE) !== true) {
            unlink($path);

            throw new RuntimeException('Unable to open the Excel report archive.');
        }

        foreach ($documents as $name => $contents) {
            if (! $archive->addFromString($name, $contents)) {
                $archive->close();
                unlink($path);

                throw new RuntimeException('Unable to write the Excel report workbook.');
            }
        }

        if (! $archive->close()) {
            unlink($path);

            throw new RuntimeException('Unable to finalize the Excel report workbook.');
        }

        return $path;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function packageRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Farm Report" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, array<int, int|float|string|null>>  $rows
     */
    private function worksheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="24" customWidth="1"/><col min="2" max="2" width="36" customWidth="1"/><col min="3" max="3" width="20" customWidth="1"/></cols>'
            .'<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xml .= '<row r="'.$excelRow.'">';

            foreach ($row as $columnIndex => $value) {
                if ($value === null) {
                    continue;
                }

                $cellReference = $this->columnName($columnIndex + 1).$excelRow;
                if (is_int($value) || is_float($value)) {
                    if (! is_finite((float) $value)) {
                        throw new RuntimeException('Excel report values must be finite numbers.');
                    }

                    $xml .= '<c r="'.$cellReference.'"><v>'.$value.'</v></c>';

                    continue;
                }

                $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $xml .= '<c r="'.$cellReference.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        if (count($rows) >= 2) {
            $xml .= '<autoFilter ref="A2:C'.count($rows).'"/>';
        }
        $xml .= '</worksheet>';

        return $xml;
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }
}
