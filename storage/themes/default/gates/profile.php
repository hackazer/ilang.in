<div class="container mt-5 mb-2" id="profile">  
     <div class="row">
        <div class="col-md-6 offset-md-3 text-center my-5">
            <?php echo message() ?> 
            <?php if(!isset($profiledata['avatarenabled']) || $profiledata['avatarenabled']): ?>
                <?php if(isset($profiledata['avatar']) && $profiledata['avatar']): ?>
                    <img src="<?php echo uploads($profiledata['avatar'], 'profile') ?>" class="rounded-circle mb-3" width="120" height="120">
                <?php else: ?>
                    <img src="<?php echo $user->avatar() ?>" class="rounded-circle mb-3" width="120" height="120">
                <?php endif ?>
            <?php endif ?>
            <h3>
                <span><?php echo $profile->name ?></span>
                <?php if($user->verified): ?>
                    <span class="text-success fw-bold ml-2 bg-white rounded-circle checkmark" data-toggle="tooltip" data-placement="top" title="<?php ee('Verified Account') ?>"><i data-feather="check-circle"></i></span>
                <?php endif ?>
            </h3>
            <div id="social" class="text-center mt-3">
                <?php foreach($profiledata['social'] as $key => $value): ?>
                    <?php if(empty($value)) continue ?>
                    <a href="<?php echo $value ?>" class="mx-2" target="_blank" data-toggle="tooltip" data-placement="top" title="<?php echo ucfirst($key) ?>" rel="nofollow"><i class="fab fa-<?php echo $key ?>"></i></a>
                <?php endforeach ?>
            </div>
            <div id="content" class="mt-5">
                <?php foreach($profiledata['links'] as $id => $value): ?>
                    <div class="item mb-3">
                        <?php if($value['type'] == "heading"): ?>
                            <?php if(in_array($value['format'], ['h1','h2','h3','h4','h5','h6'])):?>
                                <<?php echo $value['format'] ?> style="color:<?php echo $value['color'] ?? '' ?> !important"><?php echo $value['text'] ?></<?php echo $value['format'] ?>>
                            <?php else: ?>
                                <h1><?php echo $value['text'] ?></h1>
                            <?php endif ?>
                        <?php endif ?>
                        <?php if($value['type'] == "divider"): ?>
                            <hr style="background:transparent;border-top-style:<?php echo $value['style'] ?? 'solid' ?> !important;border-top-width:<?php echo $value['height'] ?? '3' ?>px !important;border-top-color:<?php echo $value['color'] ?? '#000' ?> !important;border-radius: 5px;">
                        <?php endif ?>

                        <?php if($value['type'] == "image"): ?>
                            <?php if($value['link']): ?>
                                <a href="<?php echo $value['link'] ?>" target="_blank" rel="nofollow"><img src="<?php echo uploads($value['image'], 'profile') ?>" class="img-fluid rounded w-100"></a>
                            <?php else: ?>
                                <img src="<?php echo uploads($value['image'], 'profile') ?>" class="img-fluid rounded w-100">
                            <?php endif ?>
                        <?php endif ?>

                        <?php if($value['type'] == "rss"): ?>
                            <div class="rss card card-body overflow-auto">
                                <?php $items = \Helpers\App::rss($value['link']) ?>
                                <?php if(!is_array($items)): ?>
                                    <?php echo $items ?>
                                <?php else: ?>
                                    <?php foreach($items as $item): ?>
                                        <div class="media mb-3">
                                            <?php if($item['image']): ?>
                                                <img class="mr-3" src="<?php echo $item['image'] ?>" alt="<?php echo $item['title'] ?>">
                                            <?php endif ?>
                                            <div class="media-body">
                                                <h6 class="mt-3"><a href="<?php echo $item['link'] ?>" target="_blank"><?php echo $item['title'] ?></a></h6>
                                                <?php echo $item['description'] ?>
                                            </div>
                                        </div>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </div>
                        <?php endif ?>

                        <?php if($value['type'] == "text"): ?>
                            <div><?php echo $value['text'] ?></div>
                        <?php endif ?>

                        <?php if($value['type'] == "whatsapp"): ?>
                            <a href="https://wa.me/<?php echo str_replace(' ', '', $value['phone']) ?>" class="btn btn-block d-block p-3 btn-custom"><img src="<?php echo assets('images/whatsapp.svg') ?>" height="25" class="mr-3"> <?php echo isset($value['label']) && $value['label'] ? $value['label'] : $value['phone'] ?></a>
                        <?php endif ?>
                        
                        <?php if($value['type'] == "link"): ?>
                            <a href="<?php echo $value['link'] ?>" rel="nofollow" data-blockid="<?php echo $value['urlid'] ?>" class="<?php echo (isset($value['animation']) && in_array($value['animation'], ['shake','wobble','vibrate','jello','scale']) ? 'animate_'.$value['animation'].' ':'') ?>btn btn-block d-block p-3 btn-custom" style="<?php echo in_array($value['style'], ['tro','trec']) ? 'background:transparent;border:2px solid '.$value['bg'].'' : 'background:'.($value['bg']?? '').'' ?>!important;color:<?php echo $value['color'] ?? '' ?>!important;border-radius:<?php echo (isset($value['style']) && in_array($value['style'], ['tro','rounded']) ? '50px' : '5px') ?>!important"><?php echo ($value['icon'] ?? '' ? '<i class="'.$value['icon'].' mr-2"></i>' : '') ?> <span class="align-top"><?php echo $value['text'] ?></span></a>
                        <?php endif ?>

                        <?php if($value['type'] == "youtube"): ?>
                            <iframe width="100%" height="315" src="<?php echo $value["link"] ?>" class="rounded"></iframe>
                        <?php endif ?>

                        <?php if($value['type'] == "itunes"): ?>
                            <iframe width="100%" height="315" src="<?php echo $value["link"] ?>" class="rounded"></iframe>
                        <?php endif ?>

                        <?php if($value['type'] == "paypal"): ?>

                            <form action="https://www.paypal.com/cgi-bin/webscr" method="post">

                                <input type="hidden" name="business" value="<?php echo $value['email'] ?>">

                                <input type="hidden" name="cmd" value="_xclick">

                                <input type="hidden" name="item_name" value="<?php echo $value['label'] ?>">
                                <input type="hidden" name="amount" value="<?php echo $value['amount'] ?>">
                                <input type="hidden" name="currency_code" value="<?php echo $value['currency'] ?>">

                                <button type="submit" name="submit" class="btn btn-block d-block p-3 btn-custom w-100"><?php echo $value['label'] ?></button>                            
                            </form>
                        <?php endif ?>
                        <?php if($value['type'] == "spotify"): ?>
                            <iframe width="100%" height="315" src="<?php echo $value["link"] ?>" class="rounded"></iframe>
                        <?php endif ?>                        

                        <?php if($value['type'] == "tiktok"): ?>
                            <blockquote class="tiktok-embed rounded" cite="<?php echo $value['link'] ?>" data-video-id="<?php echo $value['id'] ?>" style="max-width: 605px;min-width: 325px;" > <section> </section> </blockquote> <script async src="https://www.tiktok.com/embed.js"></script>
                        <?php endif ?>

                        <?php if($value['type'] == "html"): ?>
                            <?php echo $value['html'] ?>
                        <?php endif ?>

                        <?php if($value['type'] == "newsletter"): ?>
                            <form method="post" action="">
                                <div class="d-flex align-items-center border rounded bg-white p-1">
                                    <div>                         
                                        <input type="text" class="form-control border-0" name="email" placeholder="johnsmith@company.com">
                                    </div>		
                                    <div class="ml-auto">
                                        <button type="submit" class="btn btn-custom"><?php echo $value['text'] ?></button>
                                    </div>                                
                                </div>	
                                <input type="hidden" name="action" value="newsletter">
                                <input type="hidden" name="blockid" value="<?php echo $id ?>">
                            </form>
                        <?php endif ?>

                        <?php if($value['type'] == "contact"): ?>
                            <h3><?php $value['text'] ?></h3>
                            <form method="post" action="" class="border rounded text-left p-3">
                                <div class="form-group">
                                    <label for="email" class="form-label"><?php ee('Email') ?></label>
                                    <input type="text" class="form-control" name="email" placeholder="johnsmith@company.com">
                                </div>
                                <div class="form-group">
                                    <label for="email" class="form-label"><?php ee('Message') ?></label>
                                    <textarea class="form-control" name="message"></textarea>
                                </div>
                                <?php csrf() ?>
                                <input type="hidden" name="action" value="contact">
                                <input type="hidden" name="blockid" value="<?php echo $id ?>">
                                <?php echo \Helpers\Captcha::display(); ?>
                                <button type="submit" class="btn btn-custom"><?php echo ee('Send') ?></button>
                            </form>
                        <?php endif ?>
                        <?php if($value['type'] == "vcard"): ?>
                            <form method="post" action="?downloadvcard">
                                <?php csrf() ?>
                                <input type="hidden" name="action" value="vcard">
                                <input type="hidden" name="blockid" value="<?php echo $id ?>">
                                <button type="submit" class="btn btn-custom btn-block"><?php ee('Download vCard') ?></button>
                            </form>
                        <?php endif ?>
                        <?php if($value['type'] == "product"): ?>
                            <a href="<?php echo $value['link'] ?>" target="_blank" class="d-flex border p-1 rounded mt-2" rel="nofollow">
                                <div class="mr-3">
                                    <img src="<?php echo uploads($value['image'], 'profile') ?>" class="rounded" style="max-width: 130px">
                                </div>
                                <div class="text-left">
                                    <h3><?php echo $value['name'] ?></h3>
                                    <strong><?php echo $value['amount'] ?></strong>
                                    <p><?php echo $value['description'] ?></p>
                                </div>
                            </a>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
    <div class="text-center mt-5 opacity-8">
        <a class="navbar-brand mr-0" href="<?php echo route('home') ?>">
            <?php if(config('logo')): ?>
                <img alt="<?php echo config('title') ?>" src="<?php echo uploads(config('logo')) ?>" width="80" id="navbar-logo">
            <?php else: ?>                
                <h1 class="h5 mt-2"><?php echo config('title') ?></h1>
            <?php endif ?>
        </a>   
    </div>
</div>