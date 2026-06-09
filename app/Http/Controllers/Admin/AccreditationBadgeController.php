<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accreditation;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AccreditationBadgeController extends Controller
{
    /**
     * Jana dan muat turun PDF Badge rasmi
     */
    public function download(Accreditation $accreditation)
    {
        // Jana imej base64 QR Code
        $qrCode = base64_encode(QrCode::format('svg')->size(150)->generate($accreditation->qr_code));

        // Render PDF (Pastikan anda buat fail blade resource: resources/views/pdf/badge.blade.php)
        $pdf = Pdf::loadView('pdf.badge', compact('accreditation', 'qrCode'));

        return $pdf->download("Badge-{$accreditation->type}-{$accreditation->id}.pdf");
    }
}