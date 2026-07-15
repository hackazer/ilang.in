<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo route('admin') ?>"><?php ee('Dashboard') ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo route('admin.plugins') ?>"><?php ee('Plugins') ?></a></li>
  </ol>
</nav>

<div class="d-flex">
    <div>
        <h1 class="h3 mb-5"><?php ee('Plugins Directory') ?></h1>
        <p></p>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
      <form action="<?php echo route('admin.plugins.dir') ?>" method="get" class="card card-body">
        <div class="d-flex">
          <h6><?php ee('Install Plugins') ?></h6>
          <div class="ms-auto">
            <a href="<?php echo route('admin.plugins.dir') ?>" class="btn btn-primary btn-sm"><?php ee('View All') ?></a>
          </div>
        </div>
        <div class="d-flex mt-3">
          <div class="input-group input-group-navbar">
              <input type="text" class="form-control" name="q" value="<?php echo request()->q ?>" placeholder="Search for plugins" aria-label="Search">
              <button class="btn" type="submit">
                <i class="align-middle" data-feather="search"></i>
              </button>
          </div>
        </div>
        <h6 class="my-3"><?php ee('Filter by Category') ?></h6>
        <p>
        <?php foreach($categories as $category): ?>
            <a href="<?php echo route('admin.plugins.dir', ['category' => $category]) ?>" class="btn btn-outline-dark rounded btn-sm <?php echo (request()->category == $category ? 'active' : '' ) ?>"><?php echo ucfirst($category) ?></a>
        <?php endforeach ?>
        </p>
      </form>
    </div>
    <div class="col-md-12">        
        <div class="row">
        <?php if($plugins): ?>
            <h4 class="mb-4"><?php echo count($plugins) ?> Plugins</h4>
            <?php foreach($plugins as $plugin): ?>
                <div class="col-md-3 mb-3">
                    <div class="card h-100 position-relative"> 
                        <?php if($plugin->installed): ?>
                            <p class="position-absolute top-0 start-50 translate-middle"><span class="badge bg-success"><?php ee('Installed') ?></span></p>
                        <?php endif ?>                        
                        <?php if($plugin->thumbnail): ?>
                            <img src="<?php echo $plugin->thumbnail ?>" class="img-fluid rounded">
                        <?php endif ?>
                        <div class="card-body">
                            <p><?php echo $plugin->name ?> (v<?php echo $plugin->version ?>)<br><span class="badge me-2 bg-dark"><?php echo ucfirst($plugin->category) ?></span> <?php echo $plugin->type == "paid" ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-primary">Free</span>' ?></p>
                            <p><a href="<?php echo $plugin->link ?>" target="_blank"><strong><?php echo $plugin->author ?></strong></a></p>
                            <p><?php echo $plugin->description ?></p>
                            <?php if($plugin->type == "paid"): ?>
                                <p><a href="<?php echo $plugin->buy ?>" class="btn btn-success" target="_blank"><?php ee("Purchase") ?></a></p>
                            <?php else: ?>
                                <?php if($plugin->installed): ?>
                                    <?php if(version_compare($plugin->installedversion, $plugin->version, '<')): ?>
                                        <p><a href="<?php echo route('admin.plugins.dir', ['install' => $plugin->tag]) ?>" class="btn btn-primary"><?php ee("Update") ?></a></p>
                                    <?php endif ?>
                                <?php else: ?>
                                    <p><a href="<?php echo route('admin.plugins.dir', ['install' => $plugin->tag]) ?>" class="btn btn-primary"><?php ee("Install") ?></a></p>
                                <?php endif ?>
                            <?php endif ?>
                        </div>
                    </div>                
                </div>
            <?php endforeach ?>
        <?php else: ?>
            <div class="col-md-12">
                <div class="card card-body"><?php ee('No results.') ?></div>
            </div>
        <?php endif ?>
        </div>   
    </div>    
</div>