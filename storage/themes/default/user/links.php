<h3 class="h3 mb-5"><?php echo $title ?></h3>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex align-items-center">                
                <div>
                    <form method="post" action="" data-trigger="options">
                        <?php echo csrf() ?>
                        <input type="hidden" name="selected">
                        <div class="btn-group btn-group-sm border rounded px-1">
                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo e("Select All") ?>" data-trigger="selectall" class="fa fa-check-square btn px-3 py-2"></a>
                            <?php if(user()->teamPermission('links.edit')): ?>
                                <?php if(\Gem::currentRoute() == 'archive'): ?>
                                    <a href="<?php echo route('links.unarchive') ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo e("Unarchive Selected") ?>" data-trigger="archiveselected" class="fa fa-briefcase btn px-3 py-2 border-start"></a>
                                <?php else: ?>
                                    <a href="<?php echo route('links.archive') ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo e("Archive Selected") ?>" data-trigger="archiveselected" class="fa fa-briefcase btn px-3 py-2 border-start"></a>
                                <?php endif ?>
                            <?php endif ?>
                            <?php if(user()->teamPermission('links.edit')): ?>
                            <span data-bs-toggle="modal" data-bs-target="#bundleModal" data-trigger="getchecked" data-for="#bundleids">
                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo e("Add to Campaign") ?>" class="fa fa-crosshairs btn px-3 py-2 border-start"></a>
                            </span>
                            <span data-bs-toggle="modal" data-bs-target="#channelModal" data-trigger="getchecked" data-for="#channelids">
                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo e("Add to Channel") ?>" class="fa fa-box btn px-3 py-2 border-start"></a>
                            </span>
                            <?php endif ?>
                            <?php if(user()->teamPermission('links.delete')): ?>
                                <a data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo e("Delete Selected") ?>" href="<?php echo route('links.deleteall') ?>" data-trigger="submitchecked" class="fa fa-trash btn px-3 py-2 border-start"></a>
                            <?php endif ?>
                        </div>
                    </form>                    
                </div>
                <div class="my-md-0 my-2 ms-auto">                  
                    <div class="rounded border">
                      <form action="<?php echo route('links') ?>" method="get">
                        <a href="#search" data-bs-toggle="collapse" class="btn btn-white bg-white"><i class="align-middle" data-feather="search"></i></a>
                        <button type="button" class="btn btn-default bg-white border-start" data-bs-toggle="dropdown"  aria-expanded="false"><span  data-bs-toggle="tooltip" data-bs-placement="top" title="<?php ee('Sort Results') ?>"><i data-feather="filter"></i></span></button>  
                        <div class="dropdown-menu p-2">
                            <div class="input-select d-block mb-2">
                              <label for="perpage" class="form-label"><?php ee('Sort By') ?></label>
                              <select name="sort" id="sortable" data-name="sort" class="form-select">
                                <optgroup label="Sort by">
                                  <option value=""<?php if(!request()->sort) echo " selected" ?>><?php ee('Newest') ?></option>
                                  <option value="old"<?php if(request()->sort == 'old') echo " selected" ?>><?php ee('Oldest') ?></option>
                                  <option value="most"<?php if(request()->sort == 'most') echo " selected" ?>><?php ee('Most Popular') ?></option>
                                  <option value="less"<?php if(request()->sort == 'less') echo " selected" ?>><?php ee('Less Popular') ?></option>       
                                </optgroup>
                              </select>
                            </div>
                            <div class="input-select d-block mb-2">
                                <label for="perpage" class="form-label"><?php ee('Results Per Page') ?></label>
                                <select name="perpage" id="perpage" data-name="perpage" class="form-select">
                                    <option value="15"<?php if(!request()->perpage) echo " selected" ?>>15</option>
                                    <option value="50"<?php if(request()->perpage == 50) echo " selected" ?>>50</option>
                                    <option value="100"<?php if(request()->perpage == 100) echo " selected" ?>>100</option>
                                </select>
                            </div>      
                            <div class="input-select d-block mb-2">
                              <label for="perpage" class="form-label"><?php ee('Older than') ?></label>
                              <input type="text" class="form-control" name="date" placeholder="" value="<?php echo clean(request()->date) ?>" data-toggle="datepicker">
                            </div>    
                            <button type="submit" class="btn btn-primary"><?php ee('Filter') ?></button>
                        </div>
                      </form>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <form class="rounded border collapse mb-4 p-3" id="search" action="<?php echo route('search') ?>">
                    <div class="input-group input-group-navbar">
                        <input type="text" class="form-control bg-white" placeholder="<?php ee('Search for links') ?>" aria-label="Search">
                        <button class="btn btn-white bg-white" type="submit">
                            <i class="align-middle" data-feather="search"></i>
                        </button>
                        <button type="button" data-bs-toggle="collapse" data-bs-target="#search" class="btn btn-white d-none bg-white" data-trigger="clearsearch">
                            <i class="align-middle" data-feather="x"></i>
                        </button>
                    </div>
                </form>
                <div id="return-ajax"></div>
                <div id="link-holder" data-refresh="<?php echo \Gem::currentRoute() == 'archive' ? route('links.refresh.archive') : route('links.refresh') ?>" data-fetch="<?php echo route('links.fetch')?>">
                    <?php foreach($urls as $url): ?>
                        <?php view('partials.links', compact('url')) ?>      
                    <?php endforeach ?>                     
                    
                    <div class="d-flex">
                        <?php echo pagination() ?>
                    </div> 
                </div>           
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <?php \Helpers\App::ads('resp') ?>
        
        <?php if(\Models\User::where('id', user()->rID())->first()->has('export') !== false): ?>
        <div class="card">
            <div class="card-body">                            
                <h5 class="card-title fw-bold"><?php ee('Export Links') ?></h5>
                <p><?php ee('This tool allows you to generate a list of urls in CSV format. Some basic data such clicks will be included as well.') ?></p>
                <a href="<?php echo route('user.export.links') ?>" class="btn btn-success"><?php ee('Export') ?></a>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>
<?php if(user()->teamPermission('links.delete')): ?>
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
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
<div class="modal fade" id="resetModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php ee('Are you sure you want to reset this?') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><?php ee('You are trying to reset all statistic data for this link. This action is permanent and cannot be reversed.') ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php ee('Cancel') ?></button>
        <a href="#" class="btn btn-danger" data-trigger="confirm"><?php ee('Confirm') ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif ?>
<div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php ee('Short Link Info') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex">
            <div class="modal-qr me-3">
                <p></p>
                <div class="btn-group" role="group" aria-label="downloadQR">
                    <a href="#" class="btn btn-primary" id="downloadPNG"><?php ee('Download') ?></a>
                    <div class="btn-group" role="group">
                        <button id="btndownloadqr" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">PNG</button>
                        <ul class="dropdown-menu" aria-labelledby="btndownloadqr">
                            <li><a class="dropdown-item" href="#">PDF</a></li>
                            <li><a class="dropdown-item" href="#">SVG</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <div class="form-group">
                    <label for="short" class="form-label"><?php ee('Short Link') ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="modal-input" name="shortlink" value="">
                        <div class="input-group-text bg-white">
                            <button class="btn btn-primary copy" data-clipboard-text=""><?php ee('Copy') ?></button>
                        </div>
                    </div>
                </div>    
                <div class="mt-3" id="modal-share">
                    <?php echo \Helpers\App::share('--url--') ?>
                </div>
            </div>            
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal"><?php ee('Done') ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="bundleModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">    
    <div class="modal-content">
      <form action="<?php echo route('links.addtocampaign') ?>" data-trigger="server-form">
        <?php echo csrf() ?>
        <div class="modal-header">
            <h5 class="modal-title"><?php ee('Add to Campaign') ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <label for="campaigns" class="form-label d-block mb-2"><?php ee('Campaigns') ?></label>
            <div class="input-group input-select">            
                <select name="campaigns" id="campaigns" class="form-control" data-toggle="select">
                    <option value="0"><?php ee('None') ?></option>
                    <?php foreach(\Core\DB::bundle()->where('userid', user()->rID())->findArray() as $campaign): ?>
                        <option value="<?php echo $campaign['id'] ?>"><?php echo $campaign['name'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <input type="hidden" name="bundleids" id="bundleids" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php ee('Cancel') ?></button>
            <button type="submit" class="btn btn-success" class="btn btn-success" data-bs-dismiss="modal" data-trigger="addtocampaign"><?php ee('Add') ?></button>
        </div>          
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="channelModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">    
    <div class="modal-content">
      <form action="<?php echo route('channel.addto', ['links', null]) ?>" data-trigger="server-form">
        <?php echo csrf() ?>
        <div class="modal-header">
            <h5 class="modal-title"><?php ee('Add to Channels') ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <label for="channels" class="form-label d-block mb-2"><?php ee('Channels') ?></label>
            <div class="form-group rounded input-select">
                <select name="channels[]" id="channels" class="form-control" multiple data-toggle="select">
                    <?php foreach(\Core\DB::channels()->where('userid', user()->rID())->findArray() as $channel): ?>
                        <option value="<?php echo $channel['id'] ?>"><?php echo $channel['name'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <input type="hidden" name="channelids" id="channelids" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php ee('Cancel') ?></button>
            <button type="submit" class="btn btn-success" class="btn btn-success" data-bs-dismiss="modal" data-trigger="addtocampaign"><?php ee('Add') ?></button>
        </div>          
      </form>
    </div>
  </div>
</div>