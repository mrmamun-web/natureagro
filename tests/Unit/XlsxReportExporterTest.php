<?php

namespace Tests\Unit;

use App\Support\XlsxReportExporter;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class XlsxReportExporterTest extends TestCase
{
    public function test_it_creates_an_excel_workbook_with_numeric_cells_and_escaped_text(): void
    {
        $path = (new XlsxReportExporter)->create([
            ['Nature Agro report', '2026-11-01 to 2026-11-16'],
            ['Category', 'Measure', 'Value'],
            ['Sales & income', 'Revenue <BDT>', 1200.5],
        ]);

        try {
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path));
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            $archive->close();

            $this->assertIsString($worksheet);
            $this->assertStringContainsString('Sales &amp; income', $worksheet);
            $this->assertStringContainsString('Revenue &lt;BDT&gt;', $worksheet);
            $this->assertStringContainsString('<c r="C3"><v>1200.5</v></c>', $worksheet);
            $this->assertStringContainsString('<autoFilter ref="A2:C3"/>', $worksheet);
        } finally {
            unlink($path);
        }
    }
}
