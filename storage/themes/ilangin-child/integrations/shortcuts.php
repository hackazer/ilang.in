<div class="d-flex">
    <div>
        <h1 class="h3 mb-5"><i class="fab fa-apple me-3"></i> <?php ee('Shortcuts Integration') ?></h5>
    </div>
</div>
<div class="row">
    <div class="col-sm-6">
        <div class="card">
            <div class="card-body">
                <p><?php ee('Shortcuts in an app developed by Apple and it allows you to create an automation. You can download our powerful Shortcut and you will be able to shorten links in a snap and save it directly in your account.') ?></p>
                
                <p><h4><?php ee('How does it work?') ?></h4></p>

                <p><?php ee('The Shortcut works in various ways:') ?></p>
                
                <p><strong><?php ee('Safari') ?></strong></p>

                <p><?php ee('If you want shorten the current viewing page, tap the share icon at the bottom of the screen and it will shorten the current URL. It will copy the short URL directly to your clipboard so you can paste it somewhere.') ?></p>

                <p><strong><?php ee('Siri') ?></strong></p>

                <p><?php ee('To use Siri, copy a link and ask Siri "Shorten Link" and it will shorten the link for you and copy it to your clipboard.') ?></p>

                <p><strong><?php ee('Manual') ?></strong></p>
                
                <p><?php ee('You can also run the Shortcut by just holding a link then tap Share and you will see Shorten Link in the list.') ?></p>               
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card">
            <div class="card-header"><h4><?php ee('How to install it?') ?></h4></div>
            <div class="card-body">                               
                <ol>
                    <li class="mb-2"><?php ee('Make sure you have the Shortcuts app, if not you can download it from the App Store: ') ?><a href="https://apps.apple.com/us/app/shortcuts/id915249334" target="_blank"><?php ee('Download Shortcuts') ?></a></li>
                    <li class="mb-2"><a href="https://www.icloud.com/shortcuts/9e5151251d274664b47f934e0bc4bdab" target="_blank"><?php ee('Download our Shortcut') ?></a></li>
                    <li class="mb-2">
                        <?php ee("After installation, you will be presented with a configuration screen where you need to enter the API URL and the API key") ?>
                        <ul class="mt-2">
                            <li><?php ee('API URL') ?>: <strong><?php echo route('api.url.create') ?></strong></li>
                            <li><?php ee('API Key') ?>: <strong><?php echo user()->api ?></strong></li>
                        </ul>
                    </li>
                    <li><?php ee('After the configuration is complete, you can start shortening links directly from your device in a single tap.') ?></li>            
                </ol>

            </div>
        </div>
    </div>
</div>