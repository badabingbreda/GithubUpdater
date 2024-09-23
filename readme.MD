# GithubUpdater

Use this class to have your plugin check for an update on github.

## Adding custom plugin headers

You can add custom headers to your plugin to make it look a little nicer. Add the following to the release notes, they will be used as values for each update setting:

    Tested: 6.3
    Icons: 1x|https://domainname.com/icon-256x256.png?rev=2818463,2x|https://domainname.com/icon-256x256.png?rev=2818463
    Banners: low|https://domainname.com/banner-720x250.png,high|https://domainname.com/banner-1440x500.png
    RequiresPHP: 7.0

    |||
    Add your changelog here

## Release format

Make sure to use the correct release format to indicate that there's an update. Draft a release and add a tag in the x.y.z format. For instance:

    1.0.0
    1.1.0
    1.21.3
    2.4.9

Are all good versions.

## Using the updater

First course of action is to create a release for your plugin on Github. You can draft a release, add a tag '1.0.0' and confirm.

Add the class via Composer from within your plugin's directory:

    composer require badabingbreda/githubupdater

Make sure to include the autoload in your main plugin file.

    // load our Composer autoload
    require_once( 'vendor/autoload.php' );



In your plugin add the following:

    $github_updater = new \BadabingBreda\GithubUpdater(__FILE__ , 'yourusername' , 'repository-slug' );
    $github_updater->set_settings([
                        'requires' => '7.4',
                        'tested'   => '6.3',
                    ]);
    $github_updater->initialize();

or, when you accept the default settings:

    $githubupdater = (new \BadabingBreda\GithubUpdater( __FILE__ , 'yourusername' , 'repository-slug' ))->initialize();