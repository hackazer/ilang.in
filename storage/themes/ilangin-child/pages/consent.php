<div class="container my-5">    
    <div class="card card-body text-center mt-7">
        <div class="row">
            <div class="col-md-12">
                <h2>
                    <?php echo e("Cookie Policy Consent") ?>
                </h2>
                <p class="description">
                    <?php echo !empty(config('cookieconsent')->message) ? e(config('cookieconsent')->message) : e("This website uses cookies to ensure you get the best experience on our website. You can learn more by visiting our cookie policy. Meanwhile if you agree, please click the button below to proceed to your destination.") ?>
                </p>
                <br>
                <div class="row">
                    <div class="col-sm-6">
                        <a href="?accept=1" class="btn btn-primary btn-block redirect" rel="nofollow"><?php echo e("Accept") ?></a>
                    </div>
                    <div class="col-sm-6">
                        <a href="<?php echo config('url') ?>" class="btn btn-secondary btn-block" rel="nofollow"><?php echo e("Take me to your homepage") ?></a></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>