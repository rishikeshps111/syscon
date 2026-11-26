<?php

namespace App\Support;

use App\Models\GeneratedHrLetter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

class HrLetterPdf
{
    public function response(GeneratedHrLetter $letter, string $disposition = 'attachment'): Response
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setChroot([base_path(), storage_path(), resource_path('fonts')]);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('hr-letter.pdf', ['hrLetter' => $letter])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition.'; filename="'.$letter->letter_number.'.pdf"');
    }
}
