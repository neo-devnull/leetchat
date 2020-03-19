/**
 * Get the CSRF Token For Axios
 */
axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN' : document.querySelector('meta[name="csrf-token"]').getAttribute('content')
};

console.log("You shouldn't be here bro");


function download(data, filename, type) {
    var file = new Blob([data], {type: type});
    if (window.navigator.msSaveOrOpenBlob) // IE10+
        window.navigator.msSaveOrOpenBlob(file, filename);
    else { // Others
        var a = document.createElement("a"),
                url = URL.createObjectURL(file);
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function() {
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);  
        }, 0); 
    }
}

var app = new Vue({
  el: '#app',
  mounted(){
    document.getElementById('interests_t').focus();
    if(localStorage.noob){
      this.noob = JSON.parse(localStorage.noob)
    } else {
      localStorage.noob = false;
      this.noob = false;
    }
    self = this;
  
    this.conn.onmessage = function(data){       
        data = JSON.parse(data.data);
        switch(data.cmd){
          case 'joinRoom':
            self.joinRoom(data);
            break;

          case 'msgReceived':
            self.msgReceived(data);            
            break;

          case 'partnerDisconnected':
            self.partnerDisconnected(data);
            break;
        }
    }
  },
  data: {
    conn : new WebSocket(socket_addr),
    vim : false, 
    noob : false,  
    interests: '', 
    interestsAr: [],
    connected: 'discon',
    msgs:[],
    msg: '',
    recording: false,
    voiceNote : null,
    currentVoiceNote : null,
    mediaRecorder : null,
    recordedAudioChunks: [],
    clientId : 'ab1xkjil7982izjk29xz92sdgl337shitm8st0pm8',
    fileIdDKey : '4n0nl3xyzdjk49620l337shitm8'
       
  },
  methods:{
      /**
        * The user is thrown into the pool once this event is triggered.
       */
      joinPool: function(e){
          e.preventDefault();
          this.prepInterests();
          send = JSON.stringify({
              cmd : 'joinPool',
              data : this.interestsAr
          });
          this.conn.send(send)
          this.connected = 'inPool'
      },

      joinRoom: function(data){
        this.connected = 'inRoom'
        if(data.interests.length){
          interests = data.interests.join(',');
          msg = `You have a partner. You both like ${interests}`;
        } else {
          msg = 'You have been connected to a random stranger.';
        }

        this.msgs = [];
        this.addTextMsg(msg);
        this.vim = false;
        
      },

      /**
       * Send text message
       */
      sendMsg: function(){
        msg = this.msg.trim();
        if(msg == '') return; 
        send = JSON.stringify({
           cmd : 'sendMsg',
           data : {
             type : 'text',
             text : msg
           }
        });
        this.conn.send(send);                
        this.addTextMsg(`root@kali:~#${msg}`)
        this.msg = '';
      },

      /**
       *  Send file, after upload
       */
      sendFile : function(data){
        file_id = data.data.file_id,
        send = JSON.stringify({
          cmd : 'sendMsg',
          data : {type:'file',file_id:file_id}
        })
        this.conn.send(send);
        //A simple property to check if client sent the file(for UI purposes)
        data.data.is_mine = true 
        //Add it to the messages now 
        this.addFileMsg(data.data)
        //Set vim mode to false
        this.vim = false;
      },

      /**
       * Sends voice note, after upload
       */
      sendAudio : function(data){
        file_id = data.data.file_id,
        send = JSON.stringify({
          cmd : 'sendMsg',
          data : {type:'voice',file_id:file_id}
        })
        this.conn.send(send)
        //A simple prperty to check if client sent the file(for UI purposes)
        data.data.is_mine = true 
        //Add it to the messages now 
        this.addVoiceMsg(data.data)
        //Set vim mode to false
        this.vim = false;
      }, 

      leaveRoom: function(){
        send = JSON.stringify({
          cmd:"leaveChat",
          data:''
        });
        this.conn.send(send);
        this.connected = 'leftChat';               
        this.addTextMsg('You have terminated the chat session.');
      },

      partnerDisconnected: function(){
        this.connected = 'leftChat';
        this.addTextMsg('You partner has terminated the chat session.');
      },

      msgReceived: function(data){        
        if(data.msg.type == 'file'){
          this.addFileMsg(data.msg.file)
        }
        if(data.msg.type == 'text'){          
          this.addTextMsg(data.msg.text)
        } 
        if(data.msg.type == 'voice'){
          this.addVoiceMsg(data.msg.file)
        }               
      },

      prepInterests : function(e){
        this.interestsAr = [];
        x = this.interests.trim(); 
        x = x.split(','); 
        for(k in x){
          this.interestsAr.push(x[k].trim())
        }          
      },   

      processMessage : function(e){
        //Check if its vim mode 
        if(this.vim) return this.processVim();
        return this.sendMsg();        
      },

      processVim : function(e){    
        msg = this.msg.trim();            
        if(msg == ':wq'){           
          this.leaveRoom();   
          this.saveLog();     
        }

        if(msg == ':q!'){
          this.leaveRoom();
        }

        if(msg == ':file' || msg == ':f'){
          this.triggerFileUpload();
        }

        //Starts recording audio
        if(msg == ':vr' || msg == ':voicerecord'){
          //Recording already, exit function
          if(this.recording) return;
          this.recording = true;
          this.startRecordAudio();
        }

        //Stops recording and uploads voice note
        if(msg == ':vrs' || msg == ':voicerecordsend'){
          //Not recording already, exit function
          if(!this.recording) return;
          this.recording = false;
          this.stopRecordAudio();
        } 

        //Stops recording and discards recorded chunkss
        if(msg == ':vr!' || msg == ':voicerecord!'){
          if(!this.recording) return;
          this.recording = false;
          this.recordedAudioChunks = [];
          this.mediaRecorder.discard = true;          
          this.stopRecordAudio();
        } 

        if(msg == ':h' || msg == ':help'){
          this.sendHelpMsg();
        }      
        
        this.msg = '';
      },

      sendHelpMsg : function(){
        msgs = [
          {
            type: 'help',
            text: ':wq to terminate the chat and save chat log to your machine'
          },
          {
            type: 'help',
            text: ':q! to terminate the chat without saving chat log'
          },
          {
            type: 'help',
            text: ':file or :f to send an attachment. Attachments can be text, image or webm under or equal to 1 megabyte'
          },
          {
            type: 'help',
            text: ':vr or :voicerecord to record a voice note. Voice notes can be under or equal to 1 megabyte'
          },
          {
            type: 'help',
            text: ':vrs or :voicerecordsend to send the recorded voice note'
          },
          {
            type: 'help',
            text: ':vr! or :voicerecord! to discard the recorded voice note'
          }, 
          {
            type: 'help',
            text: 'No chat logs are saved. All uploaded files and voice notes will not be accessible after 30 minutes, but however they are stored on the server and will be deleted by a scheduled task.'
          },                                                           
        ];
        this.msgs.push(msgs[0],msgs[1],msgs[2],msgs[3],msgs[4],msgs[5],msgs[6])        
      },

      saveLog : function(e){
        content = '';
        for(msg in this.msgs){

          if(this.msgs[msg].type == 'text'){
            content = `${content}${this.msgs[msg].text}\r\n`;  
          } 

          /** Uncoment if you want to show the help messages in the downloadable chat log */

          /*
          if(this.msgs[msg].type=='help'){
            content = `${content}${this.msgs[msg].text}\r\n`;
          }
          */

          /* Uncomment if you want to show attachment links in the downloadable chat log */

          /* 
          if(this.msgs[msg].type == 'file'){
            if(this.msgs[msg].file.is_mine){
               content =  `${content}You sent an attachment`
            } else {
              content =  `${content}You received an attachment`
            }
            content = `${content} ${this.msgs[msg].file.actual_uri}`
            content = `${content} (${this.msgs[msg].file.size} bytes ${this.msgs[msg].file.mime})\r\n`
          }
          */

          /* Uncomment if you want to show voice note links in the downloadable chat log */

          /*
          if(this.msgs[msg].type == 'voice'){
            if(this.msgs[msg].file.is_mine){
               content =  `${content}You sent a voice note`
            } else {
              content =  `${content}You received a voice note`
            }
            content = `${content} ${this.msgs[msg].file.actual_uri}`                       
          }
          */

        }        
        d = new Date()        
        name = `chat_log_${d.getTime()}.log`
        download(content,name,'text/plain');
      },

      vimToggle : function(e){
        this.vim = !this.vim         
      },   

      noobToggle : function(e){
        this.noob = !this.noob;        
        localStorage.noob = this.noob;
      },

      addTextMsg : function(text){
        this.msgs.push({type:'text',text:text});
      },

      addFileMsg: function (data){
        push = {type:'file',file:data}
        this.msgs.push(push)
      },

      addVoiceMsg: function(data){
        push = {type:'voice',file:data,status:0}
        this.msgs.push(push)
      },

      triggerFileUpload: function(data){
        document.getElementById('sendFile').click();
      },

      uploadFile : function(e){
        var self = this;
        file = e.target.files[0]
        data = new FormData();
        data.append('file',file)
        document.getElementById("sendFile").value = "";
        headers = {'Content-Type': 'multipart/form-data'}        
        axios.post('/post',data,headers)        
        .then(function(data){          
          self.sendFile(data)
        })  
        .catch(function(err){            
          res = err.response.data 
          if(!res.errors){
            alert("An unexpected error occured");
            return;
          }
          errmsg = 'The following errors occured\n'
          for(err in res.errors){
            errmsg = `${errmsg}\n${res.errors[err]}`
          }
          alert(errmsg)
        })
      },

      recordToggle : function(){        
        //Start recording 
        this.recording = !this.recording;
        if(this.recording){          
          this.startRecordAudio();
        }

        //stop recording
        if(!this.recording){     
          this.mediaRecorder.discard = !confirm("Confirm that you would like to send this voice note")           
          this.stopRecordAudio();
        }
        
      },

      stopRecordAudio : function(e){
        this.mediaRecorder.stop();
      },

      startRecordAudio : function(e){        
        self = this;
        self.recordedAudioChunks = [];
        navigator.mediaDevices.getUserMedia({ audio: true, video: false })
        .then(self.handleRecording)
        .catch(function(err){
          //Stop recording thingy
          self.recording = false;
          alert('You have denied microphone permissions. If you wish to record a voice note, allow it in your browser settings and try again.')
        })        
      },

      handleRecording : function(stream){ 
        
        self = this
        this.mediaRecorder = new MediaRecorder(stream,{mimeType:'audio/webm'});        
        this.mediaRecorder.ondataavailable = function(e){          
          if(e.data.size > 0) self.recordedAudioChunks.push(e.data)          
        };

        this.mediaRecorder.onstop = function(e){
          /**
            * discard is a custom property that we set in interface to check if the user 
            * wishes to cancel his recording. If its true, we simply dont upload
           */                      
          if(e.target.discard) return;          
          self.uploadVoiceNote();
        };

        this.mediaRecorder.start();        
      },

      uploadVoiceNote : function(){
        blob = new Blob(self.recordedAudioChunks,{type:'audio/webm'})
        file = new File([blob], "voicenote.weba", {lastModified: new Date(),type:'audio/webm'});                  
        data = new FormData();
        data.append('file',file)   
        headers = {'Content-Type': 'multipart/form-data'}
        axios.post('/post',data,headers)
        .then(function(response){            
            self.sendAudio(response)
        })
        .catch(function(err){               
          res = err.response.data 
          if(!res.errors){
            alert("An unexpected error occured");
            return;
          }
          errmsg = 'The following errors occured\n'
          for(err in res.errors){
            errmsg = `${errmsg}\n${res.errors[err]}`
          }
          alert(errmsg)            

        })          
      },

      /* Audio files have 3 status 
      *  0 - stopped 
      *  1 - Paused 
      *  2 - Playing
      *  The status is used to show play/pause buttons
      */

      playAudio : function(id){ 
        audio_path = this.msgs[id].file.actual_uri
        self = this;
        //Check if another audio is being played if so stop it.
        if(this.voiceNote !== null && this.currentVoiceNote != id){          
          this.stopAudio();
        }

        //Check if current audio is paused, if so just resume it
        if(this.voiceNote !== null && this.currentVoiceNote == id){
          this.voiceNote.play();          
        } else {
          //Current voice note was not playing, play it.
          this.voiceNote = new Audio(audio_path)
          this.voiceNote.onended = function(e){
            //call stop audio when it ends set the parameter for interface
            self.stopAudio();
          }        
          this.voiceNote.play();          
          //This is just a prop we use to set status after stopping in case of conflicting plays
          //The status is used to show play/pause buttons
          this.currentVoiceNote = id;
        }
        this.msgs[id].status = 2 
      },

      pauseAudio : function(id){
        this.voiceNote.pause();
        this.msgs[id].status = 1
      },

      stopAudio : function(e){
        this.voiceNote.currentTime = 0
        this.voiceNote.pause();
        this.msgs[this.currentVoiceNote].status = 0        
      },



      
    }
})