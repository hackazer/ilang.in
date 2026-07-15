<div class="d-flex">
    <div>
        <h1 class="h3 mb-5"><?php ee('Channels') ?></h1>
    </div>
    <?php if(user()->teamPermission('bundle.create')): ?>
    <div class="ms-auto">
        <a href="#" data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-primary"><?php ee('Create Channel') ?></a>
    </div>
    <?php endif ?>
</div>
<?php if($starred): ?>
<h5 class="h5 mb-3"><i data-feather="star"></i> <span class="align-middle ms-2"><?php ee('Starred Channels') ?></span></h5>
<div class="row">
    <?php foreach($starred as $channel): ?>
        <div class="col-sm-6 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="float-end">
                        <button type="button" class="btn btn-default bg-white btn-sm" data-bs-toggle="dropdown" aria-expanded="false"><i data-feather="more-vertical"></i></button>
                        <ul class="dropdown-menu">
                        <?php if(\Core\Auth::user()->teamPermission('bundle.edit')): ?>
                            <li><a class="dropdown-item" href="<?php echo route('channel.update', [$channel->id]) ?>" data-bs-toggle="modal" data-bs-target="#updateModal" data-toggle="updateFormContent" data-content='<?php echo json_encode(['newname' => $channel->name, 'newdescription' => $channel->description, 'newcolor' => $channel->color, 'newstarred' => $channel->starred]) ?>'><i data-feather="edit"></i> <?php ee('Edit') ?></span></a></li>
                        <?php endif ?>
                        <?php if(\Core\Auth::user()->teamPermission('bundle.delete')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo route('channel.delete', [$channel->id, \Core\Helper::nonce('channel.delete')]) ?>" data-bs-toggle="modal" data-trigger="modalopen" data-bs-target="#deleteModal"><i data-feather="trash"></i> <?php ee('Delete') ?></span></a></li>
                        <?php endif ?>
                        </ul>                        
                    </div>
                    <h4 class="align-middle mb-2"><span class="badge me-2 px-2" style="background:<?php echo $channel->color ?>">&nbsp;</span> <?php echo $channel->name ?></h4>
                    <p><?php echo $channel->description ?></p>
                    <a href="<?php echo route('channel', [$channel->id]) ?>" class="btn btn-outline-secondary"><?php ee('View Channel') ?> <i class="fa fa-chevron-right ms-2"></i></a>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<h5 class="h5 mb-3"><i data-feather="package"></i> <span class="align-middle ms-2"><?php ee('My Channels') ?></span></h5>
<div class="row">
    <?php foreach($channels as $channel): ?>
        <div class="col-sm-6 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="float-end">
                        <button type="button" class="btn btn-default bg-white btn-sm" data-bs-toggle="dropdown" aria-expanded="false"><i data-feather="more-vertical"></i></button>
                        <ul class="dropdown-menu">
                        <?php if(\Core\Auth::user()->teamPermission('bundle.edit')): ?>
                            <li><a class="dropdown-item" href="<?php echo route('channel.update', [$channel->id]) ?>" data-bs-toggle="modal" data-bs-target="#updateModal" data-toggle="updateFormContent" data-content='<?php echo json_encode(['newname' => $channel->name, 'newdescription' => $channel->description, 'newcolor' => $channel->color, 'newstarred' => $channel->starred]) ?>'><i data-feather="edit"></i> <?php ee('Edit') ?></span></a></li>
                        <?php endif ?>
                        <?php if(\Core\Auth::user()->teamPermission('bundle.delete')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo route('channel.delete', [$channel->id, \Core\Helper::nonce('channel.delete')]) ?>" data-bs-toggle="modal" data-trigger="modalopen" data-bs-target="#deleteModal"><i data-feather="trash"></i> <?php ee('Delete') ?></span></a></li>
                        <?php endif ?>
                        </ul>
                    </div>
                    <h4 class="align-middle mb-2"><span class="badge me-2 px-2" style="background:<?php echo $channel->color ?>">&nbsp;</span> <?php echo $channel->name ?></h4>
                    <p><?php echo $channel->description ?></p>
                    <a href="<?php echo route('channel', [$channel->id]) ?>" class="btn btn-outline-secondary"><?php ee('View Channel') ?> <i class="fa fa-chevron-right ms-2"></i></a>
                </div>
            </div>
        </div>
    <?php endforeach ?>
    <?php echo pagination('pagination') ?>
</div>
<?php if(user()->teamPermission('bundle.create')): ?>
<div class="modal fade" id="addModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <form action="<?php echo route('channel.save') ?>" method="post">
            <div class="modal-header">
                <h5 class="modal-title"><?php ee('Create a Channel') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf() ?>
                <div class="form-group mb-3">
                    <label class="form-label"><?php ee("Name") ?> (<?php ee("required") ?>)</label>			
                    <input type="text" value="" name="name" class="form-control">
                </div> 
                <div class="form-group mb-3">
                    <label class="form-label"><?php ee("Description") ?></label>			
                    <input type="text" value="" name="description" class="form-control">
                </div>   
                <div class="form-group mb-3">
                    <label class="form-label d-block"><?php ee("Badge Color") ?></label>			
                    <input type="color" value="" name="color" class="form-control" data-trigger="colorpicker">
                </div> 
                <div class="d-flex">
                    <div>
                        <label class="form-check-label" for="starred"><?php ee('Star Channel') ?></label>
                        <p class="form-text"><?php ee('Starred channels will show up in the sidebar navigation for quick access.') ?></p>
                    </div>
                    <div class="form-check form-switch ms-auto">
                        <input class="form-check-input" type="checkbox" data-binary="true" id="starred" name="starred" value="1">
                    </div>                    
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php ee('Cancel') ?></button>
                <button type="submit" class="btn btn-success"><?php ee('Create Channel') ?></button>
            </div>
        </form>
    </div>
  </div>
</div>
<?php endif ?>
<?php if(user()->teamPermission('bundle.edit')): ?>
<div class="modal fade" id="updateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <form action="#" method="post">
            <div class="modal-header">
                <h5 class="modal-title"><?php ee('Update Channel') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf() ?>
                <div class="form-group mb-3">
                    <label class="form-label"><?php ee("Name") ?> (<?php ee("required") ?>)</label>			
                    <input type="text" value="" name="newname" id="newname" class="form-control">
                </div> 
                <div class="form-group mb-3">
                    <label class="form-label"><?php ee("Description") ?></label>			
                    <input type="text" value="" name="newdescription" id="newdescription" class="form-control">
                </div>   
                <div class="form-group mb-3">
                    <label class="form-label d-block"><?php ee("Badge Color") ?></label>			
                    <input type="color" value="" name="newcolor" id="newcolor" class="form-control" data-trigger="colorpicker">
                </div>
                <div class="d-flex">
                    <div>
                        <label class="form-check-label" for="starred"><?php ee('Star Channel') ?></label>
                        <p class="form-text"><?php ee('Starred channels will show up in the sidebar navigation for quick access.') ?></p>
                    </div>
                    <div class="form-check form-switch ms-auto">
                        <input class="form-check-input" type="checkbox" data-binary="true" id="newstarred" name="newstarred" value="1">
                    </div>                    
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php ee('Cancel') ?></button>
                <button type="submit" class="btn btn-success"><?php ee('Update') ?></button>
            </div>
        </form>
    </div>
  </div>
</div>
<?php endif ?>
<?php if(user()->teamPermission('bundle.delete')): ?>
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php ee('Are you sure you want to delete this?') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><?php ee('You are trying to delete a record. This action is permanent and cannot be reversed.') ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php ee('Cancel') ?></button>
        <a href="#" class="btn btn-danger" data-trigger="confirm"><?php ee('Confirm') ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif ?>