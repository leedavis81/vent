vent
====

PHP variable event system


| Quality / Metrics | Releases | Downloads |
| ----- | -------- | ------- | ------------- |
[![Build Status](https://travis-ci.org/leedavis81/drest.png?branch=master)](https://travis-ci.org/leedavis81/vent) [![Coverage Status](https://coveralls.io/repos/leedavis81/vent/badge.png?branch=master)](https://coveralls.io/r/leedavis81/vent?branch=master) | [![Latest Stable Version](https://poser.pugx.org/leedavis81/vent/v/stable.png)](https://packagist.org/packages/leedavis81/vent) [![Latest Unstable Version](https://poser.pugx.org/leedavis81/drest/v/unstable.png)](https://packagist.org/packages/leedavis81/vent) | [![Total Downloads](https://poser.pugx.org/leedavis81/vent/downloads.png)](https://packagist.org/packages/leedavis81/vent)

Have you ever needed to hook an event anytime a PHP variable is read? Maybe you want to ensure complete immutability even within the scope (private) of you class.
PHP variable events can be easily created by hooking into the read or write of any variable.