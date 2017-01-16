Joomlatools Console - Encrypt Plugin
====================================

The plugin adds `encrypt` and `decrypt` command which you can use to
encrypt and decrypt files of a target directory.

Installation
------------

1.  Run the following command

    `$ joomla plugin:install joomlatools/console-encrypt`

1. Verify that the plugin is available:

    `$ joomla plugin:list`

Usage
-----

You can encrypt files of a target directory by running the following command:

`$ joomla encrypt /path/to/directory`

and decrypt by:

`$ joomla decrypt /path/to/directory`

You can change the encryption key used by using the `--key` flag

`$ joomla encrypt /path/to/directory --key=yournewkey`

For more available options, run

`$ joomla help encrypt` or `$ joomla help decrypt`

Requirements
------------

* Composer
* [Joomlatools Console](https://github.com/joomlatools/joomlatools-console) >= 1.4.7
* mcrypt PHP extension

Contributing
------------

The `joomlatools/console-encrypt` plugin is an open source, community-driven project. Contributions are welcome from everyone. We have [contributing guidelines](CONTRIBUTING.md) to help you get started.

Contributors
------------

See the list of [contributors](https://github.com/joomlatools/joomlatools-console-encrypt/contributors).

License
-------

This plugin is free and open-source software licensed under the [MPLv2 license](LICENSE.txt).

## Community

Keep track of development and community news.

* Follow [@joomlatoolsdev on Twitter](https://twitter.com/joomlatoolsdev)
* Join [joomlatools/dev on Gitter](http://gitter.im/joomlatools/dev)
* Read the [Joomlatools Developer Blog](http://developer.joomlatools.com/blog/)
* Subscribe to the [Joomlatools Developer Newsletter](http://developer.joomlatools.com/newsletter)

 [Joomlatools Console]: https://www.joomlatools.com/developer/tools/console/
