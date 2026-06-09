<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    /**
     * Jana dan muat turun PDF Sijil
     */
    public function download(Certificate $certificate)
    {
        // Sijil kebiasaannya dalam orientasi Landskap (A4)
        $pdf = Pdf::loadView('pdf.certificate', compact('certificate'))->setPaper('a4', 'landscape');

        return $pdf->download("Certificate-{$certificate->type}-{$certificate->id}.pdf");
    }
}