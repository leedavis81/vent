vent
====

PHP variable event system

Have you ever needed to hook an event anytime a PHP variable is read? Maybe you want to ensure complete immutability even within the scope (private) of you class.
PHP variable events can be easily created by hooking into the read or write of any variable.