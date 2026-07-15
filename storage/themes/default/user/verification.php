<div class="d-flex mb-5">
    <div>
        <h1 class="h3">
            <?php ee('Get Verified') ?>
            <?php if($user->verified): ?>
                <i data-feather="check-circle" class="text-success"></i>
            <?php endif ?>
        </h1>
    </div>    
</div>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <p><?php ee('You can get your account verified and this provides you several benefits. After we verify we your account, you will get a verified checkmark on your Bio Pages and your links will have a trusted status.') ?></p>

                <p><?php ee('All we need from you is a document that matches your name and address. Documents can be a national card, company bill or any other official document.') ?></p>
                <form action="<?php echo route('user.verification.verify') ?>" method="post" enctype="multipart/form-data" autocomplete="off">
                <h4 class="my-4"><?php ee('Upload Document') ?></h4>
                <input type="file" name="file" class="form-control">
                <p class="form-text"><?php ee('2MB max, PDF or JPG') ?></p>

                <h4 class="my-4"><?php ee('Billing Address') ?></h4>
                <?php echo csrf() ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="billingname"><?php echo e("Full Name") ?></label>
                            <input type="text" class="form-control" id="billingname" name="billingname" placeholder="e.g. John Doe" value="<?php echo (isset($user->address->name) ? $user->address->name : $user->name ) ?>">
                        </div>									
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="company"><?php echo e("Company Name") ?></label>
                            <input type="text" class="form-control" id="company" name="company" placeholder="e.g. Acme Inc" value="<?php echo (isset($user->address->company) ? $user->address->company : "" ) ?>">
                        </div>									
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label" for="address"><?php echo e("Address") ?></label>
                    <input type="text" class="form-control" id="address" name="address" value="<?php echo (isset($user->address->address) ? $user->address->address : "" ) ?>">
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="city"><?php echo e("City") ?></label>
                            <input type="text" class="form-control" id="city" name="city" placeholder="e.g. New York" value="<?php echo (isset($user->address->city) ? $user->address->city : "" ) ?>">
                        </div>									
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="state"><?php echo e("State/Province") ?></label>
                            <input type="text" class="form-control" id="state" name="state" placeholder="e.g. NY" value="<?php echo (isset($user->address->state) ? $user->address->state : "" ) ?>">
                        </div>										
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group input-select">
                            <label class="form-label" for="country"><?php echo e("Country") ?></label>
                            <select name="country" id="country" class="form-control" data-toggle="select">
                                <?php echo \Core\Helper::Country($user->address->country ?? request()->country()['country'], true, true) ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="zip"><?php echo e("Zip/Postal code") ?></label>
                            <input type="text" class="form-control" id="zip" name="zip" placeholder="e.g. 44205" value="<?php echo (isset($user->address->zip) ? $user->address->zip : "" ) ?>">
                        </div>										
                    </div>                                  
                </div>
                <button class="btn btn-primary" type="submit"><?php ee('Submit') ?></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-body">
            <h4 class="mb-3 fw-bold"><?php ee('Verifications') ?></h4>
            <?php foreach($verifications as $verification): ?>
                <p>
                <?php if($verification->status == '1'): ?>
                    <span class="text-danger"><?php ee('Rejected') ?></span><br><?php echo date('d-m-Y', strtotime($verification->created_at)) ?>
                <?php elseif($verification->status == '2') :?>
                    <span class="text-success"><?php ee('Approved') ?></span><br><?php echo date('d-m-Y', strtotime($verification->created_at)) ?>
                <?php else: ?>
                    <span class="text-primary"><?php ee('Pending') ?></span><br><?php echo date('d-m-Y', strtotime($verification->created_at)) ?>
                <?php endif ?>
                </p>
            <?php endforeach ?>
        </div>
    </div>
</div>