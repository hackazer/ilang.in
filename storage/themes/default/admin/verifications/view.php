<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo route('admin') ?>"><?php ee('Dashboard') ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo route('admin.verifications') ?>"><?php ee('Verifications') ?></a></li>
  </ol>
</nav>
<h1 class="h3 mb-5"><?php echo $user->email ?>
    <?php if($user->verified) echo '<span class="badge bg-success ms-2">'.e('Verified').'</span>' ?>
    <?php if($verification->status == '1'): ?>
        <span class="badge bg-danger"><?php ee('Rejected') ?></span>
    <?php endif ?>         
</h1>
<div class="row">
    <div class="col-md-4 col-xl-3">
        <div class="card mb-3">
            <div class="card-body text-center">
                <img src="<?php echo $user->avatar() ?>" alt="<?php echo $user->username ?>" class="img-fluid rounded-circle mb-2" width="128" height="128" />
                <h5 class="card-title mb-0"><?php echo $user->username ?></h5>
                <div class="text-muted mb-2"><?php echo $user->pro && $plan ? $plan->name : 'Free user' ?></div>

                <div>
                    <a class="btn btn-primary btn-sm" href="<?php echo route('admin.email', ['email'=> $user->email]) ?>"><span data-feather="message-square"></span> <?php echo e('Send Email') ?></a>
                    <a class="btn btn-primary btn-sm" href="<?php echo route('admin.users.edit', [$user->id]) ?>"><span data-feather="edit"></span></a>                    
                </div>
            </div>            
        </div>
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3"><?php ee('Manage Verification') ?></h4>
                <form action="<?php echo route('admin.verifications.process', [$verification->id]) ?>" method="post">
                    <?php echo csrf() ?>
                    <div class="form-group">
                        <label for="" class="form-label"><?php ee('Action') ?></label>
                        <select name="action" id="" class="form-select">
                            <option value="2" selected><?php ee('Approve') ?></option>    
                            <option value="1"><?php ee('Reject') ?></option>
                        </select>
                    </div>
                    <div class="form-group my-3">
                        <label><input type="checkbox" name="deletefile" value="1"> <?php ee('Delete document from server') ?></label>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php ee('Process') ?></button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action" href="<?php echo route('admin.users.view', [$user->id]) ?>"><?php ee('Links') ?></a>
                    <a class="list-group-item list-group-item-action" href="<?php echo route('admin.payments', ['userid' => $user->id]) ?>"><?php ee('Payments') ?></a>
                    <a class="list-group-item list-group-item-action" href="<?php echo route('admin.subscriptions', ['userid' => $user->id]) ?>"><?php ee('Subscriptions') ?></a>
                    <a class="list-group-item list-group-item-action" href="<?php echo route('admin.domains', ['userid' => $user->id]) ?>"><?php ee('Domains') ?></a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8 col-xl-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php ee('Uploaded Document') ?></h5>
            </div>
            <div class="card-body">
                <p><?php ee('File name:') ?> <?php echo $verification->file ?></p>
                <p><?php ee('Submitted:') ?> <?php echo $verification->created_at ?></p>
                <?php if(file_exists(appConfig('app.storage')['files']['path'].'/'.$verification->file)): ?>
                    <a href="<?php echo uploads($verification->file, 'files') ?>" target="_blank" class="btn btn-primary"><?php ee('View Document') ?></a>
                <?php else: ?>
                    <p class="text-danger"><?php ee('Document removed from server') ?></p>
                <?php endif ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php ee('Billing Address') ?></h5>
            </div>
            <div class="card-body h-100">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="billingname"><?php echo e("Full Name") ?></label>
                            <input readonly type="text" class="form-control" id="billingname" name="billingname" placeholder="e.g. John Doe" value="<?php echo (isset($user->address->name) ? $user->address->name : $user->name ) ?>">
                        </div>									
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="company"><?php echo e("Company Name") ?></label>
                            <input readonly type="text" class="form-control" id="company" name="company" placeholder="e.g. Acme Inc" value="<?php echo (isset($user->address->company) ? $user->address->company : "" ) ?>">
                        </div>									
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label" for="address"><?php echo e("Address") ?></label>
                    <input readonly type="text" class="form-control" id="address" name="address" value="<?php echo (isset($user->address->address) ? $user->address->address : "" ) ?>">
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="city"><?php echo e("City") ?></label>
                            <input readonly type="text" class="form-control" id="city" name="city" placeholder="e.g. New York" value="<?php echo (isset($user->address->city) ? $user->address->city : "" ) ?>">
                        </div>									
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="state"><?php echo e("State/Province") ?></label>
                            <input readonly type="text" class="form-control" id="state" name="state" placeholder="e.g. NY" value="<?php echo (isset($user->address->state) ? $user->address->state : "" ) ?>">
                        </div>										
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group input-select">
                            <label class="form-label" for="country"><?php echo e("Country") ?></label>
                            <input readonly type="text" class="form-control" id="state" name="country" placeholder="e.g. NY" value="<?php echo $user->address->country ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="zip"><?php echo e("Zip/Postal code") ?></label>
                            <input readonly type="text" class="form-control" id="zip" name="zip" placeholder="e.g. 44205" value="<?php echo (isset($user->address->zip) ? $user->address->zip : "" ) ?>">
                        </div>										
                    </div>                                  
                </div>
            </div>
        </div>
    </div>
</div>