<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Accreditation;
use Illuminate\Http\Request;

class AccreditationScannerController extends Controller
{
    /**
     * Endpoint untuk imbasan QR di pintu masuk (Gate Validation)
     */
    public function scan(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string'
        ]);

        $accreditation = Accreditation::with('accreditable')
            ->where('qr_code', $request->qr_code)
            ->first();

        if (!$accreditation) {
            return response()->json(['valid' => false, 'message' => 'QR Code tidak sah atau tidak dijumpai.'], 404);
        }

        if (!in_array($accreditation->status, ['active', 'approved', 'printed'])) {
            return response()->json(['valid' => false, 'message' => 'Akses ditolak. Status pas: ' . ucfirst($accreditation->status)], 403);
        }

        // Di sini anda boleh tambah rekod log kemasukan pintu (Gate Entry Log) jika mahu

        return response()->json(['valid' => true, 'data' => $accreditation], 200);
    }
}