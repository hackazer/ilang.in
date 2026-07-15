<h1 class="h3 mb-5"><?php ee('Tools') ?></h1>
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action active" href="#quick" data-bs-toggle="collapse"><?php echo e("Quick Shortener") ?></a>
                    <a class="list-group-item list-group-item-action" href="#bk" data-bs-toggle="collapse"><?php echo e("Bookmarklet") ?></a>
                    <a class="list-group-item list-group-item-action" href="#jshort" data-bs-toggle="collapse"><?php echo e("Full-Page Script") ?></a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-9" id="tools">
        <div class="collapse show" id="quick" data-bs-parent="#tools">
          <div class="card">
            <div class="card-body">
              <h3 class="mb-5"><i class="me-3 fa fa-fighter-jet"></i> <?php echo e("Quick Shortener") ?></h3>

              <p><?php echo e("This tool allows you to quickly shorten any URL in any page without using any fancy method. This is perhaps the quickest and the easiest method available for you to shorten URLs across all platforms. This method will generate a unique short URL for you that you will be able to access anytime from your dashboard.") ?></p>

              <p><?php echo e("Use your quick URL below to shorten any URL by adding the URL after /q/?u=. <strong>For security reasons, you need to be logged in and using the remember me feature.</strong> Check out the examples below to understand how to use this method.") ?></p>
              <pre class="p-3 border rounded"><span><?php echo url("q/?u=URL_OF_SITE") ?></span></pre>

              <p><strong><?php echo e("Examples") ?></strong></p>
              <pre class="p-3 border rounded"><span><?php echo url("q/?u=https://www.google.com") ?></span><br><span><?php echo url("q/?u=http://www.apple.com") ?></span></pre>

              <p><strong><?php echo e("Notes") ?></strong></p>
              <p>
                <?php echo e("Please note that this method does not return anything. It simply redirects the user to the redirection page. However if you need the actual short URL, you can always get it from your dashboard.") ?>
              </p>
            </div>                
          </div>
        </div>
        <div class="collapse" id="bk" data-bs-parent="#tools">
          <div class="card">
            <div class="card-body">
              <h3 class="mb-5"><i class="me-3 fa fa-bookmark"></i> <?php echo e("Bookmarklet") ?></h3>

              <p><?php echo e("You can use our bookmarklet tool to instantaneously shorten any site you are currently viewing and if you are logged in on our site, it will be automatically saved to your account for future access. Simply drag the following link to your bookmarks bar or copy the link and manually add it to your favorites.") ?></p>

              <a class='btn btn-primary' href="javascript:void((function () {var h = '<?php echo config('url') ?>';var e = document.createElement('script');e.setAttribute('data-url', h);e.setAttribute('data-token', '<?php echo md5(user()->api) ?>');e.setAttribute('id', 'gem_bookmarklet');e.setAttribute('type', 'text/javascript');e.setAttribute('src', h+'/static/bookmarklet.js?v=<?php echo time() ?>');document.body.appendChild(e);})());" rel='nofollow' title='<?php echo e('Drag me to your Bookmark Bar') ?>' style='cursor:move'><?php echo e('Shorten URL')?> (<?php echo explode(" ", config("title"))[0] ?>)</a>

              <p class="mt-3"><?php echo e("If you can't drag the link above, use your browser's bookmark editor to create a new bookmark and add the URL below as the link.") ?></p>
              <pre class="p-3 border rounded"><span>javascript:void((function(){var e=document.createElement('script');e.setAttribute('data-url','<?php echo config('url')?>');e.setAttribute('data-token','<?php echo md5(user()->api) ?>');e.setAttribute('id','gem_bookmarklet');e.setAttribute('type','text/javascript');e.setAttribute('src','<?php echo config('url')?>/static/bookmarklet.js?v=<?php echo time() ?>');document.body.appendChild(e)})());</span></pre>
              
              <p><strong><?php echo e("Notes") ?></strong></p>
              <p>
                <?php echo e("Please note that for secured sites that use SSL, the widget will not pop up due to security issues. In that case, the user will be redirected our site where you will be able to view your short URL.") ?>
              </p>                    
            </div>
          </div>
        </div>
        <div class="collapse" id="jshort" data-bs-parent="#tools">
          <div class="card">
            <div class="card-body">
               <h3 class="mb-5"><i class="me-3 fa fa-file-code"></i> <?php echo e("Full-Page Script") ?></h3>
               
               <p><?php echo e("This script allows you to shorten all (or select) URLs on your website very easily. All you need to do is to copy and paste the code below at the end of your page. You can customize the selector as you wish to target URLs in a specific selector. Note you can just  copy the code below because everything is already for you.") ?></p>

               <p><pre class="p-3 border rounded"><span class="m-x-3">&lt;script type=&quot;text/javascript&quot;&gt;</span><span class="m-x-4">var key = &quot;<?php echo md5(user()->api) ?>&quot;;</span><span class="m-x-3">&lt;/script&gt;<br><span class="m-x-3">&lt;script type=&quot;text/javascript&quot; src=&quot;<?php echo url("script.js") ?>&quot;&gt;&lt;/script&gt;</span></span></pre></p>

               <h5><?php echo e("Choosing a different domain") ?></h5>
               <p><?php echo e("By default, the script uses the default domain on the platform however you can define a custom domain name to shorten links with. You need to make sure the domain is exactly the same as the domain added in the account, including the schema (http/https") ?></p>

               <p><pre class="p-3 border rounded"><span class="m-x-3">&lt;script type=&quot;text/javascript&quot;&gt;</span><br><span class="m-x-4">var key = &quot;<?php echo md5(user()->api) ?>&quot;;</span><br><span class="m-x-4">var domain = &quot;https://mydomain.com&quot;;</span><br><span class="m-x-3">&lt;/script&gt;<br><span class="m-x-3">&lt;script type=&quot;text/javascript&quot; src=&quot;<?php echo url("script.js") ?>&quot;&gt;&lt;/script&gt;</span></span></pre></p>
          
               <h5><?php echo e("Choosing custom selectors") ?></h5>
               <p><?php echo e("By default, this script shortens all URLs in a page. If you want to target specific URLs then you can add a selector parameter. You can see an example below where the script will only shorten URLs having a class named mylink or all direct link in the .content container or all links in the .comments container") ?></p>

               <p><pre class="p-3 border rounded"><span class="m-x-3">&lt;script type=&quot;text/javascript&quot;&gt;</span><br><span class="m-x-4">var key = &quot;<?php echo md5(user()->api) ?>&quot;;</span><br><span class="m-x-4">var selector = &quot;.mylink, .content > a, .comments a&quot;;</span><br><span class="m-x-3">&lt;/script&gt;<br><span class="m-x-3">&lt;script type=&quot;text/javascript&quot; src=&quot;<?php echo url("script.js") ?>&quot;&gt;&lt;/script&gt;</span></span></pre></p>

               <h5><?php echo e("Excluding domain names") ?></h5>
               <p><?php echo e("You can exclude domain names if you wish. You can add an exclude parameter to exclude domain names. The example below shortens all URLs but excludes URLs from google.com or apple.com") ?></p>

               <p><pre class="p-3 border rounded"><span class="m-x-3">&lt;script type=&quot;text/javascript&quot;&gt;</span><br><span class="m-x-4">var key = &quot;<?php echo md5(user()->api) ?>&quot;;</span><br><span class="m-x-4">var exclude = [&quot;google.com&quot;,&quot;apple.com&quot;];</span><br><span class="m-x-3">&lt;/script&gt;<br><span class="m-x-3">&lt;script type=&quot;text/javascript&quot; src=&quot;<?php echo url("script.js") ?>&quot;&gt;&lt;/script&gt;</span></span></pre></p>

               <h5><?php echo e("Restricting domain names") ?></h5>
               <p><?php echo e("You can restrict domain names by adding an include parameter to restrict domain names. The example below shortens all URLs within the include domain name.") ?></p>

               <p><pre class="p-3 border rounded"><span class="m-x-3">&lt;script type=&quot;text/javascript&quot;&gt;</span><br><span class="m-x-4">var key = &quot;<?php echo md5(user()->api) ?>&quot;;</span><br><span class="m-x-4">var include = [&quot;google.com&quot;];</span><br><span class="m-x-3">&lt;/script&gt;<br><span class="m-x-3">&lt;script type=&quot;text/javascript&quot; src=&quot;<?php echo url("script.js") ?>&quot;&gt;&lt;/script&gt;</span></span></pre></p>

            </div>
          </div>
        </div>
    </div>
</div>