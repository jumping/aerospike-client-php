
# Overview

The Aerospike <a href="http://www.aerospike.com/docs/architecture/clients.html"
target="_doc">PHP client</a> enables your PHP application to work with an
<a href="http://www.aerospike.com/docs/architecture/distribution.html"
target="_doc">Aerospike cluster</a> as its
<a href="http://www.aerospike.com/docs/guide/kvs.html" target="_doc">key-value store</a>.

The <a href="http://www.aerospike.com/docs/architecture/data-model.html" target="_doc">Data Model</a>
document gives further details on how data is organized in the cluster.

## Client API
The Aerospike PHP client API is described in the following sections:

### [Runtime Configuration](aerospike_config.md)
### [Session Handler](aerospike_sessions.md)
### [Aerospike Class](aerospike.md)
### [Lifecycle and Connection Methods](apiref_connection.md)
### [Error Handling Methods](apiref_error.md)
### [Key-Value Methods](apiref_kv.md)
### [Query and Scan Methods](apiref_streams.md)
### [User Defined Methods](apiref_udf.md)
### [Admin Methods](apiref_admin.md)
### [Info Methods](apiref_info.md)
### [Large Data Type Methods](aerospike_ldt.md)

## Implementation Status
So far the *Runtime Configuration*, *Lifecycle and Connection Methods*, *Error*
*Handling and Logging Methods*, *Query and Scan Methods*, *User Defined Methods*
, *Admin Methods*, *Info Methods* and *Key-Value Methods* have been implemented.

The *Large Data Type Methods* are implemented as a PHP library.

We expect the specification of the PHP client to closely describe our next
release, including the unimplemented methods.  However, it is possible that
some changes to the client spec will occur.

## Persistent Connections

Initializing the C-client to connect to a specified cluster is a costly
operation, so ideally the C-client should be reused for the multiple requests
made against the same PHP process (as is the case for mod_php and fastCGI).

The PHP developer can determine whether the Aerospike class constructor will
use persistent connections or not by way of an optional boolean argument.
After the first time Aerospike::__construct() is called within the process, the
extension will attempt to reuse the persistent connection.

When persistent connections are used the methods _reconnect()_ and _close()_ do
not actually close the connection.  Those methods only apply to instances of
class Aerospike which use non-persistent connections.

## Halting a Stream

Halting a _query()_ or _scan()_ result stream can be done by returning (an
explicit) boolean **false** from the callback.  The extension will capture the
return value from the registered PHP callback, and pass it to the C-client.
The C-client will then close the sockets to the nodes involved in streaming
results, effectively halting it.

## Handling Unsupported Types

See: [Data Types](http://www.aerospike.com/docs/guide/data-types.html)
See: [as_bytes.h](https://github.com/aerospike/aerospike-common/blob/master/src/include/aerospike/as_bytes.h)
* Allow the user to register their own serializer/deserializer method
 - OPT\_SERIALIZER : SERIALIZER\_PHP (default), SERIALIZER\_NONE, SERIALIZER\_USER
* when a write operation runs into types that do not map directly to Aerospike DB types it checks the OPT\_SERIALIZER setting:
 - if SERIALIZER\_NONE it returns an Aerospike::ERR\_PARAM error
 - if SERIALIZER\_PHP it calls the PHP serializer, sets the object's as\_bytes\_type to AS\_BYTES_PHP. This is the default behavior.
 - if SERIALIZER\_USER it calls the PHP function the user registered a callback with Aerospike::setSerializer(), and sets as\_bytes\_type to AS\_BYTES\_BLOB
* when a read operation extracts a value from an AS\_BYTES type bin:
 - if it’s a AS\_BYTES\_PHP use the PHP unserialize function
 - if it’s a AS\_BYTES\_BLOB and the user registered a callback with Aerospike::setDeserializer() call that function, otherwise place it in a PHP string

**Warning:** Strings in PHP are a binary-safe structure that allows for the
null-byte (**\0**) to be stored inside the string, not just at its end.
Binary-strings with this characteristic are created by calling functions such
as serialize() and gzdeflate(). As a result, the Aerospike client may truncate
the resulting strings. On the Aerospike server, strings are a data type that can
be queried using a secondary index, while bytes are a data type that is only
used for storage. The developer should wrap binary-string with an object to
distinguish them. This allows the serializer to behave in the correct manner.

### Example:

```php
require('autoload.php');
$client = new Aerospike(['hosts'=>[['addr'=>'127.0.0.1', 'port'=>3000]]]);

$str = 'Glagnar\'s Human Rinds, "It\'s a bunch\'a munch\'a crunch\'a human!';
$deflated = new \Aerospike\Bytes(gzdeflate($str));
$wrapped = new \Aerospike\Bytes("trunc\0ated");

$key = $client->initKey('test', 'demo', 'wrapped-bytes');
$status = $client->put($key, ['unwrapped'=>"trunc\0ated", 'wrapped'=> $wrapped, 'deflated' => $deflated]);
if ($status !== Aerospike::OK) {
    die($client->error());
}
$client->get($key, $record);
$wrapped = \Aerospike\Bytes::unwrap($record['bins']['wrapped']);
$deflated = $record['bins']['deflated'];
$inflated = gzinflate($deflated->s);
echo "$inflated\n";
echo "wrapped binary-string: ";
var_dump($wrapped);
$unwrapped = $record['bins']['unwrapped'];
echo "The binary-string that was given to put() without a wrapper: $unwrapped\n";

$client->close();
```
Outputs:
```
Glagnar's Human Rinds, "It's a bunch'a munch'a crunch'a human!
wrapped binary-string: string(10) "truncated"
The binary-string that was given to put() without a wrapper: trunc
```

## Further Reading

- [How does the Aerospike client find a node](https://discuss.aerospike.com/t/how-does-aerospike-client-find-a-node/706)
- [How would hash collisions be handled](https://discuss.aerospike.com/t/what-will-aerospike-do-if-hash-collision-for-a-key/779)
