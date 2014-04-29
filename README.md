<h1>BtitTracker v.1.5.0</h1>

Installation
================

1: Upload you files to server root.
<br />
2: Point your browse to http://www.yourdomain.ro and follow the installation steps (yourdomain.ro change it to your real domain). 

================

Requirements
================

1: Memcache extension.
<br />
2: Memcached server.

================

How to install the Depencies...

Linux:

<code>sudo apt-get install php5-memcache</code>
<br />
<code>sudo apt-get install memcached</code>
<br />
<code>sudo /etc/init.d/apache2 reload</code>
<br />
How to increase Memcache Memory on Linux:
<br />
Write in terminal:

<code>nano /etc/memcached.conf</code>
<br />

And change:

<code>-m 64</code> Wich is the default memory allocated to what your needs are for ex...
<br />
<code>-m 512</code> Wich equals 512Mb....

================

Windows: http://ghita.org/tipoftheday/xamp-with-memcache-on-windows
