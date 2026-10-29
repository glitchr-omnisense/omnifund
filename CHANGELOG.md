# Changelog

## [Unreleased]

### Fixed

- An `UnreachableException` no longer carries the request's secrets. `Http\Json::request()` put the
  HTTP client's message, the whole URL, in its message, wrapped the client's own exception as its
  previous, and every trace of the chain kept the arguments of the calls under it (the query and
  headers with the token, when `zend.exception_ignore_args` is off). The message and the previous -
  now a copy of the client's exception, the same class and code - have the key's parameters, the
  URL's user part and the request's secrets masked as `***` (`Http\Redaction`), and no trace of
  the chain keeps its arguments.
