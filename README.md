### libBitly

libBitly is a experimental library to access bit.ly services and integrate in the Content Management Systems [WebsiteBaker] [1] or [LEPTON CMS] [2]. 

#### Requirements

* minimum PHP 5.2.x
* using [WebsiteBaker] [1] _or_ using [LEPTON CMS] [2]

additional for the __bitlyGetBundleContents__ sample:

* [Dwoo] [6] installed
* [libWebThumbnail] [7] installed

optional for the __bitlyGetBundleContents__ sample:

* [DropletsExtension] [8] installed and configured

#### Installation

* download the actual [libBitly] [3] installation archive
* in CMS backend select the file from "Add-ons" -> "Modules" -> "Install module"

#### First Steps

This library is experimental an not completed yet!

**libBitly** installs the droplet `bitly_get_bundle_contents` which demonstrate the including of a bit.ly bundle to a website. This droplet uses the [Dwoo template engine] [6] and the [libWebThumbnail library] [7]. Please use this droplet to see how **libBitly** works and have a deeper look at `library.php` and the class `bitlyAccess`.

Before you can use **libBitly** you must register your Application at bit.ly, at your account go to settings and register an OAuth Application. You will get a `client_id` and a `client_secret`. Now create a file `config.json` in the **libBitly** directory `/modules/lib_bitly/` and insert:

    {
      "client_id": "<the_client_id_you_have_got_between_the_apostrophes>",
      "client_secret": "<the_client_secret_you_have_got_between_the_apostrophes>",
      "redirect_uri": "http://<the_url_where_you_access_to_libBitly>"
    } 

Save this file as `config.json` - now you are ready. At the first call bit.ly will ask you for authentication, allow this, **libBitly** will care about all of the rest.

If you are missing functions in the **libBitly** library or have ideas for further using please feel free to [contact us] [4]. 

Please visit the [phpManufaktur] [5] to get more informations about **libBitly** and join the [Addons Support Group] [4].

[1]: http://websitebaker2.org "WebsiteBaker Content Management System"
[2]: http://lepton-cms.org "LEPTON CMS"
[3]: https://addons.phpmanufaktur.de/download.php?file=libBitly
[4]: https://phpmanufaktur.de/support
[5]: https://phpmanufaktur.de
[6]: https://addons.phpmanufaktur.de/download.php?file=Dwoo
[7]: https://addons.phpmanufaktur.de/download.php?file=libWebThumbnail
[8]: https://addons.phpmanufaktur.de/download.php?file=DropletsExtension