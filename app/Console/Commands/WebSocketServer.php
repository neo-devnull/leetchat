<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Ratchet\WebSocket\WsServer;
use \React\Socket\SecureServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use \React\EventLoop\Factory;

use \React\Socket\Server;


use App\Http\Controllers\WebSocketController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Users;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize websocket for chat';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Websocket is now running');
        $this->info("Websocket is now running");        
        /**
         * No handling of kill signals yet
         * This should help.
         */
        Log::info('Clearing database');        
        $this->info("Clearing database");
        Users::truncate();
        /**
         * Don't want any remnants in cache either so lets flush it
         */
        Log::info('Flushing cache');        
        $this->info("Flushing cache");
        Cache::flush();

        /*

        Use this if you dont want to reverse proxy 

        $loop   = Factory::create();
        $webSock = new SecureServer(
            new Server('0.0.0.0:8090', $loop),
            $loop,
            array(
                'local_cert'        => '/home/haider/certs/pchat/selfsigned.crt', // path to your cert
                'local_pk'          => '/home/haider/certs/pchat/selfsigned.key', // path to your server private key
                'allow_self_signed' => TRUE, // Allow self signed certs (should be false in production)
                'verify_peer' => FALSE
            )
        );
        // Ratchet magic
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WebSocketController()
                )
            ),
            $webSock
        );
        $loop->run();        
        */
        /**
         * Use the code below for reverse proxy
         */

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new WebSocketController()
                )
            ),
            env('APP_SOCKET_PORT',9000)
        );
        $server->run();
    }

    public function shutdown(){
        Log::info('Websocket is now shutting down');            
        $this->info("Websocket is now shutting down\n");
    }
}
