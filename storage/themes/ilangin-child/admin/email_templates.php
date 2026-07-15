<div class="row">
	<div class="col-md-9">
		<div class="row">
			<div class="col-md-6">
				<div class="card">
					<div class="card-header"><label for="email.registration"><?php ee('Registration Email') ?></label></div>
					<div class="card-body">
						<form action="<?php echo route("admin.email.template") ?>" method="post">
							<div class="form-group">
								<textarea name="email.registration" id="email.registration" cols="30" rows="10" class="form-control editor"><?php echo config('email.registration') ?></textarea>
							</div>
							<?php echo csrf() ?>
							<button type="submit" class="btn btn-primary mt-2"><?php ee('Save') ?></button>
						</form>				
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card">
					<div class="card-header"><label for="email.activation"><?php ee('Activation Email') ?></label></div>
					<div class="card-body">
						<form action="<?php echo route("admin.email.template") ?>" method="post">
							<div class="form-group">
								<textarea name="email.activation" id="email.activation" cols="30" rows="10" class="form-control editor"><?php echo config('email.activation') ?></textarea>
							</div>
							<?php echo csrf() ?>
							<button type="submit" class="btn btn-primary mt-2"><?php ee('Save') ?></button>
						</form>				
					</div>
				</div>
			</div>			
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="card">
					<div class="card-header"><label for="email.activated"><?php ee('Activation Success Email') ?></label></div>
					<div class="card-body">
						<form action="<?php echo route("admin.email.template") ?>" method="post">
							<div class="form-group">
								<textarea name="email.activated" id="email.activated" cols="30" rows="10" class="form-control editor"><?php echo config('email.activated') ?></textarea>
							</div>
							<?php echo csrf() ?>
							<button type="submit" class="btn btn-primary mt-2"><?php ee('Save') ?></button>
						</form>				
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card">
					<div class="card-header"><label for="email.reset"><?php ee('Password Reset Email') ?></label></div>
					<div class="card-body">
						<form action="<?php echo route("admin.email.template") ?>" method="post">
							<div class="form-group">
								<textarea name="email.reset" id="email.reset" cols="30" rows="10" class="form-control editor"><?php echo config('email.reset') ?></textarea>
							</div>
							<?php echo csrf() ?>
							<button type="submit" class="btn btn-primary mt-2"><?php ee('Save') ?></button>
						</form>				
					</div>
				</div>
			</div>			
		</div>			
		<div class="row">
			<div class="col-md-6">
				<div class="card">
					<div class="card-header"><label for="email.invitation"><?php ee('Team Invitation Email') ?></label></div>
					<div class="card-body">
						<form action="<?php echo route("admin.email.template") ?>" method="post">
							<div class="form-group">
								<textarea name="email.invitation" id="email.invitation" cols="30" rows="10" class="form-control editor"><?php echo config('email.invitation') ?></textarea>
							</div>
							<?php echo csrf() ?>
							<button type="submit" class="btn btn-primary mt-2"><?php ee('Save') ?></button>
						</form>				
					</div>
				</div>
			</div>
		</div>				
	</div>
	<div class="col-md-3">
		<div class="card">
			<div class="card-header"><?php ee('Shortcodes') ?></div>
			<div class="card-body">
				<p><?php ee('When using the {user.activation} or {site.link} with the Link tool in the editor, make sure to sure Other for protocol.') ?></p>
				<ul>
					<li><?php ee("User's Username:") ?> <strong>{user.username}</strong></li>
					<li><?php ee("User's Email:") ?> <strong>{user.email}</strong></li>
					<li><?php ee("User's Sign Up Date:") ?> <strong>{user.date}</strong></li>
					<li><?php ee("Activation Link or Password Reset:") ?> <strong>{user.activation}</strong></li>
					<li><?php ee("Site's Title:") ?> <strong>{site.title}</strong></li>
					<li><?php ee("Site's Link:") ?> <strong>{site.link}</strong></li>
				</ul>				
			</div>
		</div>
	</div>
</div>