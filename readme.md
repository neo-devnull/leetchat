#  leetChat

  

leetChat is a chat app that is inspired by Omegle. The idea is to let strangers chat with each other depending on their interests. This was only written as a personal project. I believe it is decent enough to go on production. I may or may not actively develop this further.

  

It is written in PHP using Laravel and Ratchet PHP for the websockets part. While you may be asking why bother with php, i wanted to experiment with websockets in PHP, simply because i like to write stuff in PHP. I am not a full stack javascript developer yet.

  

The front end is written using VueJS. I personally think the code on the front end could be much cleaner but it is what it is.

  I aplogize if the installation guide is confusing. I have tried to keep it as straight forward as possible. If you face any issues, feel free to [contact me.](https://t.me/h4iderali)  

# Dependencies

-  **PHP 7.1 or higher**

-  **Mysql 5.7 or higher**

-  **Composer**

-  **Memcached**

-  **Memcached extension for php**

  
  

# Installation

-  **Clone the repository**

```
git clone https://github.com/haiderali97/leetchat.git
```

-  **Install dependecies**

```
composer install

```

- **Copy the env example **

```
cp .env.example .env

```

-  **Generate key**

```
php artisan key:generate

```

-  **Configure Websocket PORT and Websocket URL**

You will need to configure a port for the websocket to use and a URL for the client side to connect to the websocket. This is what is configured in the nginx configuration. Open up your .env file and modify these two keys

```
APP_SOCKET_PORT=9000

APP_WS_URL=wss://localhost/chatServer

```

  

-  **Run Migration**

Make sure you have configured your database in the .env file and run

```
php artisan migrate

```

- **Creating directories for log files**
The provided nginx configuration and supervisor configuration(if you choose to use it) have directives for log files. The websocket controller already logs to the laravel log(which can be found in ```storage/logs```) and it outputs to stdout as well. If you choose to you can skip this directive in  the supervisor configuration, they will still be available in storage/logs. I personally prefer having it saved to a separate directory as well. As for nginx, if you skip the directive it will log to the global log files.  
```
mkdir -p /path/to/logs/leetchat/nginx /path/to/logs/leetchat/socket
```
Make the directories and edit the path in the provided configuration files.

-  **Configure Nginx**

A basic configuration is provided in the repository. You will need to create a copy of the provided config file and create symlink to this configuration file from ```/etc/nginx/sites-enabled```.  Configure the location you would like for the reverse proxy to use. Make sure it matches ```APP_WS_URL``` from your ```.env``` file. The snippet is provided below.

```
#Change /chatServer to whatever is configured for APP_WS_URL in .env file

#reverse proxy

location /chatServer {

proxy_pass http://websocket;

proxy_http_version 1.1;

proxy_set_header Upgrade $http_upgrade;

proxy_set_header Connection "upgrade";

proxy_set_header Host $host;

  

proxy_set_header X-Real-IP $remote_addr;

proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

proxy_set_header X-Forwarded-Proto https;

proxy_read_timeout 86400;

proxy_redirect off;

}
```

Dont forget to change the port in the nginx config file to match the port in your .env file. Its this little snippet here.

```
upstream websocket{

#Change the port to whatever is configured in the .env file

server localhost:9000;

}
```
Dont forget to change the ```access_log``` and ```error_log``` directives to point to the log directory you have created. If not ```nginx -t``` will throw an error. You can comment them out if you wish to use the global logs.

Make sure you have SSL certificates generated already. You could run all of this without SSL but voice recording requires you to have SSL. Also why would you not want SSL? If you do not have SSL certificates, you could generate self signed ones or you could use LetsEncrypt.

-  **Allow the configured port in your firewall**

```
sudo ufw allow 9000
```

-  **Configuring permissions**

You need to make sure that the website is able to write and read from /storage directory. I recommend that you make the ```www-data``` user the owner and group of this directory. You could then add your user to the ```www-data``` group if you need to work within this directory.

```
sudo chown -R www-data:www-data storage
```

-  **Running the websocket server**

You will also need to run the websocket server as the ```www-data``` user so that the console script can access the storage directory for writing logs and reading the user uploaded media.

```
sudo -u www-data php artisan websocket:init
```

  - **Using supervisor**
  Supervisor is a process control system. It ensures your websocket server stays running. You can choose not to use it, or you can use any other service to manage the process as well. This will monitor your websocket server and restart it if it halts and will run when the websocket server on system boot. A basic configuration is provided in the repository. Create a copy of the provided configuration and create a symlink to it at ```/etc/supervisor/conf.d```
Don't forget to edit the log directives to the log paths that you have created. If you don't want separate logs and are okay with having them in the laravel logs, you can simply remove the log directives from the configuration.
For a basic tutorial on supervisor, you can read [here.](https://serversforhackers.com/c/monitoring-processes-with-supervisord)  
  

# More information



  

##### Why use memcached for cache but not for sessions?

Before you read further, the cache data is only used to identify the partner of a client. The initial idea was to use memcached for both sessions and cache data. However i noticed a problem when using memcached for sessions. The web application is unable to populate the session data in memcached after the server boots up. The fix was to restart the memcache daemon manually. I made the switch to file based sessions anyway. This is not that big of a problem, you can still use memcached if you chose to. All you have to do is change it in the laravel configuration. My justification for using memcached for cache is scalability. In its current state, i guess its not that impactful but it might if the project expands. But again, you can always make the switch to filesystem, just change it in the .env file. Simple as that.

  

##### User media

All user uploaded media is available through a route that serves them, the url is signed and is only accessible for 30 minutes. The files are stored on the server until they are deleted.
The uploaded files are validated using laravel. The allowed mime types and file size is hardcoded. This can be easily changed in ```Homecontroller@post```
```
$validator = Validator::make($request->all(),[

'file' => 'mimes:jpeg,png,txt,webm|max:1024',

]);
```

  

##### Care to explain the design?

I'm not a designer by any definition of the word. I have a hard time coming up with ideas. The design i created is very basic and inspired by a running gag in the telegram communities im part of. That's all.

  ##### DEPLOYMENT 
I recommend giving [this](http://socketo.me/docs/deploy) a read if you are serious about deploying this to production.

##### To Do

- Write a scheduled task that will delete the stored files.


