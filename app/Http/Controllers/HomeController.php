<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Users;


class HomeController extends Controller
{
    public function home(Request $request){                        
        $socket_addr = env('APP_WS_URL',null);
        if(!$socket_addr){
            die("Please configure the APP_WS_URL in environment");
        }                    
        return view('chat',['socket_addr' => $socket_addr]);
    }

    /**
     * API To upload files and return meta data and an uri to access them
     */
    
    public function post(Request $request){
       
       $validator = Validator::make($request->all(),[
           'file' => 'mimes:jpeg,png,txt,webm|max:1024',           
       ]);       
       $validator->after(function ($validator){
           global $sess_id;
            /**
             * Check whether user is actually in a chat, If not he's being a stupid ass by 
             * trying to use up our server resources >:( 
             * 
             * It's sort of a hack but all we need to do is check if there is atleast one 
             * entry in our database with the client's session id and the chat status of 1.
             * 
             * That is enough to assume he is in a chat. 
             */
            $sess_id = session()->getId();
            $chat = Users::where('sess_id',$sess_id)->where('status',1)->get();
            if(!count($chat)){
                $validator->errors()->add('sess','You must be in a chat to upload files.');
            }
       });

       $validator->validate();
       $sess_id = session()->getId();
       //File has been validated, lets upload this puta que pario 
       $path = "files/".Carbon::now()->toDateString()."/";
       $path = $request->file('file')->store($path);
       /**
        * The return data is going to contain full information about the file
        * This is only to display it to the sender and nothing more. However
        * once this information is back at the sender, sending it rawly to the 
        * server and then to the partner is unsafe because it can be modified by the 
        * sender. We don't want have to return anything but the file id if we simply want to 
        * display a text like "Your file was sent" to the sender
        *
        * An actual  identifier for the file is also included. This is done so that 
        * when the data is back at the client's end, only the id is sent back to the server
        * to check for the file's existence, if it does not, the action simply fails and the 
        * partner sees nothing. Modifying the id will only result in not finding the file.
        * (Hopefully, unless the client gets lucky)
        * 
        * The id is right now just the filename itself, yeah that defeats the whole purpose.
        * I don't have a better solution right now and i want to avoid calls to the database.
        *
        * We also need a way to identify to which partner this data so it can be later
        * deleted on a disconnect.
        */
       $file_id =  Crypt::encryptstring($path);
       return response()->json([           
           'file_id' => $file_id,
           'file_name' => $request->file('file')->getClientOriginalName(),
           'actual_uri' => URL::temporarySignedRoute('attachment',now()->addMinutes(1),['file_id' =>$file_id]),
           'size' => $request->file('file')->getSize(),           
           'mime' => $request->file('file')->getMimeType()
       ]);
    }

    public function attachment(Request $request, $file_id){
        
        if (! $request->hasValidSignature()) {
            abort(401);
        }        
         try {
            $file = Crypt::decryptstring($file_id);              
        } Catch (DecryptException $e){
            abort(404);
        }                              
        $file = Storage::path($file);                         
        return response()->file($file);
    }
}
