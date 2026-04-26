# General Implementation Approach/Considerations

We will be building the farmers store inventory application built on mezzio framework. We will always route a middleware before handlers anytime incoming data needs to be handled. We will use Requesthandlers for setting up templates, simple branches to determine if a template or json should be returned. We will have one RequestHandler per action.

We will be using vendor/phpdb and phpdb-mysql for the database abstraction layer. It will use a Repository/Entity pattern. We will most likely be adding mezzio/mezzio-valinor as a project dependency to allow upcasting incoming PSR Request data, POST data etc, to our DB Entities. In some cases these Entities will also be webware/command-bus command instances. This will provide a way for us to keep our Repositories out of our Middleware/RequestHandlers. Since the commandbus component provides an event layer it will also provide a means of command logging while also keeping it out of our middleware/handlers.

While CommandBus does not currently provide a QueryBus we may implement a custom QueryBus Simply by Implementing the CommandBus interface as a QueryBus to separate our reads and writes to persistent storage.

We will build a custom Module or modules. We will need to explore where these boundaries will need to be to best model the domain. We will need a src/User module. We may also have src/Product, src/Ticket src/Transfer src/Manifest. Another possible solution would be to group all of those under a src/Warehouse module and split the application along User and Warehouse. I would like to discuss the pro's and conns of each approach as well as any suggestions from you.

There is also the question of whether Auth/Authz should be its own modules or within User. I always struggle with making those choices. Both ways to some degree makes sense.

## HTMX Considerations in async

We have a Htmx\Middleware\DetectAjaxRequestMiddleware that can be piped to detect the htmx request header which also disables the layout layer for rendering the htmx partial templates. The question is how well that will work in the true async environment. I can't think of a reason it should not work. If there is please call it out.

