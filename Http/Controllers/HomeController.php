<?php

namespace App\Http\Controllers;

use COM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use URL;
use SimpleXMLElement;
use ZipArchive;
use Imagick;
use PhpOffice\PhpWord\PhpWord;
use PDF;
use NcJoes\OfficeConverter\OfficeConverter;
use PhpOffice\PhpWord\Writer\PDF\DomPDF;

class HomeController extends Controller
{



    public function test(){
        $filepath =base_path('/uploads/custom_product_file/t.pdf');

        exec("pdfinfo $filepath | grep Pages: | awk '{print $2}'");

// $converter = new OfficeConverter($filepath);
// $converter->convertTo('t.pdf'); //generates pdf file in same directory as test-file.docx
// $converter->convertTo('t.html'); //generates html file in same directory as test-file.docx

    //  /* Set the PDF Engine Renderer Path */
    //  $domPdfPath = base_path('vendor/dompdf/dompdf');
    //  \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
    //  \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

    //  /*@ Reading doc file */
    //  $template = new \PhpOffice\PhpWord\TemplateProcessor($filepath);

    //  /*@ Replacing variables in doc file */
    //  $template->setValue('date', date('d-m-Y'));
    //  $template->setValue('title', 'Mr.');
    //  $template->setValue('firstname', 'Scratch');
    //  $template->setValue('lastname', 'Coder');

    //  /*@ Save Temporary Word File With New Name */
    //  $saveDocPath = $filepath;
    //  $template->saveAs($saveDocPath);

    //  // Load temporarily create word file
    //  $Content = \PhpOffice\PhpWord\IOFactory::load($saveDocPath);

    //  //Save it into PDF
    //  $savePdfPath = public_path('new-result.pdf');

    //  /*@ If already PDF exists then delete it */
    //  if ( file_exists($savePdfPath) ) {
    //      unlink($savePdfPath);
    //  }




    }
    public function index()
    {
        $data = [];
        $data['categories']   = [];

         $data["home_banner"] = DB::selectOne("select * from banners where id = 1 ");
         $categories = DB::select("select * from categories order by id desc");
         foreach($categories as $category){
            $products = DB::select("select * from products where category = '$category->id' order by id desc");

             $data['categories'][] = array(
                 'id' => $category->id,
                 'name' => $category->name,
                 'products' => $products,
             );
         }
         $data["carts"] = DB::select("select c.id as cart_id , p.product_name  from carts as c left join products as p on c.product_id = p.id where c.user_id = '".Auth::id()."' order by c.id desc");

        $data["products"] = DB::select("select u.name , u.id ,p.id as product_id ,p.desc as product_desc , p.image as image , p.price,
         p.product_name  from products as p left join users as u on p.from = u.id where p.accepted = 1 order by p.id desc limit 6");

        return view('site.index',$data);
    }


    public function products(Request $request , $limit = 0){
        $data = [];

        $data["products"] = DB::select("select u.name , u.id ,p.id as product_id , p.product_name  from products as p left join users as u on p.user_id = u.id where p.accepted = 1  order by p.id desc");


        return $data;
    }
}
