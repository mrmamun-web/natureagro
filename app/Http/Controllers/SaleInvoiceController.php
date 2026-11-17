<?php

namespace App\Http\Controllers;

use App\Models\FarmSetting;
use App\Models\Sale;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleInvoiceController
{
    private const MAIN_LOGO_PATH = 'farm-logos/Nature Agro Green Horizon Logo.png';

    public function __invoke(Sale $sale): StreamedResponse
    {
        $sale->loadMissing(['customer', 'parentFlock', 'chickBatch']);
        $settings = FarmSetting::query()->first();
        $logoDataUri = null;
        $logoPath = Storage::disk('public')->exists(self::MAIN_LOGO_PATH)
            ? self::MAIN_LOGO_PATH
            : $settings?->farm_logo;

        if (filled($logoPath) && Storage::disk('public')->exists($logoPath)) {
            $mimeType = Storage::disk('public')->mimeType($logoPath);

            if (is_string($mimeType) && str_starts_with($mimeType, 'image/')) {
                $logoDataUri = 'data:'.$mimeType.';base64,'.base64_encode(
                    Storage::disk('public')->get($logoPath),
                );
            }
        }

        $productName = match ($sale->product_type) {
            Sale::PRODUCT_EGG => 'Eggs',
            Sale::PRODUCT_CHICK => 'Batch chickens',
            default => str($sale->product_type)->replace('_', ' ')->title()->toString(),
        };
        $stockSource = match ($sale->product_type) {
            Sale::PRODUCT_EGG => $sale->parentFlock?->name,
            Sale::PRODUCT_CHICK => trim(($sale->chickBatch?->batch_code ?? '').' '.($sale->chickBatch?->name ?? '')),
            default => null,
        };

        $options = new Options;
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsFontSubsettingEnabled(false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('invoices.sale', [
            'sale' => $sale,
            'settings' => $settings,
            'logoDataUri' => $logoDataUri,
            'productName' => $productName,
            'stockSource' => $stockSource,
            'currency' => $settings?->currency ?? 'BDT',
        ])->render(), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $contents = $pdf->output();
        $filename = 'invoice-'.$sale->invoice_number.'.pdf';

        return response()->stream(
            static function () use ($contents): void {
                echo $contents;
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ],
        );
    }
}
