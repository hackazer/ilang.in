<a href="<?php echo route('home') ?>" class="btn btn-white btn-icon-only rounded-circle position-absolute zindex-101 left-4 top-4 d-none d-lg-inline-flex" data-bs-toggle="tooltip" data-bs-placement="right" title="Go back">
    <span class="btn-inner--icon">
        <i data-feather="arrow-left"></i>
    </span>
</a>
<section class="section-half-rounded bg-dark py-4 py-sm-0">
    <div class="container-fluid d-flex flex-column">
        <div class="row align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5 col-xl-4 mx-auto">
                <div class="card shadow-lg border-0 mb-0">
                    <div class="card-body py-5 px-sm-5">
                        <div>
                            <div class="mb-5 text-center">
                                <h6 class="h3 mb-2"><?php ee('Reset Password') ?></h6>
                            </div>
                            <span class="clearfix"></span>
                            <?php message() ?>
                            <form method="post" action="<?php echo route('reset.change', [$token]) ?>">
                                <?php echo csrf() ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php ee('New Password') ?></label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i data-feather="key"></i></span>
                                        <input type="password" class="form-control" id="input-access" name="password" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php ee('Confirm Password') ?></label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i data-feather="key"></i></span>
                                        <input type="password" class="form-control" id="input-caccess" name="cpassword" required>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn w-100 btn-primary"><?php ee('Reset Password') ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>