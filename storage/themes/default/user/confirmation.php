<div class="d-flex mb-5">
    <div>
        <h1 class="h3 mb-2"><?php ee('Order Confirmation') ?></h1>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="invoice p-5">
                <div class="payment border-top mt-3 mb-3 border-bottom table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="py-2">
                                        <span class="d-block text-muted"><?php ee('Order Date') ?></span>
                                        <span><?php echo \Core\Helper::dtime($subscription->date, 'd-m-Y') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="py-2">
                                        <span class="d-block text-muted"><?php ee('Order No') ?></span>
                                        <span><?php echo $subscription->uniqueid ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="py-2">
                                        <span class="d-block text-muted"><?php ee('Billing Address') ?></span>
                                        <span><?php echo $user->address && $user->address->address ? $user->address->address.'<br>' : '' ?>
                                            <?php echo $user->address && $user->address->city ? $user->address->city.'<br>' : '' ?> 
                                            <?php echo $user->address && $user->address->state ? $user->address->state.'<br>' : '' ?> 
                                            <?php echo $user->address && $user->address->zip ? $user->address->zip.'<br>' : '' ?> 
                                            <?php echo $user->address && $user->address->country ? $user->address->country.'<br>' : '' ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="product border-bottom table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td width="80%">
                                    <span class="font-weight-bold"><?php echo $plan['name'] ?></span>
                                    <div class="product-qty">
                                        <span class="d-block"><?php echo $subscription->plan ?></span>
                                    </div>
                                </td>
                                <td width="20%">
                                    <div class="text-right">
                                        <span class="font-weight-bold"><?php echo \Helpers\App::currency(config('currency'), number_format($plan['price_'.$subscription->plan], 2)) ?></span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-between footer p-3">
                <span><?php ee('Need Help?') ?> <a href="<?php echo route('contact') ?>"><?php ee('Contact us') ?></a></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3"><?php ee('Current Plan') ?>: <?php echo $plan['name'] ?></h5>
                <ul class="list-unstyled mb-4 text-left text-sm">
                    <li class="mb-1"><?php echo $plan["permission"]->alias->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger"></span>' ?>  <?php echo e("Custom Aliases") ?></li>        		
                    <li class="mb-1"><span data-feather="check-circle" class="mr-1 text-success"></span> <?php echo $plan["urls"] == "0" ? e("Unlimited") : $plan["urls"] ?> <?php echo e("URLs allowed") ?></li>
                    <li class="mb-1"><span data-feather="check-circle" class="mr-1 text-success"></span> <?php echo $plan["clicks"] == "0" ? e("Unlimited") : $plan["clicks"] ?> <?php echo e("Clicks per month") ?></li>											
                    <li class="mb-1 <?php echo $plan["permission"]->geo->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->geo->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?> <?php echo e("Geotargeting"); ?></li>
                    <li class="mb-1 <?php echo $plan["permission"]->device->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->device->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?> <?php echo e("Device Targeting"); ?></li>     
                    <li class="mb-1 <?php echo isset($plan["permission"]->language) && $plan["permission"]->language->enabled ? '' : 'text-muted' ?>"><?php echo isset($plan["permission"]->language) && $plan["permission"]->language->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?> <?php echo e("Language Targeting"); ?></li>  
                    <li class="mb-1 <?php echo $plan["permission"]->bio->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->bio->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->bio->count == "0" ? e("Unlimited") : $plan["permission"]->bio->count)." ".e("Bio Profiles"); ?></li>
                    <li class="mb-1 <?php echo $plan["permission"]->qr->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->qr->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->qr->count == "0" ? e("Unlimited") : $plan["permission"]->qr->count)." ".e("QR Codes"); ?></li>
                    <li class="mb-1 <?php echo $plan["permission"]->splash->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->splash->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->splash->count == "0" ? e("Unlimited") : $plan["permission"]->splash->count)." ".e("Custom Splash"); ?></li>
                    <li class="mb-1 <?php echo $plan["permission"]->overlay->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->overlay->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->overlay->count == "0" ? e("Unlimited") : $plan["permission"]->overlay->count)." ".e("CTA Overlay"); ?></li>
                    <li class="mb-1 <?php echo $plan["permission"]->pixels->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->pixels->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->pixels->count == "0" ? e("Unlimited") : $plan["permission"]->pixels->count)." ".e("Event Tracking"); ?></li>
                    <li class="mb-1 <?php echo $plan["permission"]->team->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->team->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->team->count == "0" ? e("Unlimited") : $plan["permission"]->team->count)." ".e("Team Members"); ?></li>
                    <li class="mb-1 <?php echo $plan["permission"]->domain->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->domain->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->domain->count == "0" ? e("Unlimited") : $plan["permission"]->domain->count)." ".e("Branded Domains"); ?></li>
                    <li class="mb-1 <?php echo isset($plan["permission"]->channels) && $plan["permission"]->channels->enabled ? '' : 'text-muted' ?>"><?php echo isset($plan["permission"]->channels) && $plan["permission"]->channels->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo (!isset($plan["permission"]->channels) || $plan["permission"]->channels->count == "0" ? e("Unlimited") : $plan["permission"]->channels->count)." ".e("Channels"); ?></li>
                    <?php if($features = plug('feature')): ?>
                        <?php foreach($features as $feature): ?>
                            <?php if($feature['count']): ?>
                                <li class="mb-1 <?php echo $plan["permission"]->{$feature['slug']}->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->{$feature['slug']}->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo ($plan["permission"]->{$feature['slug']}->count == "0" ? e("Unlimited") : $plan["permission"]->{$feature['slug']}->count)." ".$feature['name']; ?></li>                                
                            <?php else: ?>
                                <li class="mb-1"><?php echo $plan["permission"]->{$feature['slug']}->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span> ' : '<span data-feather="x-circle" class="text-danger mr-1"></span> ' ?>  <?php echo $feature['name'] ?></li>
                            <?php endif ?>
                        <?php endforeach ?>
                    <?php endif ?>
                    <li class="mb-1 <?php echo $plan["permission"]->bundle->enabled ? '' : 'text-muted' ?>"><?php echo $plan["permission"]->bundle->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo e("Campaigns & Link Rotator") ?></li>        
                    <li class="mb-1"><?php echo $plan["permission"]->export->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span> ' : '<span data-feather="x-circle" class="text-danger mr-1"></span> ' ?>  <?php echo e("Export Data") ?></li>        
                    <li class="mb-1"><?php echo $plan["permission"]->api->enabled ? '<span data-feather="check-circle" class="mr-1 text-success"></span>' : '<span data-feather="x-circle" class="text-danger mr-1"></span>' ?>  <?php echo e("Developer API"); ?></li>											              
                    <li class="mb-1"><?php echo $plan["free"]  ? '<span data-feather="x-circle" class="text-danger mr-1"></span>' : '<span data-feather="check-circle" class="mr-1 text-success"></span>' ?> <?php echo e("URL Customization") ?></li>                
                    <li class="mb-1"><?php echo $plan["free"]  ? '<span data-feather="x-circle" class="mr-1 text-danger"></span>' : '<span data-feather="check-circle" class="text-success"></span>' ?> <?php echo e("Advertisement-Free") ?></li> 
                    <?php echo $plan["permission"]->custom  ? '<li class="mb-1"><span data-feather="check-circle" class="text-success"></span> '.$plan["permission"]->custom.'</li>' : '' ?>
                </ul>
            </div>
        </div>        
    </div>
</div>