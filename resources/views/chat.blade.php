<html>
<head>
<title>pChat</title>

<link href="https://fonts.googleapis.com/css?family=Inconsolata&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Prompt&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">

<script>
socket_addr = "{{$socket_addr}}"
</script>

@if(env('APP_ENV', 'local') == 'local' || env('APP_ENV', 'local') == 'dev')
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
@else
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.11"></script>
@endif

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script defer src='{{ asset("js/chat.js") }}'></script>
<script src="https://use.fontawesome.com/ca542a27c5.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- 
    okay, fag kid im done. i doubt you even have basic knowlege of hacking. i doul boot linux so i can run my scripts. 
    you made a big mistake of replying to my comment without using a proxy, because i'm already tracking youre ip. 
    since ur so hacking iliterate, that means internet protocol. once i find your ip i can easily install a 
    backdoor trojan into your pc, not to mention your email will be in my hands. dont even bother turning 
    off your pc, because i can rout malware into your power system so i can turn your excuse of a computer on at any time. 
    it might be a good time to cancel your credit card since ill have that too. if i wanted i could release your home information
     onto my secure irc chat and maybe if your unlucky someone will come knocking at your door. id highly suggest you take 
     your little comment about me back since i am no script kiddie. i know java and c++ fluently and make my own scripts and 
     source code. because im a nice guy ill give you a chance to take it back. you have 4 hours in unix time, clock is ticking. 
     ill let you know when the time is up by sending you an email to [redacted] which I aquired with a java program i just wrote. 
     see you then :)
-->
</head>
<body>    
    <div class='container' id='app'>
    <div class='help'>
      <a v-if="noob===false" @click.stop='noobToggle' href='#'>Enable noob mode</a>
      <a v-else @click.stop='noobToggle' href='#'>Disable noob mode</a>      
    </div>
    <div class='app'>
          <!-- All the initializing fanciness -->
          root@kali:~# /bin/l337chat<br/>
          initializing l337chat<br/>
          Firewall bypass completed<br/>
          Routing through numerous proxies<br/>      
          input your interests as csv<br/>          
          root@kali:~#<input :disabled="connected!='discon'" v-on:keyup.enter="joinPool" v-model="interests" id='interests_t' type='text'/>          
          <input v-if="connected == 'discon' && noob === true" type='submit' value='Connect' @click.stop="joinPool"/>
          <div v-if="connected=='inPool' || connected=='inRoom'">            
            Retrieving users from 69,420 compromised databases<br/>
            l337chat complex partner search algorithm initiated<br/>
          </div>
          
          <!-- user has been paired with a partner and now they are chatting their asses off -->
          <div v-if="connected=='inRoom' || connected=='leftChat'">
             <!--<p class="chatMsg" v-for="msg in msgs">@{{msg}}</p>--> 
             <p class="chatMsg" v-for="msg,key in msgs">                  
                  <template v-if="msg.type=='text' || msg.type=='help'">@{{msg.text}}</template>

                  <template v-if="msg.type=='file'">
                    <span v-if="msg.file.is_mine===true">You have sent a file. </span> 
                    <span v-else>stranger@kali has sent you a file.</span>
                    <a target="_blank" :href='msg.file.actual_uri'>@{{msg.file.file_name}}</a> (@{{msg.file.size}} bytes @{{msg.file.mime}} )
                  </template>

                  <template v-if="msg.type=='voice'">
                    <span v-if="msg.file.is_mine===true">You have sent a voice note. </span>
                    <span v-else>stranger@kali has sent you a voice note.</span>
                    <audio :id="key" :src='msg.file.actual_uri'></audio>
                    <a v-if="msg.status==2" @click.stop="pauseAudio(key)" href="#">Pause</a>                                    
                    <a v-else @click.stop="playAudio(key)" href="#">Play</a>
                  </template>
             </p>
             <div v-if="connected=='inRoom'">
                  <!--<textarea ref="chatnet" v-on:keydown.esc="vimToggle" v-on:keyup.enter="processMessage" id='chatnet' v-model='msg'></textarea>--> 
                  root@kali:~#<input type="text" ref="chatnet" v-on:keydown.esc="vimToggle" v-on:keyup.enter="processMessage" id='chatnet' v-model='msg'>
                  <input v-if="noob === true" type='submit' value='Send' @click.stop="processMessage"/>
                  <input v-if="noob === true" type='submit' value='Send file' @click.stop="triggerFileUpload"/>                   
                  <input v-if="noob === true" type='submit' value='Disconnect' @click.stop="leaveRoom"/>
                  <button v-if="noob === true" @click.stop='recordToggle' class='icobtn' v-bind:class="{ active : recording }" ><i class="fa fa-microphone" aria-hidden="true"></i></button>      
                  <!-- The input field that will be triggered for file uploads -->
                  <input style="display:none;" id='sendFile' type='file' @change='uploadFile'/>
             </div>
             <div v-if="connected=='leftChat'">
                All traces have been cleared. Rerouting through alternate proxy.<br/>
                input your interests as csv<br/>
                root@kali:~#<input v-on:keyup.enter="joinPool" v-model="interests" id='interests_t' type='text'/>
                <input v-if="noob === true" type='submit' value='Connect' @click.stop="joinPool"/>
             </div>
          </div>
        
    </div>
    </div>
</body>
<script>

</script>
</html>
