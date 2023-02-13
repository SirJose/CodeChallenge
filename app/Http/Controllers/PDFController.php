<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCPDF;

/**
 * PDF Request manager for code challenge
 */
//
class PDFController extends Controller
{

    // Generate PDF with image url
    public function thumbnail(Request $request){

        $imageURL = $request->input('image_url');
        $imageName = $request->input('image_name');

        $pdf = new TCPDF("P", "mm", "A4");

        // Set PDF title
        $pdf->SetTitle($imageName);

        // Add new page to PDF
        $pdf->AddPage();

        // Insert image to fit on page
        // See: https://tcpdf.org/docs/srcdoc/TCPDF/classes-TCPDF/#method_Image
        $pdf->Image(
            $imageURL,  // file: string
            null,       // x: float
            null,       // y: float
            0,          // w: float
            0,          // h: float
            "",         // type: string
            "",         // link: mixed
            "",         // align: string
            false,      // resize: mixed
            300,        // dpi: int
            "",         // palign: string
            false,      // ismask: bool
            false,      // imgmask: mixed
            0,          // border: mixed
            false,      // fitbox: mixed
            false,      // hidden: bool
            true        // fitonpage: bool
        );

        // Output PDF
        $pdf->Output($imageName.'.pdf');

    }

}
