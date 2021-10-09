<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CustomProduct;
use COM;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use SimpleXMLElement;
use ZipArchive;
use Imagick;

class CustomProductController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [];

        $files_ob = [];
        $urls = [];
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');

        $custom = DB::selectOne("select id from  custom_products where complete =  0 and user_id = '" . Auth::id() . "'");

        if (!$custom) {

            DB::insert("insert into custom_products (`user_id` , `created_at` , `updated_at`)
            VALUES ('" . Auth::id() . "' , '$created_at' , '$updated_at') ");
            $custom_product_id = DB::getPdo()->lastInsertId();
        } else {
            $custom_product_id = $custom->id;
        }
        $last_check = DB::select("select * from custom_products_files where custom_product = $custom_product_id ");
        if(!$last_check){
          DB::delete("delete from merged_files_cover where custom_product = $custom_product_id");
          DB::update("update custom_products set total = 0 where id = $custom_product_id ");
        }

        $files = DB::select("select * from custom_products_files where custom_product = $custom_product_id");
        foreach ($files as $file) {
            $url = URL::to('/') . '/uploads/custom_product_file/' . $file->file;
            $id = $file->id;
            $urls[] = $url;
            try{
                $file_size = filesize(base_path() . '/uploads/custom_product_file/' . $file->file);

            }catch(Exception $e){
$file_size = 1000;
            }
            $file_path = pathinfo(base_path() . '/uploads/custom_product_file/' . $file->file, PATHINFO_EXTENSION);

            $files_ob[] = array(
                'downloadUrl' =>  $url,
                'size' => $file_size ?? 1000,
                'width' => "120px",

                'key' => $id,
                'zoomData' => $url, // separate larger zoom data
                'downloadUrl' =>  $url,
                'type' => '' . ($file_path == "docx" || $file_path == "doc") ? 'office' : $file_path,      // check previewTypes (set it to 'other' if you want no content preview)
                'caption' => $file->preview_name, // caption
                'fileId' => $file->file,    // file identifier
                // 'url' =>  URL::to('/').'/uploads/custom_product_file/'.$input,
            );
        }



        $data["files"] = $files_ob;
        $data["urls"] = $urls;

        $data["papers_type"] = DB::select("select * from papers_type order by id desc ");
        $data["papers_size"] = DB::select("select * from papers_size order by id desc ");
        $data["papers_slice"] = DB::select("select * from papers_slice order by id desc ");
        $data["printer_color"] = DB::select("select * from printer_color order by id desc ");
        $data["printer_type"] = DB::select("select * from printer_type order by id desc ");
        $data["printer_method"] = DB::select("select * from printer_method order by id desc ");
        $data["custom_product_id"] = $custom_product_id;
        return view('site.custom_product.print', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


     public static function convert_to_pdf($filepath,$input ,$ext){

          $output_path =  base_path('/uploads/custom_product_file/');
         shell_exec("libreoffice --headless --convert-to pdf $filepath --outdir $output_path ");
//         $phpWord = new \PhpOffice\PhpWord\PhpWord();
//         $input= str_replace("docx","pdf",$input);
//         $max = 0;

//      $domPdfPath = base_path('vendor/dompdf/dompdf');
//      \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
//      \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');



// //Load word file
// $Content = \PhpOffice\PhpWord\IOFactory::load($filepath);

// //Save it into PDF
// $PDFWriter = \PhpOffice\PhpWord\IOFactory::createWriter($Content,'PDF');
// $PDFWriter->save(base_path() . '/uploads/custom_product_file/'.$input.'.pdf');
$input= CustomProductController::replace_extenstion($filepath,"pdf");

 $new_path =base_path() . '/uploads/custom_product_file/' . $input;
unlink($filepath);
return $new_path;
     }

     public static function replace_extenstion($file , $ext){
             $info = pathInfo($file);
             return $info["filename"].'.'.$ext;
     }
    public function upload_file_custom_product($id, Request $request)
    {


        $photo = $request->file('file_data');

        $fileName = $request->post('fileName');          // you receive the file name as a separate post data
        $fileId = $request->post('fileId');             // you receive the file identifier as a separate post data
        $input = '';
        if (!$photo) {
            return [
                'error' => 'خطا في التحميل'
            ];
        }
        $file_path =  $photo->getClientOriginalExtension() ?? '';
        if (!in_array($file_path, ['pptx', 'docx', 'pdf','doc'])) {
            return [
                'error' => 'خطا في التحميل'
            ];
        }
        if (isset($photo)) {
            $input = rand(0,1000) . $fileId;
            $destinationPath = base_path() . '/uploads/custom_product_file/';
            $photo->move($destinationPath, $input);
        } else {
            $input = '';
        }
        if ($file_path == "pdf") {
            $number_of_pages = $this->count_pdf_file(base_path() . '/uploads/custom_product_file/' . $input);
        }else{
             $path = $this->convert_to_pdf(base_path() . '/uploads/custom_product_file/' . $input , $input , $file_path);
$input = CustomProductController::replace_extenstion(base_path() . '/uploads/custom_product_file/' . $input ,"pdf");
            $number_of_pages = $this->count_pdf_file($path);
          }
        $number_of_pages = $number_of_pages ?? 1;
        $from = 0;
        $to = $number_of_pages;
        $file = $request->post('tmp_name'); // the path for the uploaded file chunk
        $fileSize = $request->post('fileSize');          // you receive the file size as a separate post data
        $index =  $request->post('chunkIndex');         // the current file chunk index
        $totalChunks = $request->post('chunkCount');
        $preview_name =  $request->file('file_data')->getClientOriginalName() ;
        if (DB::insert("insert into `custom_products_files`(`custom_product`,`file`,`preview_name`,`price`,`number_of_pages` ,`from` , `to`) VALUES ('$id','$input','$preview_name','0','" . $number_of_pages . "','$from','$to') ")) {
            $key =  DB::getPdo()->lastInsertId();

            return [
                "initialPreviewAsData" => true,
                "autoReplace" => false,
                'chunkIndex' => $index,
                'initialPreviewAsData' => true,
                'deleteUrl' => "/delete_file",

                // the chunk index processed
                'initialPreview' => [URL::to('/') . '/uploads/custom_product_file/' . $input], // the thumbnail preview data (e.g. image)
                'initialPreviewConfig' => [
                    [
                        'downloadUrl' => URL::to('/') . '/uploads/custom_product_file/' . $input,
                        'type' => 'pdf',      // check previewTypes (set it to 'other' if you want no content preview)
                        'caption' =>$preview_name, // caption
                        'key' => $key,       // keys for deleting/reorganizing preview
                        'fileId' => $fileId,    // file identifier
                        'size' => $fileSize , // file size
                    //  'url' =>  URL::to('/').'/uploads/custom_product_file/'.$input,
                        'zoomData' => URL::to('/') . '/uploads/custom_product_file/' . $input, // separate larger zoom data
                    ]
                ],
                'append' => true


            ];
        } else {
            return [
                'error' => 'خطا في التحميل'
            ];
        }
    }

    function PageCount_PPTX($filename)
    {
        $pageCount = 0;

        $zip = new ZipArchive();

        if ($zip->open($filename) === true) {
            if (($index = $zip->locateName('docProps/app.xml')) !== false) {
                $data = $zip->getFromIndex($index);
                $zip->close();
                $xml = new SimpleXMLElement($data);

                $pageCount = $xml->Slides;
                return $pageCount;
            }
            $zip->close();
        }

        return $pageCount;
    }
    public function get_price_preview($id, Request $request)
    {
        $total = 0;
        $custom_product =$request->post("custom_product");
        $files = DB::select("select id , price_id  , number_of_pages , custom_product from custom_products_files where id in ($id) ");
        foreach ($files as $get_file) {
            $number_of_pages = $get_file->number_of_pages;
            $from = $request->post('from') ?? 0;
            $to = $request->post('to');
            $quantity = $request->post('quantity') ?? 1;

            $get_price = DB::selectOne("select * from  `price_list` where
         `paper_id` = '" . $request->post("paper_size") . "' and

         `paper_type` = '" . $request->post("paper_type") . "'
          and `printer_type` = '" . $request->post("printer_type") . "' AND
          `printer_color` = '" . $request->post("printer_color") . "' AND
           `paper_slice` = '" . $request->post("paper_slice") . "' ");
            if ($get_price) {


                if (isset($from) && isset($to)) {
                    $number_of_pages = $to - $from;
                }
                $get_file = DB::selectOne("select * from custom_products_files where id = $get_file->id ");

                $total = $total + ((($number_of_pages * $get_price->price))) ;
            }
        }

$custom =  new CustomProductController();
$c = $custom->get_total_price($custom_product);

$total2 =$total +  $c->original["total"];
        return response()->json(['success' => '1', "total" => round($total  ,2)]);
    }
    public function set_prop($id, Request $request)
    {

        $get_file = DB::selectOne("select price_id  , number_of_pages , custom_product from custom_products_files where id = $id ");
        $number_of_pages = $get_file->number_of_pages;
        $from = $request->post('from') ?? 1;
        $to = $request->post('to');
        $quantity = $request->post('quantity') ?? 1;

        $get_price = DB::selectOne("select * from  `price_list` where
         `paper_id` = '" . $request->post("paper_size") . "' and

         `paper_type` = '" . $request->post("paper_type") . "'
          and `printer_type` = '" . $request->post("printer_type") . "' AND
          `printer_color` = '" . $request->post("printer_color") . "' AND
           `paper_slice` = '" . $request->post("paper_slice") . "' ");
        if ($get_price) {

            DB::update("update custom_products_files set `quantity`='$quantity',
                `price` = $get_price->price,  total = 0 , price_id = '$get_price->id' where id = $id");
            if (isset($from) && isset($to)) {
                $number_of_pages = ($to - $from) +1;
                DB::update("update custom_products_files set `from`='$from',
    `to` = $to , `number_of_pages`='$number_of_pages' where id = $id");
            }
            $get_file = DB::selectOne("select * from custom_products_files where id = $id ");

            $total = CustomProductController::get_file_total_without_cover_price($id);
            DB::update("update custom_products_files set  total = $total  where id = $id");
            $total_price = DB::selectOne("select SUM(total) as total_price from
                custom_products_files where custom_product = '$get_file->custom_product'");


            $prop = $this->get_file_prop($get_file->id);



            return response()->json(['success' => '1', 'prop' => $prop, 'message' =>  "تم تحديد الخصائص", "total" => round($total_price->total_price,2)]);
        }

        return response()->json(['success' => '0', 'message' =>  "لم يتم تحديد الخصائص"]);
    }
    public static function get_number_of_pages_for_cover($id){
        $get_file = DB::selectOne("select price_id,  id , number_of_pages  from  custom_products_files where id = '$id' ");
          $number_of_pages = $get_file->number_of_pages;
            $get_file_prop = DB::selectOne("select * from price_list where id = $get_file->price_id ");
            if ($get_file_prop) {
                if ($get_file_prop->printer_type == 2) {
                    $number_of_pages = $number_of_pages / 2;
                }
                $get_paper_slice = DB::selectOne("select * from papers_slice where id = $get_file_prop->paper_slice ");
                if ($get_paper_slice) {
                    $number_of_pages = $number_of_pages / $get_paper_slice->paper_count;
                }
            }
            if ($number_of_pages < 1) {
                $number_of_pages = 1;
            }
            return  $number_of_pages;
    }

    public function order_file(Request $request){
        $files = $request->post('files');

         $order = $request->post('order') ;

 $m_id = $request->post('m_id') ;
         foreach ($files as $key => $value) {

           DB::update("update cover_files_orders set `order` = '".$order[$key]."' where file = '$value' and m_id = $m_id");
         }
           return response()->json(['success' => '1', 'message' =>  " تم الترتيب "]);
    }
    public function set_cover($id, Request $request)
    {
        $cover_id = $request->post('cover_id') ?? 0;
        $custom_product = $request->post('custom_product') ?? 0;
        $merged = $request->post('cover_files_state') == 1 ? 1 : 2;
        $cover_side = $request->post('cover_side') ?? 0;
        $number_of_copies = 1;
        $num_pages_for_merged = 0;
        $get_price = DB::selectOne("select * from  `cover_type` where
         `id` = '" . $request->post("cover_id") . "' ");
$get_max= DB::selectOne("select  MAX(p.end_to) as max_number_of_pages from  `cover_type_price` as p left join cover_type as c on c.id = p.cover_id where c.id = '" . $request->post("cover_id") . "' ");

         $get_max_number_of_pages = DB::selectOne("select p.id as id , MAX(p.end_to) as max_number_of_pages from  `cover_type_price` as p left join cover_type as c on c.id = p.cover_id where c.id = '" . $request->post("cover_id") . "' and p.end_to >= $get_max->max_number_of_pages ");
        $files = DB::select("select id ,price_id , quantity,total ,number_of_pages , custom_product , price from custom_products_files where id in ($id) and custom_product = $custom_product ");
        if (!$files) {
            return response()->json(['success' => '0', 'message' =>  " خطا"]);
        }


        $total_number_of_pages = 0;



        foreach ($files as $get_file) {



            $done = 0;
            $number_of_pages =  $this->get_number_of_pages_for_cover($get_file->id);
            $num_pages_for_merged = $num_pages_for_merged +  $number_of_pages;
         $total_number_of_pages = $total_number_of_pages + $this->get_number_of_pages_for_cover($get_file->id);

            if ($merged == 2) {
  // DB::delete("delete from merged_files_cover where custom_product = $custom_product and files = '$get_file->id'");

  if($get_price->price_type == 1){
                $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id'");
}else{
	if($number_of_pages > $get_max_number_of_pages->max_number_of_pages){
		     $get_cover_price = DB::selectOne("select * from cover_type_price where id = '$get_max_number_of_pages->id'  ");


     $number_of_copies =$this->splited_files($number_of_pages ,$get_max_number_of_pages->max_number_of_pages);
}else{
	$number_of_copies = 1;
	     $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id' and $number_of_pages BETWEEN star_from and end_to");

}
}

                if ($get_cover_price) {
                    if($get_price->price_type == 1){
        $price_cover_total = $number_of_pages * $get_cover_price->price;
}else{
            $price_cover_total = $get_cover_price->price;

}

  $price_cover_total =  $price_cover_total * 	$number_of_copies;


                    DB::insert("insert into merged_files_cover (`custom_product` , `cover_id` ,`cover_side`  ,`number_of_pages`,`price`)
    VALUES ('$custom_product' , '$cover_id' ,'$cover_side' , '$number_of_pages' , '$price_cover_total')");
                    DB::insert("INSERT INTO `cover_files_orders`(`file`, `order`, `m_id`) VALUES ('$get_file->id','1','".DB::getPdo()->lastInsertId()."')");
                }else{
					        return response()->json(['success' => '0', 'message' =>  "لم يتم تحديد الخصائص"]);

				}
            }

            $done = 1;
        }

        if ($merged == 1) {
            // DB::delete("delete from merged_files_cover where custom_product = $custom_product and files = '$id'");

 $number_of_pages =  $num_pages_for_merged;
     if($get_price->price_type == 1){
                $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id'");
}else{
if($number_of_pages > $get_max_number_of_pages->max_number_of_pages){
		     $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$get_max_number_of_pages->id'  ");

     $number_of_copies =$this->splited_files($number_of_pages ,$get_max_number_of_pages->max_number_of_pages);
}else{
	$number_of_copies = 1;
	     $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id' and $number_of_pages BETWEEN star_from and end_to");

}
}

            if ($get_cover_price) {

                    if($get_price->price_type == 1){
        $price_cover_total = $number_of_pages * $get_cover_price->price;
}else{
            $price_cover_total = $get_cover_price->price;

}
if($get_price->price_type == 0){
if($number_of_pages > $get_max_number_of_pages->max_number_of_pages){
     $price_cover_total += $price_cover_total * $this->splited_files($number_of_pages ,$get_max_number_of_pages->max_number_of_pages);
}
}

            $price_cover_total =  $price_cover_total * 	$number_of_copies;

                DB::insert("insert into merged_files_cover (`custom_product` , `cover_id` ,`number_of_pages`,`price`)
           VALUES ('$custom_product' , '$cover_id' , '$total_number_of_pages' , '$price_cover_total')");
                $m_id = DB::getPdo()->lastInsertId();
                $i = 1;
                foreach (explode(",",$id) as $value) {
                    DB::insert("INSERT INTO `cover_files_orders`(`file`, `order`, `m_id`) VALUES (' $value','$i','".$m_id."')");
                    $i += 1;
                }
            }else{
					        return response()->json(['success' => '0', 'message' =>  "لم يتم تحديد الخصائص"]);

				}
        }



        if ($done) {
            // $total_price = DB::selectOne("select SUM(total) as total_price from
            //     custom_products_files where custom_product = '$get_file->custom_product'");
            // $cover_total_price = DB::selectOne("select SUM(price) as total_price from
            //     merged_files_cover where custom_product = '$get_file->custom_product'");
            // $total = ($total_price ? $total_price->total_price : 0) + ($cover_total_price ? $cover_total_price->total_price : 0);

            return response()->json(['success' => '1', 'message' =>  "تم تحديد الخصائص",  ]);
        }

        return response()->json(['success' => '0', 'message' =>  "لم يتم تحديد الخصائص", ]);
    }
    public static function get_file_total($file_id)
    {
        $total = 0;
        $get_file = DB::selectOne("select* from custom_products_files where id = $file_id ");
        if ($get_file) {
            $total = ((($get_file->number_of_pages * $get_file->price) + $get_file->cover_price) * $get_file->quantity);
        }
        return round($total,2);
    }
    public static function get_file_total_without_cover_price($file_id)
    {
        $total = 0;
        $get_file = DB::selectOne("select* from custom_products_files where id = $file_id ");
        if ($get_file) {
            $get_price = DB::selectOne("select * from  `price_list` where id = $get_file->price_id");

            $total = ($get_file->number_of_pages * $get_file->price);
        }
        return round($total,2);
    }
    public function has_per($custom_product_id)
    {
        $check = DB::selectOne("select * from custom_products where `id` = '$custom_product_id' and `user_id` = '" . Auth::id() . "'");
        if ($check) {
            return true;
        }
        return false;
    }

    public static function get_file_prop($file_id)
    {
        $prop = 'لم يتم تحديد الخصائص بعد';
        $get_file = DB::selectOne("select price_id from custom_products_files where id = $file_id");
        $get_file_prop = DB::selectOne("select * from price_list where id = $get_file->price_id");
        if ($get_file_prop) {
            $paper_type = DB::selectOne("select * from papers_type where id = $get_file_prop->paper_type");
            if ($paper_type) {
                $paper_type_name = $paper_type->name;
            } else {
                $paper_type_name = '';
            }
            $paper_size = DB::selectOne("select * from papers_size where id = $get_file_prop->paper_id");
            if ($paper_size) {
                $paper_size_name = $paper_size->name;
            } else {
                $paper_size_name = '';
            }
            $printer_color = DB::selectOne("select * from printer_color where id = $get_file_prop->printer_color");
            if ($printer_color) {
                $printer_color_name = $printer_color->name;
            } else {
                $printer_color_name = '';
            }
            $printer_method = DB::selectOne("select * from printer_method where id = $get_file_prop->printer_method");
            if ($printer_method) {
                $printer_method_name = $printer_method->name;
            } else {
                $printer_method_name = '';
            }
            $paper_slice = DB::selectOne("select * from papers_slice where id = $get_file_prop->paper_slice");
            if ($paper_slice) {
                $paper_slice_name = $paper_slice->name;
            } else {
                $paper_slice_name = '';
            }
            $printer_type = DB::selectOne("select * from printer_type where id = $get_file_prop->printer_type");
            if ($printer_type) {
                $printer_type_name = $printer_type->name;
            } else {
                $printer_type_name = '';
            }

            $prop = $paper_size_name . '-' . $paper_type_name . '-' . $printer_color_name . '-' . $printer_method_name . '-' . $printer_type_name . '-' . $paper_slice_name;
        }

        return $prop;
    }

    public static function copy_file($file, $new_file)
    {
        try{
            if (!copy($file, $new_file)) {
                return false;
            }
            return true;
        }catch(Exception $e){
            return false;
        }

    }

    public function split_file($id, Request $request)
    {
        $from = $request->post('from') ?? 1;
        $to = $request->post('to');
        $number_of_pages = 0;
        $data  = [];
        $data["from"] = $from;
      $merged = $request->post('merge');
        $file = DB::selectOne("select * from custom_products_files where id = $id");
        if ($file) {
            if (count($from) > 1) {
                $num_for_merged_files = 0;
                if ($merged == 1) {
                    foreach ($from as $key => $value) {
                        $number_of_pages =   ($to[$key] - $from[$key]) +1;
                        $number_of_pages = $number_of_pages ?? 1;
                        $num_for_merged_files = $num_for_merged_files + $number_of_pages;
                        DB::insert("insert into  `files_parts` (
                    `file_id` ,`from` ,`to`)
                   VALUES ('$file->id','$from[$key]','$to[$key]') ");
                    }
                    DB::update("update custom_products_files set number_of_pages = $num_for_merged_files  where id = $id");
                } else {
                    $file_to_copy = base_path().'/uploads/custom_product_file/'.$file->file;

                    foreach ($from as $key => $value) {
                        $new_file = rand(0,1000).$file->file;
                        $new_file_path = base_path().'/uploads/custom_product_file/'.$new_file;
                        $number_of_pages =   ($to[$key] - $from[$key]) + 1;
                        $number_of_pages = $number_of_pages ?? 1;

                        DB::insert("insert into  `custom_products_files` (
                    `custom_product` ,`file` ,`price` ,
                    `number_of_pages` ,`from` ,`to`,`parent`,`preview_name`)
                   VALUES ('$file->custom_product','$new_file','$file->price','" . $number_of_pages . "','$from[$key]','$to[$key]','$id','". substr(stristr($file->file, "_"),1)."') ");
                   $file_id =DB::getPdo()->lastInsertId();

                   if(!CustomProductController::copy_file($file_to_copy ,$new_file_path)){
                    DB::delete("delete  from custom_products_files where id =  $file_id");
                   }
                    }
                    DB::delete("delete  from custom_products_files where id =  $id");

                }
            } else {
                $number_of_pages =  ( $to[0] - $from[0])+1;
                $number_of_pages = $number_of_pages ?? 1;
                DB::insert("update  `custom_products_files` set
                `custom_product` ='$file->custom_product' ,`file` = '$file->file',`price` ='$file->price' ,
                `number_of_pages` = '" . $number_of_pages . "' ,
                `from` = '$from[0]' ,`to` = '$to[0]' where `id` = '$id'
             ");
            }



            return response()->json(['success' => '1', 'message' =>  "تم تقسيم الملف", 'data' => $data]);
        }

        return response()->json(['success' => '0', 'message' =>  "حدث خطأ", 'data' => $data]);
    }

    public function delete_file(Request $request)
    {
        $custom_product_id = $request->post('custom_product');
        $file_id = $request->post('key');
        $data = [];
        $message = 'تم حذف الملف';

        if (!$this->has_per($custom_product_id)) {
            return response()->json(['success' => '0', 'message' =>  "خطا"]);
        }
        $get_file = DB::selectOne("select * from  custom_products_files  where custom_product = $custom_product_id and id = $file_id");
        if (!$get_file) {
            return response()->json(['success' => '0', 'message' => 'خطا', 'data' =>  $data]);
        }
        $new_arr = [];
        $files_covers = DB::select("select * from cover_files_orders where file =  $file_id");
        foreach($files_covers as $fc){
           $get_cover_file = DB::selectOne("select * from merged_files_cover where id = '$fc->m_id' ");

if($get_cover_file){
   $get_cover_prop = DB::selectOne("select * from cover_type  where id = '$get_cover_file->cover_id' ");


           DB::update("update merged_files_cover set number_of_pages = (number_of_pages - '$get_file->number_of_pages')  where id = $get_cover_file->id");

                $number_of_pages= $get_cover_file->number_of_pages - $get_file->number_of_pages;
                if($number_of_pages > 0){
             if($get_cover_prop->price_type == 1){
               $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$get_cover_file->cover_id' ");
               $price = $number_of_pages * $get_cover_price->price;
   }else{
     $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$get_cover_file->cover_id' and '$number_of_pages' BETWEEN star_from and end_to");
     $price = $get_cover_price->price;
   }



                if($get_cover_price){

                    DB::update("update merged_files_cover  set  number_of_pages = '$number_of_pages' , `price` = '".$price."' where id = $get_cover_file->id");
                }

                }


DB::delete("delete from cover_files_orders where file = $file_id");
}else{
         //   return response()->json(['success' => '0', 'message' => 'خطا', 'data' =>  $data]);

}
        }
        DB::delete("delete from custom_products_files where custom_product = $custom_product_id and id = $file_id");

        try{
            unlink(base_path() . '/uploads/custom_product_file/' . $get_file->file);

        }catch(Exception $e){
          $message = $e->getMessage();
        }

        $last_check = DB::select("select * from custom_products_files where custom_product = $custom_product_id ");
         if(!$last_check){
           DB::delete("delete from merged_files_cover where custom_product = $custom_product_id");
           DB::update("update custom_products set total = 0 where id = $custom_product_id ");
         }

        $custom = new CustomProductController();
        $custom->get_total_price($custom_product_id);
        return response()->json(['success' => '1', 'message' => $message]);

    }
    // must
    public function delete_cover(Request $request)
    {
        $data  = [];
        $custom_product_id = $request->post('custom_product');

        if (!$this->has_per($custom_product_id)) {
            return response()->json(['success' => '0', 'message' =>  "خطا"]);
        }
        $cover_id = $request->post('cover_id');
        $get_file = DB::selectOne("select * from  merged_files_cover  where  id = $cover_id and custom_product = $custom_product_id");
    if($get_file){
          DB::update("update  custom_products set total = (total - $get_file->price) where id  = $custom_product_id ");
    }



      DB::delete("delete from merged_files_cover where id =$cover_id");
 DB::delete("delete from cover_files_orders where m_id =$cover_id");

        $data["covers"] = [];
$covers = DB::select("select m.price ,c.name , m.cover_id , c.photo,m.custom_product  , m.id as m_cover_id from cover_type as c left join merged_files_cover as m on c.id = m.cover_id  where m.custom_product = $custom_product_id  ");

        foreach ($covers as $cover) {


             $files = DB::select("select * from `custom_products_files` as cf left join cover_files_orders as co on co.file = cf.id  where co.m_id = $cover->m_cover_id order by co.order ");
            $filesarray = '';

            foreach ($files as $file) {
                $prop = $this->get_file_prop($file->file);
                $filesarray  .= '<div class="flex-container">
             <div class="accordion">
                 <a class="collapsed h4" data-bs-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapseExample">' . $file->preview_name . '</a>
                 <div class="collapse" id="collapse1">
                     ' . $prop . '
                 </div>
             </div>
             <input type="number" value="'.$file->order.'" name="order_'.$file->m_id.'[]" m_id="'.$file->m_id.'" class="number order order_values_'.$file->m_id.'"/>
             <input type="hidden" name="files_cov_'.$file->m_id.'[]" class="files_cov_'.$file->m_id.'" value="'.$file->file.'"
            <button class="edit_file_cover" file_id="' . $file->file . '" type="button"><i class="fas fa-edit "></i></button>
             <button class="delete delete_file" file_id="' . $file->file . '" type="button">
                 <i class="fas fa-trash-alt " ></i>
             </button>
         </div>

         ';
            }
               if($filesarray != ''){
                   $data["covers"][] = array(
                'id' => $cover->m_cover_id,
                'name' => $cover->name,
                'photo' => '/uploads/cover_type/' . $cover->photo,

                'files' => $filesarray,
            );
            }
        }
        $custom = new CustomProductController();
        $custom->get_total_price($custom_product_id);

        return response()->json(['success' => '1', 'message' =>  " تم الحذف  ", 'data' => $data]);
    }
    function get_num_pages_docx($filename)
    {
        $zip = new ZipArchive();
        if ($zip->open($filename) === true) {
            if (($index = $zip->locateName('docProps/app.xml')) !== false) {
                $data = $zip->getFromIndex($index);

                $xml = new SimpleXMLElement($data);

                return $xml->Pages;
            }

            $zip->close();
        }

        return false;
    }
    // function count_pdf_file($path) {
    //     $pdf = file_get_contents($path);
    //     $number = preg_match_all("/\/Page\W/", $pdf, $dummy);
    //     return $number;
    //   }
    function count_pdf_file($filepath)
    {
        // $fp = @fopen(preg_replace("/\[(.*?)\]/i", "", $filepath), "r");
        $max = 0;

            $max =  exec("pdfinfo $filepath | grep Pages: | awk '{print $2}'");

        return $max;
    }
  public static function get_cover_file(){
    $cover_cover_file = DB::select("select * custom custom_products_files as c left join cover_files_orders as cf on cf.file = c.id where cf.m_id in ($m_id) from ");
  }

    public function get_uploaded_files($id, Request $request)
    {
        $data = [];
        $data["files"] = [];
        $files = DB::select("select * from `custom_products_files` where custom_product = $id ");
        foreach ($files as $file) {
            $prop = $this->get_file_prop($file->id);


            $data["files"][] = array(
                'number_of_pages' => $file->number_of_pages,
                'file' => $file->preview_name,
                'id' => $file->id,
                'price' => $file->price,
                'prop' => $prop,
                'price_id' => $file->price_id,

                'quantity' => $file->quantity,


            );
        }
        $data["covers"] = [];
        $covers = DB::select("select m.price ,c.name , m.cover_id , c.photo,m.custom_product  , m.id as m_cover_id from cover_type as c left join merged_files_cover as m on c.id = m.cover_id  where m.custom_product = $id  ");

        foreach ($covers as $cover) {


                   $files = DB::select("select * from `custom_products_files` as cf left join cover_files_orders as co on co.file = cf.id  where co.m_id = $cover->m_cover_id order by co.order ");
            $filesarray = '';

            // <td class="no-padding" colspan="3">
            //                                 <div class="flex-container">
            //                                     <div class="accordion">
            //                                         <a class="collapsed h4" data-bs-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapseExample">ملف رقم 1</a>
            //                                         <div class="collapse" id="collapse3">
            //                                             ألوان - A4 - ورق عادي
            //                                             <br> تغليف بلاستيك- وجهين
            //                                         </div>
            //                                     </div>
            //                                     <input class="form-control" type="text" placeholder="الترتيب" value="1" style="max-width: 70px;text-align: center;">
            //                                     <div class="buttons">
            //                                         <button class="delete">
            //                                             <i class="fas fa-edit"></i>
            //                                         </button>
            //                                         <button class="delete">
            //                                             <i class="fas fa-trash-alt"></i>
            //                                         </button>
            //                                     </div>
            //                                 </div>
            //                             </td>
            foreach ($files as $file) {
                $prop = $this->get_file_prop($file->file);
                $filesarray  .= '<div class="flex-container">
                <input type="number" min="1" file_id="'. $file->file .'" class="number quantity  quantity' . $file->file .
                '" value="' . $file->quantity .
                '">
             <div class="accordion">

                 <a class="collapsed h4" data-bs-toggle="collapse" href="#collapse'.$file->file.'" role="button" aria-expanded="false" aria-controls="collapseExample">' . $file->preview_name . ' </a>
                 <div class="collapse" id="collapse'.$file->file.'">
                     ' . $prop . '
                 </div>

             </div>
             <input type="number" placeholder="الترتيب" style="max-width: 70px;text-align: center;" value="'.$file->order.'"  m_id="'.$file->m_id.'" class=" order order_values_'.$file->m_id.'"/>
             <input type="hidden" class="files_cov_'.$file->m_id.'" value="'.$file->file.'" />
                                                <div class="buttons">

            <button class="edit_file_cover edit" file_id="' . $file->file . '" type="button"><i class="fas fa-edit "></i></button>
             <button class="delete delete_file" file_id="' . $file->file . '" type="button">
                 <i class="fas fa-trash-alt " ></i>
             </button>
             </div>
         </div>

         ';
            }
           if($filesarray != ''){
                   $data["covers"][] = array(
                'id' => $cover->m_cover_id,
                'name' => $cover->name,
                'photo' => '/uploads/cover_type/' . $cover->photo,

                'files' => $filesarray,
            );
            }
        }


        return response()->json(['success' => '1', 'data' =>  $data]);
    }
    public function get_paper_type_prop($id, $paper_type)
    {
        $data  = [];
        $get_paper = DB::selectOne("select * from papers_size where id = $id");
        if (!$get_paper) {
            return response()->json(['success' => '0', 'data' =>  $data]);
        }
        $get_file_prop = DB::select("select * from price_list where paper_id = $id and paper_type =$paper_type ");
        if ($get_file_prop && $get_paper) {
            $papers_type_id = [];
            $printer_color_id = [];
            $printer_method_id = [];
            $paper_slice_id = [];
            $printer_type_id = [];
            foreach ($get_file_prop as $p) {
                $papers_type_id[] = $p->paper_type;
                $printer_color_id[] = $p->printer_color;
                $printer_method_id[] = $p->printer_method;
                $paper_slice_id[] = $p->paper_slice;
                $printer_type_id[] = $p->printer_type;
            }
            $papers_type_id = implode(",", $papers_type_id);
            $printer_color_id = implode(",", $printer_color_id);
            $printer_method_id = implode(",", $printer_method_id);
            $paper_slice_id = implode(",", $paper_slice_id);
            $printer_type_id = implode(",", $printer_type_id);
            $data["printer_color"] = DB::select("select * from printer_color   where id in ($printer_color_id) ");
            $data["printer_method"] = DB::select("select * from printer_method where id in ($printer_method_id) ");
            $data["paper_slice"] = DB::select("select * from papers_slice  where id in ($paper_slice_id) ");
            $data["printer_type"] = DB::select("select * from printer_type where id in ($printer_type_id) ");

            return response()->json(['success' => '1', 'data' =>  $data]);
        }

        return response()->json(['success' => '0', 'data' =>  $data]);
    }
public function get_covers(Request $request , $file_id){
  $data  = [];
        $get_file = DB::selectOne("select * from custom_products_files where id = $file_id");
        if (!$get_file) {
            return response()->json(['success' => '0', 'data' =>  $data]);
        }
        $get_file_prop = DB::selectOne("select * from price_list where id = $get_file->price_id");

        if ($get_file_prop) {

     $get_paper = DB::selectOne("select * from papers_size where id = $get_file_prop->paper_id");

            $data["covers"] = DB::select("select * from cover_type where id in ($get_paper->covers) order by id desc ");

            return response()->json(['success' => '1', 'data' =>  $data]);
        }

        return response()->json(['success' => '0', 'data' =>  $data]);
}
    public function get_prop($id)
    {

        $data  = [];
        $get_paper = DB::selectOne("select * from papers_size where id = $id");
        if (!$get_paper) {
            return response()->json(['success' => '0', 'data' =>  $data]);
        }
        $get_file_prop = DB::select("select * from price_list where paper_id = $id");
        if ($get_file_prop && $get_paper) {
            $papers_type_id = [];
            $printer_color_id = [];
            $printer_method_id = [];
            $paper_slice_id = [];
            $printer_type_id = [];
            foreach ($get_file_prop as $p) {
                $papers_type_id[] = $p->paper_type;
                $printer_color_id[] = $p->printer_color;
                $printer_method_id[] = $p->printer_method;
                $paper_slice_id[] = $p->paper_slice;
                $printer_type_id[] = $p->printer_type;
            }
            $papers_type_id = implode(",", $papers_type_id);
            $printer_color_id = implode(",", $printer_color_id);
            $printer_method_id = implode(",", $printer_method_id);
            $paper_slice_id = implode(",", $paper_slice_id);
            $printer_type_id = implode(",", $printer_type_id);
            $data["paper_type"] = DB::select("select * from papers_type  where id in ($papers_type_id) ");
            $data["printer_color"] = DB::select("select * from printer_color   where id in ($printer_color_id) ");
            $data["printer_method"] = DB::select("select * from printer_method where id in ($get_paper->printer_method) ");
            $data["paper_slice"] = DB::select("select * from papers_slice  where id in ($paper_slice_id) ");
            $data["printer_type"] = DB::select("select * from printer_type where id in ($printer_type_id) ");
            $data["covers"] = DB::select("select * from cover_type where id in ($get_paper->covers) order by id desc ");

            return response()->json(['success' => '1', 'data' =>  $data]);
        }

        return response()->json(['success' => '0', 'data' =>  $data]);
    }
    public function store(Request $request)
    {

        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'papers_type' => 'required',
            'papers_size' => 'required',
            'papers_slice' => 'required',
            'printer_color' => 'required',
            'printer_type' => 'required',
            'printer_method' => 'required',
            'cover_type' => 'required',
            'from' => 'required',
            'to' => 'required',
            'file' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'papers_type.required' => __('public.filed_required'),
            'papers_size.required' => __('public.filed_required'),
            'papers_slice.required' => __('public.filed_required'),
            'printer_color.required' => __('public.filed_required'),
            'printer_type.required' => __('public.filed_required'),
            'printer_method.required' => __('public.filed_required'),
            'cover_type.required' => __('public.filed_required'),
            'from.required' => __('public.filed_required'),
            'to.required' => __('public.filed_required'),
            'file.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $userId = Auth::id();
        $papers_type = $request->post('papers_type');
        $papers_size = $request->post('papers_size');
        $papers_slice = $request->post('papers_slice');
        $printer_color = $request->post('printer_color');
        $printer_type = $request->post('printer_type');
        $printer_method = $request->post('printer_method');
        $cover_type = $request->post('cover_type');
        $from = $request->post('from');
        $to = $request->post('to');
        $price_id = $request->post('price_id');
        $file = $request->file('file');
        $input = '';
        if ($file) {
            $input = time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/custom_products/';
            $file->move($destinationPath, $input);
        } else {
            $input = $request->post('old_photo');
        }
        DB::insert("insert into `custom_products` (`user_id`,`papers_type`,
        `papers_size`,`papers_slice`,`printer_color`,`printer_type`,
        `printer_method`,`cover_type`,`from`,`to`,`price_id`,`file`)
         VALUES ('$userId','$papers_type','$papers_size','$papers_slice','$printer_color','$printer_method',
         '$cover_type','$from','$to','$price_id','$input')");
        // add to the cart
        $productId =  DB::getPdo()->lastInsertId();
        $quantity = 1;
        $ip = $request->ip();
        $type = 1; // this for the printed files

        DB::insert("insert into carts (`user_id`,`product_id`,`quantity`,`ip` , `type`)
         VALUES ('$userId','$productId','$quantity','$ip' ,'$type') ");
    }

    public function create_sticker(Request $request)
    {
        $data  = [];
        $data["stickers_paper_shape"] = DB::select("select * from stickers_paper_shape order by id desc");
        $data["stickers_paper_size"] = DB::select("select * from stickers_paper_size order by id desc");
        $data["stickers_paper_type"] = DB::select("select * from stickers_paper_type order by id desc");

        return view('site.custom_product.add_sticker', $data);
    }


    public function get_sticker_price(Request $request)
    {
        $shape = $request->post('shape');
        $type = $request->post('type');
        $size = $request->post('size');

        // getting the price
        $prices = DB::selectOne("select * from stickers_paper_prices where `paper_type` = '$type' and `paper_size` = '$size' and `paper_shape` = '$shape'");
        if ($prices) {
            return response()->json(['success' => '1', 'price' => $prices->price, 'message' =>  '', 'price_id' => $prices->id]);
        }

        return response()->json(['success' => '0', 'message' => 'لا يوجد سعر متاح']);
    }

    public function get_poster_price(Request $request)
    {

        $size = $request->post('size');

        // getting the price
        $prices = DB::selectOne("select * from posters_size where `id` = '$size' ");
        if ($prices) {
            return response()->json(['success' => '1', 'price' => $prices->price, 'message' =>  '', 'price_id' => $prices->id]);
        }

        return response()->json(['success' => '0', 'message' => 'لا يوجد سعر متاح']);
    }
    public function get_rollup_price(Request $request)
    {

        $size = $request->post('size');

        // getting the price
        $prices = DB::selectOne("select * from rollups_size where `id` = '$size' ");
        if ($prices) {
            return response()->json(['success' => '1', 'price' => $prices->price, 'message' =>  '', 'price_id' => $prices->id]);
        }

        return response()->json(['success' => '0', 'message' => 'لا يوجد سعر متاح']);
    }

    public function get_personal_card_price(Request $request)
    {

        $type = $request->post('type');
        $size = $request->post('size');

        // getting the price
        $prices = DB::selectOne("select * from personal_cards_prices where `card_type` = '$type' and `card_size` = '$size'");
        if ($prices) {
            return response()->json(['success' => '1', 'price' => $prices->price, 'message' =>  '', 'price_id' => $prices->id]);
        }

        return response()->json(['success' => '0', 'message' => 'لا يوجد سعر متاح']);
    }

    public function add_sticker_product(Request $request)
    {

        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'price_id' => 'required',

            'file' => 'required',


        ];
        $rules_messages = [
            'price_id.required' => __('public.filed_required'),

            'file.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $userId = Auth::id();
        $quantity = $request->post('quantity');
        $note = $request->post('note');

        $price_id = $request->post('price_id');
        $file = $request->file('file');
        $input = '';
        if ($file) {
            $input = time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/stickers/';
            $file->move($destinationPath, $input);
        } else {
            $input = $request->post('old_photo');
        }
        DB::insert("insert into `stickers_products` (`user_id`,`price_id`,`file`,`quantity`,`note`,`created_at` , `updated_at`)
         VALUES ('$userId','$price_id','$input','$quantity','$note','$created_at','$updated_at')");

        $productId =  DB::getPdo()->lastInsertId();
        $ip = $request->ip();
        $type = 2; // this for the stickers

        DB::insert("insert into carts (`user_id`,`product_id`,`quantity`,`ip` , `type`)
         VALUES ('$userId','$productId','$quantity','$ip' ,'$type') ");
    }


    public function create_personal_card(Request $request)
    {
        $data  = [];
        $data["personal_cards_size"] = DB::select("select * from personal_cards_size order by id desc");
        $data["personal_cards_type"] = DB::select("select * from personal_cards_type order by id desc");
        dd($data);

        return view('site.custom_product.add_personal_card', $data);
    }

    public function add_personal_card_product(Request $request)
    {

        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'price_id' => 'required',

            'file' => 'required',


        ];
        $rules_messages = [
            'price_id.required' => __('public.filed_required'),

            'file.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $userId = Auth::id();
        $quantity = $request->post('quantity');
        $note = $request->post('note');

        $price_id = $request->post('price_id');
        $file = $request->file('file');
        $input = '';
        if ($file) {
            $input = time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/personal_cards_products/';
            $file->move($destinationPath, $input);
        } else {
            $input = $request->post('old_photo');
        }
        DB::insert("insert into `personal_cards_products` (`user_id`,`price_id`,`file`,`quantity`,`note`,`created_at` , `updated_at`)
         VALUES ('$userId','$price_id','$input','$quantity','$note','$created_at','$updated_at')");


        $productId =  DB::getPdo()->lastInsertId();
        $ip = $request->ip();
        $type = 3; // this for the personal card

        DB::insert("insert into carts (`user_id`,`product_id`,`quantity`,`ip` , `type`)
         VALUES ('$userId','$productId','$quantity','$ip' ,'$type') ");
    }


    public function create_rollup(Request $request)
    {
        $data  = [];
        $data["rollups_size"] = DB::select("select * from rollups_size  order by id desc");
        dd($data);

        return view('site.custom_product.add_rollup', $data);
    }

    public function add_rollup_product(Request $request)
    {

        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'price_id' => 'required',

            'file' => 'required',


        ];
        $rules_messages = [
            'price_id.required' => __('public.filed_required'),

            'file.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $userId = Auth::id();
        $quantity = $request->post('quantity');
        $note = $request->post('note');

        $price_id = $request->post('price_id');
        $file = $request->file('file');
        $input = '';
        if ($file) {
            $input = time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/rollups_products/';
            $file->move($destinationPath, $input);
        } else {
            $input = $request->post('old_photo');
        }
        DB::insert("insert into `rollups_products` (`user_id`,`price_id`,`file`,`quantity`,`note`,`created_at` , `updated_at`)
         VALUES ('$userId','$price_id','$input','$quantity','$note','$created_at','$updated_at')");

        $productId =  DB::getPdo()->lastInsertId();
        $ip = $request->ip();
        $type = 4; // this for the rollup

        DB::insert("insert into carts (`user_id`,`product_id`,`quantity`,`ip` , `type`)
         VALUES ('$userId','$productId','$quantity','$ip' ,'$type') ");
    }



    public function create_poster(Request $request)
    {
        $data  = [];
        $data["posters_size"] = DB::select("select * from  posters_size order by id desc");
        dd($data);
        return view('site.custom_product.add_poster', $data);
    }
    public function add_poster_product(Request $request)
    {

        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'price_id' => 'required',

            'file' => 'required',


        ];
        $rules_messages = [
            'price_id.required' => __('public.filed_required'),

            'file.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $userId = Auth::id();
        $quantity = $request->post('quantity');
        $note = $request->post('note');

        $price_id = $request->post('price_id');
        $file = $request->file('file');
        $input = '';
        if ($file) {
            $input = time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/posters_products/';
            $file->move($destinationPath, $input);
        } else {
            $input = $request->post('old_photo');
        }
        DB::insert("insert into `posters_products` (`user_id`,`price_id`,`file`,`quantity`,`note`,`created_at` , `updated_at`)
         VALUES ('$userId','$price_id','$input','$quantity','$note','$created_at','$updated_at')");
        $productId =  DB::getPdo()->lastInsertId();
        $ip = $request->ip();
        $type = 5; // this for the poster

        DB::insert("insert into carts (`user_id`,`product_id`,`quantity`,`ip` , `type`)
         VALUES ('$userId','$productId','$quantity','$ip' ,'$type') ");
    }
    function get_num_pages_doc($filename)
    {
        $handle = fopen($filename, 'r');
        $line = @fread($handle, filesize($filename));

        echo '<div style="font-family: courier new;">';

        $hex = bin2hex($line);
        $hex_array = str_split($hex, 4);
        $i = 0;
        $line = 0;
        $collection = '';
        foreach ($hex_array as $key => $string) {
            $collection .= $this->hex_ascii($string);
            $i++;

            if ($i == 1) {
                echo '<b>' . sprintf('%05X', $line) . '0:</b> ';
            }

            echo strtoupper($string) . ' ';

            if ($i == 8) {
                echo ' ' . $collection . ' <br />' . "\n";
                $collection = '';
                $i = 0;

                $line += 1;
            }
        }

        echo '</div>';

        exit();
    }

    function hex_ascii($string, $html_safe = true)
    {
        $return = '';

        $conv = array($string);
        if (strlen($string) > 2) {
            $conv = str_split($string, 2);
        }

        foreach ($conv as $string) {
            $num = hexdec($string);

            $ascii = '.';
            if ($num > 32) {
                $ascii = $this->unichr($num);
            }

            if ($html_safe and ($num == 62 or $num == 60)) {
                $return .= htmlentities($ascii);
            } else {
                $return .= $ascii;
            }
        }

        return $return;
    }


    public function add_custom_product_to_cart(Request $request)
    {
        $id = $request->post('product_id');
        $check = DB::selectOne("select COUNT(id) as t from custom_products_files where custom_product = $id and price_id = 0");
        if ($check->t > 0) {
            return response()->json(['success' => '0', 'message' => 'هناك ملفات لم يتم تحديد الخصائص لها']);
        }
        $check = DB::selectOne("select COUNT(id) as t from custom_products_files where custom_product = $id ");

 $check2 = DB::selectOne("select COUNT(co.id) as t from merged_files_cover as m left join cover_files_orders as co on co.m_id = m.id   where m.custom_product = $id ");
        if (!$check ) {
            return response()->json(['success' => '0', 'message' => 'هناك ملفات لم يتم تحديد الغلاف لها']);
        }
        if ($check->t != $check2->t ) {
            return response()->json(['success' => '0', 'message' => 'هناك ملفات لم يتم تحديد الغلاف لها']);
        }
        $cart = new CartController();
        $json = $cart->store($request);

        $res = $json->original;

        if ($res["success"] == "1") {
            return response()->json(['success' => '1']);
        } else {
            return response()->json(['success' => '0', 'message' => $res["data"]["message"]]);
        }
    }


    public function get_total_price($custom_product_id)
    {
        $custom_product = DB::selectOne("select `id` from custom_products where id = $custom_product_id");

        if (!$custom_product) {
            return response()->json(['success' => '0']);
        }
        $total = 0;
        $files = DB::selectOne("select SUM(total) as total , quantity from custom_products_files where custom_product  = $custom_product->id");

        if (!$custom_product) {
            return response()->json(['success' => '1', 'total' => $total]);
        }

        $total = $total + $files->total;

        $cover_price = DB::selectOne("select SUM(price) as total from merged_files_cover where custom_product  = $custom_product->id");

        $total = $total + $cover_price->total;

        $total = $total * $files->quantity;
        $total = round($total , 2);
        DB::update("update custom_products set total = $total where id = $custom_product->id");


        return response()->json(['success' => '1', 'total' => round($total,2)]);
    }

    public static function update_quantity(Request $request){
        $file_id = $request->post('file_id');
         $q = $request->post('q') ?? 1;
        $file =  DB::selectOne("select * from custom_products_files where id  = '$file_id' ");
        if($file){
           DB::update("update custom_products_files set quantity =  '$q' where id = '$file->id'");
                 return response()->json(['success' => '1']);
        }
                         return response()->json(['success' => '0']);

    }
    public static function splited_files($number_of_pages , $max){

        $number_of_cp = $number_of_pages / $max ;

        return $number_of_cp;

    }
    public  function get_cover_price_preview(Request $request , $id){
$cover_id = $request->post('cover_id') ?? 0;
        $custom_product = $request->post('custom_product') ?? 0;
        $merged = $request->post('cover_files_state') == 1 ? 1 : 2;
        $cover_side = $request->post('cover_side') ?? 0;
        $number_of_copies = 1;
        $get_price = DB::selectOne("select * from  `cover_type` where
         `id` = '" . $request->post("cover_id") . "' ");
$get_max= DB::selectOne("select  MAX(p.end_to) as max_number_of_pages from  `cover_type_price` as p left join cover_type as c on c.id = p.cover_id where c.id = '" . $request->post("cover_id") . "' ");

         $get_max_number_of_pages = DB::selectOne("select p.id as id , MAX(p.end_to) as max_number_of_pages from  `cover_type_price` as p left join cover_type as c on c.id = p.cover_id where c.id = '" . $request->post("cover_id") . "' and p.end_to >= $get_max->max_number_of_pages ");
        $files = DB::select("select id ,price_id , quantity,total ,number_of_pages , custom_product , price from custom_products_files where id in ($id) and custom_product = $custom_product ");
        if (!$files) {
            return response()->json(['success' => '0', 'message' =>  " خطا"]);
        }


        $total_number_of_pages = 0;



        foreach ($files as $get_file) {



            $done = 0;
            $number_of_pages =  $this->get_number_of_pages_for_cover($get_file->id);
         $total_number_of_pages = $total_number_of_pages + $this->get_number_of_pages_for_cover($get_file->id);

            if ($merged == 2) {
  // DB::delete("delete from merged_files_cover where custom_product = $custom_product and files = '$get_file->id'");

  if($get_price->price_type == 1){
                $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id'");
}else{
    if($number_of_pages > $get_max_number_of_pages->max_number_of_pages){
             $get_cover_price = DB::selectOne("select * from cover_type_price where id = '$get_max_number_of_pages->id'  ");


     $number_of_copies =$this->splited_files($number_of_pages ,$get_max_number_of_pages->max_number_of_pages);
}else{
    $number_of_copies = 1;
         $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id' and $number_of_pages BETWEEN star_from and end_to");

}
}

                if ($get_cover_price) {
                    if($get_price->price_type == 1){
        $price_cover_total = $number_of_pages * $get_cover_price->price;
}else{
            $price_cover_total = $get_cover_price->price;

}

  $price_cover_total =  $price_cover_total *    $number_of_copies;


                }else{
                            return response()->json(['success' => '0', 'message' =>  "لم يتم تحديد الخصائص"]);

                }
            }

            $done = 1;
        }

        if ($merged == 1) {
            // DB::delete("delete from merged_files_cover where custom_product = $custom_product and files = '$id'");


     if($get_price->price_type == 1){
                $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id'");
}else{
if($number_of_pages > $get_max_number_of_pages->max_number_of_pages){
             $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$get_max_number_of_pages->id'  ");

     $number_of_copies =$this->splited_files($number_of_pages ,$get_max_number_of_pages->max_number_of_pages);
}else{
    $number_of_copies = 1;
         $get_cover_price = DB::selectOne("select * from cover_type_price where cover_id = '$cover_id' and $number_of_pages BETWEEN star_from and end_to");

}
}

            if ($get_cover_price) {

                    if($get_price->price_type == 1){
        $price_cover_total = $number_of_pages * $get_cover_price->price;
}else{
            $price_cover_total = $get_cover_price->price;

}
if($number_of_pages > $get_max_number_of_pages->max_number_of_pages){
     $price_cover_total += $price_cover_total * $this->splited_files($number_of_pages ,$get_max_number_of_pages->max_number_of_pages);
}
            $price_cover_total =  $price_cover_total *  $number_of_copies;


            }else{
                            return response()->json(['success' => '0', 'message' =>  "لم يتم تحديد الخصائص"]);

                }
        }




$custom =  new CustomProductController();
$c = $custom->get_total_price($custom_product);

$total =  $price_cover_total + $c->original["total"];
        return response()->json(['success' => '1', 'message' =>  "لم يتم تحديد الخصائص", 'total' =>round($total,2)]);
    }
}
