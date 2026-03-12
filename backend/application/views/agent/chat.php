<style>
    /* .email-leftbar .chat-mail:active{
        background-color:#eff1f3;
    } */
     .rounded-circle {
        border-radius: 50% !important;
        width: 50px;
        height: 50px;
     }
     .conversation-list .chat-avatar img {
        width: 45px;
        border-radius: 50%;
        height: 45px;
    }
     .chat-mail {
        border-bottom: 1px solid #dbdbdb;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 7px;
     }
     .image-div{
        margin-right: 10px;
     }
     .email-leftbar {
        width: 355px !important;
        height: 70vh !important;
        overflow-y: auto;
     }
     .active-chat{
        background-color: #ebebeb
     }
     .chat-user-box {
        position: relative;
     }
     .new-message{
        width: 20px;
        height: 20px;
        background-color: #25d366;
        border-radius: 50px;
        position: absolute;
        top: 15px;
        right: 10px;
     }
</style>
<div class="row">

    <div class="col-4"> 
    <div class="email-leftbar card">
                             
                              
                                    <h5 class="mt-4">Users</h5>
        
                                    <div class="mt-4">
                                        <?php foreach ($AllChat as $key => $value) {
                                           
                                         ?>
                                        <a href="#" class="d-flex chat-mail" id="<?= $this->url_encrypt->encode($value->id) ?>" userid="<?= $this->url_encrypt->encode($value->user_id) ?>" onclick="getOldMsg('<?= $this->url_encrypt->encode($value->id) ?>', '<?= $this->url_encrypt->encode($value->user_id) ?>', '<?= $value->first_name ?>','scroll',this)">
                                            <div class="flex-shrink-0 me-3 image-div">
                                                <img class="rounded-circle" src="<?= base_url()?>data/post/<?= $value->profile_pic?>" alt="Generic placeholder image" height="36">
                                            </div>
                                            <div class="flex-grow-1 chat-user-box">
                                                <p class="user-title m-0">  <?= $value->first_name ?></p>
                                                <p class="text-muted" style="margin-bottom:0px">  <?= $value->mobile ?></p>
                                                <?php if($value->seen == 0) { ?>
                                                    <div class="new-message"></div>
                                                <?php }?>
                                            </div>
                                        </a>
                                        <?php } ?>
                                       
                                    </div>
                                </div>
        </div>

        <div class="col-5">
                                <div class="card">
                                    <div class="card-body chat-box" style="display:none">
                                        <h4 class="card-title mb-4 chat-title">Chat</h4>
                                        <div class="chat-conversation">
                                            <ul class="conversation-list" data-simplebar="init" style="max-height: 367px;"><div class="simplebar-wrapper" style="margin: 0px -10px;"><div class="simplebar-height-auto-observer-wrapper"><div class="simplebar-height-auto-observer"></div></div><div class="simplebar-mask"><div class="simplebar-offset" style="right: -20px; bottom: 0px;"><div class="simplebar-content-wrapper" style="height: auto; padding-right: 20px; padding-bottom: 0px; overflow: hidden scroll;">
                                                <div class="simplebar-content chat-box-msg" style="padding: 0px 10px;">
                                                <li class="clearfix">
                                                    <div class="chat-avatar">
                                                        <img src="<?= base_url()?>assets/images/user-4.jpg" class="avatar-xs rounded-circle" alt="male">
                                                        <span class="time">10:00</span>
                                                    </div>
                                                    <div class="conversation-text">
                                                        <div class="ctext-wrap">
                                                            <span class="user-name">John Deo</span>
                                                            <p>
                                                                Hello!
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="clearfix odd">
                                                    <div class="chat-avatar">
                                                        <img src="<?= base_url()?>assets/images/user-4.jpg" class="avatar-xs rounded-circle" alt="Female">
                                                        <span class="time">10:01</span>
                                                    </div>
                                                    <div class="conversation-text">
                                                        <div class="ctext-wrap">
                                                            <span class="user-name">Smith</span>
                                                            <p>
                                                                Hi, How are you? What about our next meeting?
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="clearfix">
                                                    <div class="chat-avatar">
                                                        <img src="<?= base_url()?>assets/images/user-4.jpg" class="avatar-xs rounded-circle" alt="male">
                                                        <span class="time">10:04</span>
                                                    </div>
                                                    <div class="conversation-text">
                                                        <div class="ctext-wrap">
                                                            <span class="user-name">John Deo</span>
                                                            <p>
                                                                Yeah everything is fine
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="clearfix odd">
                                                    <div class="chat-avatar">
                                                        <img src="<?= base_url()?>assets/images/user-4.jpg" class="avatar-xs rounded-circle" alt="male">
                                                        <span class="time">10:05</span>
                                                    </div>
                                                    <div class="conversation-text">
                                                        <div class="ctext-wrap">
                                                            <span class="user-name">Smith</span>
                                                            <p>
                                                                Wow that's great
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="clearfix odd">
                                                    <div class="chat-avatar">
                                                        <img src="<?= base_url()?>assets/images/user-4.jpg" class="avatar-xs rounded-circle" alt="male">
                                                        <span class="time">10:08</span>
                                                    </div>
                                                    <div class="conversation-text">
                                                        <div class="ctext-wrap">
                                                            <span class="user-name mb-2">Smith</span>

                                                            <img src="<?= base_url()?>assets/images/user-4.jpg" alt="" height="48" class="rounded me-2">
                                                            <img src="assets/images/small/img-2.jpg" alt="" height="48" class="rounded">
                                                        </div>
                                                    </div>
                                                </li>
                                            </div></div></div></div><div class="simplebar-placeholder" style="width: auto; height: 480px;"></div></div><div class="simplebar-track simplebar-horizontal" style="visibility: hidden;"><div class="simplebar-scrollbar" style="transform: translate3d(0px, 0px, 0px); display: none;"></div></div><div class="simplebar-track simplebar-vertical" style="visibility: visible;"><div class="simplebar-scrollbar" style="height: 292px; transform: translate3d(0px, 65px, 0px); display: block;"></div></div></ul>
                                            <div class="row">
                                                <div class="col-sm-9 col-8 chat-inputbar">
                                                    <input type="text" class="form-control chat-input" placeholder="Enter your text">
                                                </div>
                                                <div class="col-sm-3 col-5 chat-send">
                                                    <div class="d-grid">
                                                        <button type="button" onclick="sendMsg()" class="btn btn-success">Send</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
</div>
</div>


<script>
//   var interval='';
let activeConvId = "";
let activeUserId = "";
let activeUserName = "";
let activeUserElement = "";
    function getOldMsg(id, userId, userName, scrollType, elementObj) {
        console.log(elementObj)
        activeConvId = id;
        activeUserId = userId;
        activeUserName = userName;
        activeUserElement = elementObj;
        jQuery.ajax({
            url: "<?= base_url('backend/Chats/getOldMsg') ?>",
            type: "POST",
            data: {
                'id':id,
                'user_id': userId
            },
            success: function(data) {
            $('.chat-box').show();
            const response = JSON.parse(data)
            $('.chat-box-msg').html(response.chats);
            console.log(response.userlist)
            $(".email-leftbar").html(response.userlist)
            $(".chat-title").text(userName);

            console.log(scrollType);
            if(scrollType == "scroll") {
                $(".chat-box-msg").find(".clearfix").last()[0].scrollIntoView({ behavior: 'smooth' });
            }
            // $(".chat-mail").removeClass("active-chat")
            // $(activeUserElement).addClass("active-chat");
                // location.reload();
            }
        });
    }

    $(document).ready(function() {
            activeConvId = $(".chat-mail").eq(0).attr("id");
            activeUserId = $(".chat-mail").eq(0).attr("userid");
            $(".chat-mail").eq(0).click();
        })

        setInterval(function () {
            getOldMsg(activeConvId, activeUserId, activeUserName, 'noscroll', activeUserElement)
        }, 10000);

    // $(document).ready(function() {
    //     clearInterval(interval);
    //  })

    function sendMsg() {
        const message = $('.chat-input').val();
        if(!message) {
            alert("Message is missing");
            return
        }
        jQuery.ajax({
            url: "<?= base_url('backend/Chats/sendMsg') ?>",
            type: "POST",
            // data: {
            //     'id': '<?= $this->url_encrypt->encode($AllChat[0]->id) ?>',
            //     'user_id': '<?= $this->url_encrypt->encode($AllChat[0]->user_id) ?>',
            //     'msg':$('.chat-input').val()
            // },
            data: {
                'id': activeConvId,
                'user_id': activeUserId,
                'msg':$('.chat-input').val()
            },
            success: function(data) {
            // $('.chat-box-msg').html(data);
            const response = JSON.parse(data)
            $('.chat-box-msg').html(response.chats);
            $(".email-leftbar").html(response.userlist)
            $('.chat-input').val("");
            $(".chat-box-msg").find(".clearfix").last()[0].scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
</script>