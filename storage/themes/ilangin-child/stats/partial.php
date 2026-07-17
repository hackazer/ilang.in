<section class="slice slice-lg py-7 <?php echo \Helpers\App::themeConfig('homestyle', 'light', 'bg-white', 'bg-section-dark') ?>">
    <div class="container d-flex align-items-center" data-offset-top="#navbar-main">
        <div class="col py-5">
            <div class="d-flex">
                <?php if(isset($url->qr)): ?>
                    <div class="flex-grow-1 <?php echo \Helpers\App::themeConfig('homestyle', 'light', 'text-dark', 'text-white') ?>">
                        <h4 class="mb-3 <?php echo \Helpers\App::themeConfig('homestyle', 'light', 'text-dark', 'text-white') ?>"><?php echo $url->qr->name ?></h4>
                        <span class="badge bg-success text-sm"><?php echo ee('QR Code') ?></span>
                    </div>
                <?php elseif(isset($url->profile)): ?>
                    <div class="flex-grow-1 <?php echo \Helpers\App::themeConfig('homestyle', 'light', 'text-dark', 'text-white') ?>">
                        <h4 class="mb-3 <?php echo \Helpers\App::themeConfig('homestyle', 'light', 'text-dark', 'text-white') ?>"><?php echo $url->profile->name ?></h4>
                        <span class="badge bg-success text-sm"><?php echo ee('Bio Page') ?></span>
                    </div>
                <?php else: ?>
                    <img src="<?php echo \Helpers\App::shortRoute($url->domain, $url->alias.$url->custom) ?>/i" class="img-fluid rounded me-4" width="150">
                    <div class="flex-grow-1 <?php echo \Helpers\App::themeConfig('homestyle', 'light', 'text-dark', 'text-white') ?>">
                        <h4 class="mb-1"><a href="<?php echo $url->url ?>" target="blank" rel="nofollow" class="<?php echo \Helpers\App::themeConfig('homestyle', 'light', 'text-dark', 'text-white') ?>"><?php echo $url->meta_title ?></a></h4>
                        <span class="text-primary mb-2"><?php echo \Helpers\App::shortRoute($url->domain, $url->alias.$url->custom) ?></span>
                        <p class="text-sm"><?php echo $url->meta_description ?></p>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>    
    <div class="container">
        <div class="row mb-n10 position-relative zindex-100">
            <div class="col-lg-3 col-sm-4 px-2">
                <div class="card">
                    <div class="card-body text-center">                            
                        <h5 class="h3 fw-bolder mb-1"><?php echo $url->click ?></h5>
                        <span class="d-block text-sm text-muted fw-bold"><?php $url->qrid ? ee('Scans') : ee('Clicks') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-4 px-2">
                <div class="card">
                    <div class="card-body text-center">                            
                        <h5 class="h3 fw-bolder mb-1"><?php echo $url->uniqueclick ?></h5>
                        <span class="d-block text-sm text-muted fw-bold"><?php $url->qrid ? ee('Unique Scans') : ee('Unique Clicks') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-4 px-2">
                <div class="card">
                    <div class="card-body text-center">              
                        <h5 class="h3 fw-bolder mb-1"><?php echo $top->country && !empty($top->country->country) ? '<img src="'.\Helpers\App::flag($top->country->country).'" width="32" class="rounded me-1" alt=" '.ucfirst($top->country->country).'">': e('Unknown') ?></h5>
                        <span class="d-block text-sm text-muted fw-bold"><?php ee('Top Country') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-4 px-2">
                <div class="card">
                    <div class="card-body text-center">                            
                        <h5 class="h4 fw-bolder mb-1 h-100"><?php echo $top->referer ? "<small>{$top->referer->domain}</small>" : e('Unknown') ?></h5>
                        <span class="d-block text-sm text-muted fw-bold"><?php ee('Top Referrer') ?></span>
                    </div>
                </div>
            </div>
        </div> 
    </div>
</section>