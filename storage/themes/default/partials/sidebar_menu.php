<ul class="sidebar-nav">
    <li class="sidebar-item active">
        <a class="sidebar-link" href="<?php echo route('dashboard') ?>">
            <i class="align-middle" data-feather="sliders"></i> <span class="align-middle"><?php ee('Dashboard') ?></span>
        </a>
    </li>
    <?php if($user->has('bio')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('bio') ?>">
            <i class="align-middle" data-feather="layout"></i> <span class="align-middle"><?php ee('Bio Pages') ?></span>
        </a>
    </li>
    <?php endif ?>
    <?php if($user->has('qr')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('qr') ?>">
            <i class="align-middle" data-feather="aperture"></i> <span class="align-middle"><?php ee('QR Codes') ?></span>
        </a>
    </li>
    <?php endif ?>
    <?php plug('usermenu.top') ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('user.stats') ?>">
            <i class="align-middle" data-feather="bar-chart"></i> <span class="align-middle"><?php ee('Statistics') ?></span>
        </a>
    </li>
    <?php if($user->has('channels')): ?>
        <li class="sidebar-header"><?php ee('Channels') ?></li>
        <?php foreach($channels = \Core\DB::channels()->where('userid', $user->rID())->where('starred', 1)->orderByAsc('name')->findMany() as $channel): ?>
            <li class="sidebar-item"><a class="sidebar-link" href="<?php echo route('channel', [$channel->id]) ?>"><span class="badge me-2 roundeds px-2" style="background:<?php echo $channel->color ?>">&nbsp;</span> <?php echo $channel->name ?></a></li>
        <?php endforeach ?>
        <li class="sidebar-item">
            <a class="sidebar-link" href="<?php echo route('channels') ?>">
                <i data-feather="package"></i> <span class="align-middle"><?php ee('My Channels') ?></span>
            </a>
        </li>
    <?php endif ?>   
    <li class="sidebar-header"><?php ee('Link Management') ?></li>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('links') ?>">
            <i class="align-middle" data-feather="link"></i> <span class="align-middle"><?php ee('Links') ?> </span>
        </a>
    </li>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('archive') ?>">
            <i class="align-middle" data-feather="briefcase"></i> <span class="align-middle"><?php ee('Archived Links') ?> </span>
        </a>
    </li>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('expired') ?>">
            <i class="align-middle" data-feather="calendar"></i> <span class="align-middle"><?php ee('Expired Links') ?></span>
        </a>
    </li>    
    <?php plug('usermenu.medium') ?>
    <?php if($user->has('bundle')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('campaigns') ?>">
            <i class="align-middle" data-feather="crosshair"></i> <span class="align-middle"><?php ee('Campaigns') ?></span>
        </a>
    </li>    
    <?php endif ?>
    <?php if($user->has('splash')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('splash') ?>">
            <i class="align-middle" data-feather="loader"></i> <span class="align-middle"><?php ee('Custom Splash') ?></span>
        </a>
    </li>    
    <?php endif ?>
    <?php if($user->has('overlay')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('overlay') ?>">
            <i class="align-middle" data-feather="layers"></i> <span class="align-middle"><?php ee('CTA Overlay') ?></span>
        </a>
    </li>    
    <?php endif ?>
    <?php if($user->has('pixels')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('pixel') ?>">
            <i class="align-middle" data-feather="compass"></i> <span class="align-middle"><?php ee('Tracking Pixels') ?></span>
        </a>
    </li>    
    <?php endif ?>
    <?php if($user->has('domain')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('domain') ?>">
            <i class="align-middle" data-feather="globe"></i> <span class="align-middle"><?php ee('Branded Domains') ?></span>
        </a>
    </li>    
    <?php endif ?>    
    <?php if($user->has('team')): ?>
    <li class="sidebar-item">
        <a class="sidebar-link" href="<?php echo route('team') ?>">
            <i class="align-middle" data-feather="users"></i> <span class="align-middle"><?php ee('Teams') ?></span>
        </a>
    </li>    
    <?php endif ?>   
    <?php plug('usermenu') ?>
    <li class="sidebar-item">        
        <a class="sidebar-link collapsed" data-bs-target="#nav-tool" data-bs-toggle="collapse">
            <i class="align-middle" data-feather="terminal"></i> <span class="align-middle"><?php ee('Tools & Integrations') ?></span>
        </a>
        <ul id="nav-tool" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar"> 
            <?php if (config('slackclientid') && config('slacksecretid')): ?>
                <li class="sidebar-item"><a class="sidebar-link" href="<?php echo route('integrations', ['slack']) ?>"><?php ee('Slack Integration') ?></a></li>
            <?php endif ?>
            <li class="sidebar-item"><a class="sidebar-link" href="<?php echo route('integrations', ['zapier']) ?>"><?php ee('Zapier Integration') ?></a></li>
            <?php if(config('api') && $user->has('api')): ?>                
                <li class="sidebar-item"><a class="sidebar-link" href="<?php echo route('integrations', ['wordpress']) ?>"><?php ee('WordPress Integration') ?></a></li>
                <li class="sidebar-item"><a class="sidebar-link" href="<?php echo route('integrations', ['shortcuts']) ?>"><?php ee('Shortcuts Integration') ?></a></li>
            <?php endif ?>
            <li class="sidebar-item"><a class="sidebar-link" href="<?php echo route('tools') ?>"><?php ee('Tools') ?></a></li>
            <?php if(config('api') && $user->has('api')): ?>
                <li class="sidebar-item"><a class="sidebar-link" href="<?php echo route('apidocs') ?>"><?php ee('Developer API') ?></a></li>
            <?php endif ?>
        </ul>
    </li>     
</ul>