# MVC Architecture
ManaPHP offers the object-oriented classes, necessary to implement the Model, View, Controller architecture
(often referred to as [MVC]) in your application. This design pattern is widely used by other web frameworks
and desktop applications.

MVC benefits include:

* Isolation of business logic from the user interface and the database layer
* Making it clear where different types of code belong for easier maintenance

If you decide to use MVC, every request to your application resources will be managed by the MVC architecture.

## Model
Model represents the information (data) of the application and the rules to manipulate that data. Models are primarily used for
managing the rules of interaction with a corresponding database table. In most cases, each table in your database will correspond
to one model in your application. The bulk of your application's business logic will be concentrated in the models. Learn more [model](/model.html)

## View
View represents the user interface of your application. Views are often HTML files with embedded PHP code that perform tasks
related solely to the presentation of the data. Views handle the job of providing data to the web browser or other tool that
is used to make requests from your application. Learn more [view](view.html)

## Controller
Controller provides the "flow" between models and views. Controllers are responsible for processing the incoming requests
from the web browser, interrogating the models for data, and passing that dta on to the views for presentation. Learn more [controller](controller.html)

[MVC]: http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller
