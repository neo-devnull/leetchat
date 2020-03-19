<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use \Ratchet\WebSocket\MessageComponentInterface;
use App\Users;
use App;
use Config;
use Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Cache;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

class WebSocketController extends Controller implements MessageComponentInterface
{
    protected $clients;

    public function __construct(){
        $this->clients = [];        
    }

    public function onOpen(ConnectionInterface $conn){
        echo "{$conn->resourceId} has connected\n";


        /**
         * Session data works, laravel-iskly.                   
         * 
         * For more on authorizing users, read the links below. 
         * https://yohanes.gultom.me/2019/04/26/accessing-laravel-session-from-ratchet/
         * https://laravel.io/forum/01-16-2015-loading-laravels-session-using-ratchet
         * 
         */

        $session = (new SessionManager(App::getInstance()))->driver();
        $cookies = $conn->httpRequest->getHeader('Cookie');        
        $cookies = \GuzzleHttp\Psr7\parse_header($cookies)[0];
        $laravelCookie = urldecode($cookies[Config::get('session.cookie')]);
        $session_id = Crypt::decryptString($laravelCookie);
        $session->setId($session_id);
        $conn->session = $session;                       
        
        unset($cookies);
        unset($laravelCookie);

        $this->clients[$conn->resourceId] = $conn;
        
        /**
         * This is an INSERT UPDATE ON DUPLICATE KEY query.
         * This is to ensure that if the websocket server was killed using 
         * sigterm etc, that there wont be any duplicate socketIds next time 
         * 
         * This is still not the right way to do it, kill signal should be detected
         * and the application must be exit gracefully by deleting all database 
         * entires or it WILL RETURN MATCHES THAT SHOULD NOT. 
         * 
         * Probably should run this on Linux with pctnl extension and write 
         * handlers for kill signal
         * 
         * We really dont need an update on duplicate key if kill signal is handled but regardless
         * it is good to have.
         */
        Users::upsert(
            ['socketId' => $conn->resourceId, 'status' => null, 'interests' => null, 'sess_id' => $session_id],
            'socketId',
            ['status' => null, 'interests' => null, 'sess_id' => $session_id]
        );        

        unset($session_id);
    }   

    public function onMessage(ConnectionInterface $from, MessageInterface $msg){ 
        /**
         * Refresh session to access current state
         */        
        $from->session->start();                       
        $msg = json_decode($msg,true);        
        $cmd = $msg['cmd'];
        if(method_exists($this,$msg['cmd'])){
            $this->$cmd($from,$msg['data']);
        }
        unset($msg);       
        unset($cmd); 
        /**
         * In case we've modified any session data in the child methods, we need to save it
         * for it to be accessible later
         */
        $from->session->save();
    }

    public function onClose(ConnectionInterface $conn){
        /**
         * Make sure that his partner is notified if the chat is abruptly ended.
         */
        $this->leaveChat($conn);
        unset($this->clients[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected. :( \n";
        Users::where('socketId',$conn->resourceId)->delete();
    }

    public function onError(ConnectionInterface $conn, \Exception $e){
        /**
         * Make sure that his partner is notified if the chat is abrupty ended
         */  
        $err = "{$e->getMessage()} on {$e->getLine()} in {$e->getFile()}";              
        Log::error($err);
        Log::error("Terminating connection for {$conn->resourceId}");
        echo "{$err}\n";
        $conn->close();
    }

    public function sendMsg($from, $msg){        
        /**
         * Check if this socketId has a partner, if not exit function
         */        
        if(!Cache::has($from->resourceId)) return;
        /**
         * Process the message depending on type 
         * ¯\_(ツ)_/¯
         * 
         * ╭∩╮ʕ•ᴥ•ʔ╭∩╮
         */
        $send = ['cmd' => 'msgReceived'];

        if($msg['type'] == 'text'){

            $send['msg'] = [
                'type' => 'text',
                'text' => "stranger@kali:~$:{$msg['text']}"
            ];
        }

        //Voice notes and attachments
        if($msg['type'] == 'file' || $msg['type'] == 'voice'){            
            try{

                /* The file id is actually just encrypted file path */
                $file = Crypt::decryptstring($msg['file_id']);                
            } Catch (DecryptException $e) {
                /* Could not decrypt, exit function */
                return;
            }
            $send['msg']['type'] = $msg['type'];            
            $send['msg']['file'] = [
                'actual_uri' => URL::temporarySignedRoute('attachment',now()->addMinutes(1),['file_id' =>$msg['file_id']]),
                //'actual_uri' => URL::signedRoute('attachment',['file_id' => $msg['file_id']]),
                'file_name' => basename($file),
                'size' => Storage::getSize($file),
                'mime' => Storage::mimetype($file),
                'file_id' => $msg['file_id'],
                'is_mine' => false
            ];            
            unset($file);
        }

        unset($msg);
        $partner = $this->clients[Cache::get($from->resourceId)];                                
        $partner->send(json_encode($send)); 
        unset($send);  
        unset($partner);
                
    }

    public function leaveChat($from,$data=null){
        /**
         * Check if this socket id has a partner, if not exit function     
         */
        if(!Cache::has($from->resourceId)) return;

        $partner = $this->clients[Cache::get($from->resourceId)];

        /**
         * Update the status for both partner and client that is leaving chat          
         */
        Users::whereIn('socketId',[$partner->resourceId,$from->resourceId])->update(['status' => null,'interests'=>null]);

        /**
         * Tell his partner that this guy has left the chat          
         */        
        $partner->send(json_encode(
            ['cmd'=>'partnerDisconnected','data'=>'']
        ));

        /**
         * Remove both of them from memcached so that they are no longer partners
         */
        cache::forget($from->resourceId);
        cache::forget($partner->resourceId);
        
        /**
         * All done. Now when the search, they get thrown in the pool again.
         */
    }
    /**
     * Throw a user into pool and set status to 1 in database
     * 
     * Status has 3 possible values.
     * Null - User is not ready yet 
     * 0 - User is in pool 
     * 1 - User is in chat 
     * 
     * 
     */
    public function joinPool($from, $interests){

        /**
         * Lets just make sure this guy isn't already in a chat with a partner, dont want them messing with each
         * other
         */            
        if(Cache::has($from->resourceId)) return;
              
        $update = ['status' => 0];

        /**
         * I mean its pretty obvious what's happening here.
         * Trim the interests so we get a good match in the query.
         */          
        if($interests){
            foreach($interests as $k=>$v){
                $interests[$k] = trim($v);
                
            }            
            $update['interests'] = $interestsQuery = implode(",",$interests);            
        }
        

        Users::where('socketId',$from->resourceId)->update($update);
        unset($update);
        
        /**
         * Lets search for partners 
         * 
         * If no partner is found, no event is sent back. The client will just
         * wait until someone finds him.
         */

        /**
         * This is a FULL TEXT SEARCH in NATURAL LANGUAGE mode. 
         * Interests is a custon dynamic scope defined in App\Users model. 
         * 
         * This is not the best way to look for partners, but it will do for now.
         */
        $partner = Users::where('status',0)                        
                        ->where('socketId', '!=', $from->resourceId)                        
                        ->limit(1);

        /**
         * 
         * If interests is not null do a FULL TEXT Search, if not just search for null                               
         * 
         * Sure we could just check for anybody with any interest by leaving out the where
         * clause if interests are not specified but that would not prioritize users with matching 
         * interests. Not really a problem, but still a problem.
         * 
        */ 
        if($interests){
            $partner->interests($interestsQuery);
            unset($interestsQuery);
        } else {
            $partner->whereNull('interests');
        }
        
        /**
         * Don't allow users to connect with themselves using different tabs if its production version
         */        
        $env = ENV('APP_ENV','production');
        if($env != 'dev' && $env != 'local'){
            $partner->where('sess_id', '!=', $from->session->getId());    
        }
        unset($env);

        $partner = $partner->get();                        
        if(count($partner)){
        /**Found a parnter
             * Update status for both parties and send events to them indicating they are both 
             * now in a party.
             */
            echo "Partner found with socket id : {$partner[0]->socketId} and interests : {$partner[0]->interests}\n";

            Users::whereIn('socketId',[$partner[0]->socketId,$from->resourceId])->update(['status' => 1]);

            /**
             * Now lets notify both the sockets that they have a partner
             */

            /**
             * Lets get the intersection of interests before we send them a notification
             */            
            $partnerInterests = explode(",",$partner[0]->interests);
            $commonInterests = array_values(array_intersect($interests,$partnerInterests));
            $send = json_encode(['cmd' => 'joinRoom','interests' => $commonInterests]);
            $from->send($send);
            $this->clients[$partner[0]->socketId]->send($send);
            unset($interests);
            unset($partnerInterests);
            unset($commonInterests);
            unset($send);
                        
            /**
             * Create memcached entry for client's socket id where the value is his partner's socket id.
             * This is done so they can talk to each other.
             * 
             * You may use whatever cache you feel like, just configure it in laravel.
             * You can even re-write this to use a local memory array instead. However 
             * something like memcached/redis would be better for scaling.              
             */

            Cache::forever($from->resourceId,$partner[0]->socketId);
            Cache::forever($partner[0]->socketId,$from->resourceId);
            
        } else {
            echo "No partner found for {$from->resourceId} :(. We shall wait....ever so patiently.\n";
        }
        unset($partner);

    }   
    
}
