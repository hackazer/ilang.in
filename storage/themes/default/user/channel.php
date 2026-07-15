<div class="d-flex mb-5">
    <div>
        <h1 class="h3 mb-2"><span class="badge me-2 px-2" style="background:<?php echo $channel->color ?>">&nbsp;</span><?php echo $channel->name ?></h1>
        <p class="text-muted"><?php echo $channel->description ?></p>
    </div>
    <div class="ms-auto">
        <a class="btn btn-primary shadow" href="<?php echo route('channel.update', [$channel->id]) ?>" data-bs-toggle="modal" data-bs-target="#updateModal" data-toggle="updateFormContent" data-content='<?php echo htmlentities(json_encode(['newname' => $channel->name, 'newdescription' => $channel->description, 'newcolor' => $channel->color, 'newstarred' => $channel->starred]), ENT_QUOTES) ?>'><?php ee('Edit') ?></span></a>
    </div>
</div>
<div class="row">
    <?php foreach($items as $item): ?>
        <div class="col-md-4">
            <div class="card flex-fill">
                <div class="card-body">        
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <?php if($item['type'] == "bio"): ?>
                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php ee('Bio Pages') ?>"><i data-feather="layout"></i></span>
                            <?php elseif($item['type'] == "qr"): ?>
                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php ee('QR Codes') ?>"><i data-feather="aperture"></i></span>
                            <?php else: ?>
                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php ee('Links') ?>"><i data-feather="link"></i></span>
                            <?php endif ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="float-end">
                                <button type="button" class="btn btn-default shadow-lg bg-white" data-bs-toggle="dropdown" aria-expanded="false"><i data-feather="more-horizontal"></i></button>                                
                                <ul class="dropdown-menu">
                                <?php if(isset($item['urlid']) && $item['urlid']): ?>
                                    <li><a class="dropdown-item" href="<?php echo route('stats', [$item['urlid']]) ?>"><i data-feather="bar-chart-2"></i> <?php ee('Statistics') ?></a></li>
                                <?php endif ?>
                                <?php if(user()->teamPermission($item['type'].'.edit')): ?>
                                    <li><a class="dropdown-item" href="<?php echo route($item['type'].'.edit', [$item['id']]) ?>"><i data-feather="edit"></i> <?php ee('Edit') ?></a></li>    
                                <?php endif ?>
                                <?php if(user()->teamPermission('bundles.edit')): ?>
                                    <li><a class="dropdown-item" data-bs-toggle="modal" data-trigger="modalopen" data-bs-target="#deleteModal" href="<?php echo route('channel.removefrom', [$channel->id, $item['type'], $item['id']]) ?>"><i data-feather="trash"></i> <?php ee('Remove from channel') ?></a></li>
                                <?php endif ?>
                                </ul>
                            </div>
                            <?php if($item['preview']): ?>
                            <a href="<?php echo $item['preview'] ?>" target="_blank"><strong><?php echo $item['title'] ?: 'n\a' ?></strong></a><br>
                            <div>
                                <small class="text-muted" data-href="<?php echo $item['link'] ?>"><?php echo $item['link'] ?></small>
                                <a href="#copy" class="copy inline-copy" data-lang="<?php ee('Copied') ?>" data-clipboard-text="<?php echo $item['link'] ?>"><small><?php echo e("Copy")?></small></a>	
                            </div>
                            <?php else: ?>
                               <strong><?php echo $item['title'] ?: 'n\a' ?></strong><br><br>
                            <?php endif ?>
                            <small class="text-navy"><?php echo $item['date'] ?></small>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?php echo pagination('pagination') ?>
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php ee('Are you sure you want to remove this item from this channel?') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><?php ee('You are trying to remove an item from a channel.') ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php ee('Cancel') ?></button>
        <a href="#" class="btn btn-danger" data-trigger="confirm"><?php ee('Confirm') ?></a>
      </div>
    </div>
  </div>
</div>
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