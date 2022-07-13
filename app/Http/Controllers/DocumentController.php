<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Document;
use App\DocumentRevision;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use thiagoalessio\TesseractOCR\TesseractOCR;
use NlpTools\Similarity\CosineSimilarity;
use App\Curation;
use Session;
use App\Collection;
use Spatie\PdfToText\Pdf;

class DocumentController extends Controller
{

    public function list(Request $request){
        return view('all_documents');
	}

    public function loadDocument($collection_id,$document_id){
        $doc = \App\Document::find($document_id);
	$collection_id = $doc->collection_id;
	$collection = \App\Collection::find($collection_id);
	$storage_drive = empty($collection->storage_drive)?'local':$collection->storage_drive;

        $this->recordHit($document_id);

	## New code
	$download_file = $this->downloadFile($doc,$storage_drive);
	return $download_file;
    }

	public function pdfReader($collection_id, $document_id){
		return view('pdf-reader',['collection_id'=>$collection_id,'document_id'=>$document_id]);	
	}
    
    public function recordHit($document_id){
        $hit = new \App\DocumentHit;
        $revision = \App\DocumentRevision::where('document_id','=', $document_id)
            ->orderby('id', 'DESC')->first();
        $hit->document_id = $document_id;
        $hit->revision_id = $revision->id;
        $hit->user_id = empty(\Auth::user()->id)? null : \Auth::user()->id;
        $hit->save(); 
    }

	public function showUploadForm($collection_id)
    {
            $size_limit = ini_get("upload_max_filesize");	
            $collection = \App\Collection::find($collection_id);
            $document = new \App\Document;
       		return view('upload', ['collection'=>$collection, 'document'=>$document,'activePage'=>'Upload Document Form','titlePage'=>'Upload Document','size_limit'=>$size_limit]);
   	}

	public function showEditForm($document_id)
    	{
            $size_limit = ini_get("upload_max_filesize");	
            $document = \App\Document::find($document_id);
            $collection = \App\Collection::find($document->collection_id);
	    $has_approval=array();
	    $has_approval = \App\Collection::where('id','=',$document->collection_id)->where('require_approval','=','1')->get();

       		return view('upload', ['collection'=>$collection, 
			'document'=>$document,
			'collection_has_approval'=>$has_approval,
			'activePage'=>'Document Edit Form',
			'titlePage'=>'Document Edit Form',
			'size_limit'=>$size_limit]);
   	}


	public function uploadFile(Request $request){
		$messages = array();
		$errors = array();
		$warnings = array();
		//Filesize validation code starts
		$size_limit = ini_get("upload_max_filesize");
		$actual_size = $this->return_bytes($size_limit); ## Newly added line
		$collection_id = $request->input('collection_id');
        	$validator = Validator::make($request->all(), [
	    	'document' => 'file|max:'.$actual_size
        	]);
		if ($validator->fails()) {
            //Session::flash('alert-danger', 'File size exceeded. The file size should not be more than '.$size_limit.'B.');
            //return redirect('/collection/'.$collection_id.'/upload');
			return ['status'=>'failure', 'errors'=>['File size exceeded. The file size should not be more than '.$size_limit.'B.']];
        }
		// Filesize validation code ends

        if(!empty($request->input('document_id'))){
            $d = Document::find($request->input('document_id'));
        }
        else{
            $d = new Document;
        }

        $collection = \App\Collection::find($collection_id);
		$storage_drive = empty($collection->storage_drive)?'local':$collection->storage_drive;

		if($request->hasFile('document')){
			$filename = $request->file('document')->getClientOriginalName();
            $new_filename = \Auth::user()->id.'_'.time().'_'.$filename;
	
			// Saved on chosen collection storage drive.
			$filepath = $request->file('document')
				->storeAs('smartarchive_assets/'.$request
				->input('collection_id').'/'.\Auth::user()->id,$new_filename, $storage_drive);

			//Saved locally for text extraction
			$local_filepath = $request->file('document')
				->storeAs('smartarchive_assets/'.$request
				->input('collection_id').'/'.\Auth::user()->id,$new_filename);

			$filesize = $request->file('document')->getClientSize();
			$mimetype = $request->file('document')->getMimeType();

           	if(!empty($request->input('title'))){
               $d->title = $request->input('title');
           	}
           	else{
               $d->title = $this->autoDocumentTitle($request->file('document')->getClientOriginalName());
           	}
           	$d->collection_id = $request->input('collection_id');
           	$d->created_by = \Auth::user()->id;

			$d->size = $filesize;
			$d->type = $mimetype;
           	$d->path = $filepath;

           	try{
			    $text_content = $this->extractText($local_filepath, $mimetype);
		    	$current_encoding = mb_detect_encoding($text_content, 'auto');
				$d->text_content = mb_convert_encoding($text_content, "UTF-8");
		    	// Delete the file if the storage drive is other than local drive.
		    	// Command to delete / unlink the file locally.
		    	if($storage_drive != 'local'){
		    		Storage::delete($local_filepath);
		    	}
           	}
           	catch(\Exception $e){
          		\Log::error($e->getMessage());
               	$d->text_content = '';
				$warnings[] = 'No text was indexed. '. $e->getMessage();
           	}
            try{
                $d->save();
				$messages[] = 'Document uploaded successfully!';
            }
            catch(\Exception $e){
				return ['status'=>'failure', 'errors'=>[$e->getMessage()]];
            }

            // create revision
            $this->createDocumentRevision($d);
	}
	else{ // no document is uploaded
         if(!empty($request->input('approved_on'))){
           	$d->approved_by = \Auth::user()->id;
			$d->approved_on = now();
       	}

	//	Code to edit title of documen starts
		if(!empty($request->title)){
			$d->title = $request->title;
		}
	// Code to edit title of document ends
         try{
                $d->save();
				$messages[] = 'Document updated successfully!';
            }
            catch(\Exception $e){
				$errors[] = $e->getMessage();
            }
	} // else ends (document not uploaded)

        // extract meta
        $meta = $this->getMetaDataFromRequest($request);
        // put all meta values in a string
        $meta_string = '';
        foreach($meta as $m){
            $meta_string .= ' '.$m['field_value'].' ';
        }
        // save meta data
        $this->saveMetaData($d->id, $meta);
        // also update the text_content of the document
        $d->text_content = $d->text_content . $meta_string;
		
		// more work needed below.
		// if there are any errors above from the validator, an array of errors should be maintained
		// if the array of errors is empty, then the status should be successful
		return ['status'=>'successful', 'document_id'=>$d->id, 
			'messages'=>$messages, 'warnings'=>$warnings];
    }

	public function upload(Request $request){
		$upload_status = $this->uploadFile($request);
		if(!empty($upload_status['messages'])){
	        Session::flash('alert-success', implode(" ", $upload_status['messages']));
		}
		if(!empty($upload_status['warnings'])){
	        Session::flash('alert-warning', implode(" ", $upload_status['warnings']));
		}
		if(!empty($upload_status['errors'])){
	        Session::flash('alert-danger', implode(" ", $upload_status['errors']));
		}
        return redirect('/collection/'.$request->input('collection_id')); 
	}

    public static function importFile($collection_id, $path, $meta=[]){
        // get filename and create a new one
        $path_dirs = explode("/", $path);
        $filename = array_pop($path_dirs);
        $new_filename = '0_'.time().'_'.$filename;

		$collection = Collection::find($collection_id);
		//$filecontents = Storage::get($path);
		Storage::disk($collection->storage_drive)
			->writeStream('smartarchive_assets/'.$collection_id.'/0/'.$new_filename, Storage::readStream($path));
		$filesize = Storage::size($path);
		$mimetype = Storage::mimeType($path);
        $d = new Document;
        //echo "$filesize $mimetype\n";
        $dc = new \App\Http\Controllers\DocumentController;
        $d->title = empty($meta['title'])? $dc->autoDocumentTitle($filename) : $meta['title'];
        $d->collection_id = $collection_id;
        $d->created_by = 1;
		$d->size = $filesize;
		$d->type = $mimetype;
        $d->path = 'smartarchive_assets/'.$collection_id.'/0/'.$new_filename;
        try{
			$text_content = $dc->extractText($path,$mimetype);
            $d->text_content = mb_convert_encoding($text_content, "UTF-8");
        }
        catch(\Exception $e){
            echo $e->getMessage();
            $d->text_content = '';
        }
            $d->save();
            $dc->createDocumentRevision($d);

		// save metadata
		$doc_controller = new \App\Http\Controllers\DocumentController;
		$doc_controller->saveMetaData($d->id, (array)$meta);
		// return document model
	    return $d;
    }

    public function createDocumentRevision($d){
        $revision = new \App\DocumentRevision; 
        $revision->document_id = $d->id;
        $revision->created_by = $d->created_by;
        $revision->path = $d->path;
        $revision->type = $d->type;
        $revision->size = $d->size;
        $revision->text_content = $d->text_content;
            try{
                $revision->save();
                Session::flash('alert-success', 'Document uploaded successfully!');
            }
            catch(\Exception $e){
                Session::flash('alert-danger', 'Error: '.$e->getMessage());
            }
    }

    public function autoDocumentTitle($filename){
        $filename_chunks = explode(".",$filename);
		$file_ext = array_pop($filename_chunks);
		$title = implode('.', $filename_chunks);
        $title = str_replace('_',' ',$title);
        $title = str_replace('-',' ',$title);
        $title = ucfirst($title);
        return $title;
    }

    public function deleteDocument(Request $request){
        $document_id = $request->document_id;
       	$d = \App\Document::find($document_id);
       	$collection_id = $d->collection_id;
	if(!empty($request->delete_captcha) && 
		$request->delete_captcha == $request->delete_captcha){
        	$d->delete();
                Session::flash('alert-success', 'Document deleted successfully.');
        	return redirect('/collection/'.$collection_id); 
	}
	else{
                Session::flash('alert-danger', 'Please fill Captcha');
        	return redirect('/collection/'.$collection_id); 
        }

    }

	public function documentRevisions($document_id)
    {
        $c = \App\Document::find($document_id);
        $collection_id = $c->collection_id;
            $document_revisions = \App\DocumentRevision::where('document_id','=', $document_id)
                ->orderBy('id','DESC')->get();
       		return view('document-revisions', ['document_revisions'=>$document_revisions,'collection_id'=>$collection_id,'title'=>'Smart Repository','activePage'=>'Document Revsions','titlePage'=>'Document Revisions']);
   	}

    public function loadRevision($revision_id){
        $doc = \App\DocumentRevision::find($revision_id);

	$collection_id = $doc->collection_id;
        $collection = \App\Collection::find($collection_id);
        $storage_drive = empty($collection->storage_drive)?'local':$collection->storage_drive;

        ## New code
        $download_file = $this->downloadFile($doc,$storage_drive);
        return $download_file;
    }

    public function extractText($filepath, $mimetype){
        $text = '';
	    $enable_OCR = env('ENABLE_OCR');
		$ocr_langs = explode(",", env('OCR_langs'));
        if($mimetype == 'application/pdf'){
	    	$text = \Spatie\PdfToText\Pdf::getText(storage_path('app/'.$filepath));
			if(empty($text) && $enable_OCR==1){ // try OCR 
				/* this piece of code needs to be replaced with code that works with ocrmypdf
				*/
				$text = (new Pdf())
                ->setPdf(storage_path('app/'.$filepath))
                ->setOptions(['layout'])
                ->setScanOptions(['-l eng', '--skip-text'])
                ->decrypt()
                ->scan()
                ->text();
			}
        }
        else if(preg_match('/^image\//', $mimetype) && ($enable_OCR==1)){
            // try OCR
            $text = utf8_encode(
				(new TesseractOCR(storage_path('app/'.$filepath)))
				->lang(...$ocr_langs)
				->run()
			);
        }
        else if(preg_match('/^text\//', $mimetype)){
            $text = file_get_contents(storage_path('app/'.$filepath));
        }
        else{ // for doc, docx, ppt, pptx, xls, xlsx
	        $doc = new \App\DocXtract(storage_path('app/'.$filepath));
		    $text = $doc->convertToText();
        }
        return $text;
    }

    public function getMetaDataFromRequest(Request $request){
        $inputs = $request->all();
        $meta_data = array();
        foreach($inputs as $k=>$v){
            if(preg_match('/^meta_field_/', $k)){
                $field_id = str_replace('meta_field_','', $k);
                array_push($meta_data, array('field_id'=>$field_id, 'field_value'=>$v));
            }
        }
        return $meta_data;
    }

    public function saveMetaData($document_id, $meta_data){
        // first delete old and then save new 
        //\App\MetaFieldValue::where('document_id','=', $document_id)->delete();

        foreach($meta_data as $m){
			if(is_object($m)){
				$m = (array) $m;
			}
			$m_f = \App\MetaFieldValue::where('document_id',$document_id)->where('meta_field_id', $m['field_id'])->first();
			if(empty($m_f)){
            	$m_f = new \App\MetaFieldValue;
			}
            $m_f->document_id = $document_id;
            $m_f->meta_field_id = $m['field_id'];
            $m_f->value = empty($m['field_value']) ? '' : $m['field_value'];
            $m_f->save();
        }
    }

    public function showDetails($collection_id, $document_id){
	$c = \App\Collection::find($collection_id);
	if($c->content_type == 'Web resources'){
		$d = \App\Url::find($document_id);
	}
	else {
        	$d = Document::find($document_id);
	}
        return view('document-details', ['document'=>$d, 'collection'=>$c, 'word_weights'=>\App\Curation::getWordWeights($d->text_content)]);
    }

    public function showRevisionDiff($document_id, $rev1_id, $rev2_id){
        $d = Document::find($document_id);
        $rev1 = DocumentRevision::find($rev1_id);
        $rev2 = DocumentRevision::find($rev2_id);
	$cos_sim = new CosineSimilarity();
	$token_counts1 = Curation::getWordWeights($rev1->text_content);
	$token_counts2 = Curation::getWordWeights($rev2->text_content);
	$cosine_similarity = null;
	try{
		$cosine_similarity = $cos_sim->similarity($token_counts1, $token_counts2);
	}
	catch(\Exception $e){
		// do nothing for now.
	}
        return view('revision-diff', 
            ['document'=>$d, 'rev1'=>$rev1, 'rev2'=>$rev2,
	    'cosine_similarity' => round($cosine_similarity*100, 2),
            'activePage'=>'Diff in Revisions']);
    }

    public function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = substr($val, 0, -1);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    $val = $this->isa_convert_bytes_to_specified($val,'K',0);
    return $val;
    }

    public function isa_convert_bytes_to_specified($bytes, $to, $decimal_places = 1) {
    $formulas = array(
        'K' => number_format($bytes / 1024, $decimal_places,'',''),
        'M' => number_format($bytes / 1048576, $decimal_places,'',''),
        'G' => number_format($bytes / 1073741824, $decimal_places,'','')
    );
    return isset($formulas[$to]) ? $formulas[$to] : 0;
}

public function downloadFile($doc,$storage_drive){
	$exists = Storage::disk($storage_drive)->exists($doc->path);
        try{
                $file_url = $doc->path;
                $file_path  = $doc->path;
				// remove filename prefix
				$path_parts = explode('/',$file_path);
				$file_name = array_pop($path_parts);
				$file_name = preg_replace('/\d*_\d*_/','',$file_name);

                $mime = Storage::disk($storage_drive)->getDriver()->getMimetype($file_url);
                $size = Storage::disk($storage_drive)->getDriver()->getSize($file_url);

                $response =  [
                'Content-Type' => $mime,
                'Content-Length' => $size,
                'Content-Description' => 'File Transfer',
                'Content-Disposition' => "attachment; filename={$file_name}",
                'Content-Transfer-Encoding' => 'binary',
                ];

                ob_end_clean();

                return \Response::make(Storage::disk($storage_drive)->get($file_url), 200, $response);
        }
        catch(Exception $e){
                return $this->respondInternalError( $e->getMessage(), 'object', 500);
        }
}

public function proofRead($collection_id,$document_id){
	$d = \App\Document::find($document_id);
	$client = new \GuzzleHttp\Client();
	$connection_error = null;
	$lang_issues = null;
	try{
	$response = $client->request('POST', env('LANG_TOOL_URL'),
		[ 
		   'form_params'=>[
			'language'=>'en-US',
			'text'=>$d->text_content,
		   ]
		]
	);
 	if($response->getStatusCode() == 200){
		$lang_issues = json_decode((string) $response->getBody());
	}
	else{
		// error
		$reason = $response->getReasonPhrase();
	}
	}
	catch(\Exception $e){
		$connection_error = $e->getMessage();
	}
        return view('proof-reading', ['document'=>$d, 'lang_issues'=>$lang_issues, 'connection_error' => $connection_error]);
}

public function move(Request $req){
	// the user needs to be maintainer of both the collections
	$document = Document::find($req->document_id);
	$document->collection_id = $req->collection_id;
	$document->save();
	return redirect('/collection/'.$req->collection_id.'/document/'.$document->id.'/details');
}

### End of class
}
