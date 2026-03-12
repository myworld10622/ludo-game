<div class="col-xl-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Chat</h4>
                                        <div class="chat-conversation">
                                            <ul class="conversation-list" data-simplebar="init" style="max-height: 367px;"><div class="simplebar-wrapper" style="margin: 0px -10px;"><div class="simplebar-height-auto-observer-wrapper"><div class="simplebar-height-auto-observer"></div></div><div class="simplebar-mask"><div class="simplebar-offset" style="right: -20px; bottom: 0px;"><div class="simplebar-content-wrapper" style="height: auto; padding-right: 20px; padding-bottom: 0px; overflow: hidden scroll;"><div class="simplebar-content" style="padding: 0px 10px;">
                                            <?php foreach ($chats as $key => $value) {
                                                if($value->sender_type=='user'){
                                                ?>
                                               
                                                <li class="clearfix">
                                                    <div class="chat-avatar">
                                                        <img src="<?= base_url()?>assets/images/user-4.jpg" class="avatar-xs rounded-circle" alt="male">
                                                        <span class="time"><?= date('H:i',strtotime($value->added_date))?></span>
                                                    </div>
                                                    <div class="conversation-text">
                                                        <div class="ctext-wrap">
                                                            <span class="user-name"><?= $value->first_name ?></span>
                                                            <p>
                                                            <?= $value->message ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                               <?php }else{ ?>
                                                <li class="clearfix odd">
                                                    <div class="chat-avatar">
                                                        <img src="<?= base_url()?>assets/images/user-4.jpg" class="avatar-xs rounded-circle" alt="Female">
                                                        <span class="time"><?= date('H:i',strtotime($value->added_date))?></span>
                                                    </div>
                                                    <div class="conversation-text">
                                                        <div class="ctext-wrap">
                                                            <span class="user-name"><?= $this->session->first_name ?></span>
                                                            <p>
                                                            <?= $value->message ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                <?php } } ?>
                                              
                                            </div></div></div></div><div class="simplebar-placeholder" style="width: auto; height: 480px;"></div></div><div class="simplebar-track simplebar-horizontal" style="visibility: hidden;"><div class="simplebar-scrollbar" style="transform: translate3d(0px, 0px, 0px); display: none;"></div></div><div class="simplebar-track simplebar-vertical" style="visibility: visible;"><div class="simplebar-scrollbar" style="height: 292px; transform: translate3d(0px, 0px, 0px); display: block;"></div></div></ul>
                                            <div class="row">
                                                <div class="col-sm-9 col-8 chat-inputbar">
                                                    <input type="text" class="form-control chat-input" placeholder="Enter your text">
                                                </div>
                                                <div class="col-sm-3 col-4 chat-send">
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-success">Send</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>