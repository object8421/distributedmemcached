distributedmemcached  twemproxy README译文
====================

测试分布式Memcached缓存系统(仅供个人参考)

# twemproxy (nutcracker) [![Build Status](https://secure.travis-ci.org/twitter/twemproxy.png)](http://travis-ci.org/twitter/twemproxy)

**twemproxy** (发音 "two-em-proxy"), 又名 **nutcracker** 是一个快且轻量级[memcached](http://www.memcached.org/) 和 [redis](http://redis.io/) 的代理软件. 他主要用于减少web应用连接后端缓存服务器的连接数。


## 编译安装

源码包安装[distribution tarball](http://code.google.com/p/twemproxy/downloads/list):

    $ ./configure
    $ make
    $ sudo make install

源码包编译安装调试模式 from [distribution tarball](http://code.google.com/p/twemproxy/downloads/list) 

    $ CFLAGS="-ggdb3 -O0" ./configure --enable-debug=full
    $ make
    $ sudo make install

源码安装开启调试模式日志和断言

    $ git clone git@github.com:twitter/twemproxy.git
    $ cd twemproxy
    $ autoreconf -fvi
    $ ./configure --enable-debug=full
    $ make
    $ src/nutcracker -h

Few checklist：

+ 使用新版本的gcc，老版本的gcc可能会有问题
+ use CFLAGS="-O1" ./configure && make
+ use CFLAGS="-O3 -fno-strict-aliasing" ./configure && make

## 功能

+ 快速.
+ 轻量.
+ 保持长连接.
+ 保持与后端缓存服务器的更少连接数.
+ 请求和响应支持使用管道.
+ 支持代理多个缓存服务器
+ 支持多个服务器连接池
+ 自动跨机器分片存储数据
+ 支持完整的[memcached ascii](notes/memcache.txt) 和 [redis](notes/redis.md) 协议
+ 很容易通过YAML格式的一个配置文件配置服务器池
+ 支持多种哈希方式，包括一致性哈希算法
+ 节点失效的时候可通过配置禁用失效节点
+ 通过统计监控接口方便查看运行状态
+ 支持Linux, *BSD, OS X and Solaris (SmartOS)系统

## 帮助

    使用方法: nutcracker [-?hVdDt] [-v verbosity level] [-o output file]
                      [-c conf file] [-s stats port] [-a stats addr]
                      [-i stats interval] [-p pid file] [-m mbuf size]

    选项:
      -h, --help             : 帮助信息
      -V, --version          : 显示版本信息
      -t, --test-conf        : 测试配置文件是否语法错误
      -d, --daemonize        : 以守护进程方式运行
      -D, --describe-stats   : 打印系统运行状态信息
      -v, --verbosity=N      : 设置日志级别(默认: 5, 最小: 0, 最大: 11)
      -o, --output=S         : 设置日志文件(默认: stderr)
      -c, --conf-file=S      : 设置配置文件路径 (默认: conf/nutcracker.yml)
      -s, --stats-port=N     : 设置状态监控端口 (默认: 22222)
      -a, --stats-addr=S     : 设置状态监控IP (默认: 0.0.0.0)
      -i, --stats-interval=N : 每多少毫秒收集一次状态信息 (默认: 30000 毫秒)
      -p, --pid-file=S       : 设置进程pid文件 (默认: off)
      -m, --mbuf-size=N      : 设置mbuf chunk字节大小(default: 16384 bytes)

## Zero Copy

在nutcracker里面，所有的请求和回应所需的内存都在mbuf分配，mbuf支持zero-copy，因为接收客户端请求的缓冲区将被转向到缓存服务器，同理接收到的响应数据直接发送给客户端。

还有，使用一个可以重复利用的内存池用来管理mbufs内存，这意味着一旦mbuf被分配，它将不会被销毁，仅仅是将它放回内存池。默认的每个mbuf chunk大小是16K字节. 在mbuf大小和nutcracker可以支持的连接数之间有个权衡，大一点的mbuf大小可以减少系统在读请求和响应时候的系统调用次数，然而，使用更大的mbuf大小，每一个活动连接占用超过16k字节的缓冲区大小，在处理大量客户端连接数的时候可能会有问题。当nutcracker需要处理大量客户端连接时，你应该把chunk 大小设置为一个较小的值，比如 512 bytes ，使用 -m 或者 --mbuf-size=N 参数.

## 配置

nutcracker 在启动时通过一个使用-c 或者 --conf-file参数指定的 YAML 文件作为配置文件. 配置文件用于指定缓存服务器池组里面的每一个被它管理的服务器，配置文件可解析如下配置项：

+ **listen**: 套接字监听地址和端口(name:port or ip:port)
+ **hash**: 哈希函数名字，下面是可用列表
 + one_at_a_time
 + md5
 + crc16
 + crc32 (crc32 implementation compatible with [libmemcached](http://libmemcached.org/))
 + crc32a (correct crc32 implementation as per the spec)
 + fnv1_64
 + fnv1a_64
 + fnv1_32
 + fnv1a_32
 + hsieh
 + murmur
 + jenkins
+ **hash_tag**: 2个字符的字串，用于哈希时候指定key部分，例如 "{}" or "$$". [Hash tag](notes/recommendation.md#hash-tags)  允许将不同的keys映射到同一台缓存服务器。
+ **distribution**: key的分布算法,可用值列表:
 + ketama
 + modula
 + random
+ **timeout**: 等待连接或者接收响应的等待超时毫秒数，默认无限等待
+ **backlog**: The TCP backlog argument. Defaults to 512.
+ **preconnect**: 布尔值，控制是否在进程启动的时候使用长连接连接后端缓存服务器。默认FALSE
+ **redis**: 布尔值，控制是否使用redis或者memcache协议，默认FALSE 
+ **server_connections**: 每个服务器可用开启的最大连接数，默认至少开启一个连接
+ **auto_eject_hosts**: 布尔值，控制是否踢出超过失败限制次数的失效服务器
+ **server_retry_timeout**: 毫秒值，失败重试的等待时间间隔，仅当auto_eject_host为true时启用，默认30000毫秒
+ **server_failure_limit**: 连续失败次数，当超过此次数并且auto_eject_host设为true的时候，会剔除该失效服务器。默认值2
+ **servers**: 缓存服务器列表地址，端口和权重 (name:port:weight or ip:port:weight)


例如, 配置文件在 [conf/nutcracker.yml](conf/nutcracker.yml), 下面列出的这些, 配置了5种不同名字的服务器组 - _alpha_, _beta_, _gamma_, _delta_ and omega. Clients that intend to send requests to one of the 10 servers in pool delta connect to port 22124 on 127.0.0.1. Clients that intend to send request to one of 2 servers in pool omega connect to unix path /tmp/gamma. Requests sent to pool alpha and omega have no timeout and might require timeout functionality to be implemented on the client side. On the other hand, requests sent to pool beta, gamma and delta timeout after 400 msec, 400 msec and 100 msec respectively when no response is received from the server. Of the 5 server pools, only pools alpha, gamma and delta are configured to use server ejection and hence are resilient to server failures. All the 5 server pools use ketama consistent hashing for key distribution with the key hasher for pools alpha, beta, gamma and delta set to fnv1a_64 while that for pool omega set to hsieh. Also only pool beta uses [nodes names](notes/recommendation.md#node-names-for-consistent-hashing) for consistent hashing, while pool alpha, gamma, delta and omega use 'host:port:weight' for consistent hashing. Finally, only pool alpha and beta can speak redis protocol, while pool gamma, deta and omega speak memcached protocol.

    alpha:
      listen: 127.0.0.1:22121
      hash: fnv1a_64
      distribution: ketama
      auto_eject_hosts: true
      redis: true
      server_retry_timeout: 2000
      server_failure_limit: 1
      servers:
       - 127.0.0.1:6379:1

    beta:
      listen: 127.0.0.1:22122
      hash: fnv1a_64
      hash_tag: "{}"
      distribution: ketama
      auto_eject_hosts: false
      timeout: 400
      redis: true
      servers:
       - 127.0.0.1:6380:1 server1
       - 127.0.0.1:6381:1 server2
       - 127.0.0.1:6382:1 server3
       - 127.0.0.1:6383:1 server4

    gamma:
      listen: 127.0.0.1:22123
      hash: fnv1a_64
      distribution: ketama
      timeout: 400
      backlog: 1024
      preconnect: true
      auto_eject_hosts: true
      server_retry_timeout: 2000
      server_failure_limit: 3
      servers:
       - 127.0.0.1:11212:1
       - 127.0.0.1:11213:1

    delta:
      listen: 127.0.0.1:22124
      hash: fnv1a_64
      distribution: ketama
      timeout: 100
      auto_eject_hosts: true
      server_retry_timeout: 2000
      server_failure_limit: 1
      servers:
       - 127.0.0.1:11214:1
       - 127.0.0.1:11215:1
       - 127.0.0.1:11216:1
       - 127.0.0.1:11217:1
       - 127.0.0.1:11218:1
       - 127.0.0.1:11219:1
       - 127.0.0.1:11220:1
       - 127.0.0.1:11221:1
       - 127.0.0.1:11222:1
       - 127.0.0.1:11223:1

    omega:
      listen: /tmp/gamma
      hash: hsieh
      distribution: ketama
      auto_eject_hosts: false
      servers:
       - 127.0.0.1:11214:100000
       - 127.0.0.1:11215:1

最后，确保通过-t或者--test-conf参数确认YAML配置文件语法无误

## Observability

可用通过日志和统计信息观察nutcracker使用状态

Nutcracker通过暴露统计状态监控端口显示运行状态。状态时json格式化的键值对，默认端口是22222，每30秒收集一次。所有的值都可用通过启动的时候指定-c或者--conf-file和 -i 或者 --stats-interval 命令行参数指定，
你可使用-D或者--describe-stats参数打印状态详细信息

    $ nutcracker --describe-stats

    pool stats:
      client_eof          "# eof on client connections"
      client_err          "# errors on client connections"
      client_connections  "# active client connections"
      server_ejects       "# times backend server was ejected"
      forward_error       "# times we encountered a forwarding error"
      fragments           "# fragments created from a multi-vector request"

    server stats:
      server_eof          "# eof on server connections"
      server_err          "# errors on server connections"
      server_timedout     "# timeouts on server connections"
      server_connections  "# active server connections"
      requests            "# requests"
      request_bytes       "total request bytes"
      responses           "# responses"
      response_bytes      "total response bytes"
      in_queue            "# requests in incoming queue"
      in_queue_bytes      "current request bytes in incoming queue"
      out_queue           "# requests in outgoing queue"
      out_queue_bytes     "current request bytes in outgoing queue"

日志仅当编译时开启logging enabled才支持。默认情况下日志写入stderr。Nutcracker也支持将通过指定-o或者--output参数将日志写入指定的日志文件。在运行时可通过发送SIGTTIN and SIGTTOU信号调高或者调低日志级别，或者通过SIGHUP信号重新写日志文件。

## Pipelining

Nutcracker支持代理多个客户端的连接到一个或者很少的服务器连接。这种架构可以节省往返的时间。

例如，如果nutcracker代理了3个客户端连接到一个服务器上，3个连接上分别得到请求'get key\r\n', 'set key 0 0 3\r\nval\r\n' and 'delete key\r\n' , nutcracker会尝试打包这些请求然后当做单个消息一次性发送，像这样：'get key\r\nset key 0 0 3\r\nval\r\ndelete key\r\n'.

使用管道提即使多了客户端和服务器端一层，在提升吞吐量方面也能做的更好的原因。

## Deployment

If you are deploying nutcracker in production, you might consider reading through the [recommendation document](notes/recommendation.md) to understand the parameters you could tune in nutcracker to run it efficiently in the production environment.

## Packages

### Ubuntu

#### PPA Stable

https://launchpad.net/~twemproxy/+archive/ubuntu/stable

#### PPA Daily

https://launchpad.net/~twemproxy/+archive/ubuntu/daily

## Utils
+ [nagios checks](https://github.com/wanelo/nagios-checks/blob/master/check_twemproxy)
+ [circunous](https://github.com/wanelo-chef/nad-checks/blob/master/recipes/twemproxy.rb)
+ [puppet module](https://github.com/wuakitv/puppet-twemproxy)
+ [nutcracker-web](https://github.com/kontera-technologies/nutcracker-web)
+ [munin-plugin](https://github.com/eveiga/contrib/tree/nutcracker/plugins/nutcracker)
+ [collectd-plugin](https://github.com/bewie/collectd-twemproxy)
+ [redis-twemproxy agent](https://github.com/Stono/redis-twemproxy-agent)
+ [sensu-metrics](https://github.com/sensu/sensu-community-plugins/blob/master/plugins/twemproxy/twemproxy-metrics.rb)
+ [redis-mgr](https://github.com/idning/redis-mgr)
+ [smitty for twemproxy failover](https://github.com/areina/smitty)

## Users
+ [Pinterest](http://pinterest.com/)
+ [Tumblr](https://www.tumblr.com/)
+ [Twitter](https://twitter.com/)
+ [Vine](http://vine.co/)
+ [Kiip](http://www.kiip.me/)
+ [Wuaki.tv](https://wuaki.tv/)
+ [Wanelo](http://wanelo.com/)
+ [Kontera](http://kontera.com/)
+ [Wikimedia](http://www.wikimedia.org/)
+ [Bright](http://www.bright.com/)
+ [56.com](http://www.56.com/)
+ [Snapchat](http://www.snapchat.com/)
+ [Digg](http://digg.com/)
+ [Gawkermedia](http://advertising.gawker.com/)
+ [3scale.net](http://3scale.net)
+ [Ooyala](http://www.ooyala.com)
+ [Twitch](http://twitch.tv)
+ [Socrata](http://www.socrata.com/)

## Issues and Support

Have a bug or a question? Please create an issue here on GitHub!

https://github.com/twitter/twemproxy/issues

## Committers

* Manju Rajashekhar ([@manju](https://twitter.com/manju))
* Lin Yang ([@idning](https://github.com/idning))

Thank you to all of our [contributors](https://github.com/twitter/twemproxy/graphs/contributors)!

## License

Copyright 2012 Twitter, Inc.

Licensed under the Apache License, Version 2.0: http://www.apache.org/licenses/LICENSE-2.0
