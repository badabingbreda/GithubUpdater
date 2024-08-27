# GithubUpdater

Use this class to have your plugin check for an update on github.

## Adding custom plugin headers

You can add custom headers to your plugin to make it look a little nicer. Add the following to the release notes, they will be used as values for each update setting:

    Tested: 6.3
    Icons: 1x|https://domainname.com/icon-256x256.png?rev=2818463,2x|https://domainname.com/icon-256x256.png?rev=2818463
    Banners: 1x|https://domainname.com/banner-720x250.png
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