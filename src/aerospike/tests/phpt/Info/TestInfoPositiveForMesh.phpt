--TEST--
Info - Positive for mesh

--SKIPIF--
<?php
include dirname(__FILE__)."/../../astestframework/astest-phpt-loader.inc";
aerospike_phpt_skipif("Info", "testInfoPositiveForMesh");

--FILE--
<?php
include dirname(__FILE__)."/../../astestframework/astest-phpt-loader.inc";
aerospike_phpt_runtest("Info", "testInfoPositiveForMesh");
--EXPECT--
OK