# Image Optimizer
This script will download all the images on a Pantheon environment, optimize them and re-upload them back to the site.

## Dependencies
`composer install` will download all the PHP dependencies. The downloaded package has system dependencies. Check them here: https://github.com/spatie/image-optimizer

## How to use
Simple way, with a single command:
```
./image-optimizer.php --env dev --site [uuid of the Pantheon site] --destination [path to folder to host the files] --ssh [path to your SSH key] --download --process --upload
```
Let's see more about the parameters:
- `--download`: Use it to rsync into the Pantheon site and downloading the files. Requires configuring the `env`, `site`, `destination` and `ssh` parameters.
- `--process`: Will execute the optimization of the files. Requires the `destination` parameter.
- `--upload`: Will upload the images back to the Pantheon site. Requires configuring the `env`, `site`, `destination` and `ssh` parameters.

- `--env`: Pantheon environment. Usually dev, test, or live.
- `--site`: Site UUID from dashboard URL: https://dashboard.pantheon.io/sites/<UUID>
- `--destination`: Local folder where the images will be stored.
- `--ssh`: Path to the SSH key used for identification on Pantheon.

There are two other parameters that can be used, but are optional:
- `--verbose`: For full logs.
- `--quiet`: To silence all the messages, except the errors.

You can also run the command from a script. Here is a quick sample:

```
<?php

include 'image-optimizer.php';

$download_files = FALSE;
$process_files = TRUE;
$upload_files = FALSE;

$env = 'dev';
$site = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
$destination = 'files';
$ssh_key = '~/.ssh/id_rsa';

execute($download_files, $process_files, $upload_files, $env, $site, $destination, $ssh_key);
```

### What if I do not use Pantheon?
No worries! You can still use this script! You can run it directly on the server just using the `--process` option and specifying the `--destination` of your images folder. Or you can manually download and upload the files from your site instead of using the `--download` and `--upload` options.
