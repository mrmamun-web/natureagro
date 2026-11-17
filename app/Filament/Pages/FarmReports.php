<?php

namespace App\Filament\Pages;

use App\Models\ChickBatch;
use App\Models\ChickBatchMovement;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\Sale;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
use App\Support\XlsxReportExporter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FarmReports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Farm Reports';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Settings';

    protected static ?string $title = 'Farm Reports & Analytics';

    protected string $view = 'filament.pages.farm-reports';

    public string $requestedStartDate = '';

    public string $requestedEndDate = '';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->requestedStartDate = now()->startOfMonth()->toDateString();
        $this->requestedEndDate = today()->toDateString();
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = today()->toDateString();
    }

    public function applyFilters(): void
    {
        $data = Validator::make([
            'requestedStartDate' => $this->requestedStartDate,
            'requestedEndDate' => $this->requestedEndDate,
        ], [
            'requestedStartDate' => ['required', 'date_format:Y-m-d'],
            'requestedEndDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:requestedStartDate'],
        ])->validate();

        $this->startDate = Carbon::parse($data['requestedStartDate'])->toDateString();
        $this->endDate = Carbon::parse($data['requestedEndDate'])->toDateString();
    }

    protected function getViewData(): array
    {
        $dates = $this->validatedDates();
        $applyDateRange = fn ($query, string $column) => $query
            ->whereDate($column, '>=', $dates['startDate'])
            ->whereDate($column, '<=', $dates['endDate']);

        $daily = fn () => $applyDateRange(FlockDailyRecord::query(), 'recorded_on');
        $chickDeaths = $applyDateRange(
            ChickBatchMovement::query()
                ->where('movement_type', ChickBatchMovement::TYPE_MORTALITY)
                ->whereHas('chickBatch', fn ($query) => $query->where('source', ChickBatch::SOURCE_HATCHED)),
            'moved_on',
        )->sum('quantity');
        $incubated = fn () => $applyDateRange(IncubationBatch::query(), 'incubated_on');
        $hatched = fn () => $applyDateRange(IncubationBatch::query()
            ->whereNotNull('actual_hatch_on'), 'actual_hatch_on');
        $sales = fn () => $applyDateRange(Sale::query(), 'sold_on');
        $transactions = fn () => $applyDateRange(FarmTransaction::query(), 'transaction_on');
        $supplyPurchases = fn () => $applyDateRange(SupplyPurchase::query(), 'purchased_on');
        $salesRevenue = (float) $sales()->sum('total_amount');
        $feedCost = (float) $supplyPurchases()
            ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_FEED))
            ->sum('total_amount');
        $medicineCost = (float) $supplyPurchases()
            ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_MEDICINE))
            ->sum('total_amount');
        $otherIncome = (float) $transactions()
            ->where('transaction_type', FarmTransaction::TYPE_INCOME)
            ->sum('amount');
        $otherExpenses = (float) $transactions()
            ->where('transaction_type', FarmTransaction::TYPE_EXPENSE)
            ->sum('amount');

        return [
            'dates' => $dates,
            'eggsCollected' => (int) $daily()->sum('eggs_collected'),
            'goodEggs' => (int) $daily()->sum('good_eggs'),
            'damagedEggs' => (int) $daily()->sum('damaged_eggs'),
            'flockDeaths' => (int) ($daily()->sum('male_mortality') + $daily()->sum('female_mortality')),
            'chickDeaths' => (int) $chickDeaths,
            'eggsSet' => (int) $incubated()->sum('eggs_set'),
            'eggsCurrentlyIncubating' => (int) IncubationBatch::query()
                ->where('status', IncubationBatch::STATUS_INCUBATING)
                ->sum('eggs_set'),
            'activeIncubationBatches' => (int) IncubationBatch::query()
                ->where('status', IncubationBatch::STATUS_INCUBATING)
                ->count(),
            'chicksHatched' => (int) $hatched()->sum('hatched_chicks'),
            'chicksSurvivedAfterSevenDays' => (int) ChickBatch::query()
                ->where('source', ChickBatch::SOURCE_HATCHED)
                ->whereDate('survival_recorded_on', '>=', $dates['startDate'])
                ->whereDate('survival_recorded_on', '<=', $dates['endDate'])
                ->sum('survived_after_7_days'),
            'failedEggs' => (int) $hatched()->sum('failed_eggs'),
            'eggSales' => (int) $sales()->where('product_type', Sale::PRODUCT_EGG)->sum('quantity'),
            'chickSales' => (int) $sales()->where('product_type', Sale::PRODUCT_CHICK)->sum('quantity'),
            'salesRevenue' => $salesRevenue,
            'salesDue' => (float) $sales()->sum('due_amount'),
            'netProfit' => round($salesRevenue - $feedCost - $medicineCost - $otherExpenses, 2),
            'otherIncome' => $otherIncome,
            'otherExpenses' => $otherExpenses,
            'feedCost' => $feedCost,
            'medicineCost' => $medicineCost,
            'supplierDues' => (float) SupplyPurchase::query()->sum('due_amount'),
        ];
    }

    public function downloadCsv(): StreamedResponse
    {
        $this->applyFilters();
        $report = $this->getViewData();
        $rows = $this->reportRows($report);

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                throw new \RuntimeException('Unable to open the report output stream.');
            }

            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }

            fclose($stream);
        }, 'nature-agro-report-'.$report['dates']['startDate'].'-to-'.$report['dates']['endDate'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadXlsx(XlsxReportExporter $exporter): BinaryFileResponse
    {
        $this->applyFilters();
        $report = $this->getViewData();
        $path = $exporter->create($this->reportRows($report));

        return response()->download(
            $path,
            'nature-agro-report-'.$report['dates']['startDate'].'-to-'.$report['dates']['endDate'].'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    public function downloadPdf(): StreamedResponse
    {
        $this->applyFilters();
        $report = $this->getViewData();
        $options = new Options;
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsFontSubsettingEnabled(false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('filament.pages.farm-report-pdf', [
            'dates' => $report['dates'],
            'rows' => $this->reportRows($report),
        ])->render(), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $contents = $pdf->output();

        return response()->streamDownload(
            static function () use ($contents): void {
                echo $contents;
            },
            'nature-agro-report-'.$report['dates']['startDate'].'-to-'.$report['dates']['endDate'].'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function reportRows(array $report): array
    {
        return [
            ['Nature Agro farm report', $report['dates']['startDate'].' to '.$report['dates']['endDate']],
            ['Category', 'Measure', 'Value'],
            ['Eggs', 'Collected', $report['eggsCollected']],
            ['Eggs', 'Good collected', $report['goodEggs']],
            ['Eggs', 'Damaged collected', $report['damagedEggs']],
            ['Incubation', 'Eggs set', $report['eggsSet']],
            ['Incubation', 'Eggs currently incubating', $report['eggsCurrentlyIncubating']],
            ['Incubation', 'Chicks hatched', $report['chicksHatched']],
            ['Incubation', 'Chicks surviving 7-day check', $report['chicksSurvivedAfterSevenDays']],
            ['Incubation', 'Failed eggs', $report['failedEggs']],
            ['Mortality', 'Parent flock birds', $report['flockDeaths']],
            ['Mortality', 'Chick batch birds', $report['chickDeaths']],
            ['Sales', 'Eggs sold', $report['eggSales']],
            ['Sales', 'Hatch-batch chickens sold', $report['chickSales']],
            ['Sales', 'Invoice revenue (BDT)', $report['salesRevenue']],
            ['Sales', 'Outstanding due on invoices (BDT)', $report['salesDue']],
            ['Feed purchases', 'Purchase total (BDT)', $report['feedCost']],
            ['Medicine purchases', 'Purchase total (BDT)', $report['medicineCost']],
            ['Suppliers', 'Current outstanding payable (BDT)', $report['supplierDues']],
            ['Other transactions', 'Other income (BDT)', $report['otherIncome']],
            ['Other transactions', 'Other expenses (BDT)', $report['otherExpenses']],
            ['Profit and loss', 'Net profit (sales - feed - medicine - other expenses) (BDT)', $report['netProfit']],
        ];
    }

    private function validatedDates(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }
}
