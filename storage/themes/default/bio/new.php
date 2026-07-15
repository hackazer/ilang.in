<form action="<?php echo route('bio.save') ?>" method="post" data-trigger="loading">
    <div class="d-flex mb-5">
        <h1 class="h4"><?php ee('Create Bio') ?></h1>
        <div class="ms-auto">
            <button type="submit" class="btn btn-primary"><?php ee('Publish') ?></button>
        </div>        
    </div>
    <?php echo csrf() ?>
    <div class="row">
        <div class="col-md-8" id="generator">
            <div class="card card-body">
                <div class="d-flex">
                    <div class="ms-auto">                        
                        <span class="form-check form-switch">
                            <strong><?php ee('Display Avatar') ?></strong>
                            <input class="form-check-input" type="checkbox" data-binary="true" id="avatarenabled" name="avatarenabled" value="1" checked>
                        </span> 
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        <div class="me-3 mb-2 position-relative" style="max-width:200px">
                            <img src="<?php echo user()->avatar()?>" class="rounded w-100" id="useravatar">
                            <div class="position-absolute top-0 end-0">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="dropdown" aria-expanded="false"><i data-feather="more-vertical"></i></button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" data-trigger="uploadavatar"><i data-feather="upload-cloud"></i> <?php ee('Upload Avatar') ?></span></a>
                                        <input type="file" name="avatar" id="avatar" class="d-none">
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="form-group">
                            <label class="form-label"><?php ee('Bio Page Name') ?></label>
                            <input type="text" class="form-control p-2" name="name" placeholder="e.g. For Instagram" data-required>
                        </div>
                        <div class="form-group mt-4">
                            <label class="form-label"><?php ee('Bio Page Alias') ?></label>
                            <div class="d-flex">
                                <div>
                                    <select name="domain" id="domain" class="form-control p-2" data-toggle="select">
                                        <?php foreach($domains as $domain): ?>
                                            <option value="<?php echo $domain ?>"><?php echo $domain ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <p class="form-text"><?php ee('Choose domain to generate the link with') ?></p>
                                </div>
                                <div class="ms-3">
                                    <input type="text" class="form-control p-2" name="custom" placeholder="e.g. my-page">
                                    <p class="form-text"><?php ee('Leave this field empty to generate a random alias') ?></p>
                                </div>
                            </div>
                        </div>                         
                    </div>
                </div>                
            </div>
            <div class="card card-body">
                <ul class="nav nav-pills">
                    <li class="nav-item mb-3 mb-sm-0">
                        <a href="#links" class="nav-link active" data-bs-toggle="collapse" data-bs-parent="#generator"><?php ee('Content') ?></a>
                    </li>
                    <li class="nav-item mb-3 mb-sm-0">
                        <a href="#social" class="nav-link" data-bs-toggle="collapse" data-bs-parent="#generator"><?php ee('Social Links') ?></a>
                    </li>
                    <li class="nav-item mb-3 mb-sm-0">
                        <a href="#appearance" class="nav-link" data-bs-toggle="collapse" data-bs-parent="#generator"><?php ee('Appearance') ?></a>
                    </li>
                    <li class="nav-item mb-3 mb-sm-0">
                        <a href="#advanced" class="nav-link" data-bs-toggle="collapse" data-bs-parent="#generator"><?php ee('Advanced') ?></a>
                    </li>
                </ul>
            </div>
            <div class="collapse show" id="links">
                <div id="linkcontent"></div>
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#contentModal"><?php ee('Add Link or Content') ?></button>
                </div>
            </div>
            <div class="card collapse" id="social">
                <div class="card-header">
                        <h5 class="card-title fw-bold"><i class="me-2" data-feather="link"></i> <?php ee('Social Links') ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Facebook') ?></label>
                        <input type="text" class="form-control p-2" name="social[facebook]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Twitter') ?></label>
                        <input type="text" class="form-control p-2" name="social[twitter]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Instagram') ?></label>
                        <input type="text" class="form-control p-2" name="social[instagram]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Tiktok') ?></label>
                        <input type="text" class="form-control p-2" name="social[tiktok]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Linkedin') ?></label>
                        <input type="text" class="form-control p-2" name="social[linkedin]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Youtube') ?></label>
                        <input type="text" class="form-control p-2" name="social[youtube]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Telegram') ?></label>
                        <input type="text" class="form-control p-2" name="social[telegram]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Snapchat') ?></label>
                        <input type="text" class="form-control p-2" name="social[snapchat]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Discord') ?></label>
                        <input type="text" class="form-control p-2" name="social[discord]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Twitch') ?></label>
                        <input type="text" class="form-control p-2" name="social[twitch]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Pinterest') ?></label>
                        <input type="text" class="form-control p-2" name="social[pinterest]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Shopify') ?></label>
                        <input type="text" class="form-control p-2" name="social[shopify]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Amazon') ?></label>
                        <input type="text" class="form-control p-2" name="social[amazon]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label"><?php ee('Line Messenger') ?></label>
                        <input type="text" class="form-control p-2" name="social[line]" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                    </div>
                </div>
            </div>
            <div class="card collapse" id="appearance">
				<div class="card-header mt-2">
					<h5 class="card-title fw-bold"><i data-feather="plus-circle" class="me-2"></i> <?php ee('Appearance') ?></h5>
				</div>				
				<div class="card-body">
                    <h5><?php ee('Templates') ?></h5>
                    <div class="row mt-3">
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(90deg, #1CB5E0 0%, #000851 100%);" onclick="changeTheme('#1CB5E0', '#1CB5E0', '#000851', '#000851', '#ffffff', '#ffffff');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(90deg, #FC466B 0%, #3F5EFB 100%);" onclick="changeTheme('#FC466B', '#3F5EFB', '#FC466B', '#ffffff', '#FC466B', '#ffffff');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(90deg, #FDBB2D 0%, #22C1C3 100%);" onclick="changeTheme('#FDBB2D', '#22C1C3', '#FDBB2D', '#ffffff', '#FDBB2D', '#ffffff');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%);" onclick="changeTheme('#00c6ff', '#0072ff', '#00c6ff', '#ffffff', '#00c6ff', '#ffffff');">                             
                            </a>
                        </div>                                       
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(90deg, #d53369 0%, #daae51 100%);" onclick="changeTheme('#d53369', '#daae51', '#d53369', '#ffffff', '#d53369', '#ffffff');">                               
                            </a>
                        </div>                    
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(90deg, #ED4264 0%, #FFEDBC 100%);" onclick="changeTheme('#ED4264', '#FFEDBC', '#ED4264', '#ffffff', '#ED4264', '#ffffff');">                               
                            </a>
                        </div>
                    </div>
                    <div class="row mt-3"> 
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(90deg, #232526 0%, #414345 100%);" onclick="changeTheme('#232526', '#414345', '#232526', '#ffffff', '#232526', '#ffffff');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(45deg, #fff 50%, #76b852 51%);" onclick="changeTheme('#ffffff', '#ffffff', '#ffffff', '#76b852', '#ffffff', '#000000');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(45deg, #fff 50%, #f71e3e 51%);" onclick="changeTheme('#ffffff', '#ffffff', '#ffffff', '#f71e3e', '#ffffff', '#000000');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(45deg, #fff 50%, #000000 51%);" onclick="changeTheme('#ffffff', '#ffffff', '#ffffff', '#000000', '#ffffff', '#000000');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(45deg, #fff 50%, #1CB5E0 51%);" onclick="changeTheme('#ffffff', '#ffffff', '#ffffff', '#1CB5E0', '#ffffff', '#000000');">                               
                            </a>
                        </div>
                        <div class="col-2">
                            <a href="#" class="d-block text-center border rounded p-3 d-flex align-self-stretch" style="height:50px;background: linear-gradient(45deg, #fff 50%, #c7ae0a 51%);" onclick="changeTheme('#ffffff', '#ffffff', '#ffffff', '#c7ae0a', '#ffffff', '#000000');">                               
                            </a>
                        </div>
                    </div>
                    <hr>
                    <h5><?php ee('Font') ?></h5> 
                    <div class="input-group">
                        <div class="input-group-text bg-white"><i class="fa fa-font"></i></div>
                        <input type="text" class="form-control border-start-0 ps-0" name="fonts" id="fonts" autocomplete="off">
                    </div>                       
                    <hr>
                    <h5><?php ee('Background') ?></h5>
                    <div class="mb-3 mt-3">
                        <div class="btn-group">
                            <a href="#singlecolor" class="btn btn-primary btn-sm text-white" data-bs-toggle="collapse" data-bs-parent="#appearance"><?php ee('Single Color') ?></a>
                            <a href="#gradient" class="btn btn-primary btn-sm text-white" data-bs-toggle="collapse" data-bs-parent="#appearance"><?php ee('Gradient Color') ?></a>
                            <a href="#image" class="btn btn-primary btn-sm text-white" data-bs-toggle="collapse" data-bs-parent="#appearance"><?php ee('Image') ?></a>
                        </div>                      
                    </div>
                    <input type="hidden" name="mode" value="singlecolor">
                    <div id="singlecolor" class="collapse show">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="bg"><?php ee("Background") ?></label><br>
                                    <input type="text" name="bg" id="bg" value="#fff">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="gradient" class="collapse">                        
                        <div class="row">                            
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="fg"><?php ee("Gradient Start") ?></label><br>
                                    <input type="text" name="gradient[start]" id="bgst" value="#fff">
                                </div>	
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label class="form-label" for="fgs"><?php ee("Gradient Stop") ?></label><br>
                                    <input type="text" name="gradient[stop]" id="bgsp" value="#fff">
                                </div>                                
                            </div>
                        </div>
                    </div>
                    <div id="image" class="collapse">
                        <input type="file" class="form-control mb-4" name="bgimage" id="bgimage">
                    </div>
                    <h5><?php ee('Text Color') ?></h5>
                    <div class="form-group mb-4">
                        <input type="text" name="textcolor" id="textcolor" value="#000">
                    </div>                       
                    <h5><?php ee('Button Color') ?></h5>
                    <div class="form-group mb-4">
                        <input type="text" name="buttoncolor" id="buttoncolor" value="#fff">
                    </div>
                    <h5><?php ee('Button text Color') ?></h5>
                    <div class="form-group mb-4">
                        <input type="text" name="buttontextcolor" id="buttontextcolor" value="#fff">
                    </div>                                 
				</div>
			</div>
            <div class="card collapse" id="advanced">
                <div class="card-header">
                    <h5 class="card-title fw-bold"><i class="me-2" data-feather="settings"></i> <?php ee('Advanced') ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label for="title" class="form-label"><?php ee('Meta Title') ?></label>
                        <input type="text" class="form-control" name="title" id="title" autocomplete="off">
                    </div>
                    <div class="form-group mb-3">
                        <label for="description" class="form-label"><?php ee('Meta Description') ?></label>
                        <textarea class="form-control" name="description" id="description" autocomplete="off"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="pass" class="form-label"><?php ee('Password Protection') ?></label>
                        <p class="form-text"><?php ee('By adding a password, you can restrict the access') ?></p>
                        <div class="input-group">
                            <div class="input-group-text bg-white"><i data-feather="lock"></i></div>
                            <input type="text" class="form-control border-start-0 ps-0" name="pass" id="pass" placeholder="<?php echo e("Type your password here")?>" autocomplete="off">
                        </div>
                    </div>
                    <?php if(\Core\Auth::user()->has("pixels") !== false):?>
                    <div id="pixels" class="mt-4">
                        <label class="form-label"><?php echo e("Targeting Pixels")?></label>
                        <p class="form-text"><?php echo e('Add your targeting pixels below from the list. Please make sure to enable them in the pixels settings.')?></p>
                        <div class="input-group input-select">
                            <span class="input-group-text bg-white"><i data-feather="filter"></i></span>
                            <select name="pixels[]" data-placeholder="Your Pixels" multiple data-toggle="select">
                                <?php foreach(\Core\Auth::user()->pixels() as $type => $pixels): ?>     
                                    <optgroup label="<?php echo ucwords($type) ?>">                       
                                    <?php foreach($pixels as $pixel): ?>
                                        <option value="<?php echo $pixel->type ?>-<?php echo $pixel->id ?>"><?php echo $pixel->name ?></option>
                                    <?php endforeach ?>
                                    </optgroup>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <?php endif ?>
                    <hr>
                    <div class="form-group">
                        <label for="customcss" class="form-label"><?php ee('Custom CSS') ?></label>
                        <textarea class="form-control" name="customcss" id="customcss" rows="5" placeholder="e.g. .btn { display: block }"></textarea>
                    </div>
                </div>
            </div>            
        </div>
        <div class="col-md-4">
            <div id="preview">
                <div class="card border border-5 border-rounded border-dark">
                    <div class="text-center" style="max-height:600px;overflow-y:scroll">
                        <img src="<?php echo user()->avatar() ?>" id="userimage" class="rounded-circle my-3 border" height="120" width="120">
                        <h3><span></span></h3></em>
                        <div class="card-body">
                            <div id="social" class="text-center mt-2">
                            </div>
                            <div id="content" class="mt-5">
                            </div>                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="modal fade" id="contentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content rounded-0">
      <div class="modal-header">
        <h5 class="modal-title"><?php ee('Add Link or Content') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalcontent">
            <div class="collapse show" id="options">
                <h4 class="mb-3"><?php ee('Content') ?></h4>
                <div class="row" id="content-content">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-heading" class="d-block text-decoration-none border rounded p-3 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="fa fa-heading"></i></h1>    
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Heading') ?></h5>
                                    <p class="text-muted"><?php ee('Add a heading with different sizes') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-text" class="d-block text-decoration-none border rounded p-3 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="fa fa-align-center"></i></h1>
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Text') ?></h5>
                                    <p class="text-muted"><?php ee('Add a text body to your page') ?></p>
                                </div>
                            </div>                            
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-divider" class="d-block text-decoration-none border rounded p-3 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="fa fa-grip-lines"></i></h1>
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Divider') ?></h5>
                                    <p class="text-muted"><?php ee('Separate your content with a line') ?></p>
                                </div>
                            </div> 
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-links" class="d-block text-decoration-none border rounded p-3 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="fa fa-link"></i></h1>
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Links') ?></h5>
                                    <p class="text-muted"><?php ee('Add a trackable button to a link') ?></p>
                                </div>
                            </div> 
                        </a>
                    </div> 
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-html" class="d-block text-decoration-none border rounded p-3 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="fa fa-code"></i></h1>
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('HTML') ?></h5>
                                    <p class="text-muted"><?php ee('Add custom HTML code. Script codes are not accepted') ?></p>
                                </div>
                            </div> 
                        </a>
                    </div>                     
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-image" class="d-block text-decoration-none border rounded p-3 h-100" data-trigger="insertcontent" data-type="image">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="fa fa-image"></i></h1>
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Image') ?></h5>
                                    <p class="text-muted"><?php ee('Upload an image') ?></p>
                                </div>
                            </div> 
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-vcard" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="fa fa-address-card"></i></h1>
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('vCard') ?></h5>
                                    <p class="text-muted"><?php ee('Add a downloadable vCard') ?></p>
                                </div>
                            </div> 
                        </a>
                    </div>   
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-paypal" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <img src="<?php echo assets('images/paypal.svg') ?>" width="30">
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('PayPal Button') ?></h5>
                                    <p class="text-muted"><?php ee('Generate a PayPal button to accept payments') ?></p>
                                </div>
                            </div>
                        </a>
                    </div> 
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-whatsapp" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <img src="<?php echo assets('images/whatsapp.svg') ?>" width="30">
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('WhatsApp Call') ?></h5>
                                    <p class="text-muted"><?php ee('Add button that will open whatsapp') ?></p>
                                </div>
                            </div>
                        </a>                        
                    </div>                            
                </div>
                <h4 class="my-3"><?php ee('Widgets') ?></h4>
                <div class="row" id="content-widgets">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-rss" class="d-block text-decoration-none border rounded p-3 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="text-danger fa fa-rss"></i></h1>    
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('RSS Feed') ?></h5>
                                    <p class="text-muted"><?php ee('Add a dynamic RSS feed widget') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-newsletter" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="text-primary fa fa-envelope-open"></i></h1>    
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Newsletter') ?></h5>
                                    <p class="text-muted"><?php ee('Add a newsletter form to store emails') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-contact" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="text-success fa fa-envelope-square "></i></h1>    
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Contact Form') ?></h5>
                                    <p class="text-muted"><?php ee('Add a contact form to receive emails') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-product" data-trigger="insertcontent" data-type="product"  class="d-block text-decoration-none border rounded py-3 px-2 h-100">
                            <div class="d-flex">
                                <div>
                                    <h1><i class="text-warning fa fa-store"></i></h1>    
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Product') ?></h5>
                                    <p class="text-muted"><?php ee('Add a widget to a product on your site') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>   
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-youtube" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <img src="<?php echo assets('images/youtube.svg') ?>" width="30">
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Youtube Video') ?></h5>
                                    <p class="text-muted"><?php ee('Embed a Youtube video') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>                 
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-spotify" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <img src="<?php echo assets('images/spotify.svg') ?>" width="30">
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Spotify Embed') ?></h5>
                                    <p class="text-muted"><?php ee('Embed a Spotify music widget') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-itunes" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <img src="<?php echo assets('images/itunes.svg') ?>" width="30">
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('Spotify Embed') ?></h5>
                                    <p class="text-muted"><?php ee('Embed an Apple music widget') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>                                      
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="#modal-tiktok" class="d-block text-decoration-none border rounded py-3 px-2 h-100" data-bs-toggle="collapse" data-bs-parent="#modalcontent">
                            <div class="d-flex">
                                <div>
                                    <img src="<?php echo assets('images/tiktok.svg') ?>" width="30">
                                </div>
                                <div class="ms-3"> 
                                    <h5 class="fw-bold"><?php ee('TikTok Embed') ?></h5>
                                    <p class="text-muted"><?php ee('Embed a tiktok video') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>                  
                </div>               
            </div>
            <div id="modal-text" class="collapse" data-name="<?php echo e('Text') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Text') ?></label>
                    <textarea class="form-control p-2" id="editor" name="content" placeholder="e.g. some description here"></textarea>
                </div>
                <button type="button" data-trigger="insertcontent" data-type="text" class="btn btn-primary mt-3"><?php ee('Add Text') ?></button>
            </div>
            <div id="modal-links" class="collapse" data-name="<?php echo e('Links') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('FontAwesome Icon Class') ?></label>
                    <input type="text" class="form-control p-2" name="icon" placeholder="e.g. fab fa-twitter">
                    <p class="form-text"><?php ee('You can use any font from the following list:') ?> <a href="https://fontawesome.com/v5/cheatsheet/free/" target="_blank">FontAwesome</a>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Text') ?></label>
                    <input type="text" class="form-control p-2" name="text" placeholder="e.g. My Site">                        
                </div>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Link') ?></label>
                    <input type="text" class="form-control p-2" name="link" placeholder="https://" data-error="<?php ee('Please enter a valid link') ?>">                        
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label class="form-label"><?php ee('Style') ?></label>
                            <select name="style" class="form-select">
                                <option value="rectangular" selected><?php ee('Rectangular') ?></option>
                                <option value="rounded"><?php ee('Rounded') ?></option>
                                <option value="trec"><?php ee('Transparent') ?> <?php ee('Rectangular') ?></option>
                                <option value="tro"><?php ee('Transparent') ?> <?php ee('Rounded') ?></option>
                            </select>
                        </div>
                    </div> 
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label class="form-label"><?php ee('Animation') ?></label>
                            <select name="animation" class="form-select">
                                <option value="none" selected><?php ee('None') ?></option>
                                <option value="shake"><?php ee('Shake') ?></option>
                                <option value="scale"><?php ee('Scale') ?></option>
                                <option value="jello"><?php ee('Jello') ?></option>
                                <option value="vibrate"><?php ee('Vibrate') ?></option>
                                <option value="wobble"><?php ee('Wobble') ?></option>
                            </select>
                        </div>
                    </div>                   
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mt-3">
                            <label class="form-label"><?php ee('Color') ?></label>
                            <p><input type="text" class="form-control p-2" name="color" data-trigger="color" data-default="#ffffff" value="#ffffff"></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mt-3">
                            <label class="form-label"><?php ee('Background') ?></label>
                            <p><input type="text" class="form-control p-2" name="bg" data-trigger="color" value="#0000000"></p>
                        </div>                        
                    </div>                    
                </div>
                <button type="button" data-trigger="insertcontent" data-type="link" class="btn btn-primary mt-3"><?php ee('Add Link') ?></button>
            </div>
            <div id="modal-image" class="collapse" data-name="<?php echo e('Image') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Image') ?></label>
                    <p><?php ee('Click the button add insert an Image then choose the file to upload') ?></p>
                </div>
                <button type="button" data-trigger="insertcontent" data-type="image" class="btn btn-primary mt-3"><?php ee('Add Image') ?></button>
            </div>            
            <div id="modal-youtube" class="collapse" data-name="<?php echo e('Youtube Video') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Link to Video') ?></label>
                    <input type="text" class="form-control p-2" name="link" placeholder="e.g https://www.youtube.com/watch?v=dQw4w9WgXcQ" data-error="<?php ee('Please enter a valid youtube link') ?>">                        
                </div>
                <button type="button" data-trigger="insertcontent" data-type="youtube" class="btn btn-primary mt-3"><?php ee('Add Youtube Video') ?></button>
            </div>
            <div id="modal-whatsapp" class="collapse" data-name="<?php echo e('WhatsApp Call') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Phone Number') ?></label>
                    <input type="text" class="form-control p-2" name="phone" placeholder="e.g +1123456789">                        
                </div>
                <div class="form-group mt-2">
                    <label class="form-label"><?php ee('Label') ?></label>
                    <input type="text" class="form-control p-2" name="label" placeholder="e.g Call us">                        
                </div>
                <button type="button" data-trigger="insertcontent" data-type="whatsapp" class="btn btn-primary mt-3"><?php ee('Add WhatsApp Call') ?></button>
            </div>
            <div id="modal-spotify" class="collapse" data-name="<?php echo e('Spotify Embed') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Link to Spotify Song') ?></label>
                    <input type="text" class="form-control p-2" name="link" placeholder="e.g https://open.spotify.com/track/6PQ88X9TkUIAUIZJHW2upE?si=e8ab004e890a4d2f" data-error="<?php ee('Please enter a valid spotify link') ?>">                        
                </div>
                <button type="button" data-trigger="insertcontent" data-type="spotify" class="btn btn-primary mt-3"><?php ee('Add Spotify') ?></button>
            </div>
            <div id="modal-itunes" class="collapse" data-name="<?php echo e('Apple Music Embed') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Link to Apple Music Song') ?></label>
                    <input type="text" class="form-control p-2" name="link" placeholder="e.g https://music.apple.com/us/album/acapulco-jay-robinson-remix-single/1590557278" data-error="<?php ee('Please enter a valid apple music link') ?>">                        
                </div>
                <button type="button" data-trigger="insertcontent" data-type="itunes" class="btn btn-primary mt-3"><?php ee('Add Apple Music') ?></button>
            </div>
            <div id="modal-paypal" class="collapse" data-name="<?php echo e('PayPal Button') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Label') ?></label>
                    <input type="text" class="form-control p-2" name="label" placeholder="e.g New Hoodie For Sale">
                </div>                
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('PayPal Email') ?></label>
                    <input type="text" class="form-control p-2" name="email" placeholder="e.g myemail@domain.com">
                </div>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Amount') ?></label>
                    <input type="text" class="form-control p-2" name="amount" placeholder="e.g 10">
                </div>
                <div class="form-group input-select mt-3">
                    <label class="form-label d-block mb-2"><?php ee('Currency') ?></label>
                    <select name="currency" data-toggle="select" class="form-control">
                        <?php foreach(\Helpers\App::currency() as $code => $currency): ?>
                            <option value="<?php echo $code ?>" <?php echo $code == "USD" ? 'selected' : '' ?>><?php echo $currency['label'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button type="button" data-trigger="insertcontent" data-type="paypal" class="btn btn-primary mt-3"><?php ee('Add Paypal') ?></button>
            </div>
            <div id="modal-tiktok" class="collapse" data-name="<?php echo e('Tiktok Embed') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group">
                    <label class="form-label"><?php ee('Link to Tiktok Video') ?></label>
                    <input type="text" class="form-control p-2" name="link" placeholder="e.g https://www.tiktok.com/@marvel/video/7016405255604817157" data-error="<?php ee('Please enter a valid tiktok link') ?>">                        
                </div>
                <button type="button" data-trigger="insertcontent" data-type="tiktok" class="btn btn-primary mt-3"><?php ee('Add Tiktok') ?></button>
            </div>
            <div id="modal-heading" class="collapse" data-name="<?php echo e('Heading') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <select name="type" class="form-select">
                    <option value="h1" selected>H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                    <option value="h5">H5</option>
                    <option value="h6">H6</option>
                </select>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Text') ?></label>
                    <input type="text" class="form-control p-2" name="text" placeholder="e.g Heading">                        
                </div>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Color') ?></label>
                    <p><input type="text" class="form-control p-2" name="color" placeholder="e.g Heading" data-trigger="color"></p>
                </div>
                <button type="button" data-trigger="insertcontent" data-type="heading" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div>
            <div id="modal-divider" class="collapse" data-name="<?php echo e('Divider') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>                                
                <div class="form-group mt-3">
                    <select name="type" class="form-select">
                        <option value="solid" selected><?php echo e('Solid') ?></option>
                        <option value="dotted"><?php echo e('Dotted') ?></option>
                        <option value="dashed"><?php echo e('Dashed') ?></option>
                    </select>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Height') ?></label><br>
                    <select name="height" class="form-select">
                        <option value="1" selected>1px</option>
                        <option value="2">2px</option>
                        <option value="4">4px</option>
                        <option value="6">6px</option>
                        <option value="8">8px</option>
                        <option value="10">10px</option>
                    </select>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Color') ?></label><br>
                    <input type="text" class="form-control p-2" name="color" id="dividercolor" value="#000">                        
                </div>
                <button type="button" data-trigger="insertcontent" data-type="divider" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div>
            <div id="modal-rss" class="collapse" data-name="<?php echo e('RSS Feed') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>                
                <div class="form-group mt-3">
                    <p><label class="form-label"><?php ee('RSS Feed') ?></label></p>
                    <input type="text" class="form-control p-2" name="link" id="link" value="" placeholder="e.g. https://mysite/rss" data-error="<?php ee('Please enter a valid link') ?>">
                </div>
                <button type="button" data-trigger="insertcontent" data-type="rss" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div> 
            <div id="modal-vcard" class="collapse" data-name="<?php echo e('vCard') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('First Name') ?></label>
                    <input type="text" class="form-control p-2" name="fname" placeholder="e.g. John">                     
                </div>  
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Last Name') ?></label>
                    <input type="text" class="form-control p-2" name="lname" placeholder="e.g. Smith">                     
                </div>      
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Phone') ?></label>
                    <input type="text" class="form-control p-2" name="phone" placeholder="e.g. +123456789">                     
                </div>            
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Email') ?></label>
                    <input type="text" class="form-control p-2" name="email" placeholder="e.g. john@domain.com">                     
                </div> 
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Site') ?></label>
                    <input type="text" class="form-control p-2" name="site" placeholder="e.g. domain.com">                     
                </div> 
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Address') ?></label>
                    <input type="text" class="form-control p-2" name="address" placeholder="e.g. 1 infinite loop">                     
                </div>  
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('City') ?></label>
                    <input type="text" class="form-control p-2" name="city" placeholder="e.g. NY">                     
                </div>  
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('State') ?></label>
                    <input type="text" class="form-control p-2" name="state" placeholder="e.g. New York">                     
                </div>  
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Country') ?></label>
                    <input type="text" class="form-control p-2" name="country" placeholder="e.g. Canada">   
                </div>  
                <button type="button" data-trigger="insertcontent" data-type="vcard" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div>
            <div id="modal-product" class="collapse" data-name="<?php echo e('Product') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a> 
                <p><?php ee('This widget allows you to add a custom product. Click the button add below to insert a the widget') ?></p>                
                <button type="button" data-trigger="insertcontent" data-type="product" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div>
            <div id="modal-newsletter" class="collapse" data-name="<?php echo e('Newsletter') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Text') ?></label>
                    <input type="text" class="form-control p-2" name="text" placeholder="e.g. Subscribe">   
                    <p class="form-text"><?php ee('Newsletter list can be downloaded on the Edit Bio page') ?></p>                     
                </div>                
                <button type="button" data-trigger="insertcontent" data-type="newsletter" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div>
            <div id="modal-contact" class="collapse" data-name="<?php echo e('Contact') ?>">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Text') ?></label>
                    <input type="text" class="form-control p-2" name="text" placeholder="e.g. Contact us">                        
                </div>    
                <div class="form-group mt-3">
                    <label class="form-label"><?php ee('Email') ?></label>
                    <input type="email" class="form-control p-2" name="email" placeholder="e.g. email@domain.com">     
                    <p class="form-text"><?php ee('You will receive emails here') ?></p>
                </div>             
                <button type="button" data-trigger="insertcontent" data-type="contact" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div>
            <div id="modal-html" class="collapse" data-name="HTML">
                <a href="#options" data-bs-toggle="collapse" data-bs-parent="#modalcontent" class="mb-4 d-block"><i data-feather="chevron-left"></i> <?php ee('Back') ?></a>
                <div class="form-group mt-3">
                    <label class="form-label">HTML</label>
                    <textarea class="form-control" name="code"></textarea>                     
                </div>                
                <button type="button" data-trigger="insertcontent" data-type="html" class="btn btn-primary mt-3"><?php ee('Add') ?></button>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>