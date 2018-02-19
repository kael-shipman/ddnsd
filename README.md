DDNSd
======================================================================

DDNSd is a simple, HTTP-based dynamic dns solution. It is based on the idea that the vast majority of hosting companies allow you to manage your DNS through a simple web interface, and that it's just as easy for a daemon to talk to the HTTP back-end of that interface as it is for a human to do so through a browser. Thus, DDNSd monitors changes to the IP address of the machine it's running on, then literally logs into your hosting company's website and updates your DNS records, just as if you had done so yourself using your browser.


## Installation

(TBD)


## Usage

The daemon can manage any number of different accounts and domains. All of these are configured using configuration files found at `/etc/ddnsd/config.d/`.

The daemon is usually managed via systemd, though you can also start and manage it directly. For example, to use a different config file location:

```
$ ddnsd --config-dir=/home/me/.config/ddns/config.d
```


### Config File

ddnsd's config file is formatted in [hjson](http://hjson.org/) (or regular json). You can set optional global parameters in the main body, then specify domain configurations in one or more domain sections in the "profiles" key. For each domain, you'll need to specify the provider (more on this in a minute), your username and password (again, more in a minute), and then a list of A records to update. Additionally, you can specify a time-to-live (TTL) value, though it's not required (defaults to 3600 (1 hour)).

A sample config file looks like this:

```hjson
{
    check-interval: 3600,
    profiles: {
        example.com: {
            provider: name.com
            username: me@example.com
            password: my-password12345
            records: [ "@", "sub1", "sub2" ]
        },
        example2.com: {
            provider: bluehost
            username: me@example.com
            password: weak-bluehost-password
            records: [ "@", "freak", "out" ]
            ttl: 14000
        }
    }
}
```


### Providers

Because of the way ddnsd is implemented, providers must be "known". That is, you can't put any arbitrary provider in and expect it to work. Instead, you'll have to use a provider for which a ddnsd-compatible worker has been created.

To allow for better ecosystem development, ddnsd makes no assumptions about how a provider worker is implemented or even in what language. It simply looks for an executable called `ddnsd-provider-[name]` somewhere in your path. Because of this, you can fairly easily implement your own provider executables in whatever language you're comfortable in and put them in your path. The only built-in provider is name.com because this is currently my own DNS provider.


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
