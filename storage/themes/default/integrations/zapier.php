<div class="d-flex">
	<div>
		<h1 class="h3 mb-5">
			<i class="me-3 fa fa-bolt"></i> <?php echo e("Zapier Integration") ?>             
		</h1>
	</div>
	<div class="ms-auto">
		<?php if(user()->zapurl || user()->zapview): ?>
			<span class="text-success"><i class="me-1 fa fa-check-circle"></i> <?php echo e("Active") ?></span>
		<?php endif ?>                        
	</div>
</div>
<div class="card">
	<div class="card-body">		
		<p><?php echo e("You can use Zapier to automate campaigns. By adding the URL to the zapier webhook, we will send you important information to that webhook so you can use them.") ?></p>
		<p><strong><?php ee('Note') ?></strong> <?php ee('Although this tool is designed for Zapier, it can be used for any webhook system.') ?></p>
		<form action="<?php echo route("user.zapier") ?>" method="post">
			<div class="form-group">
				<label for="zapurl" class="form-label"><?php echo e("URL Zapier Notification") ?></label>
				<input type="text" id="zapurl" name="zapurl" class="form-control p-2" placeholder="e.g. https://" value="<?php echo user()->zapurl ?>">
				<p class="form-text"><?php echo e("We will send a notification to this URL when you create a short URL.") ?></p>
			</div>
			<div class="form-group">
				<label for="zapview" class="form-label"><?php echo e("Views Zapier Notification") ?></label>
				<input type="text" id="zapview" name="zapview" class="form-control p-2" placeholder="e.g. https://" value="<?php echo user()->zapview ?>">
				<p class="form-text"><?php echo e("We will send a notification to this URL when someone clicks your URL.") ?></p>
			</div>
			<?php echo csrf() ?>
			<button class="btn btn-primary" type="submit"><?php echo e("Save") ?></button>
		</form>
	</div>
</div>
<div class="card">
	<div class="card-body">	
		<h3 class="mb-5"><?php echo e("Sample Response") ?></h3>
		<p><strong><?php echo e("URL Zapier Notification") ?></strong></p>
		<pre class="p-3 border rounded">{<br>&nbsp;&nbsp;"type":"url",<br>&nbsp;&nbsp;"longurl":"https://google.com",<br>&nbsp;&nbsp;"shorturl":"<?php echo url("C2Rxy") ?>",<br>&nbsp;&nbsp;"title":"Google",<br>&nbsp;&nbsp;"date":"17-05-2020 04:17:44"<br>}</pre>

		<br>
		<p><strong><?php echo e("Views Zapier Notification") ?></strong></p>
		<pre class="p-3 border rounded">{<br>&nbsp;&nbsp"type":"view",<br>&nbsp;&nbsp;"shorturl":"<?php echo url("C2Rxy") ?>",<br>&nbsp;&nbsp;"country":"Canada",<br>&nbsp;&nbsp;"referer":"https://yahoo.com",<br>&nbsp;&nbsp;"os":"Windows",<br>&nbsp;&nbsp;"browser":"Chrome",<br>&nbsp;&nbsp;"date":"17-05-2020 04:20:19"<br>}</pre>                                  
	</div>
</div>