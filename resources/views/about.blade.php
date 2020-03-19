<html>
<head>
<title>pChat | About</title>
<link href="https://fonts.googleapis.com/css?family=Inconsolata&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Prompt&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">
</head>

<body>
    <div class='container'>
        <div class='app'>
         This is just a simple chat application like omegle written entirely in PHP<br/><br/>

        But why?<br/>
        Boredom.<br/><br/>

        But why in PHP?<br/>
        Just wanted to play around with websockets in PHP.<br/><br/>

        What's involved?<br/>
        <ul>
            <li>Laravel</li>
            <li>Ratchet PHP</li>
            <li>MySQL</li>
            <li>Memcached</li>
            <li>VueJS</li>
        </ul>
        

        What else?<br/>
        No chat log are saved on the server. Believe me, i don't have the resources.<br/>
        You have the option to download the chat log of a session onto your local machine(This works purely client side)
        All file uploads and voice recordings are only publically accessible for 30 minutes. They are still stored on the server, but organized in 
        a way that a scheduled task can easily take care of deleting them, or i could implement a better method to keep track of these files and delete 
        them when no longer needed, but ehh.<br/>
        I will delete them, i don't have the resources to keep them.

        <br/><br/>
        If you're going to check the source code out, the front end javascript is horrific, i apologize.<br/>
        I did learn to do some cool stuff in javascript on the front end that i didn't know before, and CSS as well.<br/>
        I like to think i did a decent job in the backend code at least :)
        <br/><br/>Thats about it.<br/>
        <a style="color:green;" href='' target="_blank">Repository</a>
        </div>
    </div>
</body>

