### leetChat

leetChat is a chat app that is inspired by Omegle. The idea is to let strangers chat with each other depending on their interests. This was only written as a personal project. I believe it is decent enough to go on production. I may or may not actively develop this further. 

It is written in PHP using Laravel and Ratchet PHP for the websockets part. While you may be asking why bother with php, i wanted to experiment with websockets in PHP, simply because i like to write stuff in PHP. I am not a full stack javascript developer yet.

The front end is written using VueJS. I personally think the code on the front end could be much cleaner but it is what it is.

Leetchat also lets users send attachments and voice notes. The file size is capped at 1MB. I am only realizing as i write this that i probably should add it as an configurable value in the env file, but it is hard coded and you must dwell into the source code to change it.  It is a simple laravel validation rule.




# Dependencies 
- **PHP 7.1 or higher**  
- **Mysql 5.7 or higher**
- **Composer**
- **Memcached** 
- **Memcached extension for php** 


# Installation
- **Clone the repository**
```
https://github.com/haiderali97/pchat
```
- **Install dependecies**
```
composer install
```
- **Copy the env example **
```
cp .env.example .env
```
- **Generate key**
```
php artisan key:generate
```
- **Configure Websocket PORT and Websocket URL**
You will need to configure a port for the websocket to use and a URL for the client side to connect to the websocket. This is what is configured in the nginx configuration. Open up your .env file and modify these two keys
```
APP_SOCKET_PORT=9000
APP_WS_URL=wss://localhost/chatServer
```

- **Run Migration**
Make sure you have configured your database in the .env file and run
```
php artisan migrate
```

- **Configure Nginx**
A basic configuration is provided in the repository. Configure the URL you would like to use for the reverse proxy.  You will need to create a symlink to this configuration at sites-enabled directory of nginx. Its this little snippet here.
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
Make sure you have SSL certificates generated already. You could run all of this without SSL but voice recording requires you to have SSL. Also why would you not want SSL? If you do not have SSL certificates, you could generate self signed ones or you could use LetsEncrypt.
- **Allow the configured port in your firewall**
```
sudo ufw allow 9000
```
- **Configuring permissions**
You need to make sure that the website is able to write and read from /storage directory. I recommend that you make the ```www-data``` user the owner and group of this directory. You could then add your user to the ```www-data``` group if you need to work within this directory.
```
sudo chown -R www-data:www-data storage
```
- **Running the websocket server**
You will also need to run the websocket server as the ```www-data``` user so that the console script can access the storage directory for writing logs and reading the user uploaded media.
```
sudo -u www-data php artisan websocket:init
```


### Other stuff
------------

##### Why use memcached for cache but not for sessions?
Before you read further, the cache data is only used to identify the partner of a client. The initial idea was to use memcached for both sessions and cache data.  However i noticed a problem when using memcached for sessions. The web application is unable to populate the session data in memcached after the server boots up. The fix was to restart the memcache daemon manually. I made the switch to file based sessions anyway. This is not that big of a problem, you can still use memcached if you chose to.  All you have to do is change it in the laravel configuration. My justification for using memcached for cache is scalability. In its current state, i guess its not that impactful but it might if the project expands. But again, you can always make the switch to filesystem, just change it in the .env file. Simple as that. 

##### User media 
All user uploaded media is available through a route that serves them, the url is signed and is only accessible for 30 minutes. The files are stored on the server until they are deleted.

##### Care to explain the design?
All user uploaded media is available through a route that serves them, the url is signed and is only accessible for 30 minutes. 

##### To Do?
Write a scheduled task that will delete the stored files. 