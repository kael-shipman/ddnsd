DDNSd
======================================================================

DDNSd is a simple, HTTP-based dynamic dns solution. It is based on the idea that the vast majority of hosting companies allow you to manage your DNS through a simple web interface, and that it's just as easy for a daemon to talk to the HTTP back-end of that interface as it is for a human to do so through a browser. Thus, DDNSd monitors changes to the IP address of the machine it's running on, then literally logs into your hosting company's website and updates your DNS records, just as if you had done so yourself using your browser.


## Installation

If possible, you should install `ddnsd` via your OS's package manager. Currently, however, the only packages available are for Debian-based distros (which you can get at packages.kaelshipman.me).

As an alternative, you can simply download the executable directly from the [github releases page](https://github.com/kael-shipman/php-ddnsd/releases/) and install it in your path. If you do that, you'll probably also want to install the systemd files and other auxiliary files, which you can find in the [`pkg-src/generic/` directory](/pkg-src/generic).


## Usage

The daemon can manage any number of different accounts and domains. All of these are configured using configuration files found at `/etc/ddnsd/config` and `/etc/ddnsd/config.d/`. Objects in files are merged using the following algorithm:

1. Config files in the `config.d` directory are sorted alphabetically;
2. They are then merged (recursively), with later values overwriting earlier ones;
3. The resulting object is then merged on top of the global `config` file to create a final config object.

The daemon is usually managed via systemd, though you can also start and manage it directly. For example, to use a different config location:

```
$ ddnsd --config-dir=/home/me/.config/ddns
```

Currently, the only command-line options recognized are the following:

* `-v|--version` -- Echo version information and exit
* `-h|--help` -- View usage information
* `-c|--config-dir` -- Change the location to look for config files


### Config File

ddnsd's config files are formatted in [hjson](http://hjson.org/) (or regular json). You can set optional global parameters in the main body, then specify domain configurations in one or more domain sections in the "profiles" key. For each domain, you'll need to specify the provider (more on this in a minute), your username and password (again, more in a minute), and then a list of A records to update. Additionally, you can specify a time-to-live (TTL) value, though it's not required (defaults to 3600 (1 hour)).

A sample config file looks like this:

```hjson
{
    check-interval: 3600,
    profiles: {
        example.com: {
            provider: name.com
            credentials: USERPASS:me@example.com|my-password12345
            subdomains: [ "@", "sub1", "sub2" ]
        },
        example2.com: {
            provider: bluehost
            credentials: OAUTH:aaaabbbbccccdddd1111222233334444
            subdomains: [ "@", "freak", "out" ]
            ttl: 14000
        }
    }
}
```


### Providers

Because of the way ddnsd is implemented, providers must be "known". That is, you can't put any arbitrary provider in and expect it to work. Instead, you'll have to use a provider for which a ddnsd-compatible worker has been created.

To allow for better ecosystem development, ddnsd makes no assumptions about how a provider worker is implemented or even in what language. It simply looks for an executable called `ddnsd-provider-[name]` somewhere in your path. Because of this, you can fairly easily implement your own provider executables in whatever language you're comfortable in and put them in your path. The only built-in provider is name.com because this is currently my own DNS provider.

#### Provider Interface

To allow for maximal interoperability, we've defined the provider interface at the command line level. It is as follows:

```
    ddnsd-provider-[name] change-ip [config-object]

    Commands

        change-ip           Change the IP address of the subdomains given in the config
                            object to the ip given in the config object.
```

Note that, for security, credentials are STRIPPED from the config object and are passed to subcommands via the environment variable `DDNSD_CREDENTIALS`. This variable MUST contain a string composed of the following parts:

1. Credential protocol followed by a colon: `USERPASS:`, `APIKEY:`, `OAUTH:`
2. Protocol-specific data

Provider implementations are responsible for explicitly accepting or rejecting credential protocols and formats, and parsing and using them accordingly.

All other configuration specified in the "profile" section is preserved, though may not be used by provider implementations.


### Credentials

I understand that for many, putting credentials in a config file is a deal-breaker. I'm interested in figuring out a better way to handle this, but for now this is what I've got. Hopefully providers will start offering APIs with OAuth access for this kind of stuff, but until they do, we'll have to just log in the old fashioned way. For some services, however, you may be able to create "service" users who only have access to certain aspects of your system (though in most cases access to DNS implies access to much of the other sensitive parts of the service....).

At any rate, consider this a bug that needs to be fixed.


## To-Do

* Implement provider listing: `ddnsd --list-providers`
* Figure out better password management (perhaps integrating into a password manager like Vault?)


## Technical Notes

ddnsd responsibilities:

* Stores current IP in memory
* Checks ip at given frequency
* On IP change, iterates through domain profiles and calls `change-ip` on providers

