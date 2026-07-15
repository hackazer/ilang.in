<div class="d-flex">
    <div>
        <h1 class="h3 mb-5"><i class="fab fa-slack me-3"></i> <?php ee('Slack Integration') ?></h5>
    </div>
    <div class="ms-auto">
        <?php if(user()->slackid): ?>
            <span class="text-success"><i class="me-1 fa fa-check-circle"></i> <?php echo e("Connected") ?></span>
        <?php endif ?>                        
    </div>
</div>  

<div class="card">
    <div class="card-body">               
        <p><?php echo e("You can integrate this app with your Slack account and shorten directly from the Slack interface using the command line below. This Slack integration will save all of your links in your account in case you need to access them later. Please see below how to use the command.") ?></p>
        <?php if (!user()->slackid): ?>                  
            <p><?php echo $slack->generateAuth() ?></p>
        <?php endif ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <h5><strong><?php echo e("Slack Command") ?></strong></h5>
        <p><pre class="p-3 border rounded">/<?php echo config("slackcommand") ?></pre></p>

        <h5><strong><?php echo e("Example") ?></strong></h5>
        <p><pre class="p-3 border rounded">/<?php echo config("slackcommand") ?> https://google.com</pre></p>    

        <h5><strong><?php echo e("Example with custom name") ?></strong></h5>
        <p><?php echo e("To send a custom alias, use the following parameter (ABCDXYZ). This will tell the script to choose shorten the link with the custom alias ABCDXYZ.") ?></p>
        <p><pre class="p-3 border rounded">/<?php echo config("slackcommand") ?> (google) https://google.com</pre></p>

        <p><?php echo e("The Slack command will return you the short link if everything goes well. In case there is an error, it will return you the original link. If you use the custom parameter and the alias is already taken, the script will automatically increment the alias by 1 - this means if you choose 'google' and 'google' is not available, the script will use google-1") ?></p>
    </div>
</div>