# Netlogix.WebApi

## Installation

`composer require netlogix/webapi`

## Accept-header driven exception handling

The package ships a PSR-15 middleware that catches exceptions inside the
middleware chain and turns them into a `ResponseInterface`. This way, any
response-decorating middleware (most importantly
`Sitegeist.OffCORS:CorsMiddleware`) sees a real response on errors and can
add its headers — which the default Flow exception path, running through
`set_exception_handler`, would bypass.

### Components

- `Netlogix\WebApi\Http\Middleware\ExceptionToResponseMiddleware`
  Catches any `Throwable` thrown by downstream middlewares and delegates
  rendering to the dispatcher below. Registered via
  `Configuration/Settings.Middleware.yaml` directly after
  `Sitegeist.OffCORS:CorsMiddleware`.
- `Netlogix\WebApi\Error\AcceptHeaderDependingExceptionHandler`
  Picks one of the configured Flow `ExceptionHandlerInterface`
  implementations based on the request's `Accept` header (proper content
  negotiation via `Neos\Flow\Http\Helper\MediaTypeHelper`), invokes it,
  and adapts its `header()` / `echo` style output into a PSR-7 response.
- `Netlogix\WebApi\Error\JsonExceptionHandler`
  Renders the throwable as a jsonapi.org-style error document
  (`{"errors": [{…}]}`) mirroring the shape produced by
  `Netlogix\JsonApiOrg\Controller\ApiController::errorAction()`. For
  plain throwables the entry carries `code` and `title`, plus an
  optional `id` from `WithReferenceCodeInterface`. Production-safe
  variant — does not publish the exception message.
- `Netlogix\WebApi\Error\JsonDebugExceptionHandler`
  Same envelope as `JsonExceptionHandler`, but for plain throwables
  the error entry additionally carries `detail` (the exception
  message) and a `meta` block with `exceptionType`, `file`, `line`, a
  sanitized `trace` (frame args stripped) and a recursive
  `meta.previous` chain — analogous to how
  `Neos\Flow\Error\DebugExceptionHandler` extends the
  `ProductionExceptionHandler`. Reserved for non-production contexts.

Exceptions implementing `JsonSerializable` provide the **body of one
error entry** (the jsonapi.org fields, e.g. `code` / `title` /
`detail` / `meta`). Both handlers wrap that body in the standard
`{"errors": […]}` envelope, so the exception decides its own
representation but never the response shape.

### Configuration

Configure renderers per media type under
`Netlogix.WebApi.error.availableErrorHandlers`. The shape of the
`options` entry mirrors Flow's own `Neos.Flow.error.exceptionHandler`
(`defaultRenderingOptions`, `renderingGroups`, …), so the values can be
copy-pasted between the two.

```yaml
Netlogix:
  WebApi:
    error:
      availableErrorHandlers:
        'application/vnd.api+json':
          className: 'Netlogix\WebApi\Error\JsonExceptionHandler'
        'application/json':
          className: 'Netlogix\WebApi\Error\JsonExceptionHandler'
        '*':
          className: 'Neos\Flow\Error\ProductionExceptionHandler'
          options:
            defaultRenderingOptions:
              renderTechnicalDetails: false
              logException: true
```

- Keys are IANA media types; the request's `Accept` header is negotiated
  against them, respecting q-values and `*/*` ranges.
- The special key `*` is the fallback used when no configured media type
  matches the request's preferences. If `*` is missing and nothing else
  matches, the exception escapes back to Flow's
  `set_exception_handler` chain.
- The shipped `Configuration/Development/Settings.ExceptionHandler.yaml`
  swaps every renderer for its debug counterpart:
  `Neos\Flow\Error\DebugExceptionHandler` for the `*` fallback and
  `Netlogix\WebApi\Error\JsonDebugExceptionHandler` for both JSON media
  types — mirroring how Flow itself replaces the production handler
  with the debug variant in Development.

### Logging

Logging is delegated entirely to the resolved Flow `ExceptionHandler`
(`logException` in its rendering options drives `ThrowableStorage`); the
middleware itself does not log.
